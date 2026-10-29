<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\KaryawanRequest;
use App\Models\Karyawan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class KaryawanController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::orderBy('role_jabatan')->orderBy('nama_staff')->get();

        return view('Manajer.karyawan', compact('karyawan'));
    }

    public function store(KaryawanRequest $request)
    {
        $data = $request->validated();
        $data['aktif'] = true;

        Karyawan::create($data);
        Cache::forget(DashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', "Karyawan {$data['nama_staff']} berhasil ditambahkan.");
    }

    public function update(KaryawanRequest $request, Karyawan $karyawan)
    {
        $data = $request->validated();
        $idLogin = (int) auth('karyawan')->id();
        $statusAktif = $request->boolean('aktif', true);

        // Cegah Manajer mengunci dirinya sendiri akibat menonaktifkan atau menurunkan hak akses akun aktifnya
        if ((int) $karyawan->id_karyawan === $idLogin && (! $statusAktif || $data['role_jabatan'] !== Karyawan::JABATAN_MANAJER)) {
            return back()->withErrors([
                'karyawan' => 'Anda tidak dapat menonaktifkan atau mengubah peran akun Manajer yang sedang Anda gunakan.',
            ]);
        }

        $atributUpdate = Arr::except($data, ['password']);
        $atributUpdate['aktif'] = $statusAktif;

        if (! empty($data['password'])) {
            $atributUpdate['password'] = $data['password'];
        }

        $karyawan->update($atributUpdate);

        return back()->with('status', "Data karyawan {$karyawan->nama_staff} berhasil diperbarui.");
    }

    public function destroy(Karyawan $karyawan)
    {
        if ((int) $karyawan->id_karyawan === (int) auth('karyawan')->id()) {
            return back()->withErrors([
                'karyawan' => 'Anda tidak dapat menghapus akun Manajer yang sedang Anda gunakan.',
            ]);
        }

        $bookingAktif = $karyawan->jumlahBookingAktif();

        if ($bookingAktif > 0) {
            return back()->withErrors([
                'karyawan' => "Karyawan {$karyawan->nama_staff} tidak dapat dihapus karena masih memiliki {$bookingAktif} reservasi aktif (Menunggu/Diterima). Silakan alihkan atau selesaikan reservasi tersebut terlebih dahulu.",
            ]);
        }

        $karyawan->nonaktifkanDanArsipkan();
        Cache::forget(DashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', "Karyawan {$karyawan->nama_staff} berhasil dinonaktifkan dan diarsipkan.");
    }
}
