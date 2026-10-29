<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\LayananRequest;
use App\Models\Layanan;
use Illuminate\Support\Facades\Cache;

class LayananController extends Controller
{
    public function index()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Manajer.layanan', compact('layanan'));
    }

    public function store(LayananRequest $request)
    {
        $data = $request->validated();
        Layanan::create($data);
        Cache::forget(DashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', "Layanan {$data['nama_layanan']} ({$data['durasi_menit']} menit + jeda {$data['jeda_menit']} menit) berhasil ditambahkan.");
    }

    public function update(LayananRequest $request, Layanan $layanan)
    {
        $layanan->update($request->validated());

        return back()->with('status', "Layanan {$layanan->nama_layanan} berhasil diperbarui.");
    }

    public function destroy(Layanan $layanan)
    {
        $reservasiAktif = $layanan->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'layanan' => "Layanan '{$layanan->nama_layanan}' tidak dapat dihapus karena masih terikat pada {$reservasiAktif} reservasi aktif (Menunggu/Diterima).",
            ]);
        }

        // Diarsipkan dengan soft-delete agar histori transaksi/reservasi lama tetap dapat membaca nama layanan ini
        $layanan->delete();
        Cache::forget(DashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', "Layanan {$layanan->nama_layanan} berhasil dihapus.");
    }
}
