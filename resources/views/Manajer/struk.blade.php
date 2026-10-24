@extends('Manajer.layout')

@section('judul', 'Format Struk Nota - Salon Rengganis')
@section('header', 'Pengaturan Format Struk Nota')
@section('sub_header', 'Sesuaikan tampilan nota pembayaran yang dicetak untuk pelanggan di meja Kasir.')

@push('styles')
    @vite(['resources/css/Manajer/struk.css'])
@endpush

@section('isi')
    <div class="struk-grid">
        <!-- Kolom Kiri: Form Pengaturan Struk -->
        <div class="panel-kartu">
            <h3 style="margin: 0 0 18px; font-size: 18px;">Kustomisasi Identitas &amp; Isi Nota</h3>

            <form id="formStruk">
                <div class="form-grup">
                    <label class="form-label">Nama Usaha / Cabang Salon</label>
                    <input type="text" id="inputNamaSalon" class="form-input" value="SALON RENGGANIS">
                </div>

                <div class="form-grup">
                    <label class="form-label">Slogan / Subjudul</label>
                    <input type="text" id="inputSloganSalon" class="form-input" value="Beauty &amp; Hair Treatment Center">
                </div>

                <div class="form-grup">
                    <label class="form-label">Alamat Lengkap</label>
                    <input type="text" id="inputAlamatSalon" class="form-input" value="Jl. Paingan No. 88, Maguwoharjo, Sleman">
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Nomor Telepon / WhatsApp</label>
                        <input type="text" id="inputTelpSalon" class="form-input" value="0812-3456-7890">
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Akun Instagram / Sosmed</label>
                        <input type="text" id="inputIgSalon" class="form-input" value="@salonrengganis.id">
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Opsi Rincian pada Nota</label>
                    <div class="opsi-ceklis-grup">
                        <label class="opsi-ceklis">
                            <input type="checkbox" id="cekTampilLogo" checked>
                            <span>Tampilkan Logo Bunga (🌸) di bagian atas struk</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox" id="cekTampilPetugas" checked>
                            <span>Tampilkan nama Stylist / Therapist pada rincian layanan</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox" id="cekTampilKasir" checked>
                            <span>Tampilkan nama petugas Kasir yang melayani</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox" id="cekTampilSosmed" checked>
                            <span>Tampilkan informasi Instagram di bagian bawah struk</span>
                        </label>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Pesan Penutup (Footer Nota)</label>
                    <textarea id="inputPesanKaki" class="form-textarea" rows="3">Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.</textarea>
                </div>

                <div class="modal-kaki" style="justify-content: space-between;">
                    <button type="button" class="btn-sekunder" id="btnUjiCetakStruk">🖨️ Uji Cetak Struk</button>
                    <button type="submit" class="btn-utama">💾 Simpan Format Nota</button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Pratinjau Kertas Struk Kasir (Live Preview) -->
        <div class="pratinjau-wadah">
            <span class="label-pratinjau">PRATINJAU KERTAS STRUK THERMAL (80mm)</span>

            <div class="kertas-struk" id="kertasStruk">
                <div class="struk-kepala">
                    <div class="struk-logo" id="prevLogo">🌸</div>
                    <div class="struk-judul" id="prevNamaSalon">SALON RENGGANIS</div>
                    <div class="struk-sub" id="prevSlogan">Beauty &amp; Hair Treatment Center</div>
                    <div class="struk-alamat" id="prevAlamat">Jl. Paingan No. 88, Maguwoharjo, Sleman</div>
                    <div class="struk-telp">Telp: <span id="prevTelp">0812-3456-7890</span></div>
                </div>

                <div class="struk-garis">----------------------------------------</div>

                <div class="struk-meta">
                    <div class="baris-struk"><span>No. Nota</span><span>#INV-0024</span></div>
                    <div class="baris-struk"><span>Tanggal</span><span>{{ now()->format('d/m/Y H:i') }}</span></div>
                    <div class="baris-struk"><span>Pelanggan</span><span>Rina Anggraini</span></div>
                    <div class="baris-struk" id="prevBarisKasir"><span>Kasir</span><span>Kasir Salon</span></div>
                </div>

                <div class="struk-garis">----------------------------------------</div>

                <div class="struk-item-list">
                    <div class="struk-item">
                        <div class="baris-struk">
                            <strong>Creambath</strong>
                            <span>Rp 80.000</span>
                        </div>
                        <small class="prev-petugas">Stylist: Sinta Maharani</small>
                    </div>

                    <div class="struk-item">
                        <div class="baris-struk">
                            <strong>Facial</strong>
                            <span>Rp 120.000</span>
                        </div>
                        <small class="prev-petugas">Therapist: Dewi Lestari</small>
                    </div>

                    <div class="struk-item">
                        <div class="baris-struk">
                            <strong>1x Vitamin Rambut Keratin</strong>
                            <span>Rp 65.000</span>
                        </div>
                    </div>
                </div>

                <div class="struk-garis">----------------------------------------</div>

                <div class="struk-total-area">
                    <div class="baris-struk"><span>Subtotal</span><span>Rp 265.000</span></div>
                    <div class="baris-struk"><span>Diskon (CANTIK20)</span><span>-Rp 25.000</span></div>
                    <div class="baris-struk struk-grand-total">
                        <span>TOTAL BAYAR</span>
                        <span>Rp 240.000</span>
                    </div>
                    <div class="baris-struk"><span>Metode (QRIS)</span><span>Rp 240.000</span></div>
                    <div class="baris-struk"><span>Kembalian</span><span>Rp 0</span></div>
                </div>

                <div class="struk-garis">----------------------------------------</div>

                <div class="struk-kaki">
                    <p id="prevPesanKaki">Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.</p>
                    <div class="struk-ig" id="prevBarisIg">IG: <span id="prevIg">@salonrengganis.id</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/struk.js'])
@endpush
