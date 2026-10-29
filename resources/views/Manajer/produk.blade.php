@extends('Manajer.layout')

@section('judul', 'Stok Produk - Salon Rengganis')
@section('header', 'Stok Produk Salon')
@section('sub_header', 'Kelola ketersediaan stok barang kecantikan dan harga jual produk di kasir.')

@push('styles')
    @vite(['resources/css/Manajer/produk.css'])
@endpush

@section('isi')
    <div class="panel-kartu">
        <div class="toolbar-atas">
            <div class="toolbar-kiri">
                <input type="text" id="cariProduk" class="input-cari" placeholder="🔍 Cari nama produk kecantikan...">
                <select id="filterStok" class="select-filter">
                    <option value="">Semua Status Stok</option>
                    <option value="aman">Stok Aman (&ge; 10)</option>
                    <option value="menipis">Stok Menipis (&lt; 10)</option>
                </select>
            </div>
            <button type="button" class="btn-utama" id="btnTambahProduk" data-url-simpan="{{ route('manajer.produk.simpan') }}">
                <span>＋</span> Tambah Produk
            </button>
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Sisa Stok</th>
                        <th>Status Persediaan</th>
                        <th>Harga Satuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produk as $item)
                        @php
                            $menipis = $item->stok_produk < 10;
                        @endphp
                        <tr class="baris-produk"
                            data-nama="{{ strtolower($item->nama_produk) }}"
                            data-status="{{ $menipis ? 'menipis' : 'aman' }}">
                            <td><strong>#{{ $item->id_produk }}</strong></td>
                            <td>
                                <div class="nama-produk-sel">
                                    <span class="ikon-produk">🧴</span>
                                    <strong>{{ $item->nama_produk }}</strong>
                                </div>
                            </td>
                            <td><strong>{{ $item->stok_produk }} pcs</strong></td>
                            <td>
                                @if ($menipis)
                                    <span class="badge badge-nonaktif">⚠️ Stok Menipis</span>
                                @else
                                    <span class="badge badge-aktif">✓ Tersedia</span>
                                @endif
                            </td>
                            <td class="harga-produk">Rp {{ number_format($item->harga_produk, 0, ',', '.') }}</td>
                            <td>
                                <div class="aksi-grup">
                                    <button type="button" class="btn-aksi btn-edit btnEditProduk"
                                            data-url-update="{{ route('manajer.produk.update', $item->id_produk) }}"
                                            data-nama-asli="{{ $item->nama_produk }}"
                                            data-stok="{{ $item->stok_produk }}"
                                            data-harga="{{ (int) $item->harga_produk }}">
                                        ✏️ Ubah
                                    </button>
                                    <form method="POST" action="{{ route('manajer.produk.hapus', $item->id_produk) }}"
                                          class="form-hapus-produk"
                                          data-nama="{{ $item->nama_produk }}"
                                          data-confirm-title="Konfirmasi Hapus Produk"
                                          data-confirm-item="{{ $item->nama_produk }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-aksi btn-hapus">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah / Edit Produk -->
    <div class="modal-backdrop" id="modalProduk">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalProduk">Tambah Produk Baru</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form method="POST" action="{{ route('manajer.produk.simpan') }}" id="formProduk">
                @csrf
                <input type="hidden" name="_method" id="methodProduk" value="POST">

                <div class="form-grup">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="nama_produk" id="inputNamaProduk" class="form-input" placeholder="Contoh: Vitamin Rambut Keratin 100ml" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Jumlah Stok (pcs)</label>
                        <input type="number" name="stok_produk" id="inputStokProduk" class="form-input" min="0" placeholder="Contoh: 20" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" name="harga_produk" id="inputHargaProduk" class="form-input" min="0" step="1" placeholder="Contoh: 65000" required>
                    </div>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" data-tutup-modal>Batal</button>
                    <button type="submit" class="btn-utama">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/produk.js'])
@endpush
