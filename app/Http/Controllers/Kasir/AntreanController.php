<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Reservasi;
use App\Services\ReservasiService;
use DomainException;
use Illuminate\Http\Request;

class AntreanController extends Controller
{
    public function __construct(
        private readonly ReservasiService $reservasiService
    ) {}

    public function index()
    {
        $antrean = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->where('status', Reservasi::STATUS_DITERIMA)
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->get();

        $petugasMap = $this->reservasiService->petakanNamaPetugas($antrean);
        $petugasPerJabatan = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan'])
            ->groupBy('role_jabatan');

        return view('Karyawan.Kasir.antrean', compact('antrean', 'petugasMap', 'petugasPerJabatan'));
    }

    public function gantiPetugas(Request $request, int $idReservasi, int $idLayanan)
    {
        $validated = $request->validate([
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id_karyawan'],
        ]);

        try {
            $this->reservasiService->gantiPetugasAntrean($idReservasi, $idLayanan, (int) $validated['id_karyawan']);
        } catch (DomainException $e) {
            return back()->withErrors(['antrean' => $e->getMessage()]);
        }

        return back()->with('status', "Petugas pada reservasi #{$idReservasi} berhasil dialihkan.");
    }
}
