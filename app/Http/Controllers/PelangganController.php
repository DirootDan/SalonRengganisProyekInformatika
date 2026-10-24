<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PelangganController extends Controller
{
    public function beranda()
    {
        // Beranda sekarang tidak perlu query data layanan
        return view('Pelanggan.beranda');
    }

    public function booking()
    {
        $layanan = Layanan::orderBy('nama_layanan')->get();

        // Daftar petugas dikelompokkan per jabatan: ['Stylist' => [...], 'Therapist' => [...]]
        $petugas = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan'])
            ->groupBy('role_jabatan');

        return view('pelanggan.booking', compact('layanan', 'petugas'));
    }

    public function layanan()
    {
        // Pindahkan query data layanan ke sini
        $layanan = \App\Models\Layanan::all(); // Sesuaikan dengan model Anda
        return view('Pelanggan.layanan', compact('layanan'));
    }

    public function simpanBooking(Request $request)
    {
        $data = $request->validate([
            'tgl_booking' => ['required', 'date', 'after_or_equal:today'],
            'layanan'     => ['required', 'array', 'min:1'],
            'layanan.*'   => ['integer', 'exists:layanan,id_layanan'],
        ], [
            'tgl_booking.required'          => 'Tanggal booking wajib diisi.',
            'tgl_booking.date'              => 'Format tanggal tidak valid.',
            'tgl_booking.after_or_equal'    => 'Tanggal booking tidak boleh sebelum hari ini.',
            'layanan.required'              => 'Pilih minimal satu layanan.',
            'layanan.min'                   => 'Pilih minimal satu layanan.',
            'layanan.*.exists'              => 'Layanan yang dipilih tidak ditemukan.',
        ]);

        // Pastikan setiap layanan memiliki petugas dengan jabatan yang sesuai
        $layananDipilih = Layanan::whereIn('id_layanan', array_unique($data['layanan']))->get();
        $petugasInput   = (array) $request->input('petugas', []);
        $lampiran       = [];
        $galat          = [];

        foreach ($layananDipilih as $item) {
            $jabatan   = $item->jabatan_petugas;
            $idPetugas = $petugasInput[$item->id_layanan] ?? null;

            if (! $idPetugas) {
                $galat["petugas.{$item->id_layanan}"] = "Silakan pilih {$jabatan} untuk layanan {$item->nama_layanan}.";
                continue;
            }

            $valid = Karyawan::where('id_karyawan', $idPetugas)
                ->where('role_jabatan', $jabatan)
                ->exists();

            if (! $valid) {
                $galat["petugas.{$item->id_layanan}"] = "{$jabatan} yang dipilih untuk layanan {$item->nama_layanan} tidak valid.";
                continue;
            }

            $lampiran[$item->id_layanan] = ['id_karyawan' => (int) $idPetugas];
        }

        if ($galat) {
            return back()->withErrors($galat)->withInput();
        }

        DB::transaction(function () use ($data, $lampiran) {
            $reservasi = Reservasi::create([
                'id_pelanggan' => Auth::guard('pelanggan')->id(),
                'tgl_booking'  => $data['tgl_booking'],
                'status'       => 'pending',
            ]);

            $reservasi->layanan()->attach($lampiran);
        });

        return redirect()->route('pelanggan.riwayat')
            ->with('status', 'Booking berhasil dibuat. Kami akan segera mengonfirmasi.');
    }

    public function riwayat()
    {
        $reservasi = Reservasi::with('layanan')
            ->where('id_pelanggan', Auth::guard('pelanggan')->id())
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        // Ambil nama petugas untuk setiap layanan yang tercatat di detail reservasi
        $idPetugas = $reservasi->flatMap(fn ($r) => $r->layanan->pluck('pivot.id_karyawan'))
            ->filter()
            ->unique();

        $petugas = Karyawan::whereIn('id_karyawan', $idPetugas)->pluck('nama_staff', 'id_karyawan');

        return view('pelanggan.riwayat', compact('reservasi', 'petugas'));
    }

    public function profil()
    {
        $pelanggan = Auth::guard('pelanggan')->user();

        return view('Pelanggan.profil', compact('pelanggan'));
    }

    public function updateProfil(Request $request)
    {
        /** @var \App\Models\Pelanggan $pelanggan */
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
        /** @var \App\Models\Pelanggan $pelanggan */
        $pelanggan = Auth::guard('pelanggan')->user();

        $request->validate([
            'password_lama' => ['required', 'string'],
            'password'      => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password_lama.required' => 'Password saat ini wajib diisi.',
            'password.required'      => 'Password baru wajib diisi.',
            'password.min'           => 'Password baru minimal 6 karakter.',
            'password.confirmed'     => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (! Hash::check($request->input('password_lama'), $pelanggan->password)) {
            return back()
                ->withErrors(['password_lama' => 'Password saat ini yang Anda masukkan tidak sesuai.'])
                ->withInput();
        }

        $pelanggan->update([
            'password' => $request->input('password'),
        ]);

        return redirect()->route('pelanggan.profil')
            ->with('status', 'Password akun Anda berhasil diperbarui.');
    }
}
