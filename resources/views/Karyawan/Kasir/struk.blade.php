@extends('Karyawan.Kasir.layout')

@section('judul', 'Format Struk Nota - Kasir Salon Rengganis')
@section('header', 'Format Struk Nota Pembayaran')
@section('sub_header', 'Pratinjau kertas struk pembayaran sesuai pengaturan resmi Manajer Salon Rengganis.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/struk.css'])
@endpush

@section('isi')
    <div class="struk-grid">
        <div class="panel-kartu">
            <div class="kepala-info-struk">
                <h3 class="judul-sub-struk tanpa-margin">Identitas &amp; Format Struk Aktif</h3>
                <span class="lencana-baca-saja">🔒 Diatur oleh Manajer</span>
            </div>

            <p class="catatan-info-struk">
                Informasi identitas salon di bawah ini ditetapkan oleh Manajer dan otomatis diterapkan saat mencetak struk transaksi di meja kasir. Anda tetap dapat memilih simulasi ukuran kertas untuk menguji printer kasir.
            </p>

            <dl class="daftar-info-struk">
                <div class="item-info-struk">
                    <dt>Nama Usaha / Cabang Salon</dt>
                    <dd>{{ $struk->nama_salon }}</dd>
                </div>

                <div class="item-info-struk">
                    <dt>Slogan / Subjudul</dt>
                    <dd>{{ $struk->slogan ?: '-' }}</dd>
                </div>

                <div class="item-info-struk">
                    <dt>Alamat Lengkap</dt>
                    <dd>{{ $struk->alamat }}</dd>
                </div>

                <div class="baris-dua-info">
                    <div class="item-info-struk">
                        <dt>Nomor Telepon / WhatsApp</dt>
                        <dd>{{ $struk->telepon }}</dd>
                    </div>

                    <div class="item-info-struk">
                        <dt>Akun Instagram / Sosmed</dt>
                        <dd>{{ $struk->instagram ?: '-' }}</dd>
                    </div>
                </div>

                <div class="item-info-struk">
                    <dt>Pesan Penutup (Footer Nota)</dt>
                    <dd>{{ $struk->teks_footer ?: '-' }}</dd>
                </div>
            </dl>

            <div class="kotak-simulasi-cetak">
                <div class="form-grup tanpa-margin">
                    <label class="form-label" for="pilihUkuranKertas">Simulasi Ukuran Kertas Printer Kasir</label>
                    <select id="pilihUkuranKertas" class="form-select">
                        @foreach ($daftarUkuran as $kodeUkuran => $labelUkuran)
                            <option value="{{ $kodeUkuran }}" @selected($ukuranAktif === $kodeUkuran)>
                                {{ $labelUkuran }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" id="btnUjiCetakStruk">🖨️ Uji Cetak Struk</button>
                </div>
            </div>
        </div>

        <x-pratinjau-struk :struk="$struk" :nama-kasir="$namaKasirLogin" />
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/struk.js'])
@endpush
