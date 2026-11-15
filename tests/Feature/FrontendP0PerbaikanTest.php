<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\Reservasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendP0PerbaikanTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $kasir;

    private Karyawan $manajer;

    private Pelanggan $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manajer = Karyawan::create([
            'nama_staff' => 'Manajer Uji',
            'email' => 'manajer-p0@test.com',
            'role_jabatan' => Karyawan::JABATAN_MANAJER,
            'persen_komisi' => 0,
            'aktif' => true,
            'password' => 'password123',
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff' => 'Kasir Uji',
            'email' => 'kasir-p0@test.com',
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'persen_komisi' => 0,
            'aktif' => true,
            'password' => 'password123',
        ]);

        $this->pelanggan = Pelanggan::create([
            'nama_cust' => 'Sari Pelanggan',
            'no_telp' => '081234567890',
            'email' => 'sari-p0@test.com',
            'password' => 'password123',
        ]);
    }

    public function test_halaman_metode_pembayaran_kasir_bersifat_baca_saja_tanpa_sakelar_palsu(): void
    {
        MetodePembayaran::create([
            'nama_metode' => 'Tunai',
            'kategori' => MetodePembayaran::KATEGORI_TUNAI,
            'nomor_rekening' => 'Laci Kasir',
            'atas_nama' => 'Salon Rengganis',
            'ikon' => '💵',
            'aktif' => true,
        ]);

        MetodePembayaran::create([
            'nama_metode' => 'QRIS Offline',
            'kategori' => MetodePembayaran::KATEGORI_QRIS,
            'nomor_rekening' => 'NMID-001',
            'atas_nama' => 'Salon Rengganis',
            'ikon' => '📱',
            'aktif' => false,
        ]);

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.metode'))
            ->assertOk()
            ->assertSee('baca-saja bagi Kasir')
            ->assertSee('lencana-metode-kasir', false)
            ->assertSee('Dinonaktifkan oleh Manajer')
            ->assertDontSee('toggle-metode', false)
            ->assertDontSee('sakelar', false);
    }

    public function test_paginasi_kasir_menggunakan_template_kustom_tanpa_svg_tailwind(): void
    {
        for ($i = 1; $i <= 26; $i++) {
            Reservasi::create([
                'id_pelanggan' => $this->pelanggan->id_pelanggan,
                'tgl_booking' => now()->addDays(1)->toDateString(),
                'jam_booking' => '10:00',
                'total_durasi_menit' => 45,
                'status' => Reservasi::STATUS_MENUNGGU,
            ]);
        }

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.permintaan'))
            ->assertOk()
            ->assertSee('class="paginasi-nav"', false)
            ->assertSee('aria-label="Navigasi Halaman"', false)
            ->assertDontSee('class="w-5 h-5"', false);

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.laporan'))
            ->assertOk()
            ->assertSee('class="paginasi-nav"', false)
            ->assertSee('aria-label="Navigasi Halaman"', false)
            ->assertDontSee('class="w-5 h-5"', false);
    }

    public function test_tabel_responsif_dan_layout_pelanggan_serta_design_md_valid(): void
    {
        $this->assertFileExists(base_path('DESIGN.md'));

        $this->actingAs($this->manajer, 'karyawan')
            ->get(route('manajer.persetujuan'))
            ->assertOk()
            ->assertSee('class="tabel-responsif"', false)
            ->assertDontSee('tabel-Responsif', false);

        $this->actingAs($this->pelanggan, 'pelanggan')
            ->get(route('pelanggan.beranda'))
            ->assertOk()
            ->assertSee('Halo, Sari Pelanggan');
    }
}
