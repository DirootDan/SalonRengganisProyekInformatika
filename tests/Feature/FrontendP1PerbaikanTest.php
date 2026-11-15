<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontendP1PerbaikanTest extends TestCase
{
    public function test_halaman_error_500_bebas_javascript_uri(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
        $this->assertStringContainsString('Muat Ulang Halaman', $html);
        $this->assertMatchesRegularExpression('/<a[^>]+href="[^"]+"/i', $html);
    }

    public function test_tautan_target_blank_kasir_laporan_aman(): void
    {
        $isi = File::get(resource_path('views/Karyawan/Kasir/laporan.blade.php'));

        $this->assertStringContainsString('target="_blank"', $isi);
        $this->assertStringContainsString('rel="noopener noreferrer"', $isi);
    }

    public function test_halaman_autentikasi_memiliki_autocomplete_standar(): void
    {
        $login = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringContainsString('autocomplete="username"', $login);
        $this->assertStringContainsString('autocomplete="current-password"', $login);

        $register = $this->get(route('register'))->assertOk()->getContent();
        $this->assertStringContainsString('autocomplete="name"', $register);
        $this->assertStringContainsString('autocomplete="tel"', $register);
        $this->assertStringContainsString('autocomplete="email"', $register);
        $this->assertStringContainsString('autocomplete="new-password"', $register);

        $lupa = $this->get(route('password.lupa'))->assertOk()->getContent();
        $this->assertStringContainsString('autocomplete="email"', $lupa);
    }

    public function test_layout_staf_menyertakan_toast_live_region(): void
    {
        $manajerLayout = File::get(resource_path('views/Manajer/layout.blade.php'));
        $kasirLayout = File::get(resource_path('views/Karyawan/Kasir/layout.blade.php'));

        foreach ([$manajerLayout, $kasirLayout] as $isi) {
            $this->assertStringContainsString('id="toastNotif"', $isi);
            $this->assertStringContainsString('role="status"', $isi);
            $this->assertStringContainsString('aria-live="polite"', $isi);
        }
    }

    public function test_semua_dialog_modal_memiliki_atribut_aria_lengkap(): void
    {
        $berkasModal = [
            'views/Manajer/karyawan.blade.php',
            'views/Manajer/layanan.blade.php',
            'views/Manajer/metode-pembayaran.blade.php',
            'views/Manajer/produk.blade.php',
            'views/Manajer/promo.blade.php',
            'views/Karyawan/Kasir/dashboard.blade.php',
            'views/Karyawan/Kasir/layanan.blade.php',
            'views/Karyawan/Kasir/permintaan-booking.blade.php',
            'views/Karyawan/Kasir/produk.blade.php',
            'views/Karyawan/Kasir/partials/modal-akses-katalog.blade.php',
            'views/Karyawan/Kasir/partials/modal-direct-override-pos.blade.php',
            'views/Karyawan/Kasir/partials/modal-hold-pos.blade.php',
        ];

        foreach ($berkasModal as $jalur) {
            $isi = File::get(resource_path($jalur));
            $this->assertStringContainsString('role="dialog"', $isi, $jalur);
            $this->assertStringContainsString('aria-modal="true"', $isi, $jalur);
            $this->assertStringContainsString('aria-labelledby="', $isi, $jalur);
            $this->assertStringContainsString('aria-label="Tutup"', $isi, $jalur);
        }
    }

    public function test_css_tidak_mengandung_outline_none_atau_warna_rendah_kontras_terlarang(): void
    {
        $warnaTerlarang = [
            '#9ca3af',
            '#94a3b8',
            '#10b981',
            '#16a34a',
            '#059669',
            '#d97706',
            '#ef4444',
            '#ec4899',
            '#f472b6',
        ];

        foreach (File::allFiles(resource_path('css')) as $berkas) {
            $teks = strtolower(preg_replace('/\/\*.*?\*\//s', '', File::get($berkas->getPathname())) ?? '');
            $this->assertStringNotContainsString('outline: none', $teks, $berkas->getRelativePathname());
            $this->assertStringNotContainsString('outline:none', $teks, $berkas->getRelativePathname());

            foreach ($warnaTerlarang as $hex) {
                if ($hex === '#f472b6' && str_replace('\\', '/', $berkas->getRelativePathname()) === 'global/variables.css') {
                    continue;
                }
                $this->assertStringNotContainsString($hex, $teks, $berkas->getRelativePathname());
            }
        }
    }

    public function test_modul_staf_ui_diterapkan_dan_listener_laporan_mati_dihapus(): void
    {
        $stafUi = File::get(resource_path('js/components/staf-ui.js'));
        foreach ([
            'tampilkanToastGlobal',
            'bukaModalAksesibel',
            'tutupModalAksesibel',
            'ikatModalAksesibel',
            'perbaruiEmptyStateTabel',
            'perbaruiEmptyStateGrid',
        ] as $simbol) {
            $this->assertStringContainsString($simbol, $stafUi);
        }

        $laporanManajer = File::get(resource_path('js/Manajer/laporan.js'));
        $laporanKasir = File::get(resource_path('js/Karyawan/Kasir/laporan.js'));
        $this->assertStringNotContainsString('btnTerapkanFilter', $laporanManajer);
        $this->assertStringNotContainsString('btnEksporCsv', $laporanManajer);
        $this->assertStringNotContainsString('btnTerapkanFilter', $laporanKasir);
    }
}
