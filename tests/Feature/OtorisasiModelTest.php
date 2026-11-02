<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\LogPerubahanOtorisasi;
use App\Models\PermintaanOtorisasi;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtorisasiModelTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $manajer;

    private Karyawan $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manajer = Karyawan::create([
            'nama_staff' => 'Manajer Utama',
            'role_jabatan' => Karyawan::JABATAN_MANAJER,
            'email' => 'manajer.uji@salon.test',
            'username' => 'manajer_uji',
            'password' => 'rahasia123',
            'persen_komisi' => 0,
            'aktif' => true,
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff' => 'Kasir Utama',
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'email' => 'kasir.uji@salon.test',
            'username' => 'kasir_uji',
            'password' => 'rahasia123',
            'persen_komisi' => 0,
            'aktif' => true,
        ]);
    }

    public function test_ambang_batas_approval_pos_dan_proteksi_bypass_penurunan_harga(): void
    {
        $this->assertFalse(PermintaanOtorisasi::perluPersetujuanManajer(120000, 100000));
        $this->assertFalse(PermintaanOtorisasi::perluPersetujuanManajer(149999, 149999));
        $this->assertTrue(PermintaanOtorisasi::perluPersetujuanManajer(150000, 150000));
        $this->assertTrue(PermintaanOtorisasi::perluPersetujuanManajer(120000, 165000));

        // Cegah bypass: tagihan awal Rp 250.000 diturunkan paksa ke Rp 50.000 tetap wajib approval Manajer
        $this->assertTrue(PermintaanOtorisasi::perluPersetujuanManajer(250000, 50000));
    }

    public function test_siklus_hidup_izin_satu_kali_pakai_terkunci_setelah_digunakan(): void
    {
        $izin = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_UBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_PRODUK,
            'id_target' => 10,
            'label_target' => 'Produk: Serum Wajah 30ml',
            'alasan_kasir' => 'Penyesuaian harga supplier terbaru.',
        ]);

        $this->assertSame(PermintaanOtorisasi::STATUS_MENUNGGU, $izin->status);
        $this->assertNotEmpty($izin->kode_referensi);

        $izin->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER, 'Silakan disesuaikan.');
        $this->assertSame(PermintaanOtorisasi::STATUS_DISETUJUI, $izin->status);
        $this->assertNotNull($izin->diputuskan_pada);

        $this->assertSame(
            1,
            PermintaanOtorisasi::izinKatalogSiapPakai(
                $this->kasir->id_karyawan,
                PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                PermintaanOtorisasi::AKSI_UBAH,
                10
            )->count()
        );

        // Pemakaian ke-1 berhasil dan langsung mengunci akses kembali
        $izin->tandaiDigunakan();
        $this->assertSame(PermintaanOtorisasi::STATUS_DIGUNAKAN, $izin->status);
        $this->assertNotNull($izin->digunakan_pada);

        $this->assertSame(
            0,
            PermintaanOtorisasi::izinKatalogSiapPakai(
                $this->kasir->id_karyawan,
                PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                PermintaanOtorisasi::AKSI_UBAH,
                10
            )->count()
        );

        // Percobaan memakai ulang izin yang sama wajib ditolak (One-Time Action)
        $this->expectException(DomainException::class);
        $izin->tandaiDigunakan();
    }

    public function test_log_perubahan_otorisasi_bersifat_permanen_tidak_bisa_diubah_atau_dihapus(): void
    {
        $log = LogPerubahanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI,
            'jenis_perubahan' => LogPerubahanOtorisasi::JENIS_OVERRIDE_HARGA_POS,
            'mekanisme_otorisasi' => LogPerubahanOtorisasi::MEKANISME_MANDIRI_BAWAH_AMBANG,
            'tipe_target' => PermintaanOtorisasi::TARGET_WALK_IN,
            'label_target' => 'Walk-In Pelanggan Umum',
            'alasan' => 'Potongan kompensasi antrean ramai di bawah Rp 150.000.',
            'nilai_awal' => 130000,
            'nilai_akhir' => 120000,
            'data_sebelum' => ['harga' => 130000],
            'data_sesudah' => ['harga' => 120000],
        ]);

        $this->assertDatabaseHas('log_perubahan_otorisasi', ['id_log' => $log->id_log]);

        try {
            $log->update(['nilai_akhir' => 50000]);
            $this->fail('Seharusnya gagal mengubah catatan log audit.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('permanen', $e->getMessage());
        }

        $this->expectException(DomainException::class);
        $log->delete();
    }
}
