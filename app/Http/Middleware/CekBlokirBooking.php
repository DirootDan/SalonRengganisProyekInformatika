<?php

namespace App\Http\Middleware;

use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\RiwayatPelanggaran;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CekBlokirBooking
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Pelanggan|null $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        if (! $pelanggan) {
            return redirect()->route('login');
        }

        // Segarkan data pelanggan dari database
        $pelanggan->refresh();

        // Jika masa blokir sementara telah lewat (dan bukan blokir permanen tahap ke-3), reset otomatis ke 0
        if (! $pelanggan->diblokir_permanen && $pelanggan->diblokir_sampai !== null && $pelanggan->diblokir_sampai->isPast()) {
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
                    'keterangan'   => 'Masa pemblokiran sementara telah berakhir; jumlah pelanggaran direset kembali ke 0.',
                ]);

                NotifikasiPelanggan::kirim(
                    $pelanggan->id_pelanggan,
                    'Masa Pemblokiran Berakhir',
                    'Masa pemblokiran akun Anda telah selesai dan catatan pelanggaran telah direset ke 0. Anda dapat kembali melakukan pemesanan layanan.',
                    'sukses'
                );
            });

            $pelanggan->refresh();
        }

        $kebijakan = PengaturanKebijakan::aktif();

        // Cegah pengiriman permintaan booking (POST) jika pelanggan sedang diblokir dan kebijakan aktif
        if ($kebijakan->kebijakan_aktif && $pelanggan->sedangDiblokir() && $request->isMethod('post')) {
            $alasan = $pelanggan->alasan_blokir ?: 'Mencapai batas pelanggaran reservasi.';

            if ($pelanggan->diblokir_permanen) {
                $pesan = "Maaf, akun Anda sedang diblokir secara permanen (Blokir Tahap {$pelanggan->jumlah_kali_blokir}) karena: {$alasan} Silakan hubungi Manajer salon untuk pembukaan blokir manual.";
            } else {
                $berakhir = $pelanggan->diblokir_sampai?->format('d/m/Y H:i') ?? '-';
                $sisaHari = $pelanggan->sisaHariBlokir();
                $pesan    = "Maaf, akun Anda sedang diblokir sementara dari fitur booking karena: {$alasan} Blokir berakhir pada {$berakhir} ({$sisaHari} hari lagi).";
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 403);
            }

            return redirect()->route('pelanggan.booking')
                ->withErrors(['blokir' => $pesan]);
        }

        return $next($request);
    }
}
