<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaSitusTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_publik_punya_favicon_preview_sosmed_dan_boleh_diindeks(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('images/og-salon.jpg', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('index, follow', false);
    }

    public function test_halaman_login_dan_error_punya_favicon_tetapi_tidak_diindeks(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee('noindex, nofollow', false)
            ->assertSee('Masuk - Salon Rengganis');

        $this->get('/alamat-tidak-ada')
            ->assertNotFound()
            ->assertSee('rel="icon"', false)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_berkas_favicon_dan_gambar_preview_tersedia_di_public(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('images/og-salon.jpg'));
    }
}
