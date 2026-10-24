<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Models\RiwayatPelanggaran;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalonRengganisTest extends TestCase
{
    use RefreshDatabase;

    protected Layanan $layananRambut;
    protected Layanan $layananWajah;
    protected Produk $produkVitamin;
    protected Promo $promoDiskon;
    protected Karyawan $manajer;
    protected Karyawan $kasir;
    protected Karyawan $stylist1;
    protected Karyawan $stylist2;
    protected Karyawan $therapist1;
    protected Pelanggan $pelanggan1;
    protected Pelanggan $pelanggan2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manajer = Karyawan::create([
            'nama_staff'    => 'Budi Manajer',
            'jabatan'       => 'Manajer',
            'persen_komisi' => 0,
            'aktif'         => true,
            'email'         => 'manajer@test.com',
            'password'      => 'password123',
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff'    => 'Ayu Kasir',
            'jabatan'       => 'Kasir',
            'persen_komisi' => 0,
            'aktif'         => true,
            'email'         => 'kasir@test.com',
            'password'      => 'password123',
        ]);

        $this->stylist1 = Karyawan::create([
            'nama_staff'    => 'Sinta Stylist',
            'jabatan'       => 'Stylist',
            'persen_komisi' => 15,
            'aktif'         => true,
            'email'         => 'sinta@test.com',
            'password'      => 'password123',
        ]);

        $this->stylist2 = Karyawan::create([
            'nama_staff'    => 'Nadia Stylist',
            'jabatan'       => 'Stylist',
            'persen_komisi' => 15,
            'aktif'         => true,
            'email'         => 'nadia@test.com',
            'password'      => 'password123',
        ]);

        $this->therapist1 = Karyawan::create([
            'nama_staff'    => 'Laras Therapist',
            'jabatan'       => 'Therapist',
            'persen_komisi' => 20,
            'aktif'         => true,
            'email'         => 'laras@test.com',
            'password'      => 'password123',
        ]);

        $this->layananRambut = Layanan::create([
            'nama_layanan'    => 'Potong & Blow Rambut',
            'kategori'        => 'Rambut',
            'jabatan_petugas' => 'Stylist',
            'harga_layanan'   => 100000,
        ]);

        $this->layananWajah = Layanan::create([
            'nama_layanan'    => 'Facial Glowing',
            'kategori'        => 'Perawatan',
            'jabatan_petugas' => 'Therapist',
            'harga_layanan'   => 120000,
        ]);

        $this->produkVitamin = Produk::create([
            'nama_produk'  => 'Serum Vitamin Rambut',
            'stok_produk'  => 10,
            'harga_produk' => 50000,
        ]);

        $this->promoDiskon = Promo::create([
            'kode_promo'     => 'DISKON10',
            'nama_promo'     => 'Diskon 10 Persen',
            'tipe_diskon'    => 'Persentase',
            'nilai_diskon'   => 10,
            'min_transaksi'  => 100000,
            'berlaku_mulai'  => now()->subDay()->toDateString(),
            'berlaku_sampai' => now()->addMonth()->toDateString(),
            'aktif'          => true,
        ]);

        MetodePembayaran::create([
            'nama_metode'    => 'Tunai (Cash)',
            'kategori'       => 'Tunai',
            'nomor_rekening' => 'Kasir',
            'atas_nama'      => 'Salon Rengganis',
            'ikon'           => '💵',
            'aktif'          => true,
        ]);

        $this->pelanggan1 = Pelanggan::create([
            'nama_cust'          => 'Rina Pelanggan',
            'no_telp'            => '0812111111',
            'email'              => 'rina@test.com',
            'password'           => 'password123',
            'jumlah_pelanggaran' => 0,
            'diblokir_sampai'    => null,
        ]);

        $this->pelanggan2 = Pelanggan::create([
            'nama_cust'          => 'Dewi Pelanggan',
            'no_telp'            => '0812222222',
            'email'              => 'dewi@test.com',
            'password'           => 'password123',
            'jumlah_pelanggaran' => 0,
            'diblokir_sampai'    => null,
        ]);
    }

    /**
     * Skenario 1: Pencegahan Double-Booking (Bentrok Jadwal)
     */
    public function test_pencegahan_bentrok_jadwal_double_booking(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        $res1 = $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan'     => [$this->layananRambut->id_layanan],
                'petugas'     => [
                    $this->layananRambut->id_layanan => $this->stylist1->id_karyawan,
                ],
            ]);

        $res1->assertRedirect(route('pelanggan.riwayat'));
        $this->assertDatabaseCount('reservasi', 1);

        // Pelanggan 2 mencoba memesan Stylist 1 pada tanggal dan jam yang sama (10:00)
        $res2 = $this->actingAs($this->pelanggan2, 'pelanggan')
            ->from(route('pelanggan.booking'))
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan'     => [$this->layananRambut->id_layanan],
                'petugas'     => [
                    $this->layananRambut->id_layanan => $this->stylist1->id_karyawan,
                ],
            ]);

        $res2->assertRedirect(route('pelanggan.booking'));
        $res2->assertSessionHasErrors('bentrok');
        $this->assertDatabaseCount('reservasi', 1);
    }

    /**
     * Skenario 2: State Machine Transisi Status Reservasi
     */
    public function test_state_machine_menolak_transisi_status_tidak_sah(): void
    {
        $reservasi = Reservasi::create([
            'tgl_booking'  => Carbon::tomorrow()->toDateString(),
            'jam_booking'  => '11:00',
            'status'       => Reservasi::STATUS_MENUNGGU,
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'id_karyawan'  => $this->stylist1->id_karyawan,
        ]);

        $this->assertTrue($reservasi->bolehTransisiKe(Reservasi::STATUS_DITERIMA));
        $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);
        $this->assertEquals(Reservasi::STATUS_DITERIMA, $reservasi->fresh()->status);

        $this->assertTrue($reservasi->bolehTransisiKe(Reservasi::STATUS_SELESAI));
        $reservasi->ubahStatus(Reservasi::STATUS_SELESAI);
        $this->assertEquals(Reservasi::STATUS_SELESAI, $reservasi->fresh()->status);

        $this->assertFalse($reservasi->bolehTransisiKe(Reservasi::STATUS_DIBATALKAN));
        $this->expectException(DomainException::class);
        $reservasi->ubahStatus(Reservasi::STATUS_DIBATALKAN);
    }

    /**
     * Skenario 3: Simulasi Kebijakan Pinalti Bertingkat (7 Hari -> 30 Hari -> Permanen) & Jendela 90 Hari
     */
    public function test_evaluasi_hangus_bertingkat_dan_jendela_90_hari(): void
    {
        $kebijakan = PengaturanKebijakan::aktif();
        $kebijakan->update([
            'batas_pelanggaran'        => 5,
            'rentang_hari_pelanggaran' => 90,
            'lama_blokir_hari'         => 7,
            'lama_blokir_kedua_hari'   => 30,
            'blokir_ketiga_permanen'   => true,
            'toleransi_bayar_hari'     => 1,
            'kebijakan_aktif'          => true,
        ]);

        // 1 pelanggaran lama di luar 90 hari (100 hari lalu) -> tidak boleh ikut dihitung dalam 90 hari terakhir
        RiwayatPelanggaran::create([
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'id_reservasi' => null,
            'jenis'        => 'Pelanggaran',
            'tanggal'      => Carbon::today()->subDays(100)->toDateString(),
            'keterangan'   => 'Pelanggaran lama > 90 hari.',
        ]);

        // Buat 4 reservasi Diterima 3 hari lalu -> harus menghasilkan 4 pelanggaran aktif (belum terblokir karena pelanggaran 100 hari lalu sudah kedaluwarsa)
        for ($i = 0; $i < 4; $i++) {
            Reservasi::create([
                'tgl_booking'  => Carbon::today()->subDays(3)->toDateString(),
                'jam_booking'  => sprintf('%02d:00', 9 + $i),
                'status'       => Reservasi::STATUS_DITERIMA,
                'id_pelanggan' => $this->pelanggan1->id_pelanggan,
                'id_karyawan'  => $this->stylist1->id_karyawan,
            ]);
        }

        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);
        $p = $this->pelanggan1->fresh();
        $this->assertEquals(4, $p->jumlah_pelanggaran);
        $this->assertFalse($p->sedangDiblokir());

        // Tambah pelanggaran ke-5 -> memicu BLOKIR TAHAP 1 (7 hari)
        Reservasi::create([
            'tgl_booking'  => Carbon::today()->subDays(2)->toDateString(),
            'jam_booking'  => '15:00',
            'status'       => Reservasi::STATUS_DITERIMA,
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'id_karyawan'  => $this->stylist1->id_karyawan,
        ]);

        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);
        $p = $this->pelanggan1->fresh();
        $this->assertEquals(5, $p->jumlah_pelanggaran);
        $this->assertEquals(1, $p->jumlah_kali_blokir);
        $this->assertFalse($p->diblokir_permanen);
        $this->assertEquals(7, $p->sisaHariBlokir());

        // Pelanggan ditolak saat mencoba POST booking
        $this->actingAs($p, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => Carbon::tomorrow()->toDateString(),
                'jam_booking' => '13:00',
                'layanan'     => [$this->layananRambut->id_layanan],
                'petugas'     => [$this->layananRambut->id_layanan => $this->stylist1->id_karyawan],
            ])
            ->assertRedirect(route('pelanggan.booking'))
            ->assertSessionHasErrors('blokir');

        // Simulasikan masa blokir ke-1 berakhir, lalu mencapai 5 pelanggaran lagi -> BLOKIR TAHAP 2 (30 hari)
        $p->update(['diblokir_sampai' => now()->subMinute()]);
        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);
        $this->assertEquals(0, $p->fresh()->jumlah_pelanggaran);

        for ($i = 0; $i < 5; $i++) {
            Reservasi::create([
                'tgl_booking'  => Carbon::today()->subDays(4)->toDateString(),
                'jam_booking'  => sprintf('%02d:00', 9 + $i),
                'status'       => Reservasi::STATUS_DITERIMA,
                'id_pelanggan' => $this->pelanggan1->id_pelanggan,
                'id_karyawan'  => $this->stylist1->id_karyawan,
            ]);
        }

        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);
        $p = $this->pelanggan1->fresh();
        $this->assertEquals(2, $p->jumlah_kali_blokir);
        $this->assertFalse($p->diblokir_permanen);
        $this->assertEquals(30, $p->sisaHariBlokir());

        // Simulasikan masa blokir ke-2 berakhir, lalu mencapai 5 pelanggaran lagi -> BLOKIR TAHAP 3 (PERMANEN sampai dibuka Manajer)
        $p->update(['diblokir_sampai' => now()->subMinute()]);
        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);

        for ($i = 0; $i < 5; $i++) {
            Reservasi::create([
                'tgl_booking'  => Carbon::today()->subDays(5)->toDateString(),
                'jam_booking'  => sprintf('%02d:00', 9 + $i),
                'status'       => Reservasi::STATUS_DITERIMA,
                'id_pelanggan' => $this->pelanggan1->id_pelanggan,
                'id_karyawan'  => $this->stylist1->id_karyawan,
            ]);
        }

        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);
        $p = $this->pelanggan1->fresh();
        $this->assertEquals(3, $p->jumlah_kali_blokir);
        $this->assertTrue($p->diblokir_permanen);
        $this->assertNull($p->diblokir_sampai);
        $this->assertTrue($p->sedangDiblokir());
    }

    /**
     * Skenario 4: Pembukaan Blokir Manual oleh Manajer (termasuk Blokir Permanen Tahap 3)
     */
    public function test_manajer_membuka_blokir_pelanggan_secara_manual(): void
    {
        $this->pelanggan1->update([
            'jumlah_pelanggaran' => 5,
            'jumlah_kali_blokir' => 3,
            'diblokir_permanen'  => true,
            'diblokir_sampai'    => null,
        ]);

        $respon = $this->actingAs($this->manajer, 'karyawan')
            ->post(route('manajer.kebijakan.buka', $this->pelanggan1->id_pelanggan), [
                'alasan_buka' => 'Pelanggan sudah mengonfirmasi kendala darurat medis kepada pihak salon.',
            ]);

        $respon->assertRedirect();
        $respon->assertSessionHas('status');

        $pelangganSegar = $this->pelanggan1->fresh();
        $this->assertEquals(0, $pelangganSegar->jumlah_pelanggaran);
        $this->assertFalse($pelangganSegar->diblokir_permanen);
        $this->assertNull($pelangganSegar->diblokir_sampai);
        $this->assertFalse($pelangganSegar->sedangDiblokir());

        $this->assertDatabaseHas('riwayat_pelanggaran', [
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'jenis'        => 'Buka Blokir Manual',
        ]);

        // Pastikan pelanggan kini dapat memesan layanan kembali
        $responBooking = $this->actingAs($pelangganSegar, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => Carbon::tomorrow()->toDateString(),
                'jam_booking' => '14:00',
                'layanan'     => [$this->layananRambut->id_layanan],
                'petugas'     => [
                    $this->layananRambut->id_layanan => $this->stylist1->id_karyawan,
                ],
            ]);

        $responBooking->assertRedirect(route('pelanggan.riwayat'));
    }

    /**
     * Skenario 5: Simulasi Alur Utama Lengkap (Booking -> Terima Kasir -> Selesai Stylist -> Bayar Kasir + Anti Bayar Ganda)
     */
    public function test_simulasi_alur_utama_lengkap_dan_pencegahan_pembayaran_ganda(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        // 1. Pelanggan membuat booking
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '11:00',
                'layanan'     => [$this->layananRambut->id_layanan],
                'petugas'     => [$this->layananRambut->id_layanan => $this->stylist1->id_karyawan],
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        $reservasi = Reservasi::firstOrFail();
        $this->assertEquals(Reservasi::STATUS_MENUNGGU, $reservasi->status);

        // 2. Kasir menerima booking
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.permintaan.terima', $reservasi->id_reservasi))
            ->assertRedirect();

        $this->assertEquals(Reservasi::STATUS_DITERIMA, $reservasi->fresh()->status);

        // 3. Stylist menandai layanan selesai
        $this->actingAs($this->stylist1, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertRedirect();

        // 4. Kasir memproses pembayaran dengan tambahan 1 produk & kode promo DISKON10
        // Subtotal: Layanan 100.000 + Produk 50.000 = 150.000. Diskon 10% = 15.000. Total Bayar = 135.000.
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'id_reservasi' => $reservasi->id_reservasi,
                'metode_bayar' => 'Tunai (Cash)',
                'kode_promo'   => 'DISKON10',
                'nominal_uang' => 150000,
                'produk_items' => [
                    $this->produkVitamin->id_produk => 1,
                ],
            ])
            ->assertRedirect(route('kasir.dashboard'));

        $this->assertEquals(Reservasi::STATUS_SELESAI, $reservasi->fresh()->status);
        $this->assertEquals(9, $this->produkVitamin->fresh()->stok_produk);
        $this->assertDatabaseHas('transaksi', [
            'id_reservasi' => $reservasi->id_reservasi,
            'diskon'       => 15000,
            'total_bayar'  => 135000,
            'kembalian'    => 15000,
        ]);

        // 5. Percobaan pembayaran ganda pada reservasi yang sama wajib ditolak
        $this->actingAs($this->kasir, 'karyawan')
            ->from(route('kasir.dashboard'))
            ->post(route('kasir.bayar'), [
                'id_reservasi' => $reservasi->id_reservasi,
                'metode_bayar' => 'Tunai (Cash)',
                'nominal_uang' => 150000,
            ])
            ->assertRedirect(route('kasir.dashboard'))
            ->assertSessionHasErrors('pembayaran');
    }

    /**
     * Skenario 6: Pembatasan Akses Antar Peran (Role Isolation) & Anti-IDOR
     */
    public function test_pembatasan_akses_antar_peran_dan_anti_idor(): void
    {
        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('manajer.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('manajer.kebijakan'))
            ->assertStatus(403);

        $this->actingAs($this->stylist1, 'karyawan')
            ->get(route('kasir.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->stylist1, 'karyawan')
            ->get(route('therapist.dashboard'))
            ->assertStatus(403);

        $reservasiP1 = Reservasi::create([
            'tgl_booking'  => Carbon::now()->addDays(2)->toDateString(),
            'jam_booking'  => '15:00',
            'status'       => Reservasi::STATUS_MENUNGGU,
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'id_karyawan'  => $this->stylist1->id_karyawan,
        ]);

        $reservasiP1->layanan()->attach($this->layananRambut->id_layanan, [
            'id_karyawan'    => $this->stylist1->id_karyawan,
            'harga_saat_ini' => 100000,
            'persen_komisi'  => 15,
            'nominal_komisi' => 15000,
            'status_layanan' => 'Menunggu',
        ]);

        // Anti-IDOR: Pelanggan 2 tidak boleh membatalkan reservasi milik Pelanggan 1
        $this->actingAs($this->pelanggan2, 'pelanggan')
            ->post(route('pelanggan.booking.batal', $reservasiP1->id_reservasi))
            ->assertStatus(403);

        // Anti-IDOR: Stylist 2 tidak boleh menandai selesai layanan yang ditugaskan kepada Stylist 1
        $reservasiP1->ubahStatus(Reservasi::STATUS_DITERIMA);

        $this->actingAs($this->stylist2, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasiP1->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertStatus(403);

        $this->actingAs($this->stylist1, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasiP1->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertRedirect();
    }
}
