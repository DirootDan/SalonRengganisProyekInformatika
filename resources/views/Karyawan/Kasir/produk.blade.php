@extends('Karyawan.Kasir.layout')

@section('judul', 'Kelola Produk - Kasir Salon Rengganis')
@section('header', 'Katalog & Stok Produk')
@section('sub_header', 'Pantau ketersediaan produk kecantikan dan perbarui stok barang di meja kasir.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/produk.css'])
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
            <button type="button" class="btn-utama" id="btnTambahProduk" data-url-store="{{ route('kasir.produk.store') }}">
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
                                            data-url-update="{{ route('kasir.produk.update', $item->id_produk) }}"
                                            data-nama-asli="{{ $item->nama_produk }}"
                                            data-stok="{{ $item->stok_produk }}"
                                            data-harga="{{ (int) $item->harga_produk }}">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" action="{{ route('kasir.produk.destroy', $item->id_produk) }}" class="formHapusProduk" data-nama="{{ $item->nama_produk }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-aksi btn-hapus">
                                            🗑️ Hapus
                                        </button>
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

            <form id="formProduk" method="POST" action="{{ route('kasir.produk.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodProduk" value="POST">

                <div class="form-grup">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" id="inputNamaProduk" name="nama_produk" class="form-input" placeholder="Contoh: Vitamin Rambut Keratin 100ml" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Jumlah Stok (pcs)</label>
                        <input type="number" id="inputStokProduk" name="stok_produk" class="form-input" min="0" placeholder="Contoh: 20" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" id="inputHargaProduk" name="harga_produk" class="form-input" min="0" step="1000" placeholder="Contoh: 65000" required>
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
    @vite(['resources/js/Karyawan/Kasir/produk.js'])
@endpush
