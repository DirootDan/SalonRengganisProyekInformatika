<?php

namespace Tests\Feature;

use App\Models\Layanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_tanpa_login_melihat_landing_page_beserta_layanan_dan_harga(): void
    {
        Layanan::create([
            'nama_layanan' => 'Hair Spa Premium',
            'kategori' => 'Perawatan Rambut',
            'jabatan_petugas' => 'Stylist',
            'durasi_menit' => 60,
            'jeda_menit' => 15,
            'harga_layanan' => 150000,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Booking Jadwal Sekarang')
            ->assertSee('Hair Spa Premium')
            ->assertSee('150.000')
            ->assertSee(route('register'), false)
            ->assertSee(route('login'), false);
    }

    public function test_landing_page_tetap_tampil_saat_katalog_kosong(): void
    {
        $this->get('/')->assertOk()->assertSee('Daftar layanan akan segera tersedia.');
    }
}
