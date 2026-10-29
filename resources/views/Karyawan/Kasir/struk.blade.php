@extends('Karyawan.Kasir.layout')

@section('judul', 'Format Struk Nota - Kasir Salon Rengganis')
@section('header', 'Format Struk Nota Pembayaran')
@section('sub_header', 'Pratinjau kertas struk pembayaran sesuai pengaturan identitas salon.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/struk.css'])
@endpush

@section('isi')
    <div class="struk-grid">
        <div class="panel-kartu">
            <h3>Identitas &amp; Format Struk Saat Ini</h3>

            <form id="formStruk">
                <div class="form-grup">
                    <label class="form-label">Nama Usaha / Cabang Salon</label>
                    <input type="text" id="inputNamaSalon" class="form-input" value="{{ $struk->nama_salon }}">
                </div>

                <div class="form-grup">
                    <label class="form-label">Slogan / Subjudul</label>
                    <input type="text" id="inputSloganSalon" class="form-input" value="{{ $struk->slogan }}">
                </div>

                <div class="form-grup">
                    <label class="form-label">Alamat Lengkap</label>
                    <input type="text" id="inputAlamatSalon" class="form-input" value="{{ $struk->alamat }}">
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Nomor Telepon / WhatsApp</label>
                        <input type="text" id="inputTelpSalon" class="form-input" value="{{ $struk->telepon }}">
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Akun Instagram / Sosmed</label>
                        <input type="text" id="inputIgSalon" class="form-input" value="{{ $struk->instagram }}">
                    </div>
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

                <div class="form-grup">
                    <label class="form-label">Pesan Penutup (Footer Nota)</label>
                    <textarea id="inputPesanKaki" class="form-textarea" rows="3">{{ $struk->teks_footer }}</textarea>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" id="btnUjiCetakStruk">🖨️ Uji Cetak Struk</button>
                </div>
            </form>
        </div>

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
                    <div class="baris-struk" id="prevBarisKasir"><span>Kasir</span><span>{{ auth('karyawan')->user()->nama_staff }}</span></div>
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
    @vite(['resources/js/Karyawan/Kasir/struk.js'])
@endpush
