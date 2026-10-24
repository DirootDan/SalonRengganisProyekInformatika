<?php

namespace App\Console\Commands;

use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\Reservasi;
use App\Models\RiwayatPelanggaran;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluasiBookingHangus extends Command
{
    protected $signature = 'booking:evaluasi-hangus';

    protected $description = 'Menandai booking Diterima yang melewati batas toleransi pembayaran menjadi Hangus, menghitung pelanggaran kumulatif 90 hari terakhir, menerapkan sanksi blokir bertingkat (7 hari, 30 hari, permanen), dan mereset blokir sementara yang telah berakhir.';

    public function handle(): int
    {
        $kebijakan = PengaturanKebijakan::aktif();

        // 1. Reset pelanggan yang masa blokir sementaranya sudah berakhir (bukan blokir permanen tahap ke-3)
        $pelangganBerakhir = Pelanggan::where('diblokir_permanen', false)
            ->whereNotNull('diblokir_sampai')
            ->where('diblokir_sampai', '<=', now())
            ->get();

        foreach ($pelangganBerakhir as $pelanggan) {
            DB::transaction(function () use ($pelanggan) {
                $pelanggan->update([
                    'jumlah_pelanggaran'     => 0,
                    'diblokir_sampai'        => null,
                    'alasan_blokir'          => null,
                    'reset_pelanggaran_pada' => now(),
                ]);

                RiwayatPelanggaran::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_reservasi' => null,
                    'jenis'        => 'Reset Otomatis',
                    'tanggal'      => now()->toDateString(),
                    'keterangan'   => 'Masa pemblokiran sementara telah berakhir; jumlah pelanggaran aktif direset ke 0.',
                ]);

                NotifikasiPelanggan::kirim(
                    $pelanggan->id_pelanggan,
                    'Masa Pemblokiran Berakhir',
                    'Masa pemblokiran sementara akun Anda telah selesai dan catatan pelanggaran aktif telah direset ke 0. Anda dapat kembali melakukan pemesanan layanan.',
                    'sukses'
                );
            });
        }

        // 2. Sinkronisasi kedaluwarsa pelanggaran > N hari (default 90 hari) untuk pelanggan yang memiliki riwayat
        $rentangHari = (int) ($kebijakan->rentang_hari_pelanggaran ?: 90);
        $pelangganDenganRiwayat = Pelanggan::where('jumlah_pelanggaran', '>', 0)
            ->where('diblokir_permanen', false)
            ->whereNull('diblokir_sampai')
            ->whereHas('riwayatPelanggaran')
            ->get();

        foreach ($pelangganDenganRiwayat as $p) {
            $aktifDalamRentang = $p->hitungPelanggaranAktifDalamRentang($rentangHari);
            if ($aktifDalamRentang !== (int) $p->jumlah_pelanggaran) {
                $p->update(['jumlah_pelanggaran' => $aktifDalamRentang]);
            }
        }

        if (! $kebijakan->kebijakan_aktif) {
            $this->info('Kebijakan pinalti sedang dinonaktifkan oleh Manajer.');
            return self::SUCCESS;
        }

        // 3. Evaluasi reservasi berstatus Diterima yang belum lunas melewati H + toleransi_bayar_hari pukul 23:59:59
        $kandidat = Reservasi::whereIn('status', [Reservasi::STATUS_DITERIMA, 'diterima'])
            ->where('pelanggaran_dihitung', false)
            ->doesntHave('transaksi')
            ->orderBy('tgl_booking')
            ->orderBy('id_reservasi')
            ->get();

        $jumlahHangus = 0;

        foreach ($kandidat as $reservasi) {
            $batasAkhir = Carbon::parse($reservasi->tgl_booking->toDateString())
                ->addDays($kebijakan->toleransi_bayar_hari)
                ->endOfDay();

            if (now()->lte($batasAkhir)) {
                continue;
            }

            DB::transaction(function () use ($reservasi, $kebijakan, $rentangHari, &$jumlahHangus) {
                $r = Reservasi::where('id_reservasi', $reservasi->id_reservasi)
                    ->lockForUpdate()
                    ->first();

                if (! $r || $r->pelanggaran_dihitung || $r->transaksi()->exists()) {
                    return;
                }

                if (! $r->bolehTransisiKe(Reservasi::STATUS_HANGUS)) {
                    return;
                }

                $r->ubahStatus(Reservasi::STATUS_HANGUS, [
                    'pelanggaran_dihitung' => true,
                    'alasan_penolakan'     => 'Melewati batas waktu pembayaran H+' . $kebijakan->toleransi_bayar_hari . ' pukul 23:59.',
                ]);

                $pelanggan = Pelanggan::where('id_pelanggan', $r->id_pelanggan)
                    ->lockForUpdate()
                    ->first();

                if (! $pelanggan) {
                    return;
                }

                // Catat kejadian pelanggaran dengan tanggal booking (agar evaluasi jendela 90 hari akurat)
                $tanggalPelanggaran = $r->tgl_booking->toDateString();

                RiwayatPelanggaran::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_reservasi' => $r->id_reservasi,
                    'jenis'        => 'Pelanggaran',
                    'tanggal'      => $tanggalPelanggaran,
                    'keterangan'   => "Reservasi #{$r->id_reservasi} tanggal {$r->tgl_booking->format('d/m/Y')} hangus karena tidak diselesaikan pembayarannya.",
                ]);

                // Hitung kumulatif pelanggaran dalam 90 hari terakhir (atau sesuai pengaturan Manajer)
                $jumlahAktif = $pelanggan->hitungPelanggaranAktifDalamRentang($rentangHari);
                $pelanggan->jumlah_pelanggaran = $jumlahAktif;

                if ($pelanggan->jumlah_pelanggaran >= $kebijakan->batas_pelanggaran && ! $pelanggan->sedangDiblokir()) {
                    $tahapBlokir = ((int) $pelanggan->jumlah_kali_blokir) + 1;
                    $pelanggan->jumlah_kali_blokir = $tahapBlokir;

                    if ($tahapBlokir === 1) {
                        $hariBlokir = (int) ($kebijakan->lama_blokir_hari ?: 7);
                        $diblokirSampai = now()->addDays($hariBlokir);
                        $pelanggan->diblokir_permanen = false;
                        $pelanggan->diblokir_sampai   = $diblokirSampai;
                        $pelanggan->alasan_blokir     = "Blokir Tahap 1 ({$hariBlokir} hari): mencapai {$pelanggan->jumlah_pelanggaran} pelanggaran dalam {$rentangHari} hari terakhir.";
                        $pelanggan->save();

                        NotifikasiPelanggan::kirim(
                            $pelanggan->id_pelanggan,
                            "Akun Diblokir Sementara (Tahap 1 - {$hariBlokir} Hari)",
                            "Reservasi #{$r->id_reservasi} dinyatakan Hangus. Anda telah mencapai {$pelanggan->jumlah_pelanggaran} dari {$kebijakan->batas_pelanggaran} pelanggaran dalam {$rentangHari} hari terakhir. Fitur booking ditutup sementara selama {$hariBlokir} hari hingga {$diblokirSampai->format('d/m/Y H:i')}.",
                            'bahaya',
                            $r->id_reservasi
                        );
                    } elseif ($tahapBlokir === 2) {
                        $hariBlokir = (int) ($kebijakan->lama_blokir_kedua_hari ?: 30);
                        $diblokirSampai = now()->addDays($hariBlokir);
                        $pelanggan->diblokir_permanen = false;
                        $pelanggan->diblokir_sampai   = $diblokirSampai;
                        $pelanggan->alasan_blokir     = "Blokir Tahap 2 ({$hariBlokir} hari): kembali mencapai {$pelanggan->jumlah_pelanggaran} pelanggaran dalam {$rentangHari} hari terakhir.";
                        $pelanggan->save();

                        NotifikasiPelanggan::kirim(
                            $pelanggan->id_pelanggan,
                            "Akun Diblokir Sementara (Tahap 2 - {$hariBlokir} Hari)",
                            "Reservasi #{$r->id_reservasi} dinyatakan Hangus. Ini adalah pemblokiran ke-2 Anda karena kembali mencapai {$pelanggan->jumlah_pelanggaran} pelanggaran. Fitur booking ditutup selama {$hariBlokir} hari hingga {$diblokirSampai->format('d/m/Y H:i')}.",
                            'bahaya',
                            $r->id_reservasi
                        );
                    } else {
                        if ($kebijakan->blokir_ketiga_permanen) {
                            $pelanggan->diblokir_permanen = true;
                            $pelanggan->diblokir_sampai   = null;
                            $pelanggan->alasan_blokir     = "Blokir Tahap {$tahapBlokir} (Permanen): mencapai {$pelanggan->jumlah_pelanggaran} pelanggaran berulang. Akun tetap diblokir hingga dibuka manual oleh Manajer.";
                            $pelanggan->save();

                            NotifikasiPelanggan::kirim(
                                $pelanggan->id_pelanggan,
                                'Akun Diblokir hingga Dibuka oleh Manajer (Tahap 3)',
                                "Reservasi #{$r->id_reservasi} dinyatakan Hangus. Karena ini merupakan pemblokiran ke-{$tahapBlokir}, akun Anda tetap diblokir dari fitur booking sampai Manajer salon membuka blokir secara manual.",
                                'bahaya',
                                $r->id_reservasi
                            );
                        } else {
                            $hariBlokir = (int) ($kebijakan->lama_blokir_kedua_hari ?: 30);
                            $diblokirSampai = now()->addDays($hariBlokir);
                            $pelanggan->diblokir_permanen = false;
                            $pelanggan->diblokir_sampai   = $diblokirSampai;
                            $pelanggan->alasan_blokir     = "Blokir Tahap {$tahapBlokir} ({$hariBlokir} hari): mencapai {$pelanggan->jumlah_pelanggaran} pelanggaran.";
                            $pelanggan->save();
                        }
                    }
                } else {
                    $pelanggan->save();
                    $sisa = max(0, $kebijakan->batas_pelanggaran - $pelanggan->jumlah_pelanggaran);

                    NotifikasiPelanggan::kirim(
                        $pelanggan->id_pelanggan,
                        'Reservasi Hangus & Peringatan Pelanggaran',
                        "Reservasi #{$r->id_reservasi} pada {$r->tgl_booking->format('d/m/Y')} dinyatakan Hangus karena melewati batas waktu pembayaran. Saat ini Anda memiliki {$pelanggan->jumlah_pelanggaran} dari {$kebijakan->batas_pelanggaran} pelanggaran dalam {$rentangHari} hari terakhir (tersisa {$sisa} kesempatan sebelum pemblokiran).",
                        'peringatan',
                        $r->id_reservasi
                    );
                }

                $jumlahHangus++;
            });
        }

        $this->info("Evaluasi selesai: {$jumlahHangus} reservasi ditandai Hangus.");

        return self::SUCCESS;
    }
}
