@extends('Manajer.layout')

@section('judul', 'Format Struk Nota - Salon Rengganis')
@section('header', 'Pengaturan Format Struk Nota')
@section('sub_header', 'Sesuaikan tampilan nota pembayaran yang dicetak untuk pelanggan di meja Kasir.')

@push('styles')
    @vite(['resources/css/Manajer/struk.css'])
@endpush

@section('isi')
    @php
        $ukuranTerpilih = old('ukuran_kertas', $struk->ukuranTernormalisasi());
    @endphp

    <div class="struk-grid">
        <!-- Kolom Kiri: Form Pengaturan Struk -->
        <div class="panel-kartu">
            <h3 class="judul-sub-struk">Kustomisasi Identitas &amp; Isi Nota</h3>

            <form method="POST" action="{{ route('manajer.struk.simpan') }}" id="formStruk" novalidate>
                @csrf
                @method('PUT')

                <div class="form-grup">
                    <label class="form-label" for="inputNamaSalon">Nama Usaha / Cabang Salon</label>
                    <input type="text"
                           name="nama_salon"
                           id="inputNamaSalon"
                           class="form-input @error('nama_salon') input-galat @enderror"
                           value="{{ old('nama_salon', $struk->nama_salon) }}"
                           maxlength="100"
                           required>
                    @error('nama_salon')
                        <small class="pesan-galat-field">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputSloganSalon">Slogan / Subjudul</label>
                    <input type="text"
                           name="slogan"
                           id="inputSloganSalon"
                           class="form-input @error('slogan') input-galat @enderror"
                           value="{{ old('slogan', $struk->slogan) }}"
                           maxlength="150">
                    @error('slogan')
                        <small class="pesan-galat-field">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputAlamatSalon">Alamat Lengkap</label>
                    <input type="text"
                           name="alamat"
                           id="inputAlamatSalon"
                           class="form-input @error('alamat') input-galat @enderror"
                           value="{{ old('alamat', $struk->alamat) }}"
                           maxlength="255"
                           required>
                    @error('alamat')
                        <small class="pesan-galat-field">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputTelpSalon">Nomor Telepon / WhatsApp</label>
                        <input type="text"
                               name="telepon"
                               id="inputTelpSalon"
                               class="form-input @error('telepon') input-galat @enderror"
                               value="{{ old('telepon', $struk->telepon) }}"
                               maxlength="50"
                               required>
                        @error('telepon')
                            <small class="pesan-galat-field">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputIgSalon">Akun Instagram / Sosmed</label>
                        <input type="text"
                               name="instagram"
                               id="inputIgSalon"
                               class="form-input @error('instagram') input-galat @enderror"
                               value="{{ old('instagram', $struk->instagram) }}"
                               maxlength="80">
                        @error('instagram')
                            <small class="pesan-galat-field">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputHeaderSalon">Teks Header Tambahan</label>
                    <input type="text"
                           name="teks_header"
                           id="inputHeaderSalon"
                           class="form-input @error('teks_header') input-galat @enderror"
                           value="{{ old('teks_header', $struk->teks_header) }}"
                           maxlength="150">
                    @error('teks_header')
                        <small class="pesan-galat-field">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-grup">
                    <label class="form-label" for="pilihUkuranKertas">Ukuran Kertas Struk (Standar Printer Kasir)</label>
                    <select name="ukuran_kertas"
                            id="pilihUkuranKertas"
                            class="form-select @error('ukuran_kertas') input-galat @enderror">
                        @foreach ($daftarUkuran as $kodeUkuran => $labelUkuran)
                            <option value="{{ $kodeUkuran }}" @selected($ukuranTerpilih === $kodeUkuran)>
                                {{ $labelUkuran }}
                            </option>
                        @endforeach
                    </select>
                    @error('ukuran_kertas')
                        <small class="pesan-galat-field">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-grup">
                    <span class="form-label">Opsi Rincian pada Nota</span>
                    <div class="opsi-ceklis-grup">
                        <label class="opsi-ceklis">
                            <input type="checkbox"
                                   name="tampil_logo"
                                   id="cekTampilLogo"
                                   value="1"
                                   @checked(old('tampil_logo', $struk->tampil_logo))>
                            <span>Tampilkan Logo Bunga (🌸) di bagian atas struk</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox"
                                   name="tampil_petugas"
                                   id="cekTampilPetugas"
                                   value="1"
                                   @checked(old('tampil_petugas', $struk->tampil_petugas))>
                            <span>Tampilkan nama Stylist / Therapist pada rincian layanan</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox"
                                   name="tampil_kasir"
                                   id="cekTampilKasir"
                                   value="1"
                                   @checked(old('tampil_kasir', $struk->tampil_kasir))>
                            <span>Tampilkan nama petugas Kasir yang melayani</span>
                        </label>
                        <label class="opsi-ceklis">
                            <input type="checkbox"
                                   name="tampil_sosmed"
                                   id="cekTampilSosmed"
                                   value="1"
                                   @checked(old('tampil_sosmed', $struk->tampil_sosmed))>
                            <span>Tampilkan informasi Instagram di bagian bawah struk</span>
                        </label>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputPesanKaki">Pesan Penutup (Footer Nota)</label>
                    <textarea name="teks_footer"
                              id="inputPesanKaki"
                              class="form-textarea @error('teks_footer') input-galat @enderror"
                              maxlength="500"
                              rows="3">{{ old('teks_footer', $struk->teks_footer) }}</textarea>
                    @error('teks_footer')
                        <small class="pesan-galat-field">{{ $message }}</small>
                    @enderror
                </div>

                <div class="modal-kaki kaki-sebar">
                    <button type="button" class="btn-sekunder" id="btnUjiCetakStruk">🖨️ Uji Cetak Struk</button>
                    <button type="submit" class="btn-utama" id="btnSimpanStruk">💾 Simpan Format Nota</button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Komponen Bersama Pratinjau Kertas Struk -->
        <x-pratinjau-struk :struk="$struk" nama-kasir="Kasir Salon" />
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/struk.js'])
@endpush
