<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pelanggan\SimpanBookingRequest;
use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Services\JadwalPetugasService;
use App\Services\ReservasiService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PelangganController extends Controller
{
    public function __construct(
        private readonly JadwalPetugasService $jadwalService,
        private readonly ReservasiService $reservasiService
    ) {}

    public function beranda()
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $reservasiAktif = Reservasi::with(['layanan', 'produkAddon'])
            ->where('id_pelanggan', $pelanggan->id_pelanggan)
            ->whereIn('status', Reservasi::STATUS_AKTIF_SLOT)
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->get();

        $kebijakan = PengaturanKebijakan::aktif();

        return view('Pelanggan.beranda', compact('reservasiAktif', 'kebijakan'));
    }

    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Pelanggan.layanan', compact('layanan'));
    }

    public function booking(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();
        $pelanggan->refresh();

        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();
        $produk = Produk::tersedia()->orderBy('nama_produk')->get();
        $promoAktif = Promo::sedangBerlaku()->orderBy('kode_promo')->get();
        $jamOperasional = Reservasi::JAM_OPERASIONAL;
        $kebijakan = PengaturanKebijakan::aktif();

        $petugas = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan'])
            ->groupBy('role_jabatan');

        $jmlStylistLayanan = $layanan->where('jabatan_petugas', Karyawan::JABATAN_STYLIST)->count();
        $jmlTherapistLayanan = $layanan->where('jabatan_petugas', Karyawan::JABATAN_THERAPIST)->count();
        $jamTerpilihAwal = substr((string) old('jam_booking', '10:00'), 0, 5);
        $tglTerpilihAwal = (string) old('tgl_booking', now()->toDateString());
        $idLayananDariQuery = $request->integer('layanan');
        $daftarLayananAwal = array_map('intval', (array) old('layanan', $idLayananDariQuery > 0 ? [$idLayananDariQuery] : []));

        foreach ($layanan as $item) {
            $item->setAttribute('daftar_petugas', $petugas->get($item->jabatan_petugas, collect()));
            $item->setAttribute('dipilih_awal', in_array((int) $item->id_layanan, $daftarLayananAwal, true));
            $item->setAttribute('petugas_lama', (string) old('petugas.'.$item->id_layanan, ''));
        }

        foreach ($produk as $prod) {
            $prod->setAttribute('qty_awal', (int) old('produk_addon.'.$prod->id_produk, 0));
        }

        return view('Pelanggan.booking', compact(
            'layanan',
            'produk',
            'promoAktif',
            'petugas',
            'jamOperasional',
            'kebijakan',
            'pelanggan',
            'jmlStylistLayanan',
            'jmlTherapistLayanan',
            'jamTerpilihAwal',
            'tglTerpilihAwal',
            'daftarLayananAwal'
        ));
    }

    /**
     * Endpoint AJAX untuk memuat daftar karyawan & slot jam yang tersedia berdasarkan durasi layanan + buffer.
     */
    public function petugasTersedia(Request $request)
    {
        $request->validate([
            'tgl_booking' => ['required', 'date'],
            'jam_booking' => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'],
            'layanan' => ['nullable', 'array'],
            'layanan.*' => ['integer'],
        ]);

        $tgl = (string) $request->query('tgl_booking');
        $jam = substr((string) $request->query('jam_booking'), 0, 5);
        $idLayanan = array_filter(array_map('intval', (array) $request->query('layanan', [])));

        $layananDipilih = ! empty($idLayanan)
            ? Layanan::whereIn('id_layanan', $idLayanan)->orderBy('id_layanan')->get()
            : collect();

        return response()->json(
            $this->jadwalService->petugasTersediaUntukPilihan($tgl, $jam, $layananDipilih)
        );
    }

    public function simpanBooking(SimpanBookingRequest $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();
        $pelanggan->refresh();

        $kebijakan = PengaturanKebijakan::aktif();

        if ($kebijakan->kebijakan_aktif && $pelanggan->sedangDiblokir()) {
            return back()->withErrors([
                'blokir' => 'Akun Anda sedang diblokir dari fitur booking ('.$pelanggan->keteranganBlokir().').',
            ]);
        }

        try {
            $this->reservasiService->buatReservasi($pelanggan, $request->validated());
        } catch (DomainException $e) {
            $pesan = $e->getMessage();
            $kunciError = match (true) {
                str_contains(strtolower($pesan), 'promo') => 'kode_promo',
                str_contains(strtolower($pesan), 'produk') || str_contains(strtolower($pesan), 'stok') => 'produk_addon',
                default => 'bentrok',
            };

            return back()->withErrors([$kunciError => $pesan])->withInput();
        }

        return redirect()->route('pelanggan.riwayat')
            ->with('status', 'Permintaan booking berhasil dikirim dan sedang menunggu konfirmasi kasir.');
    }

    public function batalkanBooking(Request $request, int $idReservasi)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $alasanInput = trim((string) $request->input('alasan_batal', ''));
        $keteranganBatal = $alasanInput !== ''
            ? "Dibatalkan oleh pelanggan: {$alasanInput}"
            : 'Dibatalkan oleh pelanggan sebelum batas waktu.';

        try {
            DB::transaction(function () use ($idReservasi, $pelanggan, $keteranganBatal): void {
                $reservasi = Reservasi::where('id_reservasi', $idReservasi)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $reservasi->id_pelanggan !== (int) $pelanggan->id_pelanggan) {
                    abort(403, 'Anda tidak berhak membatalkan reservasi milik pelanggan lain.');
                }

                if (! $reservasi->bolehTransisiKe(Reservasi::STATUS_DIBATALKAN)) {
                    throw new DomainException(
                        "Reservasi berstatus '{$reservasi->statusTernormalisasi()}' tidak dapat dibatalkan."
                    );
                }

                $kebijakan = PengaturanKebijakan::aktif();
                if ($reservasi->lewatBatasBatalPelanggan($kebijakan->batas_batal_jam)) {
                    $waktuJadwal = $reservasi->waktuJadwal();
                    throw new DomainException(
                        "Pembatalan gratis hanya dapat dilakukan minimal {$kebijakan->batas_batal_jam} jam sebelum jadwal kedatangan ({$waktuJadwal->format('d/m/Y H:i')}). Silakan hubungi kasir salon."
                    );
                }

                $reservasi->ubahStatus(Reservasi::STATUS_DIBATALKAN, [
                    'dibatalkan_oleh' => Reservasi::DIBATALKAN_OLEH_PELANGGAN,
                    'alasan_penolakan' => $keteranganBatal,
                ]);

                NotifikasiPelanggan::kirim(
                    $pelanggan->id_pelanggan,
                    "Reservasi #{$reservasi->id_reservasi} Berhasil Dibatalkan",
                    "Permintaan pembatalan reservasi Anda untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} WIB telah diproses tanpa pinalti pelanggaran.",
                    'info',
                    $reservasi->id_reservasi
                );
            });
        } catch (DomainException $e) {
            return back()->withErrors(['batal' => $e->getMessage()]);
        }

        return redirect()->route('pelanggan.riwayat')
            ->with('status', "Reservasi #{$idReservasi} berhasil dibatalkan tanpa pinalti pelanggaran.");
    }

    public function riwayat()
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $reservasi = Reservasi::with(['layanan', 'produkAddon'])
            ->where('id_pelanggan', $pelanggan->id_pelanggan)
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        $idPetugas = $reservasi->flatMap(fn ($r) => $r->layanan->pluck('pivot.id_karyawan'))
            ->filter()
            ->unique();

        $petugas = Karyawan::withTrashed()
            ->whereIn('id_karyawan', $idPetugas)
            ->pluck('nama_staff', 'id_karyawan');

        $notifikasi = NotifikasiPelanggan::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->latest()
            ->limit(15)
            ->get();

        $kebijakan = PengaturanKebijakan::aktif();

        $jmlNotifBelumDibaca = $notifikasi->where('dibaca', false)->count();
        $jmlMenunggu = $reservasi->filter(fn (Reservasi $r) => $r->statusTernormalisasi() === Reservasi::STATUS_MENUNGGU)->count();
        $jmlDiterima = $reservasi->filter(fn (Reservasi $r) => $r->statusTernormalisasi() === Reservasi::STATUS_DITERIMA)->count();
        $jmlSelesai = $reservasi->filter(fn (Reservasi $r) => $r->statusTernormalisasi() === Reservasi::STATUS_SELESAI)->count();
        $jmlBatal = $reservasi->filter(fn (Reservasi $r) => $r->grupFilterRiwayat() === 'BatalGroup')->count();

        return view('Pelanggan.riwayat', compact(
            'reservasi',
            'petugas',
            'notifikasi',
            'kebijakan',
            'jmlNotifBelumDibaca',
            'jmlMenunggu',
            'jmlDiterima',
            'jmlSelesai',
            'jmlBatal'
        ));
    }

    public function bacaNotifikasi()
    {
        $idPelanggan = Auth::guard('pelanggan')->id();

        NotifikasiPelanggan::where('id_pelanggan', $idPelanggan)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return back()->with('status', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    public function profil(Request $request)
    {
        $pelanggan = Auth::guard('pelanggan')->user();
        $errorsBag = session()->get('errors');
        $bukaTabPassword = $request->query('tab') === 'password'
            || ($errorsBag?->hasAny(['password_lama', 'password']) ?? false);

        return view('Pelanggan.profil', compact('pelanggan', 'bukaTabPassword'));
    }

    public function updateProfil(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $data = $request->validate([
            'nama_cust' => ['required', 'string', 'max:100'],
            'no_telp' => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('pelanggan', 'email')->ignore($pelanggan->id_pelanggan, 'id_pelanggan'),
            ],
        ], [
            'nama_cust.required' => 'Nama lengkap wajib diisi.',
            'nama_cust.max' => 'Nama lengkap maksimal 100 karakter.',
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'no_telp.regex' => 'Format nomor telepon tidak valid (8-20 angka).',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain.',
        ]);

        $pelanggan->update($data);

        return redirect()->route('pelanggan.profil')
            ->with('status', 'Informasi profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (! Hash::check($request->input('password_lama'), $pelanggan->password)) {
            return back()
                ->withErrors(['password_lama' => 'Kata sandi saat ini yang Anda masukkan tidak sesuai.'])
                ->withInput();
        }

        $pelanggan->update([
            'password' => $request->input('password'),
        ]);

        return redirect()->route('pelanggan.profil')
            ->with('status', 'Kata sandi akun Anda berhasil diperbarui.');
    }

    public function updateFoto(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $request->validate([
            'foto_profil' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto_profil.required' => 'Pilih foto terlebih dahulu.',
            'foto_profil.image' => 'File harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto_profil.max' => 'Ukuran foto maksimal 2 MB.',
            'foto_profil.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya tidak lebih dari 2 MB.',
        ]);

        if ($pelanggan->foto_profil) {
            Storage::disk('public')->delete($pelanggan->foto_profil);
        }

        $pelanggan->foto_profil = $request->file('foto_profil')
            ->store('foto-profil/'.$pelanggan->id_pelanggan, 'public');
        $pelanggan->save();

        return redirect()->route('pelanggan.profil')
            ->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function hapusFoto()
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        if ($pelanggan->foto_profil) {
            Storage::disk('public')->delete($pelanggan->foto_profil);
            $pelanggan->foto_profil = null;
            $pelanggan->save();
        }

        return redirect()->route('pelanggan.profil')
            ->with('status', 'Foto profil berhasil dihapus.');
    }
}
