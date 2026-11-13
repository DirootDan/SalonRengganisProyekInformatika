<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BantuanDanAntiKlikGandaTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_publik_menampilkan_tombol_whatsapp_dengan_nomor_salon(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('https://wa.me/6285198111050', false)
            ->assertSee('Tanya via WhatsApp')
            ->assertSee(rawurlencode('Halo Salon Rengganis, saya ingin bertanya.'), false);
    }

    public function test_nomor_whatsapp_bisa_diganti_lewat_konfigurasi(): void
    {
        config(['salon.whatsapp' => '6281200001111']);

        $this->get('/')->assertSee('https://wa.me/6281200001111', false);
    }

    public function test_semua_layout_yang_punya_form_memasang_komponen_anti_klik_ganda(): void
    {
        $layoutBerform = [
            'Auth/registerpage', 'Auth/lupapassword', 'Auth/resetpassword',
            'Karyawan/Kasir/layout', 'Karyawan/Petugas/layout',
            'Manajer/layout', 'Pelanggan/layout',
        ];

        foreach ($layoutBerform as $layout) {
            $isi = file_get_contents(resource_path("views/{$layout}.blade.php"));
            $this->assertStringContainsString('<x-anti-klik-ganda />', $isi, "Layout {$layout} belum memasang anti klik ganda.");
        }

        $this->assertFileExists(resource_path('js/components/anti-klik-ganda.js'));
    }
}
