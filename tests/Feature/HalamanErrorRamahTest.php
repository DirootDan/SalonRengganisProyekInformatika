<?php

namespace Tests\Feature;

use Tests\TestCase;

class HalamanErrorRamahTest extends TestCase
{
    public function test_url_salah_menampilkan_halaman_404_ramah_berbahasa_indonesia(): void
    {
        $this->get('/alamat-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_halaman_error_lain_dirender_dengan_pesan_dan_tombol_yang_tepat(): void
    {
        $this->assertStringContainsString('Sesi Anda Telah Berakhir', view('errors.419')->render());
        $this->assertStringContainsString('Masuk Kembali', view('errors.419')->render());
        $this->assertStringContainsString('Muat Ulang Halaman', view('errors.500')->render());
        $this->assertStringContainsString('Akses Tidak Diizinkan', view('errors.403')->render());
        $this->assertStringContainsString('Terlalu Banyak Percobaan', view('errors.429')->render());
        $this->assertStringContainsString('Sedang Dalam Pemeliharaan', view('errors.503')->render());
    }
}
