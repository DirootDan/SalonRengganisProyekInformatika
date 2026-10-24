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

    protected $description = 'Menandai booking Diterima yang melewati batas toleransi pembayaran menjadi Hangus, mencatat pelanggaran, menerapkan sanksi blokir, dan mereset blokir yang telah berakhir.';

    public function handle(): int
    {
        $kebijakan = PengaturanKebijakan::aktif();

        // 1. Reset pelanggan yang masa blokirnya sudah berakhir
        $pelangganBerakhir = Pelanggan::whereNotNull('diblokir_sampai')
            ->where('diblokir_sampai', '<=', now())
            ->get();

        foreach ($pelangganBerakhir as $pelanggan) {
            DB::transaction(function () use ($pelanggan) {
                $pelanggan->update([
                    'jumlah_pelanggaran' => 0,
                    'diblokir_sampai'    => null,
                    'alasan_blokir'      => null,
                ]);

                RiwayatPelanggaran::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_reservasi' => null,
                    'jenis'        => 'Reset Otomatis',
                    'tanggal'      => now()->toDateString(),
                    'keterangan'   => 'Masa pemblokiran telah berakhir; jumlah pelanggaran direset kembali ke 0.',
                ]);

                NotifikasiPelanggan::kirim(
                    $pelanggan->id_pelanggan,
                    'Masa Pemblokiran Berakhir',
                    'Masa pemblokiran akun Anda telah selesai dan catatan pelanggaran telah direset ke 0. Anda dapat kembali melakukan pemesanan layanan.',
                    'sukses'
                );
            });
        }

        if (! $kebijakan->kebijakan_aktif) {
            $this->info('Kebijakan pinalti sedang dinonaktifkan oleh Manajer.');
            return self::SUCCESS;
        }

        // 2. Evaluasi reservasi berstatus Diterima yang belum lunas melewati H + toleransi_bayar_hari pukul 23:59:59
        $kandidat = Reservasi::whereIn('status', [Reservasi::STATUS_DITERIMA, 'diterima'])
            ->where('pelanggaran_dihitung', false)
            ->doesntHave('transaksi')
            ->get();

        $jumlahHangus = 0;

        foreach ($kandidat as $reservasi) {
            $batasAkhir = Carbon::parse($reservasi->tgl_booking->toDateString())
                ->addDays($kebijakan->toleransi_bayar_hari)
                ->endOfDay();

            if (now()->lte($batasAkhir)) {
                continue;
            }

            DB::transaction(function () use ($reservasi, $kebijakan, &$jumlahHangus) {
                // Kunci baris agar idempoten dan aman dari eksekusi paralel
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

                $pelanggan->jumlah_pelanggaran = ((int) $pelanggan->jumlah_pelanggaran) + 1;

                RiwayatPelanggaran::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_reservasi' => $r->id_reservasi,
                    'jenis'        => 'Pelanggaran',
                    'tanggal'      => now()->toDateString(),
                    'keterangan'   => "Reservasi #{$r->id_reservasi} tanggal {$r->tgl_booking->format('d/m/Y')} hangus karena tidak diselesaikan pembayarannya.",
                ]);

                if ($pelanggan->jumlah_pelanggaran >= $kebijakan->batas_pelanggaran) {
                    $diblokirSampai = now()->addDays($kebijakan->lama_blokir_hari);
                    $pelanggan->diblokir_sampai = $diblokirSampai;
                    $pelanggan->alasan_blokir   = "Mencapai batas {$kebijakan->batas_pelanggaran} kali pelanggaran reservasi tidak hadir / tidak membayar.";
                    $pelanggan->save();

                    NotifikasiPelanggan::kirim(
                        $pelanggan->id_pelanggan,
                        'Akun Diblokir Sementara dari Fitur Booking',
                        "Reservasi #{$r->id_reservasi} dinyatakan Hangus. Karena Anda telah mencapai {$pelanggan->jumlah_pelanggaran} dari {$kebijakan->batas_pelanggaran} pelanggaran, fitur booking ditutup sementara hingga {$diblokirSampai->format('d/m/Y H:i')}.",
                        'bahaya',
                        $r->id_reservasi
                    );
                } else {
                    $pelanggan->save();
                    $sisa = $kebijakan->batas_pelanggaran - $pelanggan->jumlah_pelanggaran;

                    NotifikasiPelanggan::kirim(
                        $pelanggan->id_pelanggan,
                        'Reservasi Hangus & Peringatan Pelanggaran',
                        "Reservasi #{$r->id_reservasi} pada {$r->tgl_booking->format('d/m/Y')} dinyatakan Hangus karena melewati batas waktu pembayaran. Saat ini Anda memiliki {$pelanggan->jumlah_pelanggaran} dari {$kebijakan->batas_pelanggaran} pelanggaran (tersisa {$sisa} kesempatan sebelum pemblokiran sementara).",
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
