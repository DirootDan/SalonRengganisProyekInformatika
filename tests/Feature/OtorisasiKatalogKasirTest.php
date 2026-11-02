<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\LogPerubahanOtorisasi;
use App\Models\Pelanggan;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use App\Models\Reservasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtorisasiKatalogKasirTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $manajer;

    private Karyawan $kasir;

    private Layanan $layanan;

    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manajer = Karyawan::create([
            'nama_staff' => 'Manajer Katalog',
            'username' => 'manajer_kat',
            'email' => 'manajer.kat@salon.test',
            'password' => Hash::make('manajer123'),
            'role_jabatan' => Karyawan::JABATAN_MANAJER,
            'aktif' => true,
        ]);

        $this->kasir = Karyawan::create([
            'nama_staff' => 'Kasir Katalog',
            'username' => 'kasir_kat',
            'email' => 'kasir.kat@salon.test',
            'password' => Hash::make('kasir123'),
            'role_jabatan' => Karyawan::JABATAN_KASIR,
            'aktif' => true,
        ]);

        $this->layanan = Layanan::create([
            'nama_layanan' => 'Creambath Ginseng',
            'kategori' => 'Perawatan Rambut',
            'jabatan_petugas' => 'Stylist',
            'durasi_menit' => 60,
            'jeda_menit' => 15,
            'harga_layanan' => 110000,
        ]);

        $this->produk = Produk::create([
            'nama_produk' => 'Serum Keratin 50ml',
            'stok_produk' => 15,
            'harga_produk' => 85000,
        ]);
    }

    public function test_kasir_ditolak_mengubah_layanan_dan_produk_tanpa_persetujuan_manajer(): void
    {
        $aktor = $this->actingAs($this->kasir, 'karyawan');

        $aktor->get(route('kasir.layanan'))
            ->assertOk()
            ->assertSee('Minta Akses Tambah Layanan')
            ->assertSee('Minta Edit')
            ->assertSee('Minta Hapus');

        $aktor->get(route('kasir.produk'))
            ->assertOk()
            ->assertSee('Minta Akses Tambah Produk')
            ->assertSee('Minta Edit')
            ->assertSee('Minta Hapus');

        $aktor->post(route('kasir.layanan.store'), [
            'nama_layanan' => 'Layanan Ilegal',
            'kategori' => 'Perawatan Rambut',
            'durasi_menit' => 45,
            'jeda_menit' => 15,
            'harga_layanan' => 90000,
        ])->assertSessionHasErrors('layanan');

        $aktor->put(route('kasir.layanan.update', $this->layanan), [
            'nama_layanan' => 'Creambath Ginseng Diubah',
            'kategori' => 'Perawatan Rambut',
            'durasi_menit' => 60,
            'jeda_menit' => 15,
            'harga_layanan' => 50000,
        ])->assertSessionHasErrors('layanan');

        $aktor->delete(route('kasir.layanan.destroy', $this->layanan))
            ->assertSessionHasErrors('layanan');

        $aktor->post(route('kasir.produk.store'), [
            'nama_produk' => 'Produk Ilegal',
            'stok_produk' => 10,
            'harga_produk' => 50000,
        ])->assertSessionHasErrors('produk');

        $aktor->put(route('kasir.produk.update', $this->produk), [
            'nama_produk' => 'Serum Keratin 50ml',
            'stok_produk' => 99,
            'harga_produk' => 25000,
        ])->assertSessionHasErrors('produk');

        $aktor->delete(route('kasir.produk.destroy', $this->produk))
            ->assertSessionHasErrors('produk');

        $this->assertEquals(110000, (float) $this->layanan->fresh()->harga_layanan);
        $this->assertEquals(15, (int) $this->produk->fresh()->stok_produk);
    }

    public function test_alur_lengkap_request_akses_1x_pakai_ditolak_lalu_disetujui_dan_otomatis_terkunci_setelah_dipakai(): void
    {
        // Alasan kurang dari 5 karakter wajib ditolak validasi
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.otorisasi-katalog.store'), [
                'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                'jenis_aksi' => PermintaanOtorisasi::AKSI_UBAH,
                'id_target' => $this->produk->id_produk,
                'alasan_kasir' => 'cek',
            ])
            ->assertSessionHasErrors('alasan_kasir');

        // 1. Kasir mengajukan izin ubah stok produk
        $this->actingAs($this->kasir, 'karyawan')
            ->from(route('kasir.produk'))
            ->post(route('kasir.otorisasi-katalog.store'), [
                'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                'jenis_aksi' => PermintaanOtorisasi::AKSI_UBAH,
                'id_target' => $this->produk->id_produk,
                'alasan_kasir' => 'Barang datang dari supplier 10 botol.',
            ])
            ->assertRedirect(route('kasir.produk'));

        $tiketPertama = PermintaanOtorisasi::firstOrFail();

        // 2. Manajer menolak karena faktur belum dilampirkan
        $this->actingAs($this->manajer, 'karyawan')
            ->from(route('manajer.persetujuan'))
            ->post(route('manajer.persetujuan.tolak', $tiketPertama->id_otorisasi), [
                'catatan_manajer' => 'Sebutkan nomor surat jalan supplier pada alasan.',
            ])
            ->assertRedirect(route('manajer.persetujuan'));

        // Kasir melihat badge merah & alasan penolakan Manajer di halaman Produk, dan tetap belum bisa edit
        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.produk'))
            ->assertOk()
            ->assertSee('Request Ditolak Manajer')
            ->assertSee('Sebutkan nomor surat jalan supplier pada alasan.');

        $this->actingAs($this->kasir, 'karyawan')
            ->put(route('kasir.produk.update', $this->produk), [
                'nama_produk' => 'Serum Keratin 50ml',
                'stok_produk' => 25,
                'harga_produk' => 85000,
            ])
            ->assertSessionHasErrors('produk');

        // 3. Kasir mengajukan ulang dengan alasan lengkap -> Manajer menyetujui
        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.otorisasi-katalog.store'), [
                'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                'jenis_aksi' => PermintaanOtorisasi::AKSI_UBAH,
                'id_target' => $this->produk->id_produk,
                'alasan_kasir' => 'Restok 10 botol sesuai Surat Jalan #SJ-889.',
            ]);

        $tiketKedua = PermintaanOtorisasi::latest('id_otorisasi')->firstOrFail();
        $this->actingAs($this->manajer, 'karyawan')
            ->from(route('manajer.persetujuan'))
            ->post(route('manajer.persetujuan.setujui', $tiketKedua->id_otorisasi));

        // Halaman produk kini menampilkan tombol Edit Sekarang (1x) khusus produk tersebut
        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('kasir.produk'))
            ->assertOk()
            ->assertSee('Edit Sekarang (1x)');

        // Namun produk/layanan lain atau aksi Hapus tetap terkunci!
        $this->actingAs($this->kasir, 'karyawan')
            ->delete(route('kasir.produk.destroy', $this->produk))
            ->assertSessionHasErrors('produk');

        // 4. Kasir mengeksekusi 1x update -> berhasil, tercatat di Audit Log, dan langsung terkunci kembali
        $this->actingAs($this->kasir, 'karyawan')
            ->from(route('kasir.produk'))
            ->put(route('kasir.produk.update', $this->produk), [
                'nama_produk' => 'Serum Keratin 50ml',
                'stok_produk' => 25,
                'harga_produk' => 88000,
            ])
            ->assertRedirect(route('kasir.produk'))
            ->assertSessionHas('status');

        $this->assertEquals(25, (int) $this->produk->fresh()->stok_produk);
        $this->assertSame(PermintaanOtorisasi::STATUS_DIGUNAKAN, $tiketKedua->fresh()->status);

        $this->assertDatabaseHas('log_perubahan_otorisasi', [
            'id_otorisasi' => $tiketKedua->id_otorisasi,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
            'jenis_perubahan' => LogPerubahanOtorisasi::JENIS_UBAH_KATALOG,
            'nilai_awal' => 85000,
            'nilai_akhir' => 88000,
        ]);

        // 5. Percobaan perubahan kedua langsung ditolak karena kuota 1x aksi sudah habis!
        $this->actingAs($this->kasir, 'karyawan')
            ->put(route('kasir.produk.update', $this->produk), [
                'nama_produk' => 'Serum Keratin 50ml',
                'stok_produk' => 30,
                'harga_produk' => 90000,
            ])
            ->assertSessionHasErrors('produk');
    }

    public function test_galat_validasi_form_atau_kendala_reservasi_aktif_tidak_menghanguskan_kuota_1x_pakai(): void
    {
        // A. Galat validasi form pada Edit Layanan tidak menghanguskan izin 1x pakai
        $tiketUbah = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_UBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_LAYANAN,
            'id_target' => $this->layanan->id_layanan,
            'label_target' => 'Ubah Layanan: Creambath Ginseng',
            'alasan_kasir' => 'Penyesuaian harga katalog baru.',
        ]);
        $tiketUbah->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        // Kasir salah ketik harga negatif (-10000) -> gagal validasi FormRequest
        $this->actingAs($this->kasir, 'karyawan')
            ->put(route('kasir.layanan.update', $this->layanan), [
                'nama_layanan' => 'Creambath Ginseng',
                'kategori' => 'Perawatan Rambut',
                'durasi_menit' => 60,
                'jeda_menit' => 15,
                'harga_layanan' => -10000,
            ])
            ->assertSessionHasErrors('harga_layanan');

        // Izin wajib MASIH berstatus Disetujui (tidak hangus!)
        $this->assertSame(PermintaanOtorisasi::STATUS_DISETUJUI, $tiketUbah->fresh()->status);

        // Kasir memperbaiki input dan menyimpan ulang -> berhasil dan barulah berubah menjadi Digunakan
        $this->actingAs($this->kasir, 'karyawan')
            ->put(route('kasir.layanan.update', $this->layanan), [
                'nama_layanan' => 'Creambath Ginseng Royal',
                'kategori' => 'Perawatan Rambut',
                'durasi_menit' => 60,
                'jeda_menit' => 15,
                'harga_layanan' => 125000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PermintaanOtorisasi::STATUS_DIGUNAKAN, $tiketUbah->fresh()->status);
        $this->assertSame('Creambath Ginseng Royal', $this->layanan->fresh()->nama_layanan);

        // B. Gagal hapus karena masih ada reservasi aktif -> DB::transaction rollback, izin tidak hangus
        $pelanggan = Pelanggan::create([
            'nama_cust' => 'Sari',
            'username' => 'sari_uji',
            'email' => 'sari@uji.test',
            'no_telp' => '081234567899',
            'password' => Hash::make('secret123'),
        ]);
        $resAktif = Reservasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_karyawan' => $this->kasir->id_karyawan,
            'tgl_booking' => now()->toDateString(),
            'jam_booking' => '13:00',
            'jam_selesai' => '14:00',
            'total_durasi_menit' => 60,
            'status' => Reservasi::STATUS_DITERIMA,
        ]);
        $resAktif->layanan()->attach($this->layanan->id_layanan, [
            'jam_mulai' => '13:00',
            'jam_selesai' => '14:00',
            'jam_selesai_blokir' => '14:15',
            'durasi_saat_ini' => 60,
            'jeda_saat_ini' => 15,
            'harga_saat_ini' => 125000,
            'persen_komisi' => 15,
            'nominal_komisi' => 18750,
            'status_layanan' => Reservasi::STATUS_MENUNGGU,
        ]);

        $tiketHapus = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_HAPUS,
            'tipe_target' => PermintaanOtorisasi::TARGET_LAYANAN,
            'id_target' => $this->layanan->id_layanan,
            'label_target' => 'Hapus Layanan: Creambath Ginseng Royal',
            'alasan_kasir' => 'Menu akan dihentikan.',
        ]);
        $tiketHapus->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        $this->actingAs($this->kasir, 'karyawan')
            ->delete(route('kasir.layanan.destroy', $this->layanan))
            ->assertSessionHasErrors('layanan');

        $this->assertSame(PermintaanOtorisasi::STATUS_DISETUJUI, $tiketHapus->fresh()->status);
    }

    public function test_pembatalan_akses_oleh_kasir_dan_auto_expire_1_jam_serta_saat_logout(): void
    {
        // 1. Pembatalan oleh Kasir ("Batalkan & Kunci Akses") langsung mengunci kembali akses
        $tiketTambah = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_TAMBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_LAYANAN,
            'label_target' => 'Tambah Layanan Baru',
            'alasan_kasir' => 'Rencana tambah menu hair mask.',
        ]);
        $tiketTambah->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        $this->actingAs($this->kasir, 'karyawan')
            ->delete(route('kasir.otorisasi-katalog.destroy', $tiketTambah->id_otorisasi))
            ->assertSessionHas('status');

        $this->assertSame(PermintaanOtorisasi::STATUS_DIBATALKAN, $tiketTambah->fresh()->status);

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.layanan.store'), [
                'nama_layanan' => 'Hair Mask Baru',
                'kategori' => 'Perawatan Rambut',
                'durasi_menit' => 60,
                'jeda_menit' => 15,
                'harga_layanan' => 95000,
            ])
            ->assertSessionHasErrors('layanan');

        // 2. Auto-expire lewat dari 60 menit (1 jam)
        $tiketJam = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_TAMBAH,
            'tipe_target' => PermintaanOtorisasi::TARGET_PRODUK,
            'label_target' => 'Tambah Produk Baru',
            'alasan_kasir' => 'Tambah produk tonic baru.',
        ]);
        $tiketJam->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        $this->travel(61)->minutes();

        $this->actingAs($this->kasir, 'karyawan')
            ->post(route('kasir.produk.store'), [
                'nama_produk' => 'Hair Tonic Ginseng',
                'stok_produk' => 10,
                'harga_produk' => 60000,
            ])
            ->assertSessionHasErrors('produk');

        $this->assertSame(PermintaanOtorisasi::STATUS_KEDALUWARSA, $tiketJam->fresh()->status);
        $this->travelBack();

        // 3. Auto-expire saat Kasir Logout
        $tiketLogout = PermintaanOtorisasi::create([
            'id_kasir' => $this->kasir->id_karyawan,
            'konteks' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_HAPUS,
            'tipe_target' => PermintaanOtorisasi::TARGET_PRODUK,
            'id_target' => $this->produk->id_produk,
            'label_target' => 'Hapus Produk: Serum Keratin 50ml',
            'alasan_kasir' => 'Produk sudah discontinue.',
        ]);
        $tiketLogout->setujui($this->manajer->id_karyawan, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER);

        $this->actingAs($this->kasir, 'karyawan')->post(route('logout'))->assertRedirect(route('login'));
        $this->assertSame(PermintaanOtorisasi::STATUS_KEDALUWARSA, $tiketLogout->fresh()->status);
    }
}
