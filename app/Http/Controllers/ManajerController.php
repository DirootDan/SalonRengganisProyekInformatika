<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\PengaturanStruk;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Models\RiwayatPelanggaran;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManajerController extends Controller
{
    public function dashboard()
    {
        $totalKaryawan  = Karyawan::count();
        $totalLayanan   = Layanan::count();
        $totalProduk    = Produk::count();
        $totalPelanggan = Pelanggan::count();
        $totalReservasi = Reservasi::count();
        $reservasiBaru  = Reservasi::with('layanan')
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->limit(6)
            ->get();

        $pelangganMap = Pelanggan::whereIn('id_pelanggan', $reservasiBaru->pluck('id_pelanggan'))
            ->pluck('nama_cust', 'id_pelanggan');

        return view('Manajer.dashboard', compact(
            'totalKaryawan',
            'totalLayanan',
            'totalProduk',
            'totalPelanggan',
            'totalReservasi',
            'reservasiBaru',
            'pelangganMap'
        ));
    }

    /* ================= 1A. CRUD KARYAWAN ================= */

    public function karyawan()
    {
        $karyawan = Karyawan::orderBy('role_jabatan')->orderBy('nama_staff')->get();

        return view('Manajer.karyawan', compact('karyawan'));
    }

    public function simpanKaryawan(Request $request)
    {
        $data = $request->validate([
            'nama_staff'    => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('karyawan', 'username')],
            'email'         => ['required', 'email', 'max:100', Rule::unique('karyawan', 'email')],
            'role_jabatan'  => ['required', Rule::in(['Manajer', 'Kasir', 'Stylist', 'Therapist'])],
            'persen_komisi' => ['required', 'numeric', 'min:0', 'max:100'],
            'password'      => ['required', 'string', 'min:6'],
        ], [
            'nama_staff.required' => 'Nama lengkap karyawan wajib diisi.',
            'username.required'   => 'Username login wajib diisi.',
            'username.unique'     => 'Username ini sudah digunakan.',
            'email.unique'        => 'Alamat email sudah terdaftar.',
            'password.min'        => 'Kata sandi minimal 6 karakter.',
        ]);

        $data['aktif'] = true;
        Karyawan::create($data);

        return back()->with('status', "Karyawan {$data['nama_staff']} berhasil ditambahkan.");
    }

    public function updateKaryawan(Request $request, int $id)
    {
        $staf = Karyawan::findOrFail($id);

        $data = $request->validate([
            'nama_staff'    => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('karyawan', 'username')->ignore($staf->id_karyawan, 'id_karyawan')],
            'email'         => ['required', 'email', 'max:100', Rule::unique('karyawan', 'email')->ignore($staf->id_karyawan, 'id_karyawan')],
            'role_jabatan'  => ['required', Rule::in(['Manajer', 'Kasir', 'Stylist', 'Therapist'])],
            'persen_komisi' => ['required', 'numeric', 'min:0', 'max:100'],
            'aktif'         => ['nullable', 'boolean'],
            'password'      => ['nullable', 'string', 'min:6'],
        ]);

        $idLogin = auth('karyawan')->id();
        $statusAktif = $request->boolean('aktif', true);

        if ((int) $staf->id_karyawan === (int) $idLogin && (! $statusAktif || $data['role_jabatan'] !== 'Manajer')) {
            return back()->withErrors(['karyawan' => 'Anda tidak dapat menonaktifkan atau mengubah peran akun Manajer yang sedang Anda gunakan.']);
        }

        $update = [
            'nama_staff'    => $data['nama_staff'],
            'username'      => $data['username'],
            'email'         => $data['email'],
            'role_jabatan'  => $data['role_jabatan'],
            'persen_komisi' => $data['persen_komisi'],
            'aktif'         => $statusAktif,
        ];

        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $staf->update($update);

        return back()->with('status', "Data karyawan {$staf->nama_staff} berhasil diperbarui.");
    }

    public function hapusKaryawan(int $id)
    {
        $staf = Karyawan::findOrFail($id);

        if ((int) $staf->id_karyawan === (int) auth('karyawan')->id()) {
            return back()->withErrors(['karyawan' => 'Anda tidak dapat menghapus akun Manajer yang sedang Anda gunakan.']);
        }

        // Kondisi tepi (Anti-Bug #9): periksa apakah karyawan masih punya booking aktif di masa mendatang
        $bookingAktif = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->where('detail_reservasi.id_karyawan', $staf->id_karyawan)
            ->whereIn('reservasi.status', [Reservasi::STATUS_MENUNGGU, Reservasi::STATUS_DITERIMA])
            ->count();

        $staf->update(['aktif' => false]);
        $staf->delete(); // Soft delete (Anti-Bug #7)

        $catatan = $bookingAktif > 0
            ? " Perhatian: karyawan ini memiliki {$bookingAktif} reservasi aktif yang perlu dialihkan."
            : '';

        return back()->with('status', "Karyawan {$staf->nama_staff} berhasil dinonaktifkan/dihapus.{$catatan}");
    }

    /* ================= 1B. CRUD LAYANAN ================= */

    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Manajer.layanan', compact('layanan'));
    }

    public function simpanLayanan(Request $request)
    {
        $data = $request->validate([
            'nama_layanan'    => ['required', 'string', 'max:100'],
            'kategori'        => ['required', 'string', 'max:50'],
            'jabatan_petugas' => ['required', Rule::in(Layanan::JABATAN_PETUGAS)],
            'harga_layanan'   => ['required', 'numeric', 'min:0'],
        ]);

        Layanan::create($data);

        return back()->with('status', "Layanan {$data['nama_layanan']} berhasil ditambahkan.");
    }

    public function updateLayanan(Request $request, int $id)
    {
        $layanan = Layanan::findOrFail($id);

        $data = $request->validate([
            'nama_layanan'    => ['required', 'string', 'max:100'],
            'kategori'        => ['required', 'string', 'max:50'],
            'jabatan_petugas' => ['required', Rule::in(Layanan::JABATAN_PETUGAS)],
            'harga_layanan'   => ['required', 'numeric', 'min:0'],
        ]);

        $layanan->update($data);

        return back()->with('status', "Layanan {$layanan->nama_layanan} berhasil diperbarui.");
    }

    public function hapusLayanan(int $id)
    {
        $layanan = Layanan::findOrFail($id);
        $layanan->delete(); // Soft delete

        return back()->with('status', "Layanan {$layanan->nama_layanan} berhasil dihapus.");
    }

    /* ================= 1C. CRUD PRODUK ================= */

    public function produk()
    {
        $produk = Produk::orderBy('nama_produk')->get();

        return view('Manajer.produk', compact('produk'));
    }

    public function simpanProduk(Request $request)
    {
        $data = $request->validate([
            'nama_produk'  => ['required', 'string', 'max:100'],
            'stok_produk'  => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ]);

        Produk::create($data);

        return back()->with('status', "Produk {$data['nama_produk']} berhasil ditambahkan.");
    }

    public function updateProduk(Request $request, int $id)
    {
        $produk = Produk::findOrFail($id);

        $data = $request->validate([
            'nama_produk'  => ['required', 'string', 'max:100'],
            'stok_produk'  => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ]);

        $produk->update($data);

        return back()->with('status', "Produk {$produk->nama_produk} berhasil diperbarui.");
    }

    public function hapusProduk(int $id)
    {
        $produk = Produk::findOrFail($id);
        $produk->delete(); // Soft delete

        return back()->with('status', "Produk {$produk->nama_produk} berhasil dihapus.");
    }

    /* ================= 1D. CRUD PROMO ================= */

    public function promo()
    {
        $promo = Promo::latest('id_promo')->get();

        return view('Manajer.promo', compact('promo'));
    }

    public function simpanPromo(Request $request)
    {
        $maksDiskon = $request->input('tipe_diskon') === 'Persentase' ? ['max:100'] : [];

        $data = $request->validate([
            'kode_promo'     => ['required', 'string', 'max:40', Rule::unique('promo', 'kode_promo')],
            'nama_promo'     => ['required', 'string', 'max:100'],
            'tipe_diskon'    => ['required', Rule::in(['Persentase', 'Nominal'])],
            'nilai_diskon'   => array_merge(['required', 'numeric', 'min:1'], $maksDiskon),
            'min_transaksi'  => ['required', 'numeric', 'min:0'],
            'berlaku_sampai' => ['required', 'date'],
            'aktif'          => ['nullable', 'boolean'],
        ], [
            'nilai_diskon.max' => 'Potongan diskon persentase tidak boleh melebihi 100%.',
        ]);

        $data['kode_promo']    = strtoupper($data['kode_promo']);
        $data['berlaku_mulai'] = now()->toDateString();
        $data['aktif']         = $request->boolean('aktif', true);

        Promo::create($data);

        return back()->with('status', "Program promo {$data['kode_promo']} berhasil disimpan.");
    }

    public function updatePromo(Request $request, int $id)
    {
        $promo = Promo::findOrFail($id);
        $maksDiskon = $request->input('tipe_diskon') === 'Persentase' ? ['max:100'] : [];

        $data = $request->validate([
            'kode_promo'     => ['required', 'string', 'max:40', Rule::unique('promo', 'kode_promo')->ignore($promo->id_promo, 'id_promo')],
            'nama_promo'     => ['required', 'string', 'max:100'],
            'tipe_diskon'    => ['required', Rule::in(['Persentase', 'Nominal'])],
            'nilai_diskon'   => array_merge(['required', 'numeric', 'min:1'], $maksDiskon),
            'min_transaksi'  => ['required', 'numeric', 'min:0'],
            'berlaku_sampai' => ['required', 'date'],
            'aktif'          => ['nullable', 'boolean'],
        ], [
            'nilai_diskon.max' => 'Potongan diskon persentase tidak boleh melebihi 100%.',
        ]);

        $data['kode_promo'] = strtoupper($data['kode_promo']);
        $data['aktif']      = $request->boolean('aktif', true);

        $promo->update($data);

        return back()->with('status', "Promo {$promo->kode_promo} berhasil diperbarui.");
    }

    public function hapusPromo(int $id)
    {
        $promo = Promo::findOrFail($id);
        $promo->delete(); // Soft delete

        return back()->with('status', "Promo {$promo->kode_promo} berhasil dihapus.");
    }

    /* ================= 2. LAPORAN & EKSPOR ================= */

    public function laporan(Request $request)
    {
        $tglMulai   = $request->query('mulai', now()->startOfMonth()->toDateString());
        $tglSelesai = $request->query('selesai', now()->endOfMonth()->toDateString());

        $reservasi = Reservasi::with(['layanan', 'transaksi'])
            ->whereBetween('tgl_booking', [$tglMulai, $tglSelesai])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        $pelangganMap = Pelanggan::whereIn('id_pelanggan', $reservasi->pluck('id_pelanggan'))
            ->pluck('nama_cust', 'id_pelanggan');

        $totalPendapatan = Transaksi::whereBetween('created_at', [$tglMulai . ' 00:00:00', $tglSelesai . ' 23:59:59'])
            ->sum('total_bayar');

        $totalKomisi = Transaksi::whereBetween('created_at', [$tglMulai . ' 00:00:00', $tglSelesai . ' 23:59:59'])
            ->sum('komisi_stylist');

        // Kinerja karyawan
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

        // Layanan terlaris
        $layananTerlaris = DB::table('detail_reservasi')
            ->join('layanan', 'layanan.id_layanan', '=', 'detail_reservasi.id_layanan')
            ->select('layanan.nama_layanan', DB::raw('COUNT(*) as total_pesan'))
            ->groupBy('layanan.id_layanan', 'layanan.nama_layanan')
            ->orderByDesc('total_pesan')
            ->first();

        return view('Manajer.laporan', compact(
            'reservasi',
            'pelangganMap',
            'tglMulai',
            'tglSelesai',
            'totalPendapatan',
            'totalKomisi',
            'kinerjaKaryawan',
            'layananTerlaris'
        ));
    }

    public function eksporLaporanCsv(Request $request)
    {
        $tglMulai   = $request->query('mulai', now()->startOfMonth()->toDateString());
        $tglSelesai = $request->query('selesai', now()->endOfMonth()->toDateString());

        $reservasi = Reservasi::with(['layanan', 'pelanggan', 'transaksi'])
            ->whereBetween('tgl_booking', [$tglMulai, $tglSelesai])
            ->latest('tgl_booking')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"laporan-salon-rengganis-{$tglMulai}-{$tglSelesai}.csv\"",
        ];

        $callback = function () use ($reservasi) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID Reservasi', 'Tanggal', 'Jam', 'Pelanggan', 'Rincian Layanan', 'Total (Rp)', 'Status']);

            foreach ($reservasi as $r) {
                $total = $r->transaksi ? $r->transaksi->total_bayar : $r->layanan->sum('harga_layanan');
                fputcsv($out, [
                    '#' . $r->id_reservasi,
                    $r->tgl_booking->format('d/m/Y'),
                    $r->jam_booking,
                    $r->pelanggan->nama_cust ?? 'Pelanggan',
                    $r->layanan->pluck('nama_layanan')->join(', '),
                    (int) $total,
                    $r->statusTernormalisasi(),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ================= 3. PENGATURAN STRUK NOTA ================= */

    public function struk()
    {
        $struk = PengaturanStruk::aktif();

        return view('Manajer.struk', compact('struk'));
    }

    public function simpanStruk(Request $request)
    {
        $data = $request->validate([
            'nama_salon'  => ['required', 'string', 'max:100'],
            'slogan'      => ['nullable', 'string', 'max:150'],
            'alamat'      => ['required', 'string', 'max:255'],
            'telepon'     => ['required', 'string', 'max:50'],
            'instagram'   => ['nullable', 'string', 'max:80'],
            'teks_header' => ['nullable', 'string', 'max:150'],
            'teks_footer' => ['nullable', 'string', 'max:500'],
        ]);

        $data['tampil_logo']    = $request->boolean('tampil_logo');
        $data['tampil_petugas'] = $request->boolean('tampil_petugas');
        $data['tampil_kasir']   = $request->boolean('tampil_kasir');
        $data['tampil_sosmed']  = $request->boolean('tampil_sosmed');

        PengaturanStruk::aktif()->update($data);

        return back()->with('status', 'Pengaturan format struk nota berhasil disimpan.');
    }

    /* ================= 4. METODE PEMBAYARAN ================= */

    public function metodePembayaran()
    {
        $metode = MetodePembayaran::orderBy('id_metode')->get();

        return view('Manajer.metode-pembayaran', compact('metode'));
    }

    public function simpanMetode(Request $request)
    {
        $data = $request->validate([
            'nama_metode'    => ['required', 'string', 'max:80'],
            'kategori'       => ['required', 'string', 'max:50'],
            'nomor_rekening' => ['required', 'string', 'max:100'],
            'atas_nama'      => ['required', 'string', 'max:100'],
        ]);

        $data['ikon']  = match ($data['kategori']) {
            'Tunai' => '💵',
            'Dompet Digital / QRIS', 'Dompet Digital / QR' => '📱',
            'Mesin EDC' => '💳',
            default => '🏦',
        };
        $data['aktif'] = true;

        MetodePembayaran::create($data);

        return back()->with('status', "Metode pembayaran {$data['nama_metode']} berhasil ditambahkan.");
    }

    public function updateMetode(Request $request, int $id)
    {
        $metode = MetodePembayaran::findOrFail($id);

        $data = $request->validate([
            'nama_metode'    => ['required', 'string', 'max:80'],
            'kategori'       => ['required', 'string', 'max:50'],
            'nomor_rekening' => ['required', 'string', 'max:100'],
            'atas_nama'      => ['required', 'string', 'max:100'],
        ]);

        $metode->update($data);

        return back()->with('status', "Metode {$metode->nama_metode} berhasil diperbarui.");
    }

    public function toggleMetode(int $id)
    {
        $metode = MetodePembayaran::findOrFail($id);
        $metode->update(['aktif' => ! $metode->aktif]);

        return back()->with('status', "Status metode {$metode->nama_metode} telah diperbarui.");
    }

    /* ================= 5. PENGATURAN KEBIJAKAN BOOKING & BUKA BLOKIR ================= */

    public function kebijakan()
    {
        $kebijakan = PengaturanKebijakan::aktif();

        $pelangganDiblokir = Pelanggan::where(function ($q) {
                $q->where('diblokir_permanen', true)
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('diblokir_sampai')
                          ->where('diblokir_sampai', '>', now());
                  });
            })
            ->orderByDesc('diblokir_permanen')
            ->orderByDesc('diblokir_sampai')
            ->get();

        $pelangganPelanggar = Pelanggan::where('jumlah_pelanggaran', '>', 0)
            ->orWhere('diblokir_permanen', true)
            ->orderByDesc('diblokir_permanen')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        $riwayatPelanggaran = RiwayatPelanggaran::with('pelanggan')
            ->latest('id_pelanggaran')
            ->limit(25)
            ->get();

        return view('Manajer.kebijakan', compact(
            'kebijakan',
            'pelangganDiblokir',
            'pelangganPelanggar',
            'riwayatPelanggaran'
        ));
    }

    public function simpanKebijakan(Request $request)
    {
        $data = $request->validate([
            'batas_pelanggaran'        => ['required', 'integer', 'min:1', 'max:20'],
            'rentang_hari_pelanggaran' => ['nullable', 'integer', 'min:1', 'max:365'],
            'lama_blokir_hari'         => ['required', 'integer', 'min:1', 'max:90'],
            'lama_blokir_kedua_hari'   => ['nullable', 'integer', 'min:1', 'max:180'],
            'toleransi_bayar_hari'     => ['required', 'integer', 'min:1', 'max:14'],
            'batas_batal_jam'          => ['required', 'integer', 'min:0', 'max:48'],
        ]);

        $data['rentang_hari_pelanggaran'] = $data['rentang_hari_pelanggaran'] ?? 90;
        $data['lama_blokir_kedua_hari']   = $data['lama_blokir_kedua_hari'] ?? 30;
        $data['blokir_ketiga_permanen']   = $request->has('blokir_ketiga_permanen')
            ? $request->boolean('blokir_ketiga_permanen')
            : true;
        $data['kebijakan_aktif'] = $request->boolean('kebijakan_aktif');

        PengaturanKebijakan::aktif()->update($data);

        return back()->with('status', 'Pengaturan kebijakan booking dan pinalti bertingkat berhasil disimpan.');
    }

    public function bukaBlokirPelanggan(Request $request, int $idPelanggan)
    {
        $data = $request->validate([
            'alasan_buka' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'alasan_buka.required' => 'Alasan pembukaan blokir wajib diisi.',
            'alasan_buka.min'      => 'Alasan pembukaan blokir minimal 5 karakter.',
        ]);

        $pelanggan = Pelanggan::findOrFail($idPelanggan);

        DB::transaction(function () use ($pelanggan, $data) {
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
                'jenis'        => 'Buka Blokir Manual',
                'tanggal'      => now()->toDateString(),
                'keterangan'   => 'Dibuka oleh Manajer. Alasan: ' . $data['alasan_buka'],
            ]);

            NotifikasiPelanggan::kirim(
                $pelanggan->id_pelanggan,
                'Blokir Akun Dibuka oleh Manajer',
                "Pemblokiran akun Anda telah dibuka oleh Manajer dengan catatan: \"{$data['alasan_buka']}\". Jumlah pelanggaran aktif Anda telah direset ke 0.",
                'sukses'
            );
        });

        return back()->with('status', "Blokir pelanggan {$pelanggan->nama_cust} berhasil dibuka dan pelanggaran direset ke 0.");
    }
}
