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
use App\Models\Transaksi;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function dashboard()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();
        $produk  = Produk::where('stok_produk', '>', 0)->orderBy('nama_produk')->get();

        // Ringkasan angka di dashboard
        $jmlMenunggu = Reservasi::whereIn('status', [Reservasi::STATUS_MENUNGGU, 'pending'])->count();
        $jmlDiproses = Reservasi::where('status', Reservasi::STATUS_DITERIMA)->count();
        $jmlSelesai  = Reservasi::where('status', Reservasi::STATUS_SELESAI)->count();

        // Hanya tampilkan beberapa antrean berstatus Diterima terdekat di POS agar tidak menumpuk
        $reservasiAktif = Reservasi::with(['layanan', 'pelanggan'])
            ->where('status', Reservasi::STATUS_DITERIMA)
            ->doesntHave('transaksi')
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->limit(6)
            ->get();

        $petugas = Karyawan::whereIn('role_jabatan', ['Stylist', 'Therapist'])
            ->where('aktif', true)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'persen_komisi']);

        $metodeAktif = MetodePembayaran::where('aktif', true)->orderBy('id_metode')->get();
        $promoAktif  = Promo::where('aktif', true)
            ->whereDate('berlaku_sampai', '>=', now()->toDateString())
            ->orderBy('kode_promo')
            ->get();

        $struk = PengaturanStruk::aktif();

        return view('Karyawan.Kasir.dashboard', compact(
            'layanan',
            'produk',
            'jmlMenunggu',
            'jmlDiproses',
            'jmlSelesai',
            'reservasiAktif',
            'petugas',
            'metodeAktif',
            'promoAktif',
            'struk'
        ));
    }

    /**
     * Menu Terpisah: Permintaan Booking dengan Tab Status & Paginasi.
     */
    public function permintaanBooking(Request $request)
    {
        $statusTab = $request->query('status', Reservasi::STATUS_MENUNGGU);

        $query = Reservasi::with(['layanan', 'pelanggan'])
            ->latest('tgl_booking')
            ->latest('id_reservasi');

        if ($statusTab === Reservasi::STATUS_MENUNGGU) {
            $query->whereIn('status', [Reservasi::STATUS_MENUNGGU, 'pending']);
        } elseif ($statusTab !== 'Semua') {
            $query->where('status', $statusTab);
        }

        $daftarBooking = $query->paginate(8)->withQueryString();
        $kebijakan     = PengaturanKebijakan::aktif();

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

                // Audit Fix #1: Periksa apakah ada bentrok jadwal dengan reservasi Diterima lainnya pada tanggal & jam yang sama
                $idPetugas = $reservasi->layanan
                    ->pluck('pivot.id_karyawan')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($idPetugas)) {
                    $bentrok = DB::table('detail_reservasi')
                        ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
                        ->where('reservasi.id_reservasi', '!=', $reservasi->id_reservasi)
                        ->whereDate('reservasi.tgl_booking', $reservasi->tgl_booking->toDateString())
                        ->where('reservasi.jam_booking', $reservasi->jam_booking)
                        ->where('reservasi.status', Reservasi::STATUS_DITERIMA)
                        ->whereIn('detail_reservasi.id_karyawan', $idPetugas)
                        ->lockForUpdate()
                        ->exists();

                    if ($bentrok) {
                        throw new DomainException("Tidak dapat menerima reservasi #{$reservasi->id_reservasi} karena petugas sudah memiliki reservasi Diterima lain pada tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking}.");
                    }
                }

                $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);

                NotifikasiPelanggan::kirim(
                    $reservasi->id_pelanggan,
                    'Permintaan Booking Diterima',
                    "Kabar gembira! Reservasi #{$reservasi->id_reservasi} untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} telah diterima oleh kasir. Silakan datang tepat waktu.",
                    'sukses',
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
            'alasan_penolakan.min'      => 'Alasan penolakan minimal 4 karakter.',
        ]);

        try {
            DB::transaction(function () use ($idReservasi, $data) {
                $reservasi = Reservasi::where('id_reservasi', $idReservasi)
                    ->lockForUpdate()
                    ->firstOrFail();

                $reservasi->ubahStatus(Reservasi::STATUS_DITOLAK, [
                    'alasan_penolakan' => $data['alasan_penolakan'],
                    'dibatalkan_oleh'  => 'Kasir',
                ]);

                NotifikasiPelanggan::kirim(
                    $reservasi->id_pelanggan,
                    'Permintaan Booking Ditolak',
                    "Mohon maaf, reservasi #{$reservasi->id_reservasi} untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} belum dapat kami terima. Alasan: {$data['alasan_penolakan']}.",
                    'bahaya',
                    $reservasi->id_reservasi
                );
            });
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} telah ditolak beserta alasan kepada pelanggan.");
    }

    /**
     * Menu Terpisah: Antrean Hari Ini (daftar antrean aktif saja).
     */
    public function antreanHariIni()
    {
        $antrean = Reservasi::with(['layanan', 'pelanggan'])
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

    /**
     * Proses Transaksi Pembayaran (Anti-Bug #2, #4, #6).
     */
    public function prosesBayar(Request $request)
    {
        $data = $request->validate([
            'id_reservasi'  => ['nullable', 'integer', 'exists:reservasi,id_reservasi'],
            'metode_bayar'  => ['required', 'string', 'max:80'],
            'nominal_uang'  => ['required', 'numeric', 'min:0'],
            'kode_promo'    => ['nullable', 'string', 'max:40'],
            'layanan_ids'   => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan,id_layanan'],
            'produk_items'  => ['nullable', 'array'],
        ], [
            'metode_bayar.required' => 'Metode pembayaran wajib dipilih.',
            'nominal_uang.required' => 'Nominal uang yang diterima wajib diisi.',
        ]);

        try {
            $transaksi = DB::transaction(function () use ($data, $request) {
                $idKasir = Auth::guard('karyawan')->id();
                $reservasi = null;

                // Audit Fix #5: Pastikan metode pembayaran yang dipilih benar-benar aktif di tabel metode_pembayaran
                if (MetodePembayaran::where('aktif', true)->exists()) {
                    $metodeValid = MetodePembayaran::where('nama_metode', $data['metode_bayar'])
                        ->where('aktif', true)
                        ->exists();

                    if (! $metodeValid) {
                        throw new DomainException("Metode pembayaran '{$data['metode_bayar']}' sedang tidak aktif atau tidak terdaftar.");
                    }
                }

                if (! empty($data['id_reservasi'])) {
                    $reservasi = Reservasi::with('layanan')
                        ->where('id_reservasi', $data['id_reservasi'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Anti-Bug #4: Cegah pembayaran pada reservasi yang sudah punya transaksi lunas
                    if ($reservasi->transaksi()->exists()) {
                        throw new DomainException("Reservasi #{$reservasi->id_reservasi} sudah pernah dibayar lunas sebelumnya.");
                    }

                    // Anti-Bug #4: Cegah pembayaran pada reservasi berstatus Hangus / Dibatalkan / Ditolak
                    $statusSekarang = $reservasi->statusTernormalisasi();
                    if (in_array($statusSekarang, [Reservasi::STATUS_HANGUS, Reservasi::STATUS_DIBATALKAN, Reservasi::STATUS_DITOLAK], true)) {
                        throw new DomainException("Reservasi #{$reservasi->id_reservasi} berstatus '{$statusSekarang}' dan tidak dapat diproses pembayarannya.");
                    }
                } else {
                    // Audit Fix #5: Pastikan transaksi walk-in memiliki minimal 1 layanan atau 1 produk
                    $layananIds = array_unique((array) ($data['layanan_ids'] ?? []));
                    $produkQtyTotal = collect((array) ($data['produk_items'] ?? []))->map(fn ($v) => (int) $v)->sum();

                    if (empty($layananIds) && $produkQtyTotal <= 0) {
                        throw new DomainException('Pilih minimal satu layanan atau satu produk untuk memproses transaksi langsung (walk-in).');
                    }

                    // Transaksi langsung (walk-in): buat reservasi otomatis atas nama pelanggan pertama
                    $pelangganDefault = Pelanggan::first();
                    if (! $pelangganDefault) {
                        throw new DomainException('Data pelanggan belum tersedia untuk mencatat transaksi.');
                    }

                    $reservasi = Reservasi::create([
                        'id_pelanggan' => $pelangganDefault->id_pelanggan,
                        'id_karyawan'  => $request->input('id_petugas') ?: $idKasir,
                        'tgl_booking'  => now()->toDateString(),
                        'jam_booking'  => now()->format('H:i'),
                        'status'       => Reservasi::STATUS_DITERIMA,
                    ]);

                    $stafWalkIn = Karyawan::find($request->input('id_petugas'));
                    $persenWalkIn = (float) ($stafWalkIn->persen_komisi ?? 15);

                    $lampiran = [];
                    foreach (Layanan::whereIn('id_layanan', $layananIds)->get() as $l) {
                        $harga = (float) $l->harga_layanan;
                        $lampiran[$l->id_layanan] = [
                            'id_karyawan'    => $stafWalkIn?->id_karyawan,
                            'harga_saat_ini' => $harga,
                            'persen_komisi'  => $persenWalkIn,
                            'nominal_komisi' => round($harga * ($persenWalkIn / 100), 2),
                            'status_layanan' => 'Selesai',
                        ];
                    }

                    if (! empty($lampiran)) {
                        $reservasi->layanan()->attach($lampiran);
                    }
                    $reservasi->load('layanan');
                }

                // Hitung subtotal layanan berdasarkan snapshot harga (atau harga layanan saat ini jika snapshot 0)
                $subtotalLayanan = 0;
                $totalKomisi     = 0;

                foreach ($reservasi->layanan as $l) {
                    $hargaSnap = (float) ($l->pivot->harga_saat_ini ?: $l->harga_layanan);
                    $persenSnap = (float) ($l->pivot->persen_komisi ?: 15);
                    $komisiSnap = (float) ($l->pivot->nominal_komisi ?: round($hargaSnap * ($persenSnap / 100), 2));

                    $subtotalLayanan += $hargaSnap;
                    $totalKomisi     += $komisiSnap;

                    DB::table('detail_reservasi')
                        ->where('id_reservasi', $reservasi->id_reservasi)
                        ->where('id_layanan', $l->id_layanan)
                        ->update([
                            'harga_saat_ini' => $hargaSnap,
                            'persen_komisi'  => $persenSnap,
                            'nominal_komisi' => $komisiSnap,
                            'status_layanan' => 'Selesai',
                        ]);
                }

                // Hitung subtotal produk & kurangi stok
                $subtotalProduk = 0;
                $lampiranProduk = [];
                $produkItems    = (array) ($data['produk_items'] ?? []);

                foreach ($produkItems as $idProduk => $qty) {
                    $qty = (int) $qty;
                    if ($qty <= 0) {
                        continue;
                    }

                    $produk = Produk::where('id_produk', $idProduk)->lockForUpdate()->first();
                    if (! $produk) {
                        continue;
                    }

                    if ($produk->stok_produk < $qty) {
                        throw new DomainException("Stok produk '{$produk->nama_produk}' tidak mencukupi (tersisa {$produk->stok_produk}).");
                    }

                    $produk->decrement('stok_produk', $qty);
                    $hargaProduk = (float) $produk->harga_produk;
                    $subtotalProduk += ($hargaProduk * $qty);

                    $lampiranProduk[$produk->id_produk] = [
                        'jumlah'         => $qty,
                        'harga_saat_ini' => $hargaProduk,
                    ];
                }

                $subtotal = $subtotalLayanan + $subtotalProduk;

                // Validasi & hitung diskon promo di server (Anti-Bug #6 & #9)
                $diskon    = 0;
                $kodePromo = null;

                if (! empty($data['kode_promo'])) {
                    $promo = Promo::where('kode_promo', strtoupper(trim($data['kode_promo'])))->first();
                    if (! $promo || ! $promo->masihBerlaku()) {
                        throw new DomainException("Kode promo '{$data['kode_promo']}' sudah tidak berlaku atau tidak ditemukan.");
                    }
                    if ($subtotal < (float) $promo->min_transaksi) {
                        throw new DomainException("Kode promo '{$promo->kode_promo}' memerlukan minimal transaksi Rp " . number_format($promo->min_transaksi, 0, ',', '.'));
                    }

                    $diskon    = $promo->hitungPotongan($subtotal);
                    $kodePromo = $promo->kode_promo;
                }

                $totalBayar  = max(0, $subtotal - $diskon);
                $nominalUang = (float) $data['nominal_uang'];

                if ($nominalUang < $totalBayar) {
                    throw new DomainException('Nominal uang yang diterima kurang dari total tagihan (Rp ' . number_format($totalBayar, 0, ',', '.') . ').');
                }

                $kembalian = $nominalUang - $totalBayar;

                // Ubah status reservasi ke Selesai
                if ($reservasi->statusTernormalisasi() === Reservasi::STATUS_MENUNGGU) {
                    $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);
                }
                $reservasi->ubahStatus(Reservasi::STATUS_SELESAI);

                $trx = Transaksi::create([
                    'id_reservasi'   => $reservasi->id_reservasi,
                    'id_karyawan'    => $idKasir,
                    'kode_promo'     => $kodePromo,
                    'diskon'         => $diskon,
                    'total_bayar'    => $totalBayar,
                    'nominal_uang'   => $nominalUang,
                    'kembalian'      => $kembalian,
                    'metode_bayar'   => $data['metode_bayar'],
                    'komisi_stylist' => $totalKomisi,
                ]);

                if (! empty($lampiranProduk)) {
                    $trx->produk()->attach($lampiranProduk);
                }

                return $trx;
            });
        } catch (DomainException $e) {
            return back()->withErrors(['pembayaran' => $e->getMessage()])->withInput();
        }

        return redirect()->route('kasir.dashboard')
            ->with('status', "Transaksi #INV-{$transaksi->id_transaksi} berhasil dibayar lunas! Kembalian: Rp " . number_format($transaksi->kembalian, 0, ',', '.'));
    }

    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Karyawan.Kasir.layanan', compact('layanan'));
    }

    public function storeLayanan(Request $request)
    {
        $data = $request->validate([
            'nama_layanan'  => ['required', 'string', 'max:100'],
            'kategori'      => ['required', 'in:Rambut,Perawatan'],
            'harga_layanan' => ['required', 'numeric', 'min:0'],
        ]);

        Layanan::create([
            'nama_layanan'    => $data['nama_layanan'],
            'kategori'        => $data['kategori'],
            'jabatan_petugas' => Layanan::jabatanUntukKategori($data['kategori']),
            'harga_layanan'   => $data['harga_layanan'],
        ]);

        return back()->with('status', 'Layanan baru berhasil ditambahkan.');
    }

    public function updateLayanan(Request $request, int $id)
    {
        $layanan = Layanan::findOrFail($id);

        $data = $request->validate([
            'nama_layanan'  => ['required', 'string', 'max:100'],
            'kategori'      => ['required', 'in:Rambut,Perawatan'],
            'harga_layanan' => ['required', 'numeric', 'min:0'],
        ]);

        $layanan->update([
            'nama_layanan'    => $data['nama_layanan'],
            'kategori'        => $data['kategori'],
            'jabatan_petugas' => Layanan::jabatanUntukKategori($data['kategori']),
            'harga_layanan'   => $data['harga_layanan'],
        ]);

        return back()->with('status', 'Data layanan berhasil diperbarui.');
    }

    public function destroyLayanan(int $id)
    {
        $layanan = Layanan::findOrFail($id);
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
            'nama_produk'  => ['required', 'string', 'max:100'],
            'stok_produk'  => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ]);

        Produk::create($data);

        return back()->with('status', 'Produk baru berhasil ditambahkan.');
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

        return back()->with('status', 'Stok dan harga produk berhasil diperbarui.');
    }

    public function destroyProduk(int $id)
    {
        $produk = Produk::findOrFail($id);
        $produk->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    public function laporan()
    {
        $reservasi = Reservasi::with(['layanan', 'transaksi'])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        $pelangganMap = Pelanggan::whereIn('id_pelanggan', $reservasi->pluck('id_pelanggan'))
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
