<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    public function jadwalStylist(Request $request)
    {
        return $this->tampilJadwal($request, 'Stylist');
    }

    public function komisiStylist(Request $request)
    {
        return $this->tampilKomisi($request, 'Stylist');
    }

    public function jadwalTherapist(Request $request)
    {
        return $this->tampilJadwal($request, 'Therapist');
    }

    public function komisiTherapist(Request $request)
    {
        return $this->tampilKomisi($request, 'Therapist');
    }

    private function tampilJadwal(Request $request, string $peran)
    {
        /** @var Karyawan $petugas */
        $petugas  = Auth::guard('karyawan')->user();
        $mode     = $request->query('mode', 'semua'); // harian, mingguan, semua
        $tanggal  = $request->query('tanggal', now()->toDateString());

        $query = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->join('layanan', 'layanan.id_layanan', '=', 'detail_reservasi.id_layanan')
            ->join('pelanggan', 'pelanggan.id_pelanggan', '=', 'reservasi.id_pelanggan')
            ->where('detail_reservasi.id_karyawan', $petugas->id_karyawan)
            ->select(
                'reservasi.id_reservasi',
                'reservasi.tgl_booking',
                'reservasi.jam_booking',
                'reservasi.status as status_reservasi',
                'pelanggan.nama_cust',
                'pelanggan.no_telp',
                'layanan.id_layanan',
                'layanan.nama_layanan',
                'detail_reservasi.harga_saat_ini',
                'detail_reservasi.nominal_komisi',
                'detail_reservasi.status_layanan'
            )
            ->orderBy('reservasi.tgl_booking')
            ->orderBy('reservasi.jam_booking');

        if ($mode === 'harian') {
            $query->whereDate('reservasi.tgl_booking', $tanggal);
        } elseif ($mode === 'mingguan') {
            $awalMinggu  = now()->parse($tanggal)->startOfWeek()->toDateString();
            $akhirMinggu = now()->parse($tanggal)->endOfWeek()->toDateString();
            $query->whereBetween('reservasi.tgl_booking', [$awalMinggu, $akhirMinggu]);
        }

        $jadwal = $query->get();

        $view = $peran === 'Stylist'
            ? 'Karyawan.Stylist.dashboard'
            : 'Karyawan.Therapist.dashboard';

        return view($view, compact('petugas', 'jadwal', 'mode', 'tanggal', 'peran'));
    }

    public function tandaiLayananSelesai(Request $request, int $idReservasi, int $idLayanan)
    {
        /** @var Karyawan $petugas */
        $petugas = Auth::guard('karyawan')->user();

        // Anti-Bug #5 (IDOR): pastikan detail layanan ini benar-benar milik petugas yang sedang login
        $baris = DB::table('detail_reservasi')
            ->where('id_reservasi', $idReservasi)
            ->where('id_layanan', $idLayanan)
            ->where('id_karyawan', $petugas->id_karyawan)
            ->first();

        if (! $baris) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengubah jadwal petugas lain.');
        }

        // Audit Fix #2: Pastikan reservasi induk berstatus Diterima atau Selesai sebelum layanan ditandai Selesai
        $reservasi = Reservasi::findOrFail($idReservasi);
        if (! in_array($reservasi->statusTernormalisasi(), [Reservasi::STATUS_DITERIMA, Reservasi::STATUS_SELESAI], true)) {
            return back()->withErrors([
                'status' => "Layanan pada reservasi #{$idReservasi} tidak dapat ditandai selesai karena status reservasi saat ini adalah '{$reservasi->statusTernormalisasi()}'.",
            ]);
        }

        DB::table('detail_reservasi')
            ->where('id_reservasi', $idReservasi)
            ->where('id_layanan', $idLayanan)
            ->where('id_karyawan', $petugas->id_karyawan)
            ->update(['status_layanan' => 'Selesai']);

        return back()->with('status', 'Layanan berhasil ditandai Selesai.');
    }

    private function tampilKomisi(Request $request, string $peran)
    {
        /** @var Karyawan $petugas */
        $petugas    = Auth::guard('karyawan')->user();
        $tglMulai   = $request->query('mulai', now()->startOfMonth()->toDateString());
        $tglSelesai = $request->query('selesai', now()->endOfMonth()->toDateString());

        // Hanya hitung komisi dari reservasi yang sudah Selesai dan memiliki Transaksi lunas
        $daftarKomisi = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->join('transaksi', 'transaksi.id_reservasi', '=', 'reservasi.id_reservasi')
            ->join('layanan', 'layanan.id_layanan', '=', 'detail_reservasi.id_layanan')
            ->join('pelanggan', 'pelanggan.id_pelanggan', '=', 'reservasi.id_pelanggan')
            ->where('detail_reservasi.id_karyawan', $petugas->id_karyawan)
            ->where('reservasi.status', Reservasi::STATUS_SELESAI)
            ->whereBetween('reservasi.tgl_booking', [$tglMulai, $tglSelesai])
            ->select(
                'reservasi.id_reservasi',
                'reservasi.tgl_booking',
                'pelanggan.nama_cust',
                'layanan.nama_layanan',
                'detail_reservasi.harga_saat_ini',
                'detail_reservasi.persen_komisi',
                'detail_reservasi.nominal_komisi'
            )
            ->orderByDesc('reservasi.tgl_booking')
            ->get();

        $totalKomisi = $daftarKomisi->sum('nominal_komisi');
        $totalOmzet  = $daftarKomisi->sum('harga_saat_ini');

        $view = $peran === 'Stylist'
            ? 'Karyawan.Stylist.komisi'
            : 'Karyawan.Therapist.komisi';

        return view($view, compact('petugas', 'daftarKomisi', 'totalKomisi', 'totalOmzet', 'tglMulai', 'tglSelesai', 'peran'));
    }
}
