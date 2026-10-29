<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Reservasi;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JadwalPetugasService
{
    /**
     * Menyusun jadwal berurutan (timeline) untuk satu atau beberapa layanan dalam satu reservasi.
     *
     * @param  Collection<int, Layanan>  $layananDipilih
     * @return array{jam_mulai: string, jam_selesai: string, total_durasi_menit: int, items: array<int, array<string, mixed>>}
     */
    public function susunTimelineLayanan(Collection $layananDipilih, string $jamKedatangan): array
    {
        $jamAwal = substr($jamKedatangan, 0, 5);
        $mulaiAwal = Carbon::createFromFormat('H:i', $jamAwal);
        $batasTutup = Carbon::createFromFormat('H:i', Reservasi::JAM_TUTUP_SALON);

        $kursor = $mulaiAwal->copy();
        $totalDurasi = 0;
        $items = [];

        foreach ($layananDipilih->sortBy('id_layanan') as $layanan) {
            $durasi = max(15, (int) ($layanan->durasi_menit ?: Layanan::DEFAULT_DURASI_MENIT));
            $jeda = max(0, (int) ($layanan->jeda_menit ?? Layanan::DEFAULT_JEDA_MENIT));

            $mulaiItem = $kursor->copy();
            $selesaiItem = $mulaiItem->copy()->addMinutes($durasi);
            $blokirItem = $selesaiItem->copy()->addMinutes($jeda);

            $items[$layanan->id_layanan] = [
                'id_layanan' => $layanan->id_layanan,
                'nama_layanan' => $layanan->nama_layanan,
                'jabatan_petugas' => $layanan->jabatan_petugas,
                'durasi_saat_ini' => $durasi,
                'jeda_saat_ini' => $jeda,
                'jam_mulai' => $mulaiItem->format('H:i'),
                'jam_selesai' => $selesaiItem->format('H:i'),
                'jam_selesai_blokir' => $blokirItem->format('H:i'),
            ];

            $kursor = $selesaiItem;
            $totalDurasi += $durasi;
        }

        if (! $kursor->isSameDay($mulaiAwal) || $kursor->gt($batasTutup)) {
            $jamSelesaiTeks = $kursor->format('H:i');

            throw new DomainException(
                "Total durasi perawatan ({$totalDurasi} menit, selesai pukul {$jamSelesaiTeks} WIB) melewati jam operasional tutup salon (pukul ".Reservasi::JAM_TUTUP_SALON.' WIB). Silakan pilih jam kedatangan lebih awal.'
            );
        }

        return [
            'jam_mulai' => $jamAwal,
            'jam_selesai' => $kursor->format('H:i'),
            'total_durasi_menit' => $totalDurasi,
            'items' => $items,
        ];
    }

    /**
     * Mengambil daftar baris jadwal aktif petugas pada tanggal tertentu.
     */
    public function ambilJadwalAktifTanggal(string $tanggal, ?int $kecualiIdReservasi = null, bool $kunciBaris = false): Collection
    {
        $kueri = DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->whereDate('reservasi.tgl_booking', $tanggal)
            ->whereIn('reservasi.status', Reservasi::STATUS_AKTIF_SLOT)
            ->whereNotNull('detail_reservasi.id_karyawan')
            ->select([
                'reservasi.id_reservasi',
                'reservasi.jam_booking',
                'reservasi.jam_selesai as jam_selesai_reservasi',
                'detail_reservasi.id_layanan',
                'detail_reservasi.id_karyawan',
                'detail_reservasi.jam_mulai',
                'detail_reservasi.jam_selesai',
                'detail_reservasi.jam_selesai_blokir',
            ]);

        if ($kecualiIdReservasi !== null) {
            $kueri->where('reservasi.id_reservasi', '!=', $kecualiIdReservasi);
        }

        if ($kunciBaris) {
            $kueri->lockForUpdate();
        }

        return $kueri->get();
    }

    /**
     * Memeriksa apakah dua rentang waktu [mulaiA, selesaiA) dan [mulaiB, selesaiB) saling tumpang tindih.
     */
    public function apakahRentangBentrok(string $mulaiA, string $selesaiA, string $mulaiB, string $selesaiB): bool
    {
        return $mulaiA < $selesaiB && $selesaiA > $mulaiB;
    }

    /**
     * Mencari daftar ID karyawan yang sedang sibuk pada rentang [jamMulai, jamSelesaiBlokir) di tanggal tersebut.
     *
     * @return array<int, int>
     */
    public function cariIdPetugasSibukPadaRentang(
        string $tanggal,
        string $jamMulai,
        string $jamSelesaiBlokir,
        ?int $kecualiIdReservasi = null,
        bool $kunciBaris = false
    ): array {
        $jadwalHariIni = $this->ambilJadwalAktifTanggal($tanggal, $kecualiIdReservasi, $kunciBaris);

        return $this->saringIdSibukDariKoleksi($jadwalHariIni, $jamMulai, $jamSelesaiBlokir);
    }

    /**
     * Menyaring ID karyawan yang bentrok dari koleksi jadwal harian yang sudah diambil.
     *
     * @return array<int, int>
     */
    public function saringIdSibukDariKoleksi(Collection $jadwalHariIni, string $jamMulai, string $jamSelesaiBlokir): array
    {
        $idSibuk = [];

        foreach ($jadwalHariIni as $baris) {
            [$mulaiBaris, $blokirBaris] = $this->normalisasiRentangBaris($baris);

            if ($this->apakahRentangBentrok($jamMulai, $jamSelesaiBlokir, $mulaiBaris, $blokirBaris)) {
                $idSibuk[] = (int) $baris->id_karyawan;
            }
        }

        return array_values(array_unique($idSibuk));
    }

    /**
     * Menentukan petugas untuk setiap layanan (baik dipilih manual oleh pelanggan maupun dipilihkan otomatis).
     *
     * @param  Collection<int, Layanan>  $layananDipilih
     * @param  array<int, array<string, mixed>>  $timelineItems
     * @param  array<int|string, mixed>  $petugasInput
     * @return array<int, Karyawan>
     */
    public function tentukanPetugasUntukTimeline(
        Collection $layananDipilih,
        array $timelineItems,
        array $petugasInput,
        string $tanggal,
        ?int $kecualiIdReservasi = null,
        bool $kunciBaris = false
    ): array {
        $semuaPetugasQuery = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->orderBy('id_karyawan');

        if ($kunciBaris) {
            $semuaPetugasQuery->lockForUpdate();
        }

        $semuaPetugas = $semuaPetugasQuery->get()->keyBy('id_karyawan');
        $jadwalHariIni = $this->ambilJadwalAktifTanggal($tanggal, $kecualiIdReservasi, $kunciBaris);
        $bebanHarian = $jadwalHariIni->countBy('id_karyawan');

        $hasilPetugas = [];

        foreach ($layananDipilih->sortBy('id_layanan') as $layanan) {
            $slot = $timelineItems[$layanan->id_layanan];
            $idSibuk = $this->saringIdSibukDariKoleksi(
                $jadwalHariIni,
                $slot['jam_mulai'],
                $slot['jam_selesai_blokir']
            );

            $pilihanManual = $petugasInput[$layanan->id_layanan] ?? null;

            if (! empty($pilihanManual) && $pilihanManual !== 'auto') {
                $idStaf = (int) $pilihanManual;
                $staf = $semuaPetugas->get($idStaf);

                if (! $staf || $staf->role_jabatan !== $layanan->jabatan_petugas) {
                    throw new DomainException(
                        "{$layanan->jabatan_petugas} yang dipilih untuk layanan {$layanan->nama_layanan} tidak valid atau sedang tidak aktif."
                    );
                }

                if (in_array($idStaf, $idSibuk, true)) {
                    throw new DomainException(
                        "Maaf, {$staf->nama_staff} sedang menangani pelanggan lain pada tanggal {$tanggal} pukul {$slot['jam_mulai']} - {$slot['jam_selesai_blokir']} WIB. Silakan pilih jam atau petugas lain."
                    );
                }

                $hasilPetugas[$layanan->id_layanan] = $staf;
                $bebanHarian[$idStaf] = ((int) ($bebanHarian[$idStaf] ?? 0)) + 1;

                continue;
            }

            // Pilih otomatis (auto-assign) petugas aktif yang kosong dengan beban paling ringan hari itu
            $kandidat = $semuaPetugas
                ->filter(fn (Karyawan $k) => $k->role_jabatan === $layanan->jabatan_petugas && ! in_array((int) $k->id_karyawan, $idSibuk, true))
                ->sortBy(fn (Karyawan $k) => (int) ($bebanHarian[$k->id_karyawan] ?? 0))
                ->first();

            if (! $kandidat) {
                throw new DomainException(
                    "Maaf, seluruh {$layanan->jabatan_petugas} penuh pada tanggal {$tanggal} pukul {$slot['jam_mulai']} - {$slot['jam_selesai']} WIB untuk layanan {$layanan->nama_layanan}. Silakan pilih jam lain."
                );
            }

            $hasilPetugas[$layanan->id_layanan] = $kandidat;
            $bebanHarian[$kandidat->id_karyawan] = ((int) ($bebanHarian[$kandidat->id_karyawan] ?? 0)) + 1;
        }

        return $hasilPetugas;
    }

    /**
     * Mengecek daftar jam operasional yang tersedia berdasarkan layanan yang dipilih pelanggan.
     *
     * @param  Collection<int, Layanan>  $layananDipilih
     * @return array<string, bool>
     */
    public function statusSlotJamOperasional(string $tanggal, Collection $layananDipilih): array
    {
        $jadwalHariIni = $this->ambilJadwalAktifTanggal($tanggal);
        $petugasPerJabatan = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->get()
            ->groupBy('role_jabatan');

        $statusJam = [];

        foreach (Reservasi::JAM_OPERASIONAL as $jam) {
            if ($layananDipilih->isEmpty()) {
                $idSibuk = $this->saringIdSibukDariKoleksi(
                    $jadwalHariIni,
                    $jam,
                    Carbon::createFromFormat('H:i', $jam)->addMinutes(75)->format('H:i')
                );
                $totalAktif = $petugasPerJabatan->flatten(1)->count();
                $statusJam[$jam] = count($idSibuk) < $totalAktif;

                continue;
            }

            try {
                $timeline = $this->susunTimelineLayanan($layananDipilih, $jam);
                $bisaSemua = true;

                foreach ($layananDipilih as $item) {
                    $slot = $timeline['items'][$item->id_layanan];
                    $idSibuk = $this->saringIdSibukDariKoleksi($jadwalHariIni, $slot['jam_mulai'], $slot['jam_selesai_blokir']);
                    $tersediaJabatan = $petugasPerJabatan
                        ->get($item->jabatan_petugas, collect())
                        ->reject(fn ($k) => in_array((int) $k->id_karyawan, $idSibuk, true));

                    if ($tersediaJabatan->isEmpty()) {
                        $bisaSemua = false;
                        break;
                    }
                }

                $statusJam[$jam] = $bisaSemua;
            } catch (DomainException) {
                $statusJam[$jam] = false;
            }
        }

        return $statusJam;
    }

    /**
     * Menormalisasi jam mulai & jam selesai blokir untuk satu baris detail_reservasi (termasuk data lama).
     *
     * @return array{0: string, 1: string}
     */
    private function normalisasiRentangBaris(object $baris): array
    {
        $mulai = substr((string) ($baris->jam_mulai ?: ($baris->jam_booking ?: '10:00')), 0, 5);

        if (! empty($baris->jam_selesai_blokir)) {
            return [$mulai, substr((string) $baris->jam_selesai_blokir, 0, 5)];
        }

        $selesaiDefault = Carbon::createFromFormat('H:i', $mulai)
            ->addMinutes(Layanan::DEFAULT_DURASI_MENIT + Layanan::DEFAULT_JEDA_MENIT)
            ->format('H:i');

        return [$mulai, $selesaiDefault];
    }
}
