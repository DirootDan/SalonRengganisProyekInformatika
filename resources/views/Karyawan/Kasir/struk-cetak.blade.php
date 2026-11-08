@php
    $ukuran = $struk->ukuranTernormalisasi();
    $nomorNota = str_pad($transaksi->id_reservasi, 4, '0', STR_PAD_LEFT);
    $rupiah = fn ($angka) => 'Rp '.number_format((float) $angka, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk #INV-{{ $nomorNota }}</title>
    @vite(['resources/css/Karyawan/Kasir/struk-cetak.css', 'resources/js/Karyawan/Kasir/struk-cetak.js'])
</head>
<body class="struk-cetak kertas-{{ $ukuran }}" data-ukuran="{{ $ukuran }}">
    @if (session('status'))
        <div class="notif-sukses-cetak">✅ {{ session('status') }}</div>
    @endif

    <div class="struk-kepala">
        @if ($struk->tampil_logo)
            <div aria-hidden="true" style="font-size: 36px;">🌸</div>
        @endif
        <div class="struk-kepala-teks {{ $struk->tampil_logo ? '' : 'pusat' }}">
            <h3 class="struk-nama">{{ $struk->nama_salon }}</h3>
            @if ($struk->slogan)<div class="kecil">{{ $struk->slogan }}</div>@endif
            <div class="kecil">{{ $struk->alamat }}</div>
            <div class="kecil">{{ $struk->telepon }}</div>
            @if ($struk->tampil_sosmed && $struk->instagram)
                <div class="kecil">{{ $struk->instagram }}</div>
            @endif
        </div>
    </div>

    @if ($struk->teks_header)
        <div class="pusat kecil">{{ $struk->teks_header }}</div>
        <div class="garis"></div>
    @endif

    <div class="baris"><span>#INV-{{ $nomorNota }}</span><span>{{ $transaksi->created_at->format('d/m/y H:i') }}</span></div>
    <div class="baris"><span>Pelanggan:</span><span>{{ \Illuminate\Support\Str::limit($transaksi->reservasi->pelanggan->nama_cust ?? 'Umum', 15, '') }}</span></div>
    @if ($struk->tampil_kasir)
        <div class="baris"><span>Kasir:</span><span>{{ $transaksi->kasir->nama_staff ?? '-' }}</span></div>
    @endif

    <div class="garis"></div>

    @foreach ($transaksi->reservasi->layanan as $l)
        <div class="butir">
            <div>{{ $l->nama_layanan }}</div>
            <div class="baris">
                <span class="petugas">
                    @if ($struk->tampil_petugas && $l->pivot->id_karyawan && isset($petugasMap[$l->pivot->id_karyawan]))
                        {{ $l->jabatan_petugas }}: {{ $petugasMap[$l->pivot->id_karyawan] }}
                    @endif
                </span>
                <span>{{ $rupiah($l->pivot->harga_saat_ini) }}</span>
            </div>
        </div>
    @endforeach

    @foreach ($transaksi->produk as $p)
        <div class="butir">
            <div>{{ $p->nama_produk }}</div>
            <div class="baris">
                <span class="petugas">{{ $p->pivot->jumlah }} x {{ $rupiah($p->pivot->harga_saat_ini) }}</span>
                <span>{{ $rupiah($p->pivot->harga_saat_ini * (int) $p->pivot->jumlah) }}</span>
            </div>
        </div>
    @endforeach

    <div class="garis"></div>

    @if ($transaksi->kode_promo)
        <div class="baris"><span>Promo: {{ $transaksi->kode_promo }}</span><span>-{{ $rupiah($transaksi->diskon) }}</span></div>
    @endif
    <div class="baris total-besar"><span>TOTAL</span><span>{{ $rupiah($transaksi->total_bayar) }}</span></div>
    <div class="baris"><span>Bayar ({{ $transaksi->metode_bayar }})</span><span>{{ $rupiah($transaksi->nominal_uang) }}</span></div>
    <div class="baris"><span>Kembalian</span><span>{{ $rupiah($transaksi->kembalian) }}</span></div>

    <div class="garis"></div>

    @if ($struk->teks_footer)
        <div class="pusat kecil">{{ $struk->teks_footer }}</div>
    @endif

    <div class="aksi-cetak">
        <button type="button" id="btnCetakUlang">🖨️ Cetak Ulang</button>
        <a href="{{ route('kasir.dashboard') }}">← Kembali ke Kasir</a>
    </div>
</body>
</html>
