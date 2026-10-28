<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\MetodePembayaranRequest;
use App\Models\MetodePembayaran;

class MetodePembayaranController extends Controller
{
    public function index()
    {
        $metode = MetodePembayaran::orderBy('id_metode')->get();

        return view('Manajer.metode-pembayaran', compact('metode'));
    }

    public function store(MetodePembayaranRequest $request)
    {
        $data = $request->validated();
        $data['ikon']  = MetodePembayaran::ikonUntukKategori($data['kategori']);
        $data['aktif'] = true;

        MetodePembayaran::create($data);

        return back()->with('status', "Metode pembayaran {$data['nama_metode']} berhasil ditambahkan.");
    }

    public function update(MetodePembayaranRequest $request, MetodePembayaran $metode)
    {
        $data = $request->validated();
        $data['ikon'] = MetodePembayaran::ikonUntukKategori($data['kategori']);

        $metode->update($data);

        return back()->with('status', "Metode {$metode->nama_metode} berhasil diperbarui.");
    }

    public function toggle(MetodePembayaran $metode)
    {
        $metode->update(['aktif' => ! $metode->aktif]);

        return back()->with('status', "Status metode {$metode->nama_metode} telah diperbarui.");
    }
}
