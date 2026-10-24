<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
}