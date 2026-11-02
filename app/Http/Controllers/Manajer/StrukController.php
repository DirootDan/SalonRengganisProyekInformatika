<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\PengaturanStrukRequest;
use App\Models\PengaturanStruk;

class StrukController extends Controller
{
    public function edit()
    {
        $struk = PengaturanStruk::aktif();
        $daftarUkuran = PengaturanStruk::DAFTAR_UKURAN_KERTAS;

        return view('Manajer.struk', compact('struk', 'daftarUkuran'));
    }

    public function update(PengaturanStrukRequest $request)
    {
        PengaturanStruk::aktif()->update($request->validated());

        return back()->with('status', 'Pengaturan format struk nota berhasil disimpan.');
    }
}
