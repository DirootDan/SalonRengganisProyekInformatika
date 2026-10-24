@extends('Manajer.layout')

@section('judul', 'Metode Pembayaran - Salon Rengganis')
@section('header', 'Pengaturan Metode Pembayaran')
@section('sub_header', 'Kelola pilihan pembayaran yang tersedia saat Kasir memproses transaksi pelanggan.')

@push('styles')
    @vite(['resources/css/Manajer/metode.css'])
@endpush

@section('isi')
    <div class="toolbar-atas">
        <p style="margin: 0; color: #6b7280; font-size: 14px;">
            Aktifkan atau nonaktifkan kanal pembayaran di bawah ini. Perubahan akan langsung berlaku pada tampilan meja Kasir.
        </p>
        <button type="button" class="btn-utama" id="btnTambahMetode">
            <span>＋</span> Tambah Metode Baru
        </button>
    </div>

    <div class="metode-grid">
        @foreach ($metode as $m)
            <div class="kartu-metode {{ $m->aktif ? 'aktif' : 'nonaktif' }}">
                <div class="metode-atas">
                    <div class="metode-identitas">
                        <span class="metode-ikon">{{ $m->ikon }}</span>
                        <div>
                            <span class="metode-kategori">{{ $m->kategori }}</span>
                            <h3 class="metode-nama">{{ $m->nama }}</h3>
                        </div>
                    </div>

                    <label class="sakelar" title="Aktifkan / Nonaktifkan">
                        <input type="checkbox" class="toggle-metode"
                               data-nama="{{ $m->nama }}"
                               @checked($m->aktif)>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="metode-detail">
                    <div class="baris-info">
                        <span>Nomor / Keterangan</span>
                        <strong>{{ $m->nomor_rek }}</strong>
                    </div>
                    <div class="baris-info">
                        <span>Atas Nama</span>
                        <strong>{{ $m->atas_nama }}</strong>
                    </div>
                </div>

                <div class="metode-kaki">
                    <span class="status-teks">{{ $m->aktif ? '● Aktif di Kasir' : '○ Nonaktif' }}</span>
                    <div class="aksi-grup">
                        <button type="button" class="btn-aksi btn-edit btnEditMetode"
                                data-nama="{{ $m->nama }}"
                                data-kategori="{{ $m->kategori }}"
                                data-rek="{{ $m->nomor_rek }}"
                                data-an="{{ $m->atas_nama }}">
                            ✏️ Edit Rincian
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Tambah / Edit Metode Pembayaran -->
    <div class="modal-backdrop" id="modalMetode">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalMetode">Edit Metode Pembayaran</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form id="formMetode">
                <div class="form-grup">
                    <label class="form-label">Nama Metode Pembayaran</label>
                    <input type="text" id="inputNamaMetode" class="form-input" placeholder="Contoh: Transfer Bank BRI" required>
                </div>

                <div class="form-grup">
                    <label class="form-label">Kategori Kanal</label>
                    <select id="inputKategoriMetode" class="form-select">
                        <option value="Tunai">Tunai</option>
                        <option value="Dompet Digital / QR">Dompet Digital / QRIS</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="Mesin EDC">Mesin EDC (Debit / Kredit)</option>
                    </select>
                </div>

                <div class="form-grup">
                    <label class="form-label">Nomor Rekening / ID Merchant / Keterangan</label>
                    <input type="text" id="inputRekMetode" class="form-input" placeholder="Contoh: 8420-9912-34" required>
                </div>

                <div class="form-grup">
                    <label class="form-label">Atas Nama Pemilik Rekening</label>
                    <input type="text" id="inputAnMetode" class="form-input" placeholder="Contoh: PT Rengganis Cantika" required>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" data-tutup-modal>Batal</button>
                    <button type="submit" class="btn-utama">Simpan Metode</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/metode.js'])
@endpush
