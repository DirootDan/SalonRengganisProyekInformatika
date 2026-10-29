<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LimaKebutuhanSalonTest extends TestCase
{
    use RefreshDatabase;

    protected Karyawan $manajer;

    protected Karyawan $kasir;

    protected Karyawan $stylist1;

    protected Karyawan $stylist2;

    protected Karyawan $therapist1;

    protected Layanan $layananRambut;

    protected Layanan $layananSmoothing;

    protected Layanan $layananWajah;

    protected Produk $produkVitamin;

    protected Promo $promoPersen;

    protected Promo $promoNominalBesar;

    protected Pelanggan $pelanggan1;

    protected Pelanggan $pelanggan2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manajer = Karyawan::create([
            'nama_staff' => 'Manajer Utama',
            'jabatan' => 'Manajer',
            'persen_komisi' => 0,
            'aktif' => true,
            'email' => 'manajer.lima@test.com',
            'password' => 'password123',
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff' => 'Kasir Utama',
            'jabatan' => 'Kasir',
            'persen_komisi' => 0,
            'aktif' => true,
            'email' => 'kasir.lima@test.com',
            'password' => 'password123',
        ]);

        $this->stylist1 = Karyawan::create([
            'nama_staff' => 'Sinta Stylist',
            'jabatan' => 'Stylist',
            'persen_komisi' => 15,
            'aktif' => true,
            'email' => 'sinta.lima@test.com',
            'password' => 'password123',
        ]);

        $this->stylist2 = Karyawan::create([
            'nama_staff' => 'Nadia Stylist Senior',
            'jabatan' => 'Stylist',
            'persen_komisi' => 25,
            'aktif' => true,
            'email' => 'nadia.lima@test.com',
            'password' => 'password123',
        ]);

        $this->therapist1 = Karyawan::create([
            'nama_staff' => 'Laras Therapist',
            'jabatan' => 'Therapist',
            'persen_komisi' => 20,
            'aktif' => true,
            'email' => 'laras.lima@test.com',
            'password' => 'password123',
        ]);

        $this->layananRambut = Layanan::create([
            'nama_layanan' => 'Potong & Blow',
            'kategori' => 'Rambut',
            'jabatan_petugas' => 'Stylist',
            'harga_layanan' => 100000,
            'durasi_menit' => 60,
            'jeda_menit' => 15,
            'petugas_eksklusif' => true,
        ]);

        $this->layananSmoothing = Layanan::create([
            'nama_layanan' => 'Smoothing Keratin',
            'kategori' => 'Rambut',
            'jabatan_petugas' => 'Stylist',
            'harga_layanan' => 250000,
            'durasi_menit' => 180,
            'jeda_menit' => 15,
            'petugas_eksklusif' => true,
        ]);

        $this->layananWajah = Layanan::create([
            'nama_layanan' => 'Facial Detox',
            'kategori' => 'Perawatan',
            'jabatan_petugas' => 'Therapist',
            'harga_layanan' => 120000,
            'durasi_menit' => 60,
            'jeda_menit' => 15,
            'petugas_eksklusif' => true,
        ]);

        $this->produkVitamin = Produk::create([
            'nama_produk' => 'Hair Serum Keratin',
            'stok_produk' => 5,
            'harga_produk' => 50000,
        ]);

        $this->promoPersen = Promo::create([
            'kode_promo' => 'CANTIK10',
            'nama_promo' => 'Diskon 10 Persen',
            'tipe_diskon' => 'Persentase',
            'nilai_diskon' => 10,
            'min_transaksi' => 150000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'berlaku_sampai' => now()->addDays(10)->toDateString(),
            'aktif' => true,
        ]);

        $this->promoNominalBesar = Promo::create([
            'kode_promo' => 'POTONG500K',
            'nama_promo' => 'Voucer Spesial 500rb',
            'tipe_diskon' => 'Nominal',
            'nilai_diskon' => 500000,
            'min_transaksi' => 50000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'berlaku_sampai' => now()->addDays(10)->toDateString(),
            'aktif' => true,
        ]);

        MetodePembayaran::create([
            'nama_metode' => 'Tunai',
            'kategori' => 'Tunai',
            'nomor_rekening' => 'Kasir',
            'atas_nama' => 'Salon Rengganis',
            'ikon' => 'T',
            'aktif' => true,
        ]);

        $this->pelanggan1 = Pelanggan::create([
            'nama_cust' => 'Rina Pelanggan',
            'no_telp' => '081211112222',
            'email' => 'rina.lima@test.com',
            'password' => 'password123',
        ]);

        $this->pelanggan2 = Pelanggan::create([
            'nama_cust' => 'Dewi Pelanggan',
            'no_telp' => '081233334444',
            'email' => 'dewi.lima@test.com',
            'password' => 'password123',
        ]);
    }

    public function test_kebutuhan_1_promo_saat_booking_validasi_server_dan_snapshot(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        // 1. Gagal bila minimum transaksi promo (Rp 150.000) belum terpenuhi (hanya Rp 100.000)
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'kode_promo' => 'CANTIK10',
            ])
            ->assertSessionHasErrors('kode_promo');

        // 2. Berhasil dengan Layanan (100rb) + Produk Add-On 1x (50rb) = 150rb -> Diskon 10% = 15rb
        // Sekaligus uji manipulasi harga/diskon dari client diabaikan oleh server
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'produk_addon' => [$this->produkVitamin->id_produk => 1],
                'kode_promo' => 'cantik10',
                'nominal_diskon' => 999999,
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        $reservasi = Reservasi::firstOrFail();
        $this->assertEquals('CANTIK10', $reservasi->kode_promo);
        $this->assertEquals(15000, (float) $reservasi->nominal_diskon);
        $this->assertEquals(135000, $reservasi->estimasiTotalAkhir());

        // 3. Diskon nominal besar tidak boleh membuat total negatif (maksimal sebesar subtotal)
        $this->actingAs($this->pelanggan2, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '13:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'kode_promo' => 'POTONG500K',
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        $res2 = Reservasi::latest('id_reservasi')->firstOrFail();
        $this->assertEquals(100000, (float) $res2->nominal_diskon);
        $this->assertEquals(0, $res2->estimasiTotalAkhir());
    }

    public function test_kebutuhan_2_produk_addon_potong_dan_kembalikan_stok_otomatis(): void
    {
        $besok = Carbon::tomorrow()->addDay()->toDateString();

        // Gagal bila memesan melebihi sisa stok (stok 5, diminta 6)
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'produk_addon' => [$this->produkVitamin->id_produk => 6],
            ])
            ->assertSessionHasErrors('produk_addon');
        $this->assertEquals(5, $this->produkVitamin->fresh()->stok_produk);

        // Berhasil memesan 2 pcs -> stok berkurang menjadi 3
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'produk_addon' => [$this->produkVitamin->id_produk => 2],
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        $this->assertEquals(3, $this->produkVitamin->fresh()->stok_produk);
        $reservasi = Reservasi::firstOrFail();

        // Pastikan tombol "Batalkan Booking" langsung tampil & kartu terbuka otomatis di Riwayat dan Beranda
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->get(route('pelanggan.riwayat'))
            ->assertStatus(200)
            ->assertSee('Batalkan Booking')
            ->assertSee('Batalkan Reservasi Ini')
            ->assertSee('reservasi-item aktif', false);

        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->get(route('pelanggan.beranda'))
            ->assertStatus(200)
            ->assertSee('Jadwal Booking Aktif Anda')
            ->assertSee('Batalkan Booking');

        // Saat dibatalkan oleh pelanggan beserta alasan, stok kembali utuh ke 5 dan tidak dikembalikan dua kali
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.batal', $reservasi->id_reservasi), [
                'alasan_batal' => 'Ada rapat mendadak di kantor',
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        $this->assertEquals(5, $this->produkVitamin->fresh()->stok_produk);
        $this->assertStringContainsString('Ada rapat mendadak di kantor', (string) $reservasi->fresh()->alasan_penolakan);
        $this->assertDatabaseHas('detail_reservasi', [
            'id_reservasi' => $reservasi->id_reservasi,
            'status_layanan' => Reservasi::STATUS_DIBATALKAN,
        ]);
        $reservasi->fresh()->kembalikanStokProdukAddon();
        $this->assertEquals(5, $this->produkVitamin->fresh()->stok_produk);
    }

    public function test_kebutuhan_3_kasir_petugas_otomatis_komisi_final_dan_kasus_tepi_nonaktif(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        // Buat reservasi dengan Stylist 1 (komisi 15%) + 1 produk add-on (stok awal 5 -> jadi 4)
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'petugas' => [$this->layananRambut->id_layanan => $this->stylist1->id_karyawan],
                'produk_addon' => [$this->produkVitamin->id_produk => 1],
            ]);

        $reservasi = Reservasi::firstOrFail();
        $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);

        // Kasus tepi: Stylist 1 dinonaktifkan setelah reservasi dibuat -> kasir wajib mengganti petugas
        $this->stylist1->update(['aktif' => false]);

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'id_reservasi' => $reservasi->id_reservasi,
                'layanan_ids' => [$this->layananRambut->id_layanan],
                'petugas_layanan' => [$this->layananRambut->id_layanan => $this->stylist1->id_karyawan],
                'produk_items' => [$this->produkVitamin->id_produk => 1],
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 150000,
            ])
            ->assertSessionHasErrors('pembayaran');

        // Kasir mengganti ke Stylist 2 (aktif, komisi 25%) -> pembayaran berhasil & komisi dihitung 25% (Rp 25.000)
        // Sekaligus uji pembayaran dengan nominal pecahan ratusan rupiah (misal Rp 150.250 -> kembalian Rp 250)
        // Stok produk yang sudah dipotong 1 saat booking tidak dipotong ganda (tetap 4)
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'id_reservasi' => $reservasi->id_reservasi,
                'layanan_ids' => [$this->layananRambut->id_layanan],
                'petugas_layanan' => [$this->layananRambut->id_layanan => $this->stylist2->id_karyawan],
                'produk_items' => [$this->produkVitamin->id_produk => 1],
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 150250,
            ])
            ->assertRedirect();

        $this->assertEquals(4, $this->produkVitamin->fresh()->stok_produk);
        $this->assertDatabaseHas('transaksi', [
            'id_reservasi' => $reservasi->id_reservasi,
            'total_bayar' => 150000,
            'nominal_uang' => 150250,
            'kembalian' => 250,
            'komisi_stylist' => 25000,
        ]);
        $this->assertDatabaseHas('detail_reservasi', [
            'id_reservasi' => $reservasi->id_reservasi,
            'id_layanan' => $this->layananRambut->id_layanan,
            'id_karyawan' => $this->stylist2->id_karyawan,
            'persen_komisi' => 25,
            'nominal_komisi' => 25000,
        ]);
    }

    public function test_kebutuhan_4_modal_konfirmasi_gui_dan_proteksi_hapus_data_aktif(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '11:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'petugas' => [$this->layananRambut->id_layanan => $this->stylist1->id_karyawan],
                'produk_addon' => [$this->produkVitamin->id_produk => 1],
                'kode_promo' => 'POTONG500K',
            ]);

        $manajer = $this->actingAs($this->manajer, 'karyawan');

        // Komponen <x-confirm-modal> dirender di halaman Manajer
        $manajer->get(route('manajer.layanan'))
            ->assertStatus(200)
            ->assertSee('id="modalKonfirmasi"', false)
            ->assertSee('role="alertdialog"', false);

        // Server menolak penghapusan Karyawan, Layanan, Produk, dan Promo yang terikat reservasi aktif
        $manajer->delete(route('manajer.karyawan.hapus', $this->stylist1))->assertSessionHasErrors('karyawan');
        $manajer->delete(route('manajer.layanan.hapus', $this->layananRambut))->assertSessionHasErrors('layanan');
        $manajer->delete(route('manajer.produk.hapus', $this->produkVitamin))->assertSessionHasErrors('produk');
        $manajer->delete(route('manajer.promo.hapus', $this->promoNominalBesar))->assertSessionHasErrors('promo');
    }

    public function test_kebutuhan_5_durasi_pengerjaan_jeda_buffer_auto_assign_dan_jam_tutup(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        // 1. Smoothing (180 menit + 15 menit jeda = blokir 09:00 s/d 12:15) pada Stylist 1
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '09:00',
                'layanan' => [$this->layananSmoothing->id_layanan],
                'petugas' => [$this->layananSmoothing->id_layanan => $this->stylist1->id_karyawan],
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        // Stylist 1 bentrok pada pukul 11:00 maupun pukul 12:00 (karena jeda selesai pukul 12:15)
        $this->actingAs($this->pelanggan2, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '12:00',
                'layanan' => [$this->layananRambut->id_layanan],
                'petugas' => [$this->layananRambut->id_layanan => $this->stylist1->id_karyawan],
            ])
            ->assertSessionHasErrors('bentrok');

        // 2. Jika pelanggan memilih "Pilihkan Otomatis" (petugas dikosongkan) pada pukul 11:25 (menit spesifik dinamis),
        // server otomatis menugaskan Stylist 2 yang masih kosong
        $this->actingAs($this->pelanggan2, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '11:25',
                'layanan' => [$this->layananRambut->id_layanan],
                'petugas' => [$this->layananRambut->id_layanan => ''],
            ])
            ->assertRedirect(route('pelanggan.riwayat'));

        $resAuto = Reservasi::latest('id_reservasi')->firstOrFail();
        $this->assertEquals($this->stylist2->id_karyawan, $resAuto->id_karyawan);
        $this->assertEquals('11:25', $resAuto->jam_booking);
        $this->assertEquals('12:25', $resAuto->jam_selesai);

        // 3. Multi-layanan yang melewati jam tutup 21:00 (Smoothing 180m + Potong 60m = 4 jam dari 19:00 -> 23:00) ditolak
        $this->actingAs($this->pelanggan1, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => $besok,
                'jam_booking' => '19:00',
                'layanan' => [
                    $this->layananSmoothing->id_layanan,
                    $this->layananRambut->id_layanan,
                ],
            ])
            ->assertSessionHasErrors('bentrok');
    }
}
