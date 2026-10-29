<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\PengaturanStrukRequest;
use App\Models\PengaturanStruk;
use Illuminate\Support\Facades\Schema;

class StrukController extends Controller
{
    public function edit()
    {
        $struk = PengaturanStruk::aktif();

        return view('Manajer.struk', compact('struk'));
    }

    public function update(PengaturanStrukRequest $request)
    {
        $data = $request->validated();

        $data['tampil_logo']    = $request->boolean('tampil_logo');
        $data['tampil_petugas'] = $request->boolean('tampil_petugas');
        $data['tampil_kasir']   = $request->boolean('tampil_kasir');
        $data['tampil_sosmed']  = $request->boolean('tampil_sosmed');

        if (! Schema::hasColumn('pengaturan_struk', 'ukuran_kertas')) {
            unset($data['ukuran_kertas']);
        } else {
            $data['ukuran_kertas'] = $data['ukuran_kertas'] ?? PengaturanStruk::UKURAN_80MM;
        }

        PengaturanStruk::aktif()->update($data);

        return back()->with('status', 'Pengaturan format struk nota berhasil disimpan.');
    }
}
