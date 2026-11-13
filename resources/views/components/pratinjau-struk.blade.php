@props([
    'struk',
    'namaKasir' => 'Kasir Salon',
])

<div class="pratinjau-wadah">
    <span class="label-pratinjau" id="labelPratinjauStruk">
        {{ $struk->labelPratinjau() }}
    </span>

    <div class="kertas-struk kertas-{{ $struk->ukuranTernormalisasi() }}" id="kertasStruk" data-ukuran="{{ $struk->ukuranTernormalisasi() }}">
        <div class="struk-kepala">
            <div class="struk-logo {{ $struk->tampil_logo ? '' : 'elemen-tersembunyi' }}" id="prevLogo">🌸</div>
            <div class="struk-judul" id="prevNamaSalon">{{ $struk->nama_salon }}</div>
            <div class="struk-sub" id="prevSlogan">{{ $struk->slogan }}</div>
            <div class="struk-alamat" id="prevAlamat">{{ $struk->alamat }}</div>
            <div class="struk-telp">Telp: <span id="prevTelp">{{ $struk->telepon }}</span></div>
            <div class="struk-header-tambahan {{ empty($struk->teks_header) ? 'elemen-tersembunyi' : '' }}" id="prevHeader">{{ $struk->teks_header }}</div>
        </div>

        <div class="struk-garis">----------------------------------------</div>

        <div class="struk-meta">
            <div class="baris-struk"><span>No. Nota</span><span>#INV-0024</span></div>
            <div class="baris-struk"><span>Tanggal</span><span>{{ now()->format('d/m/Y H:i') }}</span></div>
            <div class="baris-struk"><span>Pelanggan</span><span>Rina Anggraini</span></div>
            <div class="baris-struk {{ $struk->tampil_kasir ? '' : 'elemen-tersembunyi' }}" id="prevBarisKasir">
                <span>Kasir</span>
                <span>{{ $namaKasir }}</span>
            </div>
        </div>

        <div class="struk-garis">----------------------------------------</div>

        <div class="struk-item-list">
            <div class="struk-item">
                <div class="baris-struk">
                    <strong>Creambath</strong>
                    <span>Rp 80.000</span>
                </div>
                <small class="prev-petugas {{ $struk->tampil_petugas ? '' : 'elemen-tersembunyi' }}">Stylist: Sinta Maharani</small>
            </div>

            <div class="struk-item">
                <div class="baris-struk">
                    <strong>Facial</strong>
                    <span>Rp 120.000</span>
                </div>
                <small class="prev-petugas {{ $struk->tampil_petugas ? '' : 'elemen-tersembunyi' }}">Therapist: Dewi Lestari</small>
            </div>
        </div>

        <div class="struk-garis">----------------------------------------</div>

        <div class="struk-total-area">
            <div class="baris-struk"><span>Subtotal</span><span>Rp 200.000</span></div>
            <div class="baris-struk struk-grand-total">
                <span>TOTAL BAYAR</span>
                <span>Rp 200.000</span>
            </div>
            <div class="baris-struk"><span>Metode (QRIS)</span><span>Rp 200.000</span></div>
            <div class="baris-struk"><span>Kembalian</span><span>Rp 0</span></div>
        </div>

        <div class="struk-garis">----------------------------------------</div>

        <div class="struk-kaki">
            <p id="prevPesanKaki">{{ $struk->teks_footer }}</p>
            <div class="struk-ig {{ $struk->tampil_sosmed ? '' : 'elemen-tersembunyi' }}" id="prevBarisIg">
                IG: <span id="prevIg">{{ $struk->instagram }}</span>
            </div>
        </div>
    </div>
</div>
