<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\ProdukRequest;
use App\Models\Produk;
use Illuminate\Support\Facades\Cache;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::orderBy('nama_produk')->get();

        return view('Manajer.produk', compact('produk'));
    }

    public function store(ProdukRequest $request)
    {
        $data = $request->validated();
        Produk::create($data);
        Cache::forget(DashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', "Produk {$data['nama_produk']} berhasil ditambahkan.");
    }

    public function update(ProdukRequest $request, Produk $produk)
    {
        $produk->update($request->validated());

        return back()->with('status', "Produk {$produk->nama_produk} berhasil diperbarui.");
    }

    public function destroy(Produk $produk)
    {
        // Diarsipkan dengan soft-delete agar histori penjualan produk pada nota lama tidak rusak
        $produk->delete();
        Cache::forget(DashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', "Produk {$produk->nama_produk} berhasil dihapus.");
    }
}
