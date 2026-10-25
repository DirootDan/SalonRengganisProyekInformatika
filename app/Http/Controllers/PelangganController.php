<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PelangganController extends Controller
{
    public function beranda()
    {
        return view('Pelanggan.beranda');
    }

    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Pelanggan.layanan', compact('layanan'));
    }

    public function booking()
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();
        $pelanggan->refresh();

        $layanan        = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();
        $jamOperasional = Reservasi::JAM_OPERASIONAL;
        $kebijakan      = PengaturanKebijakan::aktif();

        $petugas = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan'])
            ->groupBy('role_jabatan');

        return view('Pelanggan.booking', compact('layanan', 'petugas', 'jamOperasional', 'kebijakan', 'pelanggan'));
    }

    /**
     * Endpoint AJAX untuk memuat daftar karyawan yang tersedia pada tanggal & jam tertentu (tanpa reload halaman).
     */
    public function petugasTersedia(Request $request)
    {
        $request->validate([
            'tgl_booking' => ['required', 'date'],
            'jam_booking' => ['required', Rule::in(Reservasi::JAM_OPERASIONAL)],
        ]);

        $tgl = $request->query('tgl_booking');
        $jam = $request->query('jam_booking');

        // Cari ID karyawan yang sudah terpakai pada tanggal & jam tersebut (status Menunggu atau Diterima)
        $idSibuk = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->whereDate('reservasi.tgl_booking', $tgl)
            ->where('reservasi.jam_booking', $jam)
            ->whereIn('reservasi.status', [Reservasi::STATUS_MENUNGGU, Reservasi::STATUS_DITERIMA, 'pending'])
            ->whereNotNull('detail_reservasi.id_karyawan')
            ->pluck('detail_reservasi.id_karyawan')
            ->unique()
            ->values()
            ->all();

        $petugas = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->whereNotIn('id_karyawan', $idSibuk)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan'])
            ->groupBy('role_jabatan');

        return response()->json([
            'tanggal'         => $tgl,
            'jam'             => $jam,
            'id_sibuk'        => $idSibuk,
            'petugas'         => [
                'Stylist'   => $petugas->get('Stylist', collect())->values(),
                'Therapist' => $petugas->get('Therapist', collect())->values(),
            ],
        ]);
    }

    public function simpanBooking(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();
        $pelanggan->refresh();

        $kebijakan = PengaturanKebijakan::aktif();

        if ($kebijakan->kebijakan_aktif && $pelanggan->sedangDiblokir()) {
            return back()->withErrors([
                'blokir' => 'Akun Anda sedang diblokir dari fitur booking (' . $pelanggan->keteranganBlokir() . ').',
            ]);
        }

        $data = $request->validate([
            'tgl_booking' => ['required', 'date', 'after_or_equal:today'],
            'jam_booking' => ['required', Rule::in(Reservasi::JAM_OPERASIONAL)],
            'layanan'     => ['required', 'array', 'min:1'],
            'layanan.*'   => ['integer', 'exists:layanan,id_layanan'],
        ], [
            'tgl_booking.required'       => 'Tanggal kedatangan wajib diisi.',
            'tgl_booking.date'           => 'Format tanggal tidak valid.',
            'tgl_booking.after_or_equal' => 'Tanggal kedatangan tidak boleh di masa lalu.',
            'jam_booking.required'       => 'Jam kedatangan wajib dipilih.',
            'jam_booking.in'             => 'Jam kedatangan harus berada dalam jam operasional salon (09:00 - 19:00).',
            'layanan.required'           => 'Pilih minimal satu layanan perawatan.',
            'layanan.min'                => 'Pilih minimal satu layanan perawatan.',
            'layanan.*.exists'           => 'Layanan yang dipilih tidak ditemukan.',
        ]);

        $layananDipilih = Layanan::whereIn('id_layanan', array_unique($data['layanan']))->get();
        $petugasInput   = (array) $request->input('petugas', []);
        $galat          = [];

        foreach ($layananDipilih as $item) {
            $jabatan   = $item->jabatan_petugas;
            $idPetugas = $petugasInput[$item->id_layanan] ?? null;

            if (! $idPetugas) {
                $galat["petugas.{$item->id_layanan}"] = "Silakan pilih {$jabatan} untuk layanan {$item->nama_layanan}.";
                continue;
            }

            $staf = Karyawan::where('id_karyawan', $idPetugas)
                ->where('role_jabatan', $jabatan)
                ->where('aktif', true)
                ->first();

            if (! $staf) {
                $galat["petugas.{$item->id_layanan}"] = "{$jabatan} yang dipilih untuk layanan {$item->nama_layanan} tidak valid atau sedang tidak aktif.";
            }
        }

        if ($galat) {
            return back()->withErrors($galat)->withInput();
        }

        // Gunakan DB::transaction + lockForUpdate untuk mencegah double booking secara bersamaan
        try {
            DB::transaction(function () use ($data, $layananDipilih, $petugasInput, $pelanggan) {
                $idPetugasUnik = array_values(array_unique(array_map('intval', $petugasInput)));

                // Kunci baris karyawan yang dipilih agar request bersamaan menunggu
                $stafTerkunci = Karyawan::whereIn('id_karyawan', $idPetugasUnik)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id_karyawan');

                // Periksa ulang ketersediaan slot di dalam transaksi (Anti-Bug #1)
                $bentrok = DB::table('detail_reservasi')
                    ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
                    ->whereDate('reservasi.tgl_booking', $data['tgl_booking'])
                    ->where('reservasi.jam_booking', $data['jam_booking'])
                    ->whereIn('reservasi.status', [Reservasi::STATUS_MENUNGGU, Reservasi::STATUS_DITERIMA, 'pending'])
                    ->whereIn('detail_reservasi.id_karyawan', $idPetugasUnik)
                    ->lockForUpdate()
                    ->pluck('detail_reservasi.id_karyawan')
                    ->all();

                if (! empty($bentrok)) {
                    $namaBentrok = $stafTerkunci->only($bentrok)->pluck('nama_staff')->join(', ');
                    throw new \RuntimeException("Maaf, petugas ({$namaBentrok}) baru saja dipesan oleh pelanggan lain pada tanggal {$data['tgl_booking']} pukul {$data['jam_booking']}. Silakan pilih jam atau petugas lain.");
                }

                $lampiran = [];
                foreach ($layananDipilih as $item) {
                    $idStaf       = (int) $petugasInput[$item->id_layanan];
                    $staf         = $stafTerkunci->get($idStaf);
                    $harga        = (float) $item->harga_layanan;
                    $persenKomisi = (float) ($staf->persen_komisi ?? 15);
                    $nominalKomisi= round($harga * ($persenKomisi / 100), 2);

                    // Simpan snapshot harga & komisi saat reservasi dibuat (Anti-Bug #6)
                    $lampiran[$item->id_layanan] = [
                        'id_karyawan'    => $idStaf,
                        'harga_saat_ini' => $harga,
                        'persen_komisi'  => $persenKomisi,
                        'nominal_komisi' => $nominalKomisi,
                        'status_layanan' => 'Menunggu',
                    ];
                }

                $reservasi = Reservasi::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_karyawan'  => $idPetugasUnik[0] ?? null,
                    'tgl_booking'  => $data['tgl_booking'],
                    'jam_booking'  => $data['jam_booking'],
                    'status'       => Reservasi::STATUS_MENUNGGU,
                ]);

                $reservasi->layanan()->attach($lampiran);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['bentrok' => $e->getMessage()])->withInput();
        }

        return redirect()->route('pelanggan.riwayat')
            ->with('status', 'Permintaan booking berhasil dikirim dan sedang menunggu konfirmasi kasir.');
    }

    public function batalkanBooking(Request $request, int $idReservasi)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        // Cegah IDOR: pastikan reservasi milik pelanggan yang sedang login (Anti-Bug #5)
        $reservasi = Reservasi::findOrFail($idReservasi);

        if ((int) $reservasi->id_pelanggan !== (int) $pelanggan->id_pelanggan) {
            abort(403, 'Anda tidak berhak membatalkan reservasi milik pelanggan lain.');
        }

        if (! $reservasi->bolehTransisiKe(Reservasi::STATUS_DIBATALKAN)) {
            return back()->withErrors([
                'batal' => "Reservasi berstatus '{$reservasi->statusTernormalisasi()}' tidak dapat dibatalkan.",
            ]);
        }

        $kebijakan = PengaturanKebijakan::aktif();
        $waktuJadwal = Carbon::parse($reservasi->tgl_booking->toDateString() . ' ' . ($reservasi->jam_booking ?: '10:00'));
        $batasGratis = $waktuJadwal->copy()->subHours($kebijakan->batas_batal_jam);

        if ($reservasi->statusTernormalisasi() === Reservasi::STATUS_DITERIMA && now()->gt($batasGratis)) {
            return back()->withErrors([
                'batal' => "Pembatalan gratis hanya dapat dilakukan minimal {$kebijakan->batas_batal_jam} jam sebelum jadwal kedatangan ({$waktuJadwal->format('d/m/Y H:i')}). Silakan hubungi kasir salon.",
            ]);
        }

        DB::transaction(function () use ($reservasi) {
            $reservasi->ubahStatus(Reservasi::STATUS_DIBATALKAN, [
                'dibatalkan_oleh'  => 'Pelanggan',
                'alasan_penolakan' => 'Dibatalkan oleh pelanggan sebelum batas waktu.',
            ]);
        });

        return redirect()->route('pelanggan.riwayat')
            ->with('status', "Reservasi #{$reservasi->id_reservasi} berhasil dibatalkan tanpa pinalti pelanggaran.");
    }

    public function riwayat()
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $reservasi = Reservasi::with('layanan')
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

        return view('Pelanggan.riwayat', compact('reservasi', 'petugas', 'notifikasi', 'kebijakan'));
    }

    public function bacaNotifikasi()
    {
        $idPelanggan = Auth::guard('pelanggan')->id();

        NotifikasiPelanggan::where('id_pelanggan', $idPelanggan)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return back()->with('status', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    public function profil()
    {
        $pelanggan = Auth::guard('pelanggan')->user();

        return view('Pelanggan.profil', compact('pelanggan'));
    }

    public function updateProfil(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $data = $request->validate([
            'nama_cust' => ['required', 'string', 'max:100'],
            'no_telp'   => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email'     => [
                'required',
                'email',
                'max:100',
                Rule::unique('pelanggan', 'email')->ignore($pelanggan->id_pelanggan, 'id_pelanggan'),
            ],
        ], [
            'nama_cust.required' => 'Nama lengkap wajib diisi.',
            'nama_cust.max'      => 'Nama lengkap maksimal 100 karakter.',
            'no_telp.required'   => 'Nomor telepon wajib diisi.',
            'no_telp.regex'      => 'Format nomor telepon tidak valid (8-20 angka).',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format alamat email tidak valid.',
            'email.unique'       => 'Alamat email ini sudah digunakan oleh akun lain.',
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
            'password'      => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required'      => 'Kata sandi baru wajib diisi.',
            'password.min'           => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed'     => 'Konfirmasi kata sandi baru tidak cocok.',
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

    /* ===== FOTO PROFIL ===== */

    public function updateFoto(Request $request)
    {
        /** @var Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $request->validate([
            'foto_profil' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto_profil.required' => 'Pilih foto terlebih dahulu.',
            'foto_profil.image'    => 'File harus berupa gambar.',
            'foto_profil.mimes'    => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto_profil.max'      => 'Ukuran foto maksimal 2 MB.',
            'foto_profil.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya tidak lebih dari 2 MB.',
        ]);

        // Hapus foto lama agar penyimpanan tidak menumpuk
        if ($pelanggan->foto_profil) {
            Storage::disk('public')->delete($pelanggan->foto_profil);
        }

        // Satu folder per akun: storage/app/public/foto-profil/{id_pelanggan}/namaacak.jpg
        $pelanggan->foto_profil = $request->file('foto_profil')
            ->store('foto-profil/' . $pelanggan->id_pelanggan, 'public');
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