<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\PromoRequest;
use App\Models\Promo;

class PromoController extends Controller
{
    public function index()
    {
        $promo = Promo::latest('id_promo')->get();

        return view('Manajer.promo', compact('promo'));
    }

    public function store(PromoRequest $request)
    {
        $data = $request->validated();
        $data['kode_promo'] = strtoupper($data['kode_promo']);
        $data['berlaku_mulai'] = now()->toDateString();
        $data['aktif'] = $request->boolean('aktif', true);

        Promo::create($data);

        return back()->with('status', "Program promo {$data['kode_promo']} berhasil disimpan.");
    }

    public function update(PromoRequest $request, Promo $promo)
    {
        $data = $request->validated();
        $data['kode_promo'] = strtoupper($data['kode_promo']);
        $data['aktif'] = $request->boolean('aktif', true);

        $promo->update($data);

        return back()->with('status', "Promo {$promo->kode_promo} berhasil diperbarui.");
    }

    public function destroy(Promo $promo)
    {
        $reservasiAktif = $promo->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'promo' => "Promo '{$promo->kode_promo}' tidak dapat dihapus karena masih digunakan pada {$reservasiAktif} reservasi aktif (Menunggu/Diterima).",
            ]);
        }

        // Diarsipkan dengan soft-delete agar riwayat pemakaian diskon pada transaksi masa lalu tetap terjaga
        $promo->delete();

        return back()->with('status', "Promo {$promo->kode_promo} berhasil dihapus.");
    }
}
