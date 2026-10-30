<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kasir\ProsesBayarRequest;
use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\NotifikasiPelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\PengaturanStruk;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Services\JadwalPetugasService;
use App\Services\PembayaranKasirService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function __construct(
        private readonly JadwalPetugasService $jadwalService,
        private readonly PembayaranKasirService $pembayaranService
    ) {}

    public function dashboard()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();
        $produk = Produk::tersedia()->orderBy('nama_produk')->get();

        $jmlMenunggu = Reservasi::whereIn('status', [Reservasi::STATUS_MENUNGGU, 'pending'])->count();
        $jmlDiproses = Reservasi::where('status', Reservasi::STATUS_DITERIMA)->count();
        $jmlSelesai = Reservasi::where('status', Reservasi::STATUS_SELESAI)->count();

        $reservasiAktif = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->where('status', Reservasi::STATUS_DITERIMA)
            ->doesntHave('transaksi')
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->limit(25)
            ->get();

        $petugas = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'persen_komisi']);

        $semuaPetugasMap = Karyawan::withTrashed()
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'aktif', 'deleted_at'])
            ->keyBy('id_karyawan');

        $metodeAktif = MetodePembayaran::where('aktif', true)->orderBy('id_metode')->get();
        $promoAktif = Promo::sedangBerlaku()->orderBy('kode_promo')->get();
        $struk = PengaturanStruk::aktif();

        return view('Karyawan.Kasir.dashboard', compact(
            'layanan',
            'produk',
            'jmlMenunggu',
            'jmlDiproses',
            'jmlSelesai',
            'reservasiAktif',
            'petugas',
            'semuaPetugasMap',
            'metodeAktif',
            'promoAktif',
            'struk'
        ));
    }

    public function permintaanBooking(Request $request)
    {
        $statusTab = $request->query('status', Reservasi::STATUS_MENUNGGU);

        $query = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->latest('tgl_booking')
            ->latest('id_reservasi');

        if ($statusTab === Reservasi::STATUS_MENUNGGU) {
            $query->whereIn('status', [Reservasi::STATUS_MENUNGGU, 'pending']);
        } elseif ($statusTab !== 'Semua') {
            $query->where('status', $statusTab);
        }

        $daftarBooking = $query->paginate(8)->withQueryString();
        $kebijakan = PengaturanKebijakan::aktif();

        $idPetugas = $daftarBooking->getCollection()
            ->flatMap(fn ($r) => $r->layanan->pluck('pivot.id_karyawan'))
            ->filter()
            ->unique();

        $petugasMap = Karyawan::withTrashed()
            ->whereIn('id_karyawan', $idPetugas)
            ->pluck('nama_staff', 'id_karyawan');

        return view('Karyawan.Kasir.permintaan-booking', compact(
            'daftarBooking',
            'statusTab',
            'kebijakan',
            'petugasMap'
        ));
    }

    public function terimaBooking(int $idReservasi)
    {
        try {
            DB::transaction(function () use ($idReservasi) {
                $reservasi = Reservasi::with('layanan')
                    ->where('id_reservasi', $idReservasi)
                    ->lockForUpdate()
                    ->firstOrFail();

                foreach ($reservasi->layanan as $layanan) {
                    $idStaf = $layanan->pivot->id_karyawan ? (int) $layanan->pivot->id_karyawan : null;
                    if (! $idStaf) {
                        continue;
                    }

                    $jamMulai = (string) ($layanan->pivot->jam_mulai ?: $reservasi->jam_booking ?: '10:00');
                    $jamBlokir = (string) ($layanan->pivot->jam_selesai_blokir ?: $reservasi->jam_selesai ?: '11:15');

                    $idSibuk = $this->jadwalService->cariIdPetugasSibukPadaRentang(
                        $reservasi->tgl_booking->toDateString(),
                        $jamMulai,
                        $jamBlokir,
                        (int) $reservasi->id_reservasi,
                        true
                    );

                    // Hanya cek bentrok terhadap reservasi yang sudah berstatus Diterima
                    $bentrokDiterima = DB::table('detail_reservasi')
                        ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
                        ->where('reservasi.id_reservasi', '!=', $reservasi->id_reservasi)
                        ->whereDate('reservasi.tgl_booking', $reservasi->tgl_booking->toDateString())
                        ->where('reservasi.status', Reservasi::STATUS_DITERIMA)
                        ->where('detail_reservasi.id_karyawan', $idStaf)
                        ->whereIn('detail_reservasi.id_karyawan', $idSibuk)
                        ->exists();

                    if ($bentrokDiterima) {
                        throw new DomainException(
                            "Tidak dapat menerima reservasi #{$reservasi->id_reservasi} karena petugas sudah memiliki reservasi Diterima lain pada tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$jamMulai} - {$jamBlokir} WIB."
                        );
                    }
                }

                $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);

                NotifikasiPelanggan::kirim(
                    $reservasi->id_pelanggan,
                    'Permintaan Booking Diterima',
                    "Kabar gembira! Reservasi #{$reservasi->id_reservasi} untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} telah diterima oleh kasir. Silakan datang tepat waktu.",
                    NotifikasiPelanggan::TIPE_SUKSES,
                    $reservasi->id_reservasi
                );
            });
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} berhasil diterima dan notifikasi telah dikirim ke pelanggan.");
    }

    public function tolakBooking(Request $request, int $idReservasi)
    {
        $data = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:4', 'max:255'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi agar pelanggan mengetahui penyebabnya.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 4 karakter.',
        ]);

        try {
            DB::transaction(function () use ($idReservasi, $data) {
                $reservasi = Reservasi::where('id_reservasi', $idReservasi)
                    ->lockForUpdate()
                    ->firstOrFail();

                $reservasi->ubahStatus(Reservasi::STATUS_DITOLAK, [
                    'alasan_penolakan' => $data['alasan_penolakan'],
                    'dibatalkan_oleh' => 'Kasir',
                ]);

                NotifikasiPelanggan::kirim(
                    $reservasi->id_pelanggan,
                    'Permintaan Booking Ditolak',
                    "Mohon maaf, reservasi #{$reservasi->id_reservasi} untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} belum dapat kami terima. Alasan: {$data['alasan_penolakan']}.",
                    NotifikasiPelanggan::TIPE_BAHAYA,
                    $reservasi->id_reservasi
                );
            });
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} telah ditolak beserta alasan kepada pelanggan.");
    }

    public function antreanHariIni()
    {
        $antrean = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->where('status', Reservasi::STATUS_DITERIMA)
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->get();

        $idPetugas = $antrean->flatMap(fn ($r) => $r->layanan->pluck('pivot.id_karyawan'))
            ->filter()
            ->unique();

        $petugasMap = Karyawan::withTrashed()
            ->whereIn('id_karyawan', $idPetugas)
            ->pluck('nama_staff', 'id_karyawan');

        return view('Karyawan.Kasir.antrean', compact('antrean', 'petugasMap'));
    }

    public function prosesBayar(ProsesBayarRequest $request)
    {
        try {
            $idKasir = (int) Auth::guard('karyawan')->id();
            $transaksi = $this->pembayaranService->prosesBayar($idKasir, $request->validated());
        } catch (DomainException $e) {
            return back()->withErrors(['pembayaran' => $e->getMessage()])->withInput();
        }

        return redirect()->route('kasir.dashboard')
            ->with('status', "Transaksi #INV-{$transaksi->id_transaksi} berhasil dibayar lunas! Kembalian: Rp ".number_format((float) $transaksi->kembalian, 0, ',', '.'));
    }

    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Karyawan.Kasir.layanan', compact('layanan'));
    }

    public function storeLayanan(Request $request)
    {
        $data = $request->validate([
            'nama_layanan' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'in:Rambut,Perawatan'],
            'harga_layanan' => ['required', 'numeric', 'min:0'],
            'durasi_menit' => ['nullable', 'integer', 'min:15', 'max:480'],
            'jeda_menit' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        Layanan::create([
            'nama_layanan' => $data['nama_layanan'],
            'kategori' => $data['kategori'],
            'jabatan_petugas' => Layanan::jabatanUntukKategori($data['kategori']),
            'harga_layanan' => $data['harga_layanan'],
            'durasi_menit' => (int) ($data['durasi_menit'] ?? Layanan::DEFAULT_DURASI_MENIT),
            'jeda_menit' => (int) ($data['jeda_menit'] ?? Layanan::DEFAULT_JEDA_MENIT),
            'petugas_eksklusif' => true,
        ]);

        return back()->with('status', 'Layanan baru berhasil ditambahkan.');
    }

    public function updateLayanan(Request $request, int $id)
    {
        $layanan = Layanan::findOrFail($id);

        $data = $request->validate([
            'nama_layanan' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'in:Rambut,Perawatan'],
            'harga_layanan' => ['required', 'numeric', 'min:0'],
            'durasi_menit' => ['nullable', 'integer', 'min:15', 'max:480'],
            'jeda_menit' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        $layanan->update([
            'nama_layanan' => $data['nama_layanan'],
            'kategori' => $data['kategori'],
            'jabatan_petugas' => Layanan::jabatanUntukKategori($data['kategori']),
            'harga_layanan' => $data['harga_layanan'],
            'durasi_menit' => (int) ($data['durasi_menit'] ?? $layanan->durasi_menit ?: Layanan::DEFAULT_DURASI_MENIT),
            'jeda_menit' => (int) ($data['jeda_menit'] ?? $layanan->jeda_menit ?? Layanan::DEFAULT_JEDA_MENIT),
        ]);

        return back()->with('status', 'Data layanan berhasil diperbarui.');
    }

    public function destroyLayanan(int $id)
    {
        $layanan = Layanan::findOrFail($id);
        $reservasiAktif = $layanan->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'layanan' => "Layanan '{$layanan->nama_layanan}' tidak dapat dihapus karena masih terikat pada {$reservasiAktif} reservasi aktif.",
            ]);
        }

        $layanan->delete();

        return back()->with('status', 'Layanan berhasil dihapus.');
    }

    public function produk()
    {
        $produk = Produk::orderBy('nama_produk')->get();

        return view('Karyawan.Kasir.produk', compact('produk'));
    }

    public function storeProduk(Request $request)
    {
        $data = $request->validate([
            'nama_produk' => [
                'required', 
                'string', 
                'max:100', 
                \Illuminate\Validation\Rule::unique('produk', 'nama_produk') // Menolak nama produk kembar
            ],
            'stok_produk' => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ], [
            'nama_produk.unique' => 'Nama produk ini sudah terdaftar di sistem. Gunakan nama lain.',
        ]);

        Produk::create($data);

        return back()->with('status', 'Produk baru berhasil ditambahkan.');
    }

    public function updateProduk(Request $request, int $id)
    {
        $produk = Produk::findOrFail($id);

        $data = $request->validate([
            'nama_produk' => [
                'required', 
                'string', 
                'max:100', 
                \Illuminate\Validation\Rule::unique('produk', 'nama_produk')->ignore($id, 'id_produk') // Mengabaikan ID produk saat edit
            ],
            'stok_produk' => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ], [
            'nama_produk.unique' => 'Nama produk ini sudah terdaftar di sistem. Gunakan nama lain.',
        ]);

        $produk->update($data);

        return back()->with('status', 'Stok dan harga produk berhasil diperbarui.');
    }

    public function destroyProduk(int $id)
    {
        $produk = Produk::findOrFail($id);
        $reservasiAktif = $produk->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'produk' => "Produk '{$produk->nama_produk}' tidak dapat dihapus karena masih dipesan pada {$reservasiAktif} reservasi aktif.",
            ]);
        }

        $produk->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    public function laporan()
    {
        $reservasi = Reservasi::with(['layanan', 'produkAddon', 'pelanggan', 'transaksi'])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        $pelangganMap = $reservasi
            ->pluck('pelanggan')
            ->filter()
            ->pluck('nama_cust', 'id_pelanggan');

        return view('Karyawan.Kasir.laporan', compact('reservasi', 'pelangganMap'));
    }

    public function metodePembayaran()
    {
        $metode = MetodePembayaran::orderBy('id_metode')->get();

        return view('Karyawan.Kasir.metode-pembayaran', compact('metode'));
    }

    public function struk()
    {
        $struk = PengaturanStruk::aktif();

        return view('Karyawan.Kasir.struk', compact('struk'));
    }
}
