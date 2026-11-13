<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Reservasi;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Portal kerja Stylist & Therapist: jadwal pribadi, tandai layanan selesai, dan laporan komisi.
 * Kedua peran memakai view yang sama; perbedaan teks/rute diatur lewat TAMPILAN_PERAN.
 */
class PetugasController extends Controller
{
    private const MODE_JADWAL = ['semua', 'harian', 'mingguan'];

    /** Status reservasi yang masih relevan untuk dikerjakan petugas. */
    private const STATUS_JADWAL_TAMPIL = [
        Reservasi::STATUS_MENUNGGU,
        Reservasi::STATUS_DITERIMA,
        Reservasi::STATUS_SELESAI,
    ];

    private const TAMPILAN_PERAN = [
        Karyawan::JABATAN_STYLIST => [
            'prefix_rute' => 'stylist',
            'ikon' => '✂️',
            'bidang' => 'perawatan rambut',
        ],
        Karyawan::JABATAN_THERAPIST => [
            'prefix_rute' => 'therapist',
            'ikon' => '🌿',
            'bidang' => 'perawatan wajah & tubuh',
        ],
    ];

    public function jadwalStylist(Request $request)
    {
        return $this->tampilJadwal($request, Karyawan::JABATAN_STYLIST);
    }

    public function komisiStylist(Request $request)
    {
        return $this->tampilKomisi($request, Karyawan::JABATAN_STYLIST);
    }

    public function jadwalTherapist(Request $request)
    {
        return $this->tampilJadwal($request, Karyawan::JABATAN_THERAPIST);
    }

    public function komisiTherapist(Request $request)
    {
        return $this->tampilKomisi($request, Karyawan::JABATAN_THERAPIST);
    }

    public function tandaiLayananSelesai(int $idReservasi, int $idLayanan)
    {
        $petugas = $this->petugasLogin();

        // Anti-IDOR: layanan harus milik petugas yang sedang login
        $baris = DB::table('detail_reservasi')
            ->where('id_reservasi', $idReservasi)
            ->where('id_layanan', $idLayanan)
            ->where('id_karyawan', $petugas->id_karyawan)
            ->first();

        if (! $baris) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengubah jadwal petugas lain.');
        }

        if ($baris->status_layanan === Reservasi::STATUS_SELESAI) {
            return back()->with('status', 'Layanan ini sudah ditandai Selesai sebelumnya.');
        }

        $statusReservasi = Reservasi::findOrFail($idReservasi)->statusTernormalisasi();
        if (! self::bolehDitandaiSelesai($statusReservasi)) {
            return back()->withErrors([
                'status' => "Layanan pada reservasi #{$idReservasi} belum bisa ditandai selesai karena status booking masih '{$statusReservasi}'. Tunggu sampai Kasir menerima booking ini.",
            ]);
        }

        DB::table('detail_reservasi')
            ->where('id_reservasi', $idReservasi)
            ->where('id_layanan', $idLayanan)
            ->where('id_karyawan', $petugas->id_karyawan)
            ->update(['status_layanan' => Reservasi::STATUS_SELESAI]);

        return back()->with('status', 'Layanan berhasil ditandai Selesai.');
    }

    private function tampilJadwal(Request $request, string $peran)
    {
        $petugas = $this->petugasLogin();
        $mode = in_array($request->query('mode'), self::MODE_JADWAL, true) ? $request->query('mode') : 'semua';
        $tanggal = $this->tanggalDariQuery($request, 'tanggal', now());

        $query = $this->kueriLayananPetugas($petugas->id_karyawan)
            ->whereIn('reservasi.status', self::STATUS_JADWAL_TAMPIL)
            ->addSelect([
                'reservasi.jam_booking',
                'reservasi.status as status_reservasi',
                DB::raw("COALESCE(pelanggan.no_telp, 'Walk-In') as no_telp"),
                'layanan.id_layanan',
                'detail_reservasi.status_layanan',
            ])
            ->orderBy('reservasi.tgl_booking')
            ->orderBy('reservasi.jam_booking');

        if ($mode === 'harian') {
            $query->whereDate('reservasi.tgl_booking', $tanggal->toDateString());
        } elseif ($mode === 'mingguan') {
            $query->whereBetween('reservasi.tgl_booking', [
                $tanggal->copy()->startOfWeek()->toDateString(),
                $tanggal->copy()->endOfWeek()->toDateString(),
            ]);
        }

        $jadwal = $this->lengkapiBarisJadwal($query->get());
        $totalJadwal = $jadwal->count();
        $layananSelesai = $jadwal->where('sudah_selesai', true)->count();
        $layananBelumSelesai = $totalJadwal - $layananSelesai;
        $jadwalHariIni = $this->kueriLayananPetugas($petugas->id_karyawan)
            ->whereIn('reservasi.status', self::STATUS_JADWAL_TAMPIL)
            ->whereDate('reservasi.tgl_booking', now()->toDateString())
            ->count();

        return view('Karyawan.Petugas.jadwal', [
            'petugas' => $petugas,
            'jadwal' => $jadwal,
            'mode' => $mode,
            'tanggal' => $tanggal->toDateString(),
            'peran' => $peran,
            'tampilan' => self::TAMPILAN_PERAN[$peran],
            'totalJadwal' => $totalJadwal,
            'layananSelesai' => $layananSelesai,
            'layananBelumSelesai' => $layananBelumSelesai,
            'jadwalHariIni' => $jadwalHariIni,
        ]);
    }

    private function tampilKomisi(Request $request, string $peran)
    {
        $petugas = $this->petugasLogin();
        $tglMulai = $this->tanggalDariQuery($request, 'mulai', now()->startOfMonth())->toDateString();
        $tglSelesai = $this->tanggalDariQuery($request, 'selesai', now()->endOfMonth())->toDateString();

        // Komisi hanya dari reservasi Selesai yang sudah punya transaksi lunas
        $daftarKomisi = $this->kueriLayananPetugas($petugas->id_karyawan)
            ->join('transaksi', 'transaksi.id_reservasi', '=', 'reservasi.id_reservasi')
            ->where('reservasi.status', Reservasi::STATUS_SELESAI)
            ->whereBetween('reservasi.tgl_booking', [$tglMulai, $tglSelesai])
            ->addSelect(['detail_reservasi.persen_komisi'])
            ->orderByDesc('reservasi.tgl_booking')
            ->get()
            ->each(fn (object $baris) => $baris->tgl_booking = Carbon::parse($baris->tgl_booking));

        return view('Karyawan.Petugas.komisi', [
            'petugas' => $petugas,
            'daftarKomisi' => $daftarKomisi,
            'totalKomisi' => $daftarKomisi->sum('nominal_komisi'),
            'totalOmzet' => $daftarKomisi->sum('harga_saat_ini'),
            'tglMulai' => $tglMulai,
            'tglSelesai' => $tglSelesai,
            'peran' => $peran,
            'tampilan' => self::TAMPILAN_PERAN[$peran],
        ]);
    }

    /**
     * Kueri dasar detail layanan milik satu petugas (dipakai jadwal & komisi).
     */
    private function kueriLayananPetugas(int $idKaryawan): Builder
    {
        return DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->join('layanan', 'layanan.id_layanan', '=', 'detail_reservasi.id_layanan')
            ->leftJoin('pelanggan', 'pelanggan.id_pelanggan', '=', 'reservasi.id_pelanggan')
            ->where('detail_reservasi.id_karyawan', $idKaryawan)
            ->select([
                'reservasi.id_reservasi',
                'reservasi.tgl_booking',
                DB::raw("COALESCE(NULLIF(reservasi.nama_pelanggan_walkin, ''), pelanggan.nama_cust, 'Pelanggan Umum') as nama_cust"),
                'layanan.nama_layanan',
                'detail_reservasi.harga_saat_ini',
                'detail_reservasi.nominal_komisi',
            ]);
    }

    /**
     * Tambahkan data siap-tampil per baris agar Blade tidak berisi logika.
     */
    private function lengkapiBarisJadwal(Collection $jadwal): Collection
    {
        return $jadwal->each(function (object $baris) {
            $baris->tgl_booking = Carbon::parse($baris->tgl_booking);
            $baris->sudah_selesai = $baris->status_layanan === Reservasi::STATUS_SELESAI;
            $baris->bisa_ditandai_selesai = ! $baris->sudah_selesai
                && self::bolehDitandaiSelesai(Reservasi::normalisasiStatus($baris->status_reservasi));
        });
    }

    private static function bolehDitandaiSelesai(string $statusReservasi): bool
    {
        return in_array($statusReservasi, [Reservasi::STATUS_DITERIMA, Reservasi::STATUS_SELESAI], true);
    }

    /**
     * Ambil tanggal Y-m-d dari query string; nilai tidak valid diganti default (anti error 500).
     */
    private function tanggalDariQuery(Request $request, string $kunci, Carbon $default): Carbon
    {
        $nilai = (string) $request->query($kunci, '');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) === 1 && checkdate((int) substr($nilai, 5, 2), (int) substr($nilai, 8, 2), (int) substr($nilai, 0, 4))) {
            return Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay();
        }

        return $default->copy()->startOfDay();
    }

    private function petugasLogin(): Karyawan
    {
        /** @var Karyawan $petugas */
        $petugas = Auth::guard('karyawan')->user();

        return $petugas;
    }
}
