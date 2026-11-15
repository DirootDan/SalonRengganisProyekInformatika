<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontendP3PerbaikanTest extends TestCase
{
    use RefreshDatabase;

    public function test_aset_favicon_dan_og_image_valid_serta_dimensi_standar(): void
    {
        $icoPath = public_path('favicon.ico');
        $svgPath = public_path('favicon.svg');
        $ogPath = public_path('images/og-salon.jpg');

        $this->assertFileExists($icoPath);
        $this->assertGreaterThan(0, filesize($icoPath));
        $icoHeader = substr(File::get($icoPath), 0, 4);
        $this->assertSame("\x00\x00\x01\x00", $icoHeader);

        $this->assertFileExists($svgPath);
        $svg = File::get($svgPath);
        $this->assertStringNotContainsString('<text', $svg);
        $this->assertStringContainsString('#be185d', $svg);

        $this->assertFileExists($ogPath);
        $dimensi = getimagesize($ogPath);
        $this->assertIsArray($dimensi);
        $this->assertSame(1200, $dimensi[0]);
        $this->assertSame(630, $dimensi[1]);

        $meta = File::get(resource_path('views/components/meta-situs.blade.php'));
        $this->assertStringContainsString('favicon.svg', $meta);
        $this->assertStringContainsString('favicon.ico', $meta);
        $this->assertStringContainsString('content="#be185d"', $meta);
        $this->assertStringContainsString('property="og:image:width" content="1200"', $meta);
        $this->assertStringContainsString('property="og:image:height" content="630"', $meta);
    }

    public function test_semua_head_menyertakan_preconnect_google_fonts_dan_bebas_import_eksternal_css(): void
    {
        $daftarHead = [
            'views/Auth/loginpage.blade.php',
            'views/Auth/registerpage.blade.php',
            'views/Auth/lupapassword.blade.php',
            'views/Auth/resetpassword.blade.php',
            'views/Pelanggan/layout.blade.php',
            'views/Manajer/layout.blade.php',
            'views/Karyawan/Kasir/layout.blade.php',
            'views/Karyawan/Petugas/layout.blade.php',
            'views/Karyawan/Kasir/struk-cetak.blade.php',
            'views/errors/layout.blade.php',
            'views/Publik/beranda.blade.php',
        ];

        foreach ($daftarHead as $jalur) {
            $isi = File::get(resource_path($jalur));
            $this->assertStringContainsString('rel="preconnect" href="https://fonts.googleapis.com"', $isi, $jalur);
            $this->assertStringContainsString('rel="preconnect" href="https://fonts.gstatic.com" crossorigin', $isi, $jalur);
        }

        $strukCetakCss = File::get(resource_path('css/Karyawan/Kasir/struk-cetak.css'));
        $this->assertStringNotContainsString('@import', $strukCetakCss);
        $this->assertStringNotContainsString('fonts.googleapis.com', $strukCetakCss);
    }

    public function test_css_pelanggan_terpisah_per_halaman_dan_dead_code_telah_dihapus(): void
    {
        $pelangganLayout = File::get(resource_path('views/Pelanggan/layout.blade.php'));
        $this->assertStringContainsString('resources/css/app.css', $pelangganLayout);
        $this->assertStringNotContainsString('resources/css/pages/pelanggan/booking.css', $pelangganLayout);
        $this->assertStringNotContainsString('resources/css/pages/pelanggan/akun.css', $pelangganLayout);

        $pelangganLayanan = File::get(resource_path('views/Pelanggan/layanan.blade.php'));
        $this->assertStringContainsString("@push('styles')", $pelangganLayanan);
        $this->assertStringContainsString('resources/css/pages/pelanggan/booking.css', $pelangganLayanan);

        $pelangganRiwayat = File::get(resource_path('views/Pelanggan/riwayat.blade.php'));
        $this->assertStringContainsString("@push('styles')", $pelangganRiwayat);
        $this->assertStringContainsString('resources/css/pages/pelanggan/akun.css', $pelangganRiwayat);

        $kasirDashCss = File::get(resource_path('css/Karyawan/Kasir/dashboard.css'));
        $this->assertStringNotContainsString('.stat-kasir-', $kasirDashCss);
        $this->assertStringNotContainsString('.panel-antrean-ringkas', $kasirDashCss);
        $this->assertStringNotContainsString('.btn-buka-modal-antrean', $kasirDashCss);

        $this->assertFileDoesNotExist(resource_path('views/welcome.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/Karyawan/PlaceHolder.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/Karyawan/beranda.blade.php'));
        $this->assertFileDoesNotExist(resource_path('asset/loginpage2.jpg'));
    }

    public function test_tabular_nums_dan_deduplikasi_modul_js_tersedia(): void
    {
        $berkasTabular = [
            'css/global/staf-common.css',
            'css/Manajer/dashboard.css',
            'css/Manajer/laporan.css',
            'css/Karyawan/Kasir/dashboard.css',
            'css/Karyawan/Kasir/struk-cetak.css',
            'css/Karyawan/Petugas/dashboard.css',
            'css/Publik/beranda.css',
            'css/Pelanggan/dashboard.css',
            'css/Pelanggan/layanan.css',
            'css/Pelanggan/booking.css',
            'css/Pelanggan/riwayat.css',
        ];

        foreach ($berkasTabular as $jalurCss) {
            $css = File::get(resource_path($jalurCss));
            $this->assertStringContainsString('font-variant-numeric: tabular-nums', $css, $jalurCss);
        }

        $this->assertFileExists(resource_path('js/components/struk-preview.js'));
        $this->assertFileExists(resource_path('js/components/ikon-svg.js'));
        $this->assertStringContainsString('struk-preview', File::get(resource_path('js/Manajer/struk.js')));
        $this->assertStringContainsString('struk-preview', File::get(resource_path('js/Karyawan/Kasir/struk.js')));
        $this->assertStringContainsString('ikatSidebarStaf', File::get(resource_path('js/Manajer/layout.js')));
        $this->assertStringContainsString('ikatSidebarStaf', File::get(resource_path('js/Karyawan/Kasir/layout.js')));
    }

    public function test_auth_cta_title_case_ikon_svg_valid_dan_bebas_emoji_serta_em_dash(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk Sekarang');
        $this->get(route('register'))->assertOk()->assertSee('Daftar Sekarang');
        $this->get(route('password.lupa'))->assertOk()->assertSee('Kirim Link Reset');

        $resetHtml = view('Auth.resetpassword', [
            'token' => 'token-uji',
            'email' => 'uji@salonrengganis.test',
        ])->render();
        $this->assertStringContainsString('Ganti Password', $resetHtml);

        $ikonSrc = File::get(resource_path('views/components/ikon.blade.php'));
        preg_match_all("/@case\('([^']+)'\)/", $ikonSrc, $mKasus);
        $kasusIkon = array_flip($mKasus[1]);

        $polaEmoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}✅⚠️⏳🗑️✏️🔍👁🖨🔒🔓💳🧾🧴💇📊📈📩🖥️🛡️🔑🚪💬📅💰🎉✨🌸⚡✋🛠️🧹⏸️📜📥🔄📋🤖📷🔔❌✓✕]/u';

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $relatif = str_replace('\\', '/', $berkas->getRelativePathname());
            $isi = File::get($berkas->getPathname());

            $this->assertStringNotContainsString("\u{2014}", $isi, $relatif);

            preg_match_all('/<x-ikon\s+name="([^"]+)"/', $isi, $mPakai);
            foreach ($mPakai[1] as $namaIkon) {
                $this->assertArrayHasKey($namaIkon, $kasusIkon, "Ikon tidak terdaftar di {$relatif}: {$namaIkon}");
            }

            if ($relatif !== 'components/ikon.blade.php') {
                $this->assertDoesNotMatchRegularExpression($polaEmoji, $isi, "Masih ada emoji UI di {$relatif}");
            }
        }

        foreach (File::allFiles(resource_path('js')) as $berkasJs) {
            $relatif = str_replace('\\', '/', $berkasJs->getRelativePathname());
            $isi = File::get($berkasJs->getPathname());

            $this->assertStringNotContainsString("\u{2014}", $isi, $relatif);
            $this->assertDoesNotMatchRegularExpression($polaEmoji, $isi, "Masih ada emoji UI di {$relatif}");
        }
    }
}
