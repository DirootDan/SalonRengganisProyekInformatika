<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\Reservasi;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalonRengganisTest extends TestCase
{
    use RefreshDatabase;

    protected Layanan $layananRambut;
    protected Layanan $layananWajah;
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
            'harga_layanan'   => 60000,
        ]);

        $this->layananWajah = Layanan::create([
            'nama_layanan'    => 'Facial Glowing',
            'kategori'        => 'Perawatan',
            'jabatan_petugas' => 'Therapist',
            'harga_layanan'   => 120000,
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
     * Dua pelanggan memilih karyawan yang sama pada tanggal dan jam yang sama;
     * permintaan kedua wajib ditolak oleh server.
     */
    public function test_pencegahan_bentrok_jadwal_double_booking(): void
    {
        $besok = Carbon::tomorrow()->toDateString();

        // Pelanggan 1 berhasil memesan Stylist 1 pada jam 10:00
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
     * Transisi tidak sah seperti Selesai -> Dibatalkan atau Ditolak -> Diterima harus ditolak.
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

        // Transisi sah: Menunggu -> Diterima -> Selesai
        $this->assertTrue($reservasi->bolehTransisiKe(Reservasi::STATUS_DITERIMA));
        $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);
        $this->assertEquals(Reservasi::STATUS_DITERIMA, $reservasi->fresh()->status);

        $this->assertTrue($reservasi->bolehTransisiKe(Reservasi::STATUS_SELESAI));
        $reservasi->ubahStatus(Reservasi::STATUS_SELESAI);
        $this->assertEquals(Reservasi::STATUS_SELESAI, $reservasi->fresh()->status);

        // Transisi tidak sah: Selesai -> Dibatalkan wajib melempar DomainException
        $this->assertFalse($reservasi->bolehTransisiKe(Reservasi::STATUS_DIBATALKAN));
        $this->expectException(DomainException::class);
        $reservasi->ubahStatus(Reservasi::STATUS_DIBATALKAN);
    }

    /**
     * Skenario 3: Perhitungan Pelanggaran & Pemblokiran Otomatis (booking:evaluasi-hangus)
     * 5 reservasi Diterima yang melewati tenggat tanpa pelunasan akan memicu blokir 7 hari,
     * dan middleware cek.blokir menolak akses booking pelanggan tersebut.
     */
    public function test_evaluasi_hangus_dan_pemblokiran_otomatis_pelanggan(): void
    {
        $kebijakan = PengaturanKebijakan::aktif();
        $kebijakan->update([
            'batas_pelanggaran'    => 5,
            'lama_blokir_hari'     => 7,
            'toleransi_bayar_hari' => 1,
            'kebijakan_aktif'      => true,
        ]);

        // Buat 5 reservasi berstatus Diterima pada 3 hari lalu (sudah melewati H+1)
        for ($i = 0; $i < 5; $i++) {
            Reservasi::create([
                'tgl_booking'  => Carbon::today()->subDays(3)->toDateString(),
                'jam_booking'  => sprintf('%02d:00', 9 + $i),
                'status'       => Reservasi::STATUS_DITERIMA,
                'id_pelanggan' => $this->pelanggan1->id_pelanggan,
                'id_karyawan'  => $this->stylist1->id_karyawan,
            ]);
        }

        // Jalankan command evaluasi hangus (dan pastikan idempoten jika dijalankan 2x)
        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);
        $this->artisan('booking:evaluasi-hangus')->assertExitCode(0);

        $pelangganSegar = $this->pelanggan1->fresh();
        $this->assertEquals(5, $pelangganSegar->jumlah_pelanggaran);
        $this->assertNotNull($pelangganSegar->diblokir_sampai);
        $this->assertTrue($pelangganSegar->sedangDiblokir());

        // Pastikan ke-5 reservasi berstatus Hangus
        $this->assertEquals(5, Reservasi::where('status', Reservasi::STATUS_HANGUS)->count());

        // Pelanggan yang diblokir mencoba kirim POST booking -> ditolak oleh middleware cek.blokir
        $responBooking = $this->actingAs($pelangganSegar, 'pelanggan')
            ->post(route('pelanggan.booking.simpan'), [
                'tgl_booking' => Carbon::tomorrow()->toDateString(),
                'jam_booking' => '13:00',
                'layanan'     => [$this->layananRambut->id_layanan],
                'petugas'     => [
                    $this->layananRambut->id_layanan => $this->stylist1->id_karyawan,
                ],
            ]);

        $responBooking->assertRedirect(route('pelanggan.booking'));
        $responBooking->assertSessionHasErrors('blokir');
    }

    /**
     * Skenario 4: Pembukaan Blokir Manual oleh Manajer
     * Manajer membuka blokir pelanggan beserta alasan wajib -> pelanggaran di-reset ke 0,
     * tercatat di riwayat_pelanggaran, dan pelanggan dapat kembali melakukan booking.
     */
    public function test_manajer_membuka_blokir_pelanggan_secara_manual(): void
    {
        $this->pelanggan1->update([
            'jumlah_pelanggaran' => 5,
            'diblokir_sampai'    => now()->addDays(7),
        ]);

        $respon = $this->actingAs($this->manajer, 'karyawan')
            ->post(route('manajer.kebijakan.buka', $this->pelanggan1->id_pelanggan), [
                'alasan_buka' => 'Pelanggan sudah mengonfirmasi kendala darurat medis kepada pihak salon.',
            ]);

        $respon->assertRedirect();
        $respon->assertSessionHas('status');

        $pelangganSegar = $this->pelanggan1->fresh();
        $this->assertEquals(0, $pelangganSegar->jumlah_pelanggaran);
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
     * Skenario 5: Pembatasan Akses Antar Peran (Role Isolation) & Anti-IDOR
     */
    public function test_pembatasan_akses_antar_peran_dan_anti_idor(): void
    {
        // 1. Kasir tidak boleh mengakses halaman Manajer
        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('manajer.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->kasir, 'karyawan')
            ->get(route('manajer.kebijakan'))
            ->assertStatus(403);

        // 2. Stylist tidak boleh mengakses halaman Kasir maupun Therapist
        $this->actingAs($this->stylist1, 'karyawan')
            ->get(route('kasir.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->stylist1, 'karyawan')
            ->get(route('therapist.dashboard'))
            ->assertStatus(403);

        // 3. Anti-IDOR: Pelanggan 2 tidak boleh membatalkan reservasi milik Pelanggan 1
        $reservasiP1 = Reservasi::create([
            'tgl_booking'  => Carbon::now()->addDays(2)->toDateString(),
            'jam_booking'  => '15:00',
            'status'       => Reservasi::STATUS_MENUNGGU,
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'id_karyawan'  => $this->stylist1->id_karyawan,
        ]);

        $reservasiP1->layanan()->attach($this->layananRambut->id_layanan, [
            'id_karyawan'    => $this->stylist1->id_karyawan,
            'harga_saat_ini' => 60000,
            'persen_komisi'  => 15,
            'nominal_komisi' => 9000,
            'status_layanan' => 'Menunggu',
        ]);

        $this->actingAs($this->pelanggan2, 'pelanggan')
            ->post(route('pelanggan.booking.batal', $reservasiP1->id_reservasi))
            ->assertStatus(403);

        // 4. Anti-IDOR: Stylist 2 tidak boleh menandai selesai layanan yang ditugaskan kepada Stylist 1
        $reservasiP1->ubahStatus(Reservasi::STATUS_DITERIMA);

        $this->actingAs($this->stylist2, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasiP1->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertStatus(403);

        // Sedangkan Stylist 1 yang bertugas dapat menandai selesai
        $this->actingAs($this->stylist1, 'karyawan')
            ->post(route('stylist.layanan.selesai', [$reservasiP1->id_reservasi, $this->layananRambut->id_layanan]))
            ->assertRedirect();

        $this->assertDatabaseHas('detail_reservasi', [
            'id_reservasi'   => $reservasiP1->id_reservasi,
            'id_layanan'     => $this->layananRambut->id_layanan,
            'id_karyawan'    => $this->stylist1->id_karyawan,
            'status_layanan' => 'Selesai',
        ]);
    }
}
