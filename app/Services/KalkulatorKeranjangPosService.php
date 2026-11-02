<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\LogPerubahanOtorisasi;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use App\Models\Reservasi;
use DomainException;
use Illuminate\Support\Collection;

class KalkulatorKeranjangPosService
{
    public function __construct(
        private readonly KalkulatorHargaService $kalkulatorHarga
    ) {}

    /**
     * Mengevaluasi kondisi keranjang POS (sebelum vs sesudah perubahan) dari harga otoritatif server.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function evaluasiKeranjang(array $data): array
    {
        $reservasi = ! empty($data['id_reservasi'])
            ? Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])->findOrFail((int) $data['id_reservasi'])
            : null;

        $overrideLayanan = $this->normalisasiPetaHarga((array) ($data['harga_override_layanan'] ?? []));
        $overrideProduk = $this->normalisasiPetaHarga((array) ($data['harga_override_produk'] ?? []));

        [$layananAwal, $layananUsulan, $adaUbahLayanan, $adaOverrideLayanan] = $this->susunPerbandinganLayanan($reservasi, $data, $overrideLayanan);
        [$produkAwal, $produkUsulan, $adaOverrideProduk] = $this->susunPerbandinganProduk($reservasi, $data, $overrideProduk);

        if (empty($layananUsulan) && empty($produkUsulan)) {
            throw new DomainException('Keranjang transaksi tidak boleh kosong.');
        }

        $kodePromo = $this->tentukanKodePromo($reservasi, $data);
        $totalAwal = $this->hitungTotalSnapshot($reservasi, $layananAwal, $produkAwal, $reservasi?->kode_promo ?? $kodePromo);
        $totalAkhir = $this->hitungTotalSnapshot($reservasi, $layananUsulan, $produkUsulan, $kodePromo);

        $labelTarget = $reservasi
            ? "Reservasi #{$reservasi->id_reservasi} - ".($reservasi->pelanggan?->nama_cust ?? 'Pelanggan')
            : 'Walk-In - '.(trim((string) ($data['nama_pelanggan_walkin'] ?? '')) ?: 'Pelanggan Umum');

        $snapshotAwal = ['id_reservasi' => $reservasi?->id_reservasi, 'pelanggan_label' => $labelTarget, 'kode_promo' => $reservasi?->kode_promo ?? $kodePromo, 'layanan' => $layananAwal, 'produk' => $produkAwal];
        $snapshotUsulan = ['id_reservasi' => $reservasi?->id_reservasi, 'pelanggan_label' => $labelTarget, 'kode_promo' => $kodePromo, 'layanan' => $layananUsulan, 'produk' => $produkUsulan];

        $adaOverrideHarga = $adaOverrideLayanan || $adaOverrideProduk;
        $jenisPerubahan = $this->tentukanJenisPerubahan($adaUbahLayanan, $adaOverrideHarga);

        return [
            'reservasi' => $reservasi,
            'label_target' => $labelTarget,
            'ada_perubahan' => $jenisPerubahan !== null,
            'ada_ubah_layanan' => $adaUbahLayanan,
            'ada_override_harga' => $adaOverrideHarga,
            'jenis_perubahan' => $jenisPerubahan,
            'total_awal' => $totalAwal,
            'total_akhir' => $totalAkhir,
            'wajib_approval' => $jenisPerubahan !== null && PermintaanOtorisasi::perluPersetujuanManajer($totalAwal, $totalAkhir),
            'snapshot_awal' => $snapshotAwal,
            'snapshot_usulan' => $snapshotUsulan,
            'hash_usulan' => PermintaanOtorisasi::hitungHashKeranjang($snapshotUsulan),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $mentah
     * @return array<int, float>
     */
    private function normalisasiPetaHarga(array $mentah): array
    {
        $hasil = [];
        foreach ($mentah as $id => $nominal) {
            if ($nominal !== null && $nominal !== '') {
                $hasil[(int) $id] = max(0.0, round((float) $nominal, 0));
            }
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, float>  $petaOverride
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: bool, 3: bool}
     */
    private function susunPerbandinganLayanan(?Reservasi $reservasi, array $data, array $petaOverride): array
    {
        $petugasInput = (array) ($data['petugas_layanan'] ?? []);
        $idFallback = ! empty($data['id_petugas']) ? (int) $data['id_petugas'] : null;

        $idAwal = $reservasi ? $reservasi->layanan->pluck('id_layanan')->map(fn ($v) => (int) $v)->values()->all() : [];
        $layananDikirim = array_key_exists('layanan_ids', $data) || ! empty($data['keranjang_disinkronkan']);
        $idUsulan = $layananDikirim
            ? array_values(array_unique(array_map('intval', (array) ($data['layanan_ids'] ?? []))))
            : $idAwal;

        $katalogMap = Layanan::withTrashed()->whereIn('id_layanan', array_unique(array_merge($idAwal, $idUsulan)))->get()->keyBy('id_layanan');
        $pivotMap = $reservasi ? $reservasi->layanan->keyBy('id_layanan') : collect();

        $awal = $this->petakanDaftarLayanan($reservasi ? $idAwal : $idUsulan, $katalogMap, $pivotMap, [], $petugasInput, $idFallback);
        $usulan = $this->petakanDaftarLayanan($idUsulan, $katalogMap, $pivotMap, $petaOverride, $petugasInput, $idFallback);

        $idAwalUrut = $idAwal;
        $idUsulanUrut = $idUsulan;
        sort($idAwalUrut);
        sort($idUsulanUrut);

        $adaUbahLayanan = $reservasi !== null && $idAwalUrut !== $idUsulanUrut;
        $adaOverride = collect($usulan)->contains(fn (array $it) => $it['di_override'] === true);

        return [$awal, $usulan, $adaUbahLayanan, $adaOverride];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function petakanDaftarLayanan(array $daftarId, Collection $katalogMap, Collection $pivotMap, array $petaOverride, array $petugasInput, ?int $idFallback): array
    {
        $hasil = [];
        foreach ($daftarId as $idLayanan) {
            $master = $katalogMap->get($idLayanan);
            if (! $master) {
                continue;
            }
            $dariPivot = $pivotMap->get($idLayanan);
            $hargaNormal = $dariPivot && (float) $dariPivot->pivot->harga_saat_ini > 0
                ? round((float) $dariPivot->pivot->harga_saat_ini, 0)
                : round((float) $master->harga_layanan, 0);

            $hargaAktif = array_key_exists($idLayanan, $petaOverride) ? $petaOverride[$idLayanan] : $hargaNormal;
            $idPetugas = ! empty($petugasInput[$idLayanan])
                ? (int) $petugasInput[$idLayanan]
                : ($dariPivot?->pivot->id_karyawan ? (int) $dariPivot->pivot->id_karyawan : $idFallback);

            $hasil[] = [
                'id_layanan' => (int) $idLayanan,
                'nama' => $master->nama_layanan,
                'jabatan_petugas' => $master->jabatan_petugas,
                'id_petugas' => $idPetugas,
                'harga_normal' => $hargaNormal,
                'harga_aktif' => $hargaAktif,
                'di_override' => abs($hargaAktif - $hargaNormal) >= 1,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, float>  $petaOverride
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: bool}
     */
    private function susunPerbandinganProduk(?Reservasi $reservasi, array $data, array $petaOverride): array
    {
        $addonMap = $reservasi ? $reservasi->produkAddon->keyBy('id_produk') : collect();
        $produkDikirim = array_key_exists('produk_items', $data) || ! empty($data['keranjang_disinkronkan']);

        $qtyUsulanMap = [];
        if ($produkDikirim) {
            foreach ((array) ($data['produk_items'] ?? []) as $idProd => $qty) {
                if ((int) $qty > 0) {
                    $qtyUsulanMap[(int) $idProd] = (int) $qty;
                }
            }
        } else {
            $qtyUsulanMap = $addonMap->mapWithKeys(fn ($p, $id) => [(int) $id => (int) $p->pivot->jumlah])->all();
        }

        $qtyAwalMap = $reservasi
            ? $addonMap->mapWithKeys(fn ($p, $id) => [(int) $id => (int) $p->pivot->jumlah])->all()
            : $qtyUsulanMap;

        $katalogProduk = Produk::withTrashed()->whereIn('id_produk', array_unique(array_merge(array_keys($qtyAwalMap), array_keys($qtyUsulanMap))))->get()->keyBy('id_produk');

        $awal = $this->petakanDaftarProduk($qtyAwalMap, $katalogProduk, $addonMap, []);
        $usulan = $this->petakanDaftarProduk($qtyUsulanMap, $katalogProduk, $addonMap, $petaOverride);

        return [$awal, $usulan, collect($usulan)->contains(fn (array $it) => $it['di_override'] === true)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function petakanDaftarProduk(array $qtyMap, Collection $katalogProduk, Collection $addonMap, array $petaOverride): array
    {
        $hasil = [];
        ksort($qtyMap);
        foreach ($qtyMap as $idProduk => $qty) {
            $master = $katalogProduk->get($idProduk);
            if (! $master || $qty <= 0) {
                continue;
            }
            $addon = $addonMap->get($idProduk);
            $hargaNormal = $addon && (float) $addon->pivot->harga_saat_ini > 0
                ? round((float) $addon->pivot->harga_saat_ini, 0)
                : round((float) $master->harga_produk, 0);
            $hargaAktif = array_key_exists($idProduk, $petaOverride) ? $petaOverride[$idProduk] : $hargaNormal;

            $hasil[] = [
                'id_produk' => (int) $idProduk,
                'nama' => $master->nama_produk,
                'qty' => (int) $qty,
                'harga_normal' => $hargaNormal,
                'harga_aktif' => $hargaAktif,
                'di_override' => abs($hargaAktif - $hargaNormal) >= 1,
            ];
        }

        return $hasil;
    }

    private function tentukanKodePromo(?Reservasi $reservasi, array $data): ?string
    {
        if (! empty($data['keranjang_disinkronkan']) || array_key_exists('kode_promo', $data)) {
            $input = strtoupper(trim((string) ($data['kode_promo'] ?? '')));

            return $input !== '' ? $input : null;
        }

        return $reservasi?->kode_promo;
    }

    private function hitungTotalSnapshot(?Reservasi $reservasi, array $layanan, array $produk, ?string $kodePromo): float
    {
        $subLayanan = (float) collect($layanan)->sum(fn (array $i) => (float) $i['harga_aktif']);
        $subProduk = (float) collect($produk)->sum(fn (array $i) => (float) $i['harga_aktif'] * (int) $i['qty']);
        $subtotal = $subLayanan + $subProduk;

        if (! $kodePromo) {
            return $this->kalkulatorHarga->hitungTotalAkhir($subLayanan, $subProduk, 0.0);
        }

        if ($reservasi && $reservasi->kode_promo && strtoupper($reservasi->kode_promo) === strtoupper($kodePromo)) {
            $potongan = $reservasi->tipe_diskon_promo === 'Persentase'
                ? round($subtotal * (min(100.0, max(0.0, (float) $reservasi->nilai_diskon_promo)) / 100), 0)
                : (float) ($reservasi->nilai_diskon_promo > 0 ? $reservasi->nilai_diskon_promo : $reservasi->nominal_diskon);

            return $this->kalkulatorHarga->hitungTotalAkhir($subLayanan, $subProduk, min($subtotal, $potongan));
        }

        try {
            $info = $this->kalkulatorHarga->validasiDanHitungPromo($kodePromo, $subtotal);

            return $this->kalkulatorHarga->hitungTotalAkhir($subLayanan, $subProduk, $info['nominal_diskon']);
        } catch (DomainException) {
            return $this->kalkulatorHarga->hitungTotalAkhir($subLayanan, $subProduk, 0.0);
        }
    }

    private function tentukanJenisPerubahan(bool $adaUbahLayanan, bool $adaOverrideHarga): ?string
    {
        if ($adaUbahLayanan && $adaOverrideHarga) {
            return LogPerubahanOtorisasi::JENIS_GABUNGAN_POS;
        }
        if ($adaUbahLayanan) {
            return LogPerubahanOtorisasi::JENIS_UBAH_LAYANAN_POS;
        }

        return $adaOverrideHarga ? LogPerubahanOtorisasi::JENIS_OVERRIDE_HARGA_POS : null;
    }
}
