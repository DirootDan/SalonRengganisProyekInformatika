<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Pelanggan;
use App\Models\Reservasi;
use Illuminate\Support\Facades\DB;

class ManajerController extends Controller
{
    public function dashboard()
    {
        $totalKaryawan  = Karyawan::count();
        $totalLayanan   = Layanan::count();
        $totalProduk    = DB::table('produk')->count();
        $totalPelanggan = Pelanggan::count();
        $totalReservasi = Reservasi::count();
        $reservasiBaru  = Reservasi::with('layanan')
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->limit(5)
            ->get();

        // Ambil nama pelanggan untuk ringkasan reservasi terbaru
        $pelangganMap = Pelanggan::whereIn('id_pelanggan', $reservasiBaru->pluck('id_pelanggan'))
            ->pluck('nama_cust', 'id_pelanggan');

        return view('Manajer.dashboard', compact(
            'totalKaryawan',
            'totalLayanan',
            'totalProduk',
            'totalPelanggan',
            'totalReservasi',
            'reservasiBaru',
            'pelangganMap'
        ));
    }

    public function karyawan()
    {
        $karyawan = Karyawan::orderBy('role_jabatan')->orderBy('nama_staff')->get();

        return view('Manajer.karyawan', compact('karyawan'));
    }

    public function layanan()
    {
        $layanan = Layanan::orderBy('nama_layanan')->get();

        return view('Manajer.layanan', compact('layanan'));
    }

    public function produk()
    {
        $produk = DB::table('produk')->orderBy('nama_produk')->get();

        // Data contoh apabila tabel produk masih kosong agar tampilan tetap terlihat jelas
        if ($produk->isEmpty()) {
            $produk = collect([
                (object) ['id_produk' => 1, 'nama_produk' => 'Vitamin Rambut Keratin 100ml', 'stok_produk' => 24, 'harga_produk' => 65000],
                (object) ['id_produk' => 2, 'nama_produk' => 'Masker Rambut Argan Oil 250g', 'stok_produk' => 15, 'harga_produk' => 95000],
                (object) ['id_produk' => 3, 'nama_produk' => 'Serum Wajah Brightening 30ml', 'stok_produk' => 8,  'harga_produk' => 120000],
                (object) ['id_produk' => 4, 'nama_produk' => 'Shampo Anti-Dandruff Salon 500ml', 'stok_produk' => 4, 'harga_produk' => 85000],
            ]);
        }

        return view('Manajer.produk', compact('produk'));
    }

    public function promo()
    {
        // Data pratinjau promo salon
        $promo = collect([
            (object) [
                'id_promo'     => 1,
                'kode_promo'   => 'CANTIK20',
                'nama_promo'   => 'Diskon Perawatan Akhir Pekan',
                'tipe_diskon'  => 'Persentase',
                'nilai_diskon' => 20,
                'min_transaksi'=> 150000,
                'berlaku_sampai'=> '2026-12-31',
                'status'       => 'Aktif',
            ],
            (object) [
                'id_promo'     => 2,
                'kode_promo'   => 'MEMBERBARU',
                'nama_promo'   => 'Potongan Pelanggan Baru',
                'tipe_diskon'  => 'Nominal',
                'nilai_diskon' => 25000,
                'min_transaksi'=> 100000,
                'berlaku_sampai'=> '2026-11-30',
                'status'       => 'Aktif',
            ],
            (object) [
                'id_promo'     => 3,
                'kode_promo'   => 'SPESIALGAJIAN',
                'nama_promo'   => 'Paket Hemat Creambath & Facial',
                'tipe_diskon'  => 'Persentase',
                'nilai_diskon' => 15,
                'min_transaksi'=> 200000,
                'berlaku_sampai'=> '2026-10-01',
                'status'       => 'Berakhir',
            ],
        ]);

        return view('Manajer.promo', compact('promo'));
    }

    public function laporan()
    {
        $reservasi = Reservasi::with('layanan')
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->get();

        $pelangganMap = Pelanggan::whereIn('id_pelanggan', $reservasi->pluck('id_pelanggan'))
            ->pluck('nama_cust', 'id_pelanggan');

        return view('Manajer.laporan', compact('reservasi', 'pelangganMap'));
    }

    public function metodePembayaran()
    {
        $metode = collect([
            (object) [
                'id_metode' => 1,
                'nama'      => 'Tunai (Cash)',
                'kategori'  => 'Tunai',
                'nomor_rek' => 'Pembayaran langsung di meja kasir',
                'atas_nama' => 'Kasir Salon Rengganis',
                'ikon'      => '💵',
                'aktif'     => true,
            ],
            (object) [
                'id_metode' => 2,
                'nama'      => 'QRIS All Payment',
                'kategori'  => 'Dompet Digital / QR',
                'nomor_rek' => 'NMID: ID102003004005',
                'atas_nama' => 'Salon Rengganis Official',
                'ikon'      => '📱',
                'aktif'     => true,
            ],
            (object) [
                'id_metode' => 3,
                'nama'      => 'Transfer Bank BCA',
                'kategori'  => 'Transfer Bank',
                'nomor_rek' => '8420-9912-34',
                'atas_nama' => 'PT Rengganis Cantika',
                'ikon'      => '🏦',
                'aktif'     => true,
            ],
            (object) [
                'id_metode' => 4,
                'nama'      => 'Transfer Bank Mandiri',
                'kategori'  => 'Transfer Bank',
                'nomor_rek' => '137-00-9821345-6',
                'atas_nama' => 'PT Rengganis Cantika',
                'ikon'      => '🏦',
                'aktif'     => true,
            ],
            (object) [
                'id_metode' => 5,
                'nama'      => 'Kartu Debit / Kredit (EDC)',
                'kategori'  => 'Mesin EDC',
                'nomor_rek' => 'Terminal EDC BCA & Mandiri',
                'atas_nama' => 'Salon Rengganis',
                'ikon'      => '💳',
                'aktif'     => false,
            ],
        ]);

        return view('Manajer.metode-pembayaran', compact('metode'));
    }

    public function struk()
    {
        return view('Manajer.struk');
    }

    public function kasirPos()
    {
        $layanan = Layanan::orderBy('nama_layanan')->get();
        $produk  = DB::table('produk')->orderBy('nama_produk')->get();

        if ($produk->isEmpty()) {
            $produk = collect([
                (object) ['id_produk' => 1, 'nama_produk' => 'Vitamin Rambut Keratin 100ml', 'stok_produk' => 24, 'harga_produk' => 65000],
                (object) ['id_produk' => 2, 'nama_produk' => 'Masker Rambut Argan Oil 250g', 'stok_produk' => 15, 'harga_produk' => 95000],
                (object) ['id_produk' => 3, 'nama_produk' => 'Serum Wajah Brightening 30ml', 'stok_produk' => 8,  'harga_produk' => 120000],
                (object) ['id_produk' => 4, 'nama_produk' => 'Shampo Anti-Dandruff Salon 500ml', 'stok_produk' => 4, 'harga_produk' => 85000],
            ]);
        }

        $reservasiPending = Reservasi::with('layanan')
            ->where('status', 'pending')
            ->latest('tgl_booking')
            ->get();

        $pelangganMap = Pelanggan::whereIn('id_pelanggan', $reservasiPending->pluck('id_pelanggan'))
            ->pluck('nama_cust', 'id_pelanggan');

        $petugas = Karyawan::whereIn('role_jabatan', ['Stylist', 'Therapist'])
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan']);

        return view('Manajer.kasir', compact('layanan', 'produk', 'reservasiPending', 'pelangganMap', 'petugas'));
    }
}
