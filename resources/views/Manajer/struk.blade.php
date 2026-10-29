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
            <h3 class="judul-sub-struk">Kustomisasi Identitas &amp; Isi Nota</h3>

            <form method="POST" action="{{ route('manajer.struk.simpan') }}" id="formStruk">
                @csrf
                @method('PUT')

                <div class="form-grup">
                    <label class="form-label">Nama Usaha / Cabang Salon</label>
                    <input type="text" name="nama_salon" id="inputNamaSalon" class="form-input" value="{{ $struk->nama_salon }}" required>
                </div>

                <div class="form-grup">
                    <label class="form-label">Slogan / Subjudul</label>
                    <input type="text" name="slogan" id="inputSloganSalon" class="form-input" value="{{ $struk->slogan }}">
                </div>

                <div class="form-grup">
                    <label class="form-label">Alamat Lengkap</label>
                    <input type="text" name="alamat" id="inputAlamatSalon" class="form-input" value="{{ $struk->alamat }}" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="telepon" id="inputTelpSalon" class="form-input" value="{{ $struk->telepon }}" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Akun Instagram / Sosmed</label>
                        <input type="text" name="instagram" id="inputIgSalon" class="form-input" value="{{ $struk->instagram }}">
                    </div>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputHeaderSalon">Teks Header Tambahan</label>
                        <input type="text" name="teks_header" id="inputHeaderSalon" class="form-input" value="{{ $struk->teks_header }}">
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputUkuranKertas">Ukuran Kertas Printer (Thermal)</label>
                        <select name="ukuran_kertas" id="inputUkuranKertas" class="form-select">
                            @foreach (\App\Models\PengaturanStruk::DAFTAR_UKURAN_KERTAS as $nilaiUkuran => $labelUkuran)
                                <option value="{{ $nilaiUkuran }}" @selected(($struk->ukuran_kertas ?? '80mm') === $nilaiUkuran)>
                                    {{ $labelUkuran }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Opsi Rincian pada Nota</label>
                    <div class="opsi-ceklis-grup">
                        <label class="opsi-ceklis">
                            <input type="checkbox" name="tampil_logo" id="cekTampilLogo" value="1" @checked($struk->tampil_logo)>
                            <span>Tampilkan Logo Bunga (🌸) di bagian atas struk</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox" name="tampil_petugas" id="cekTampilPetugas" value="1" @checked($struk->tampil_petugas)>
                            <span>Tampilkan nama Stylist / Therapist pada rincian layanan</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox" name="tampil_kasir" id="cekTampilKasir" value="1" @checked($struk->tampil_kasir)>
                            <span>Tampilkan nama petugas Kasir yang melayani</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox" name="tampil_sosmed" id="cekTampilSosmed" value="1" @checked($struk->tampil_sosmed)>
                            <span>Tampilkan informasi Instagram di bagian bawah struk</span>
                        </label>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Pesan Penutup (Footer Nota)</label>
                    <textarea name="teks_footer" id="inputPesanKaki" class="form-textarea" rows="3">{{ $struk->teks_footer }}</textarea>
                </div>

                <div class="modal-kaki kaki-sebar">
                    <button type="button" class="btn-sekunder" id="btnUjiCetakStruk">🖨️ Uji Cetak Struk</button>
                    <button type="submit" class="btn-utama">💾 Simpan Format Nota</button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Pratinjau Kertas Struk Kasir -->
        <div class="pratinjau-wadah">
            <span class="label-pratinjau">
                PRATINJAU KERTAS STRUK THERMAL (<span id="labelUkuranKertas">{{ $struk->ukuran_kertas ?? '80mm' }}</span>)
            </span>

            <div class="kertas-struk ukuran-{{ $struk->ukuran_kertas ?? '80mm' }}" id="kertasStruk" data-ukuran="{{ $struk->ukuran_kertas ?? '80mm' }}">
                <div class="struk-kepala">
                    <div class="struk-logo" id="prevLogo">🌸</div>
                    <div class="struk-judul" id="prevNamaSalon">{{ $struk->nama_salon }}</div>
                    <div class="struk-sub" id="prevSlogan">{{ $struk->slogan }}</div>
                    <div class="struk-alamat" id="prevAlamat">{{ $struk->alamat }}</div>
                    <div class="struk-telp">Telp: <span id="prevTelp">{{ $struk->telepon }}</span></div>
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
                    <div class="struk-ig" id="prevBarisIg">IG: <span id="prevIg">{{ $struk->instagram }}</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/struk.js'])
@endpush
