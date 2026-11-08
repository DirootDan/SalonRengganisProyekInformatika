<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Struk #INV-{{ str_pad($transaksi->id_reservasi, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: {
                    {
                    $struk->ukuranTernormalisasi()==='58mm' ? '58mm': '80mm'
                }
            }

            auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;

            width: {
                    {
                    $struk->ukuranTernormalisasi()==='58mm' ? '58mm': '80mm'
                }
            }

            ;
            margin: 0 auto;
            padding: 8px;
            box-sizing: border-box;
        }

        .pusat {
            text-align: center;
        }

        .garis {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .baris {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .kecil {
            font-size: 10px;
        }

        .aksi-cetak {
            margin-top: 14px;
            text-align: center;
        }

        .aksi-cetak a,
        .aksi-cetak button {
            display: inline-block;
            margin: 4px;
            padding: 8px 14px;
            font-size: 12px;
            border: 1px solid #000;
            background: #fff;
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .notif-sukses-cetak {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 11px;
            margin-bottom: 10px;
            text-align: center;
        }

        @media print {

            .aksi-cetak,
            .notif-sukses-cetak {
                display: none;
            }
        }
    </style>
</head>

<body>
    @if (session('status'))
    <div class="notif-sukses-cetak">✅ {{ session('status') }}</div>
    @endif

    @if ($struk->tampil_logo)
    <div class="pusat"><strong>{{ $struk->nama_salon }}</strong></div>
    @endif
    @if ($struk->slogan)<div class="pusat kecil">{{ $struk->slogan }}</div>@endif
    <div class="pusat kecil">{{ $struk->alamat }}</div>
    <div class="pusat kecil">{{ $struk->telepon }}</div>
    @if ($struk->tampil_sosmed && $struk->instagram)
    <div class="pusat kecil">{{ $struk->instagram }}</div>
    @endif

    <div class="garis"></div>

    @if ($struk->teks_header)
    <div class="pusat kecil">{{ $struk->teks_header }}</div>
    <div class="garis"></div>
    @endif

    <div>No. Nota: INV-{{ str_pad($transaksi->id_reservasi, 4, '0', STR_PAD_LEFT) }}</div>
    <div>Tanggal: {{ $transaksi->created_at->format('d/m/Y H:i') }} WIB</div>
    <div>Pelanggan: {{ $transaksi->reservasi->pelanggan->nama_cust ?? 'Umum' }}</div>
    @if ($struk->tampil_kasir)
    <div>Kasir: {{ $transaksi->kasir->nama_staff ?? '-' }}</div>
    @endif

    <div class="garis"></div>

    @foreach ($transaksi->reservasi->layanan as $l)
    <div class="baris">
        <span>
            {{ $l->nama_layanan }}
            @if ($struk->tampil_petugas && $l->pivot->id_karyawan && isset($petugasMap[$l->pivot->id_karyawan]))
            <br><span class="kecil">({{ $l->jabatan_petugas }}: {{ $petugasMap[$l->pivot->id_karyawan] }})</span>
            @endif
        </span>
        <span>Rp {{ number_format((float) $l->pivot->harga_saat_ini, 0, ',', '.') }}</span>
    </div>
    @endforeach

    @foreach ($transaksi->produk as $p)
    <div class="baris">
        <span>{{ $p->nama_produk }} x{{ $p->pivot->jumlah }}</span>
        <span>Rp {{ number_format((float) $p->pivot->harga_saat_ini * (int) $p->pivot->jumlah, 0, ',', '.') }}</span>
    </div>
    @endforeach

    @if ($transaksi->kode_promo)
    <div class="baris">
        <span>Promo: {{ $transaksi->kode_promo }}</span>
        <span>-Rp {{ number_format((float) $transaksi->diskon, 0, ',', '.') }}</span>
    </div>
    @endif

    <div class="garis"></div>

    <div class="baris"><strong>Total</strong><strong>Rp {{ number_format((float) $transaksi->total_bayar, 0, ',', '.') }}</strong></div>
    <div class="baris"><span>Bayar ({{ $transaksi->metode_bayar }})</span><span>Rp {{ number_format((float) $transaksi->nominal_uang, 0, ',', '.') }}</span></div>
    <div class="baris"><span>Kembalian</span><span>Rp {{ number_format((float) $transaksi->kembalian, 0, ',', '.') }}</span></div>

    <div class="garis"></div>

    @if ($struk->teks_footer)<div class="pusat kecil">{{ $struk->teks_footer }}</div>@endif

    <div class="aksi-cetak">
        <button type="button" onclick="window.print()">🖨️ Cetak Ulang</button>
        <a href="{{ route('kasir.dashboard') }}">← Kembali ke Kasir</a>
    </div>

    <script>
        window.onload = () => window.print();
    </script>
</body>

</html>