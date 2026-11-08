<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_tanpa_login_melihat_landing_page_beserta_layanan_dan_harga(): void
    {
        $layanan = Layanan::create([
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
            ->assertSee(route('pelanggan.booking', ['layanan' => $layanan->id_layanan]), false)
            ->assertSee(route('register'), false)
            ->assertSee(route('login'), false);
    }

    public function test_landing_page_tetap_tampil_saat_katalog_kosong(): void
    {
        $this->get('/')->assertOk()->assertSee('Daftar layanan akan segera tersedia.');
    }

    public function test_klik_pilih_layanan_otomatis_mencentang_layanan_di_halaman_booking(): void
    {
        $pelanggan = Pelanggan::create([
            'nama_cust' => 'Siti Pelanggan',
            'email' => 'siti@example.com',
            'password' => 'password123',
            'no_telp' => '081234567890',
        ]);

        $layanan = Layanan::create([
            'nama_layanan' => 'Creambath Ginseng',
            'kategori' => 'Perawatan Rambut',
            'jabatan_petugas' => 'Therapist',
            'durasi_menit' => 45,
            'jeda_menit' => 10,
            'harga_layanan' => 120000,
        ]);

        $this->actingAs($pelanggan, 'pelanggan')
            ->get(route('pelanggan.layanan'))
            ->assertOk()
            ->assertSee('Perawatan Rambut')
            ->assertSee('45 menit')
            ->assertSee('Ditangani Therapist')
            ->assertSee(route('pelanggan.booking', ['layanan' => $layanan->id_layanan]), false);

        $this->actingAs($pelanggan, 'pelanggan')
            ->get(route('pelanggan.booking', ['layanan' => $layanan->id_layanan]))
            ->assertOk()
            ->assertSee('opsi-blok terpilih', false)
            ->assertSee('data-id-layanan="'.$layanan->id_layanan.'"', false)
            ->assertSee('stepper-booking', false)
            ->assertSee('tabLayananTerpilih', false)
            ->assertSee('barChipTerpilih', false)
            ->assertSee('btnToggleMenitKustom', false)
            ->assertSee('barBawahMobile', false);
    }

    public function test_registrasi_pelanggan_baru_langsung_diarahkan_ke_halaman_booking(): void
    {
        $layanan = Layanan::create([
            'nama_layanan' => 'Potong Rambut Wanita',
            'kategori' => 'Potong & Styling',
            'jabatan_petugas' => 'Stylist',
            'durasi_menit' => 30,
            'jeda_menit' => 10,
            'harga_layanan' => 75000,
        ]);

        $this->get(route('register', ['layanan' => $layanan->id_layanan]))->assertOk();

        $this->post('/register', [
            'nama_cust' => 'Rina Baru',
            'email' => 'rina@example.com',
            'no_telp' => '081299998888',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('pelanggan.booking', ['layanan' => $layanan->id_layanan]));
    }

    public function test_halaman_auth_memiliki_tombol_kembali_ke_beranda_publik(): void
    {
        $urlBeranda = route('beranda.publik');

        $this->get(route('login'))->assertOk()->assertSee($urlBeranda, false);
        $this->get(route('register'))->assertOk()->assertSee($urlBeranda, false);
        $this->get(route('password.lupa'))->assertOk()->assertSee($urlBeranda, false);
    }

    public function test_pengunjung_belum_login_klik_pilih_layanan_diarahkan_ke_login_bukan_404(): void
    {
        $layanan = Layanan::create([
            'nama_layanan' => 'Facial Glowing',
            'kategori' => 'Perawatan Wajah',
            'jabatan_petugas' => 'Therapist',
            'durasi_menit' => 60,
            'jeda_menit' => 15,
            'harga_layanan' => 180000,
        ]);

        Pelanggan::create([
            'nama_cust' => 'Dewi Pelanggan',
            'email' => 'dewi@example.com',
            'password' => 'rahasia123',
            'no_telp' => '081277776666',
        ]);

        $urlBookingLayanan = route('pelanggan.booking', ['layanan' => $layanan->id_layanan]);

        $this->get($urlBookingLayanan)
            ->assertRedirect(route('login'));

        $this->post(route('login.proses'), [
            'login_id' => 'dewi@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect($urlBookingLayanan);
    }

    public function test_pintasan_url_internal_staf_dan_pos_mengarahkan_ke_login_atau_dashboard(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Portal Staf');

        $this->get('/staf')->assertRedirect(route('login'));
        $this->get('/karyawan')->assertRedirect(route('login'));
        $this->get('/pos')->assertRedirect(route('login'));

        $kasir = Karyawan::create([
            'nama_staff' => 'Kasir Utama',
            'email' => 'kasir@salon.test',
            'username' => 'kasir_utama',
            'password' => 'rahasia123',
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'persen_komisi' => 0,
            'aktif' => true,
        ]);

        $this->actingAs($kasir, 'karyawan')
            ->get('/pos')
            ->assertRedirect(route('kasir.dashboard'));
    }
}
