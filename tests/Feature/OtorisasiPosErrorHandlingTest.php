<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\LogPerubahanOtorisasi;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OtorisasiPosErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $manajer;

    private Karyawan $kasir;

    private Karyawan $kasirLain;

    private Karyawan $stylist;

    private Layanan $layananMahal;

    private Layanan $layananMurah;

    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        Pelanggan::create([
            'nama_cust' => 'Pelanggan Uji',
            'no_telp' => '081234567890',
            'email' => 'pelanggan.pos@salon.test',
            'password' => 'rahasia123',
        ]);

        $this->manajer = Karyawan::create([
            'nama_staff' => 'Ibu Rengganis',
            'role_jabatan' => Karyawan::JABATAN_MANAJER,
            'email' => 'manajer.pos@salon.test',
            'username' => 'manajer_pos',
            'password' => 'manajer123',
            'persen_komisi' => 0,
            'aktif' => true,
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff' => 'Kasir Satu',
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'email' => 'kasir1.pos@salon.test',
            'username' => 'kasir_satu',
            'password' => 'kasir123',
            'persen_komisi' => 0,
            'aktif' => true,
        ]);

        $this->kasirLain = Karyawan::create([
            'nama_staff' => 'Kasir Dua',
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'email' => 'kasir2.pos@salon.test',
            'username' => 'kasir_dua',
            'password' => 'kasirdua123',
            'persen_komisi' => 0,
            'aktif' => true,
        ]);

        $this->stylist = Karyawan::create([
            'nama_staff' => 'Stylist Handal',
            'role_jabatan' => Karyawan::JABATAN_STYLIST,
            'email' => 'stylist.pos@salon.test',
            'username' => 'stylist_pos',
            'password' => 'stylist123',
            'persen_komisi' => 20,
            'aktif' => true,
        ]);

        $this->layananMahal = Layanan::create([
            'nama_layanan' => 'Smoothing Keratin',
            'kategori' => 'Perawatan Rambut',
            'jabatan_petugas' => 'Stylist',
            'durasi_menit' => 90,
            'jeda_menit' => 15,
            'harga_layanan' => 200000,
        ]);

        $this->layananMurah = Layanan::create([
            'nama_layanan' => 'Cuci Blow Cepat',
            'kategori' => 'Perawatan Rambut',
            'jabatan_petugas' => 'Stylist',
            'durasi_menit' => 45,
            'jeda_menit' => 10,
            'harga_layanan' => 120000,
        ]);

        $this->produk = Produk::create([
            'nama_produk' => 'Vitamin Rambut',
            'stok_produk' => 10,
            'harga_produk' => 60000,
        ]);

        MetodePembayaran::create([
            'nama_metode' => 'Tunai',
            'kategori' => MetodePembayaran::KATEGORI_TUNAI,
            'nomor_rekening' => '-',
            'atas_nama' => 'Kasir',
            'ikon' => '💵',
            'aktif' => true,
        ]);
    }

    public function test_kasus_1_race_condition_direct_override_otomatis_menutup_pending_dan_menolak_reject_susulan(): void
    {
        // 1. Kasir kirim request ke Dashboard Manajer & Hold (Rp 200.000 -> Rp 170.000)
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.otorisasi.hold'), [
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 170000],
                'alasan_kasir' => 'Diskon pelanggan VIP ulang tahun.',
            ])
            ->assertRedirect(route('kasir.dashboard'));

        $tiket = PermintaanOtorisasi::firstOrFail();
        $this->assertSame(PermintaanOtorisasi::STATUS_MENUNGGU, $tiket->status);

        // 2. Manajer datang ke meja kasir dan melakukan Direct Override atas tiket yang sama
        $this->actingAs($this->kasir, 'karyawan')
            ->postJson(route('kasir.otorisasi.direct'), [
                'id_otorisasi' => $tiket->id_otorisasi,
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 170000],
                'alasan_kasir' => 'Diskon pelanggan VIP ulang tahun.',
                'username_manajer' => 'manajer_pos',
                'password_manajer' => 'manajer123',
            ])
            ->assertOk()
            ->assertJsonPath('berhasil', true);

        $tiket->refresh();
        $this->assertSame(PermintaanOtorisasi::STATUS_DISETUJUI, $tiket->status);
        $this->assertSame(PermintaanOtorisasi::METODE_KREDENSIAL_LANGSUNG, $tiket->metode_persetujuan);
        $this->assertSame(0, PermintaanOtorisasi::menungguPersetujuan()->count());

        // 3. Manajer tidak sengaja menekan Reject di tab lama -> ditolak aman tanpa merusak status Disetujui
        $this->actingAs($this->manajer, 'karyawan')
            ->post(route('manajer.persetujuan.tolak', $tiket->id_otorisasi), [
                'catatan_manajer' => 'Jangan kasih diskon besar.',
            ])
            ->assertSessionHasErrors('persetujuan');

        $this->assertSame(PermintaanOtorisasi::STATUS_DISETUJUI, $tiket->fresh()->status);
    }

    public function test_kasus_2_request_hold_ditolak_manajer_mengembalikan_harga_normal_tanpa_menghapus_hold(): void
    {
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.otorisasi.hold'), [
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'id_petugas' => $this->stylist->id_karyawan,
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 150000],
                'alasan_kasir' => 'Permintaan tawar harga paket.',
            ]);

        $tiket = PermintaanOtorisasi::firstOrFail();

        $this->actingAs($this->manajer, 'karyawan')
            ->post(route('manajer.persetujuan.tolak', $tiket->id_otorisasi), [
                'catatan_manajer' => 'Harga bahan keratin sedang naik, gunakan harga normal.',
            ])
            ->assertSessionHasNoErrors();

        $tiket->refresh();
        $this->assertSame(PermintaanOtorisasi::STATUS_DITOLAK, $tiket->status);
        $this->assertTrue($tiket->sedang_dihold);
        $this->assertEquals(200000, (float) $tiket->total_akhir);
        $this->assertEquals(200000, (float) $tiket->snapshot_keranjang['layanan'][0]['harga_aktif']);
        $this->assertSame('Harga bahan keratin sedang naik, gunakan harga normal.', $tiket->catatan_manajer);

        // Kasir melanjutkan pembayaran dengan harga normal Rp 200.000 -> Hold ditutup rapi
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'id_otorisasi' => $tiket->id_otorisasi,
                'keranjang_disinkronkan' => 1,
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'id_petugas' => $this->stylist->id_karyawan,
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 200000,
            ])
            ->assertRedirect(route('kasir.dashboard'));

        $this->assertFalse($tiket->fresh()->sedang_dihold);
        $this->assertEquals(200000, (float) $this->layananMahal->fresh()->harga_layanan);
    }

    public function test_kasus_3_proteksi_direct_override_validasi_role_rate_limit_dan_zero_session_hijacking(): void
    {
        RateLimiter::clear("direct-override:{$this->kasir->id_karyawan}:127.0.0.1");

        // 1. Kredensial benar tetapi role bukan Manajer -> ditolak pesan spesifik & sesi tetap Kasir Satu
        $resRole = $this->actingAs($this->kasir, 'karyawan')
            ->postJson(route('kasir.otorisasi.direct'), [
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 180000],
                'alasan_kasir' => 'Coba pakai akun sesama kasir.',
                'username_manajer' => 'kasir_dua',
                'password_manajer' => 'kasirdua123',
            ]);

        $resRole->assertStatus(422)
            ->assertJsonPath('message', 'Akun ini tidak memiliki wewenang Manajer.');
        $this->assertSame($this->kasir->id_karyawan, Auth::guard('karyawan')->id());

        // 2. Gagal 2x lagi masih 422, gagal ke-4 langsung mengunci (429), dan percobaan ke-5 (meski password benar) tetap terkunci 429
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($this->kasir, 'karyawan')->postJson(route('kasir.otorisasi.direct'), [
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 180000],
                'alasan_kasir' => 'Menebak password manajer.',
                'username_manajer' => 'manajer_pos',
                'password_manajer' => 'salah_terus',
            ])->assertStatus(422);
        }

        $this->actingAs($this->kasir, 'karyawan')->postJson(route('kasir.otorisasi.direct'), [
            'layanan_ids' => [$this->layananMahal->id_layanan],
            'harga_override_layanan' => [$this->layananMahal->id_layanan => 180000],
            'alasan_kasir' => 'Gagal keempat langsung memicu kunci.',
            'username_manajer' => 'manajer_pos',
            'password_manajer' => 'salah_terus',
        ])->assertStatus(429);

        $resKunci = $this->actingAs($this->kasir, 'karyawan')->postJson(route('kasir.otorisasi.direct'), [
            'layanan_ids' => [$this->layananMahal->id_layanan],
            'harga_override_layanan' => [$this->layananMahal->id_layanan => 180000],
            'alasan_kasir' => 'Percobaan saat sudah dikunci.',
            'username_manajer' => 'manajer_pos',
            'password_manajer' => 'manajer123',
        ]);

        $resKunci->assertStatus(429);
        $this->assertGreaterThan(0, $resKunci->json('detik_terkunci'));
        $this->assertSame($this->kasir->id_karyawan, Auth::guard('karyawan')->id());
    }

    public function test_kasus_4_ambang_batas_150rb_anti_bypass_dan_audit_log_mandiri_tanpa_mengubah_harga_master(): void
    {
        // A. Transaksi awal Rp 200.000 diturunkan ke Rp 50.000 tanpa approval -> WAJIB DITOLAK (tetap terbaca >= Rp 150.000)
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'keranjang_disinkronkan' => 1,
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'id_petugas' => $this->stylist->id_karyawan,
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 50000],
                'alasan_perubahan' => 'Coba turunkan di bawah 150 ribu.',
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 50000,
            ])
            ->assertSessionHasErrors('pembayaran');

        // B. Transaksi di bawah Rp 150.000 (Rp 120.000 -> Rp 100.000) langsung berhasil mandiri, harga master utuh, masuk log audit
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.bayar'), [
                'keranjang_disinkronkan' => 1,
                'layanan_ids' => [$this->layananMurah->id_layanan],
                'id_petugas' => $this->stylist->id_karyawan,
                'harga_override_layanan' => [$this->layananMurah->id_layanan => 100000],
                'alasan_perubahan' => 'Rambut pendek anak sekolah.',
                'metode_bayar' => 'Tunai',
                'nominal_uang' => 100000,
            ])
            ->assertRedirect(route('kasir.dashboard'));

        $this->assertEquals(120000, (float) $this->layananMurah->fresh()->harga_layanan);
        $this->assertDatabaseHas('log_perubahan_otorisasi', [
            'id_kasir' => $this->kasir->id_karyawan,
            'mekanisme_otorisasi' => LogPerubahanOtorisasi::MEKANISME_MANDIRI_BAWAH_AMBANG,
            'nilai_awal' => 120000,
            'nilai_akhir' => 100000,
            'alasan' => 'Rambut pendek anak sekolah.',
        ]);

        // Halaman Persetujuan & Audit Manajer merender log perubahan mandiri tersebut dengan baik
        $this->actingAs($this->manajer, 'karyawan')
            ->get(route('manajer.persetujuan', ['tab' => 'audit']))
            ->assertOk()
            ->assertSee('Rambut pendek anak sekolah.');
    }

    public function test_kasus_5_kedaluwarsa_otomatis_izin_katalog_setelah_satu_jam_dan_saat_kasir_logout(): void
    {
        $izin1 = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_UBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_PRODUK,
            'id_target' => $this->produk->id_produk,
            'label_target' => 'Produk: Vitamin Rambut',
            'alasan_kasir' => 'Koreksi stok fisik.',
        ]);
        $izin1->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        // Lewat 61 menit -> scope izin siap pakai otomatis mengabaikannya
        $this->travel(61)->minutes();
        $this->assertSame(0, PermintaanOtorisasi::izinKatalogSiapPakai($this->kasir->id_karyawan, PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK, PermintaanOtorisasi::AKSI_UBAH, $this->produk->id_produk)->count());
        $this->travelBack();

        // Buat izin baru lalu Kasir Logout -> otomatis berubah status menjadi Kedaluwarsa
        $izin2 = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_TAMBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_LAYANAN,
            'label_target' => 'Tambah Layanan Baru',
            'alasan_kasir' => 'Layanan musiman lebaran.',
        ]);
        $izin2->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        $this->actingAs($this->kasir, 'karyawan')->post(route('logout'))->assertRedirect(route('login'));
        $this->assertSame(PermintaanOtorisasi::STATUS_KEDALUWARSA, $izin2->fresh()->status);
    }

    public function test_kasus_6_abandoned_hold_dibatalkan_kasir_otomatis_ditarik_dari_antrean_manajer(): void
    {
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.otorisasi.hold'), [
                'layanan_ids' => [$this->layananMahal->id_layanan],
                'harga_override_layanan' => [$this->layananMahal->id_layanan => 160000],
                'alasan_kasir' => 'Pelanggan minta harga khusus grup.',
            ]);

        $tiket = PermintaanOtorisasi::firstOrFail();

        $this->actingAs($this->kasir, 'karyawan')
            ->delete(route('kasir.otorisasi.batal', $tiket->id_otorisasi))
            ->assertRedirect(route('kasir.dashboard'));

        $tiket->refresh();
        $this->assertSame(PermintaanOtorisasi::STATUS_DIBATALKAN, $tiket->status);
        $this->assertFalse($tiket->sedang_dihold);
        $this->assertSame(0, PermintaanOtorisasi::menungguPersetujuan()->count());

        // Jika Manajer menekan Setujui setelah pelanggan pulang/dibatalkan Kasir -> ditolak aman
        $this->actingAs($this->manajer, 'karyawan')
            ->post(route('manajer.persetujuan.setujui', $tiket->id_otorisasi))
            ->assertSessionHasErrors('persetujuan');
    }
}
