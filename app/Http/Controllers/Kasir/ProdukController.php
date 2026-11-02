<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manajer\DashboardController as ManajerDashboardController;
use App\Http\Requests\Manajer\ProdukRequest;
use App\Models\Produk;
use Illuminate\Support\Facades\Cache;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::orderBy('nama_produk')->get();

        return view('Karyawan.Kasir.produk', compact('produk'));
    }

    public function store(ProdukRequest $request)
    {
        Produk::create($request->validated());
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Produk baru berhasil ditambahkan.');
    }

    public function update(ProdukRequest $request, Produk $produk)
    {
        $produk->update($request->validated());
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Stok dan harga produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        $reservasiAktif = $produk->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'produk' => "Produk '{$produk->nama_produk}' tidak dapat dihapus karena masih dipesan pada {$reservasiAktif} reservasi aktif.",
            ]);
        }

        $produk->delete();
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Produk berhasil dihapus.');
    }
}
