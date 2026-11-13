<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use App\Models\Reservasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegritasLogikaAntarPeranTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $manajer;

    private Karyawan $kasir;

    private Karyawan $stylist;

    private Karyawan $therapist;

    private Layanan $layananRambut;

    private Pelanggan $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manajer = Karyawan::create([
            'nama_staff' => 'Manajer Audit',
            'username' => 'manajer_audit',
            'role_jabatan' => Karyawan::JABATAN_MANAJER,
            'persen_komisi' => 0,
            'aktif' => true,
            'email' => 'manajer.audit@test.com',
            'password' => 'password123',
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff' => 'Kasir Audit',
            'username' => 'kasir_audit',
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'persen_komisi' => 0,
            'aktif' => true,
            'email' => 'kasir.audit@test.com',
            'password' => 'password123',
        ]);

        $this->stylist = Karyawan::create([
            'nama_staff' => 'Sinta Stylist',
            'username' => 'sinta_audit',
            'role_jabatan' => Karyawan::JABATAN_STYLIST,
            'persen_komisi' => 15,
            'aktif' => true,
            'email' => 'sinta.audit@test.com',
            'password' => 'password123',
        ]);

        $this->therapist = Karyawan::create([
            'nama_staff' => 'Tari Therapist',
            'username' => 'tari_audit',
            'role_jabatan' => Karyawan::JABATAN_THERAPIST,
            'persen_komisi' => 20,
            'aktif' => true,
            'email' => 'tari.audit@test.com',
            'password' => 'password123',
        ]);

        $this->layananRambut = Layanan::create([
            'nama_layanan' => 'Creambath & Hair Spa',
            'kategori' => 'Perawatan Rambut',
            'jabatan_petugas' => Karyawan::JABATAN_STYLIST,
            'harga_layanan' => 120000,
            'durasi_menit' => 45,
            'jeda_menit' => 15,
        ]);

        $this->pelanggan = Pelanggan::create([
            'nama_cust' => 'Ayu Pelanggan',
            'no_telp' => '081299998888',
            'email' => 'ayu.audit@test.com',
            'password' => 'password123',
        ]);

        MetodePembayaran::create(['nama_metode' => 'Tunai', 'aktif' => true]);
    }

    public function test_bug01_error_tandai_selesai_ditampilkan_dan_status_tunggu_kasir_jelas(): void
    {
        $reservasi = $this->buatReservasiDenganDetail(Reservasi::STATUS_MENUNGGU);

        $this->actingAs($this->stylist, 'karyawan')
            ->get(route('stylist.dashboard'))
            ->assertOk()
            ->assertSee('Menunggu diterima Kasir');

        $this->actingAs($this->stylist, 'karyawan')
            ->followingRedirects()
            ->from(route('stylist.dashboard'))
            ->post(route('stylist.layanan.selesai', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertOk()
            ->assertSee('belum bisa ditandai selesai');
    }

    public function test_bug03_tolak_dan_hangus_menyinkronkan_status_layanan_dan_membersihkan_jadwal(): void
    {
        $reservasiDitolak = $this->buatReservasiDenganDetail(Reservasi::STATUS_MENUNGGU);

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.permintaan.tolak', $reservasiDitolak->id_reservasi), [
                'alasan_penolakan' => 'Slot penuh untuk hari tersebut.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('detail_reservasi', [
            'id_reservasi' => $reservasiDitolak->id_reservasi,
            'status_layanan' => Reservasi::STATUS_DITOLAK,
        ]);

        $this->actingAs($this->stylist, 'karyawan')
            ->get(route('stylist.dashboard'))
            ->assertOk()
            ->assertDontSee("<strong>#{$reservasiDitolak->id_reservasi}</strong>", false)
            ->assertSee('Tidak ada jadwal reservasi');

        $reservasiHangus = $this->buatReservasiDenganDetail(Reservasi::STATUS_DITERIMA);
        $reservasiHangus->ubahStatus(Reservasi::STATUS_HANGUS);

        $this->assertDatabaseHas('detail_reservasi', [
            'id_reservasi' => $reservasiHangus->id_reservasi,
            'status_layanan' => Reservasi::STATUS_HANGUS,
        ]);
    }

    public function test_bug04_bug05_walkin_punya_timeline_durasi_nyata_dan_tidak_mencatat_kasir_sebagai_petugas(): void
    {
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'nama_pelanggan_walkin' => 'Tamu Walk-In',
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 150000,
                'layanan_ids' => [$this->layananRambut->id_layanan],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $reservasi = Reservasi::latest('id_reservasi')->firstOrFail();

        $this->assertNull($reservasi->id_karyawan);
        $this->assertSame(45, $reservasi->total_durasi_menit);
        $this->assertNotSame($reservasi->jam_booking, $reservasi->jam_selesai);

        $detail = DB::table('detail_reservasi')->where('id_reservasi', $reservasi->id_reservasi)->first();
        $this->assertSame(45, (int) $detail->durasi_saat_ini);
        $this->assertSame(15, (int) $detail->jeda_saat_ini);
        $this->assertNotSame($detail->jam_mulai, $detail->jam_selesai_blokir);
    }

    public function test_bug08_bug09_manajer_dilarang_nonaktif_atau_ubah_jabatan_staf_berbooking_dan_kasir_tolak_staf_nonaktif(): void
    {
        $reservasi = $this->buatReservasiDenganDetail(Reservasi::STATUS_MENUNGGU);

        // BUG-08: Manajer coba menonaktifkan atau mengganti jabatan stylist yang masih punya booking aktif
        $this->actingAs($this->manajer, 'karyawan')
            ->put(route('manajer.karyawan.update', $this->stylist->id_karyawan), [
                'nama_staff' => $this->stylist->nama_staff,
                'username' => $this->stylist->username,
                'email' => $this->stylist->email,
                'role_jabatan' => Karyawan::JABATAN_THERAPIST,
                'persen_komisi' => 15,
                'aktif' => '0',
            ])
            ->assertSessionHasErrors('karyawan');

        $this->assertTrue((bool) $this->stylist->fresh()->aktif);
        $this->assertSame(Karyawan::JABATAN_STYLIST, $this->stylist->fresh()->role_jabatan);

        // BUG-09: Simulasikan staf nonaktif langsung di DB, lalu kasir coba menerima reservasi
        $this->stylist->update(['aktif' => false]);

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.permintaan.terima', $reservasi->id_reservasi))
            ->assertSessionHasErrors('status');

        $this->assertSame(Reservasi::STATUS_MENUNGGU, $reservasi->fresh()->status);
    }

    public function test_bug10_query_tanggal_rusak_di_portal_petugas_tidak_menyebabkan_error_500(): void
    {
        $this->actingAs($this->therapist, 'karyawan')
            ->get(route('therapist.dashboard', ['mode' => 'mingguan', 'tanggal' => 'bukan-tanggal']))
            ->assertOk();

        $this->actingAs($this->therapist, 'karyawan')
            ->get(route('therapist.komisi', ['mulai' => '2026-99-99', 'selesai' => 'abc']))
            ->assertOk();
    }

    public function test_bug02_kasir_wajib_tunggu_petugas_tandai_selesai_sebelum_checkout(): void
    {
        $reservasi = $this->buatReservasiDenganDetail(Reservasi::STATUS_DITERIMA);

        $this->assertFalse($reservasi->fresh()->semuaLayananSudahSelesai());
        $this->assertSame(0, Reservasi::siapBayarKasir()->count());

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.antrean'))
            ->assertOk()
            ->assertSee('Menunggu Petugas Selesai')
            ->assertDontSee('Proses di POS');

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'id_reservasi' => $reservasi->id_reservasi,
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 150000,
            ])
            ->assertSessionHasErrors('pembayaran');

        $this->assertDatabaseCount('transaksi', 0);

        $this->actingAs($this->stylist, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertRedirect();

        $this->assertTrue($reservasi->fresh()->semuaLayananSudahSelesai());
        $this->assertSame(1, Reservasi::siapBayarKasir()->count());

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.antrean'))
            ->assertOk()
            ->assertSee('Proses di POS');

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'id_reservasi' => $reservasi->id_reservasi,
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 150000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('transaksi', 1);
        $this->assertSame(Reservasi::STATUS_SELESAI, $reservasi->fresh()->status);
    }

    public function test_ajax_petugas_tersedia_menghitung_timeline_berurutan_dan_jam_tutup(): void
    {
        $layananWajah = Layanan::create([
            'nama_layanan' => 'Facial Glow',
            'kategori' => 'Wajah',
            'jabatan_petugas' => Karyawan::JABATAN_THERAPIST,
            'harga_layanan' => 150000,
            'durasi_menit' => 60,
            'jeda_menit' => 15,
        ]);

        // Stylist sibuk mulai 11:00, seharusnya tetap tersedia untuk layanan 1 (10:00 - 11:00 blokir)
        $besok = Carbon::now()->addDay()->toDateString();
        $resLain = Reservasi::create([
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_karyawan' => $this->stylist->id_karyawan,
            'tgl_booking' => $besok,
            'jam_booking' => '11:00',
            'jam_selesai' => '11:45',
            'total_durasi_menit' => 45,
            'status' => Reservasi::STATUS_DITERIMA,
        ]);
        $resLain->layanan()->attach($this->layananRambut->id_layanan, [
            'id_karyawan' => $this->stylist->id_karyawan,
            'jam_mulai' => '11:00',
            'jam_selesai' => '11:45',
            'jam_selesai_blokir' => '12:00',
            'durasi_saat_ini' => 45,
            'jeda_saat_ini' => 15,
            'harga_saat_ini' => 120000,
            'persen_komisi' => 15,
            'nominal_komisi' => 18000,
            'status_layanan' => Reservasi::STATUS_MENUNGGU,
        ]);

        $resp = $this->actingAs($this->pelanggan, 'pelanggan')
            ->getJson(route('pelanggan.booking.petugas', [
                'tgl_booking' => $besok,
                'jam_booking' => '10:00',
                'layanan' => [$this->layananRambut->id_layanan, $layananWajah->id_layanan],
            ]))
            ->assertOk()
            ->assertJsonPath('melewati_jam_tutup', false);

        $perLayanan = $resp->json('petugas_per_layanan');
        $this->assertCount(1, $perLayanan[$this->layananRambut->id_layanan]);
        $this->assertCount(1, $perLayanan[$layananWajah->id_layanan]);

        // Jam 20:30 dengan durasi 105 menit -> melewati jam tutup 21:00
        $this->actingAs($this->pelanggan, 'pelanggan')
            ->getJson(route('pelanggan.booking.petugas', [
                'tgl_booking' => $besok,
                'jam_booking' => '20:30',
                'layanan' => [$this->layananRambut->id_layanan, $layananWajah->id_layanan],
            ]))
            ->assertOk()
            ->assertJsonPath('melewati_jam_tutup', true)
            ->assertJsonCount(0, 'petugas.Stylist');
    }

    public function test_kasir_dapat_ganti_petugas_di_antrean_dan_ditolak_jika_salah_jabatan_atau_sudah_selesai(): void
    {
        $stylistPengganti = Karyawan::create([
            'nama_staff' => 'Sari Pengganti',
            'email' => 'sari@salon.test',
            'role_jabatan' => Karyawan::JABATAN_STYLIST,
            'persen_komisi' => 20,
            'aktif' => true,
            'password' => 'password123',
        ]);

        $reservasi = $this->buatReservasiDenganDetail(Reservasi::STATUS_DITERIMA);

        // Salah jabatan (Therapist untuk layanan Stylist) ditolak
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.antrean.ganti-petugas', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]), [
                'id_karyawan' => $this->therapist->id_karyawan,
            ])
            ->assertSessionHasErrors('antrean');

        // Pengganti valid berhasil memperbarui petugas & komisi
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.antrean.ganti-petugas', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]), [
                'id_karyawan' => $stylistPengganti->id_karyawan,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('detail_reservasi', [
            'id_reservasi' => $reservasi->id_reservasi,
            'id_layanan' => $this->layananRambut->id_layanan,
            'id_karyawan' => $stylistPengganti->id_karyawan,
            'persen_komisi' => 20,
            'nominal_komisi' => 24000,
        ]);

        // Setelah petugas baru menandai selesai, ganti petugas ditolak
        $this->actingAs($stylistPengganti, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertRedirect();

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.antrean.ganti-petugas', [$reservasi->id_reservasi, $this->layananRambut->id_layanan]), [
                'id_karyawan' => $this->stylist->id_karyawan,
            ])
            ->assertSessionHasErrors('antrean');
    }

    public function test_endpoint_ringkasan_badge_kasir_dan_batal_aman_terhadap_status_terminal(): void
    {
        $this->buatReservasiDenganDetail(Reservasi::STATUS_MENUNGGU);
        $siapBayar = $this->buatReservasiDenganDetail(Reservasi::STATUS_DITERIMA);
        DB::table('detail_reservasi')
            ->where('id_reservasi', $siapBayar->id_reservasi)
            ->update(['status_layanan' => Reservasi::STATUS_SELESAI]);

        $this->actingAs($this->kasir, 'karyawan')
            ->getJson(route('kasir.badge-ringkasan'))
            ->assertOk()
            ->assertJson([
                'menunggu' => 1,
                'antrean' => 1,
            ]);

        $this->actingAs($this->stylist, 'karyawan')
            ->getJson(route('kasir.badge-ringkasan'))
            ->assertForbidden();

        // Pembatalan pada reservasi yang sudah ditolak ditangani ramah (bukan 500)
        $ditolak = $this->buatReservasiDenganDetail(Reservasi::STATUS_DITOLAK);
        $this->actingAs($this->pelanggan, 'pelanggan')
            ->post(route('pelanggan.booking.batal', $ditolak->id_reservasi))
            ->assertSessionHasErrors('batal');
    }

    public function test_katalog_bebas_n_plus_1_dan_layout_manajer_terima_badge_dari_composer(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Layanan::create([
                'nama_layanan' => "Layanan Ekstra {$i}",
                'kategori' => 'Perawatan Rambut',
                'jabatan_petugas' => Karyawan::JABATAN_STYLIST,
                'harga_layanan' => 80000 + ($i * 1000),
                'durasi_menit' => 45,
                'jeda_menit' => 15,
            ]);
            Produk::create([
                'nama_produk' => "Produk Ekstra {$i}",
                'stok_produk' => $i <= 3 ? 5 : 20,
                'harga_produk' => 45000,
            ]);
        }

        $this->buatReservasiDenganDetail(Reservasi::STATUS_MENUNGGU);

        PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_TAMBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_LAYANAN,
            'label_target' => 'Tambah Layanan Uji Composer',
            'alasan_kasir' => 'Pengujian View Composer Manajer',
            'total_awal' => 0,
            'total_akhir' => 0,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $resManajerLayanan = $this->actingAs($this->manajer, 'karyawan')
            ->get(route('manajer.layanan'));
        $jumlahKueri = count(DB::getQueryLog());
        DB::disableQueryLog();

        $resManajerLayanan
            ->assertOk()
            ->assertSee('1 permintaan otorisasi menunggu keputusan')
            ->assertSee('memiliki 1 reservasi aktif');

        // Dengan 13+ layanan, kueri harus konstan O(1) tanpa 1 kueri per baris katalog
        $this->assertLessThanOrEqual(6, $jumlahKueri);

        $this->actingAs($this->manajer, 'karyawan')->get(route('manajer.produk'))->assertOk();
        $this->actingAs($this->kasir, 'karyawan')->get(route('kasir.layanan'))->assertOk();
        $this->actingAs($this->kasir, 'karyawan')->get(route('kasir.produk'))->assertOk();
    }

    public function test_halaman_utama_tiap_peran_render_bersih_dan_tab_profil_aktif_dari_controller(): void
    {
        $this->buatReservasiDenganDetail(Reservasi::STATUS_DITERIMA);

        foreach (['manajer.persetujuan', 'manajer.laporan', 'manajer.promo', 'manajer.struk'] as $ruteManajer) {
            $this->actingAs($this->manajer, 'karyawan')->get(route($ruteManajer))->assertOk();
        }

        foreach (['kasir.laporan', 'kasir.permintaan', 'kasir.struk'] as $ruteKasir) {
            $this->actingAs($this->kasir, 'karyawan')->get(route($ruteKasir))->assertOk();
        }

        foreach (['pelanggan.booking', 'pelanggan.riwayat'] as $rutePelanggan) {
            $this->actingAs($this->pelanggan, 'pelanggan')->get(route($rutePelanggan))->assertOk();
        }

        $this->actingAs($this->pelanggan, 'pelanggan')
            ->get(route('pelanggan.profil', ['tab' => 'password']))
            ->assertOk()
            ->assertViewHas('bukaTabPassword', true);
    }

    private function buatReservasiDenganDetail(string $status): Reservasi
    {
        $reservasi = Reservasi::create([
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_karyawan' => $this->stylist->id_karyawan,
            'tgl_booking' => Carbon::now()->addDay()->toDateString(),
            'jam_booking' => '10:00',
            'jam_selesai' => '10:45',
            'total_durasi_menit' => 45,
            'status' => $status,
        ]);

        $reservasi->layanan()->attach($this->layananRambut->id_layanan, [
            'id_karyawan' => $this->stylist->id_karyawan,
            'jam_mulai' => '10:00',
            'jam_selesai' => '10:45',
            'jam_selesai_blokir' => '11:00',
            'durasi_saat_ini' => 45,
            'jeda_saat_ini' => 15,
            'harga_saat_ini' => 120000,
            'persen_komisi' => 15,
            'nominal_komisi' => 18000,
            'status_layanan' => Reservasi::STATUS_MENUNGGU,
        ]);

        return $reservasi;
    }
}
