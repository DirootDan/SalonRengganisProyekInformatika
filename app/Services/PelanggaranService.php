<?php

namespace App\Services;

use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\RiwayatPelanggaran;
use Illuminate\Support\Facades\DB;

class PelanggaranService
{
    /**
     * Membuka blokir pelanggan secara manual, mereset akumulasi pelanggaran ke 0,
     * mencatat log audit riwayat pelanggaran, dan mengirim notifikasi ke pelanggan.
     */
    public function bukaBlokir(Pelanggan $pelanggan, string $alasanBuka): void
    {
        DB::transaction(function () use ($pelanggan, $alasanBuka) {
            $pelanggan->update([
                'jumlah_pelanggaran'     => 0,
                'diblokir_permanen'      => false,
                'diblokir_sampai'        => null,
                'alasan_blokir'          => null,
                'reset_pelanggaran_pada' => now(),
            ]);

            RiwayatPelanggaran::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_reservasi' => null,
                'jenis'        => RiwayatPelanggaran::JENIS_BUKA_MANUAL,
                'tanggal'      => now()->toDateString(),
                'keterangan'   => 'Dibuka oleh Manajer. Alasan: ' . $alasanBuka,
            ]);

            NotifikasiPelanggan::kirim(
                $pelanggan->id_pelanggan,
                'Blokir Akun Dibuka oleh Manajer',
                "Pemblokiran akun Anda telah dibuka oleh Manajer dengan catatan: \"{$alasanBuka}\". Jumlah pelanggaran aktif Anda telah direset ke 0.",
                NotifikasiPelanggan::TIPE_SUKSES
            );
        });
    }
}
