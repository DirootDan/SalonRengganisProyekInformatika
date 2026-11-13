<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
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
