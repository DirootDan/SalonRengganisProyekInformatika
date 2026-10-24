<?php

namespace App\Http\Controllers;

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

        return view('pelanggan.booking', compact('layanan'));
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

        DB::transaction(function () use ($data) {
            $reservasi = Reservasi::create([
                'id_pelanggan' => Auth::guard('pelanggan')->id(),
                'tgl_booking'  => $data['tgl_booking'],
                'status'       => 'pending',
            ]);

            $reservasi->layanan()->attach(array_unique($data['layanan']));
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

        return view('pelanggan.riwayat', compact('reservasi'));
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
