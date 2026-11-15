<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontendP2PerbaikanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_auth_dan_struk_cetak_memiliki_landmark_main_h1_dan_viewport(): void
    {
        $halamanAuth = [
            $this->get(route('login'))->assertOk()->getContent(),
            $this->get(route('register'))->assertOk()->getContent(),
            $this->get(route('password.lupa'))->assertOk()->getContent(),
            view('Auth.resetpassword', [
                'token' => 'token-uji',
                'email' => 'uji@salonrengganis.test',
            ])->render(),
        ];

        foreach ($halamanAuth as $html) {
            $this->assertStringContainsString('<main class="login-card">', $html);
            $this->assertMatchesRegularExpression('/<h1>[^<]+<\/h1>/', $html);
        }

        $strukCetak = File::get(resource_path('views/Karyawan/Kasir/struk-cetak.blade.php'));
        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1.0">', $strukCetak);
        $this->assertStringContainsString('<main class="struk-main">', $strukCetak);
        $this->assertStringContainsString('<h1 class="struk-nama">', $strukCetak);
    }

    public function test_beranda_publik_memuat_drawer_menu_js_dan_scroll_margin(): void
    {
        $html = $this->get(route('beranda.publik'))->assertOk()->getContent();
        $this->assertStringContainsString('id="btnMenuPublik"', $html);
        $this->assertStringContainsString('aria-controls="pbMenuNav"', $html);

        $viewPublik = File::get(resource_path('views/Publik/beranda.blade.php'));
        $this->assertStringContainsString('resources/js/Publik/beranda.js', $viewPublik);

        $viteConfig = File::get(base_path('vite.config.js'));
        $this->assertStringContainsString('resources/js/Publik/beranda.js', $viteConfig);

        $cssPublik = File::get(resource_path('css/pages/publik/beranda.css'));
        $this->assertStringContainsString('scroll-margin-top: 84px', $cssPublik);
    }

    public function test_css_aksesibilitas_reduced_motion_forced_colors_dan_layout_p2_tersedia(): void
    {
        $aksesibilitasCss = File::get(resource_path('css/base/reset.css'));
        $this->assertStringContainsString(':focus-visible', $aksesibilitasCss);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $aksesibilitasCss);
        $this->assertStringContainsString('@media (forced-colors: active)', $aksesibilitasCss);

        $formsCss = File::get(resource_path('css/components/forms.css'));
        $this->assertStringContainsString('.form-dua-kolom', $formsCss);

        $stafDashCss = File::get(resource_path('css/pages/staf/dashboard.css'));
        $this->assertStringContainsString('overflow-y: visible', $stafDashCss);
    }

    public function test_layout_sidebar_dan_panel_tab_memiliki_atribut_aria_lengkap(): void
    {
        foreach (['views/Manajer/layout.blade.php', 'views/Karyawan/Kasir/layout.blade.php'] as $jalurLayout) {
            $isi = File::get(resource_path($jalurLayout));
            $this->assertMatchesRegularExpression('/<aside[^>]+aria-label="/', $isi, $jalurLayout);
            $this->assertMatchesRegularExpression('/<nav[^>]+aria-label="/', $isi, $jalurLayout);
        }

        $persetujuan = File::get(resource_path('views/Manajer/persetujuan.blade.php'));
        $this->assertStringContainsString('aria-controls="panelPending"', $persetujuan);
        $this->assertStringContainsString('aria-controls="panelAudit"', $persetujuan);
        $this->assertStringContainsString('role="tabpanel" aria-labelledby="tabPending"', $persetujuan);
        $this->assertStringContainsString('role="tabpanel" aria-labelledby="tabAudit"', $persetujuan);

        $profil = File::get(resource_path('views/Pelanggan/profil.blade.php'));
        $this->assertStringContainsString('role="tablist"', $profil);
        $this->assertStringContainsString('aria-controls="tab-biodata"', $profil);
        $this->assertStringContainsString('aria-controls="tab-password"', $profil);
        $this->assertStringContainsString('role="tabpanel" aria-labelledby="tabBtnBiodata"', $profil);
        $this->assertStringContainsString('role="tabpanel" aria-labelledby="tabBtnPassword"', $profil);

        $riwayat = File::get(resource_path('views/Pelanggan/riwayat.blade.php'));
        $this->assertStringContainsString('role="tablist"', $riwayat);
        $this->assertStringContainsString('role="tab"', $riwayat);

        $ariaJs = File::get(resource_path('js/components/aria-widget.js'));
        $this->assertStringContainsString('sinkronTabAria', $ariaJs);
        $this->assertStringContainsString('ikatNavigasiTabAria', $ariaJs);
        $this->assertStringContainsString('sinkronRadioAria', $ariaJs);
        $this->assertStringContainsString('ikatNavigasiRadioAria', $ariaJs);
    }

    public function test_hierarki_heading_halaman_utama_dan_panel_tidak_melompat_level(): void
    {
        $halamanTanpaH3 = [
            'views/Karyawan/Petugas/jadwal.blade.php',
            'views/Pelanggan/beranda.blade.php',
            'views/Pelanggan/layanan.blade.php',
            'views/Pelanggan/profil.blade.php',
            'views/Pelanggan/riwayat.blade.php',
            'views/Manajer/karyawan.blade.php',
            'views/Manajer/kebijakan.blade.php',
            'views/Manajer/laporan.blade.php',
            'views/Manajer/layanan.blade.php',
            'views/Manajer/metode-pembayaran.blade.php',
            'views/Manajer/produk.blade.php',
            'views/Manajer/promo.blade.php',
            'views/Manajer/struk.blade.php',
            'views/Karyawan/Kasir/antrean.blade.php',
            'views/Karyawan/Kasir/laporan.blade.php',
            'views/Karyawan/Kasir/layanan.blade.php',
            'views/Karyawan/Kasir/metode-pembayaran.blade.php',
            'views/Karyawan/Kasir/permintaan-booking.blade.php',
            'views/Karyawan/Kasir/produk.blade.php',
            'views/Karyawan/Kasir/struk.blade.php',
        ];

        foreach ($halamanTanpaH3 as $jalur) {
            $isi = File::get(resource_path($jalur));
            $this->assertStringNotContainsString('<h3', $isi, $jalur);
            $this->assertStringNotContainsString('<h4', $isi, $jalur);
        }

        $persetujuan = File::get(resource_path('views/Manajer/persetujuan.blade.php'));
        $this->assertStringContainsString('<h2', $persetujuan);
        $this->assertStringNotContainsString('<h4', $persetujuan);

        $kasirDash = File::get(resource_path('views/Karyawan/Kasir/dashboard.blade.php'));
        $this->assertStringContainsString('<h2', $kasirDash);
        $this->assertStringNotContainsString('<h4', $kasirDash);
    }
}
