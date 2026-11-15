<?php

namespace App\Services;

use App\Models\Reservasi;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanService
{
    /**
     * Mengambil rentang tanggal laporan dari query request atau default awal-akhir bulan ini.
     *
     * @return array{0: string, 1: string, 2: Carbon, 3: Carbon}
     */
    public function rentangTanggal(Request $request): array
    {
        $waktuMulai = $this->parseTanggalQuery($request->query('mulai'), now()->startOfMonth());
        $waktuSelesai = $this->parseTanggalQuery($request->query('selesai'), now()->endOfMonth());

        if ($waktuMulai->gt($waktuSelesai)) {
            [$waktuMulai, $waktuSelesai] = [$waktuSelesai, $waktuMulai];
        }

        $tglMulai = $waktuMulai->toDateString();
        $tglSelesai = $waktuSelesai->toDateString();

        return [$tglMulai, $tglSelesai, $waktuMulai->copy()->startOfDay(), $waktuSelesai->copy()->endOfDay()];
    }

    private function parseTanggalQuery(mixed $nilai, Carbon $default): Carbon
    {
        if (is_string($nilai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) === 1) {
            [$tahun, $bulan, $hari] = array_map('intval', explode('-', $nilai));
            if (checkdate($bulan, $hari, $tahun)) {
                return Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay();
            }
        }

        return $default->copy()->startOfDay();
    }

    /**
     * Menyusun seluruh data ringkasan laporan keuangan & kinerja karyawan.
     */
    public function ringkasanLaporan(string $tglMulai, string $tglSelesai, Carbon $waktuMulai, Carbon $waktuSelesai): array
    {
        $reservasi = Reservasi::with(['layanan', 'produkAddon', 'pelanggan', 'transaksi'])
            ->whereBetween('tgl_booking', [$tglMulai, $tglSelesai])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        // Tetap sediakan $pelangganMap untuk kompatibilitas mundur jika ada view lama,
        // namun diambil dari relasi yang sudah di-eager-load tanpa query tambahan ke database.
        $pelangganMap = $reservasi
            ->pluck('pelanggan')
            ->filter()
            ->pluck('nama_cust', 'id_pelanggan');

        // Satu query agregasi untuk mengambil total pendapatan sekaligus total komisi
        $ringkasanTransaksi = Transaksi::whereBetween('created_at', [$waktuMulai, $waktuSelesai])
            ->selectRaw('COALESCE(SUM(total_bayar), 0) as total_pendapatan, COALESCE(SUM(komisi_stylist), 0) as total_komisi')
            ->first();

        $totalPendapatan = (float) ($ringkasanTransaksi->total_pendapatan ?? 0);
        $totalKomisi = (float) ($ringkasanTransaksi->total_komisi ?? 0);

        $kinerjaKaryawan = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->join('karyawan', 'karyawan.id_karyawan', '=', 'detail_reservasi.id_karyawan')
            ->whereBetween('reservasi.tgl_booking', [$tglMulai, $tglSelesai])
            ->where('reservasi.status', Reservasi::STATUS_SELESAI)
            ->select(
                'karyawan.nama_staff',
                'karyawan.role_jabatan',
                DB::raw('COUNT(*) as jumlah_layanan'),
                DB::raw('SUM(detail_reservasi.nominal_komisi) as total_komisi')
            )
            ->groupBy('karyawan.id_karyawan', 'karyawan.nama_staff', 'karyawan.role_jabatan')
            ->orderByDesc('jumlah_layanan')
            ->get();

        $layananTerlaris = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->join('layanan', 'layanan.id_layanan', '=', 'detail_reservasi.id_layanan')
            ->whereBetween('reservasi.tgl_booking', [$tglMulai, $tglSelesai])
            ->where('reservasi.status', Reservasi::STATUS_SELESAI)
            ->select('layanan.nama_layanan', DB::raw('COUNT(*) as total_pesan'))
            ->groupBy('layanan.id_layanan', 'layanan.nama_layanan')
            ->orderByDesc('total_pesan')
            ->first();

        $jumlahSelesai = $reservasi->filter(fn (Reservasi $r) => $r->statusTernormalisasi() === Reservasi::STATUS_SELESAI)->count();
        $jumlahHangus = $reservasi->filter(fn (Reservasi $r) => $r->statusTernormalisasi() === Reservasi::STATUS_HANGUS)->count();

        return compact(
            'reservasi',
            'pelangganMap',
            'tglMulai',
            'tglSelesai',
            'totalPendapatan',
            'totalKomisi',
            'kinerjaKaryawan',
            'layananTerlaris',
            'jumlahSelesai',
            'jumlahHangus'
        );
    }

    /**
     * Mengekspor laporan reservasi ke format CSV secara streaming menggunakan cursor()
     * agar hemat memori meskipun jumlah baris reservasi sangat besar.
     */
    public function eksporCsv(string $tglMulai, string $tglSelesai): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"laporan-salon-rengganis-{$tglMulai}-{$tglSelesai}.csv\"",
        ];

        $callback = function () use ($tglMulai, $tglSelesai) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID Reservasi', 'Tanggal', 'Jam', 'Pelanggan', 'Rincian Layanan', 'Total (Rp)', 'Status']);

            $kueriReservasi = Reservasi::with(['layanan', 'produkAddon', 'pelanggan', 'transaksi'])
                ->whereBetween('tgl_booking', [$tglMulai, $tglSelesai])
                ->latest('tgl_booking');

            foreach ($kueriReservasi->cursor() as $r) {
                $total = $r->transaksi ? $r->transaksi->total_bayar : $r->estimasiTotalAkhir();
                $rincianLayanan = $r->layanan->pluck('nama_layanan')->join(', ');
                $rincianProduk = $r->produkAddon->map(fn ($p) => $p->nama_produk.' (x'.$p->pivot->jumlah.')')->join(', ');
                $rincianItem = trim($rincianLayanan.($rincianProduk ? ' + '.$rincianProduk : ''));

                fputcsv($out, [
                    '#'.$r->id_reservasi,
                    $r->tgl_booking->format('d/m/Y'),
                    $r->jam_booking,
                    $this->amankanSelCsv($r->namaPelangganTampil()),
                    $this->amankanSelCsv($rincianItem),
                    (int) $total,
                    $this->amankanSelCsv($r->statusTernormalisasi()),
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mencegah CSV formula injection di Excel/Google Sheets pada kolom teks bebas.
     */
    private function amankanSelCsv(?string $nilai): string
    {
        $teks = (string) ($nilai ?? '');
        if ($teks !== '' && str_contains("=+-@\t\r", $teks[0])) {
            return "'".$teks;
        }

        return $teks;
    }
}
