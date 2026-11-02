<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manajer\DashboardController as ManajerDashboardController;
use App\Http\Requests\Manajer\LayananRequest;
use App\Models\Layanan;
use Illuminate\Support\Facades\Cache;

class LayananController extends Controller
{
    public function index()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Karyawan.Kasir.layanan', compact('layanan'));
    }

    public function store(LayananRequest $request)
    {
        Layanan::create($request->validated());
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Layanan baru berhasil ditambahkan.');
    }

    public function update(LayananRequest $request, Layanan $layanan)
    {
        $layanan->update($request->validated());

        return back()->with('status', 'Data layanan berhasil diperbarui.');
    }

    public function destroy(Layanan $layanan)
    {
        $reservasiAktif = $layanan->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'layanan' => "Layanan '{$layanan->nama_layanan}' tidak dapat dihapus karena masih terikat pada {$reservasiAktif} reservasi aktif.",
            ]);
        }

        $layanan->delete();
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Layanan berhasil dihapus.');
    }
}
