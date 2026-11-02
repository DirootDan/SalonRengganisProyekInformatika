@extends('Karyawan.Kasir.layout')

@section('judul', 'Kelola Produk - Kasir Salon Rengganis')
@section('header', 'Katalog & Stok Produk')
@section('sub_header', 'Pantau ketersediaan produk kecantikan atau ajukan izin 1x pakai ke Manajer untuk perubahan stok dan harga.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/produk.css'])
@endpush

@section('isi')
    @php
        $konteksProduk = \App\Models\PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK;
        $tiketTambah = $petaAkses[\App\Services\OtorisasiKatalogService::kunciAkses(\App\Models\PermintaanOtorisasi::AKSI_TAMBAH, null)] ?? null;
        $bukaUlangModalProduk = $errors->hasAny(['nama_produk', 'stok_produk', 'harga_produk']);
    @endphp

    @include('Karyawan.Kasir.partials.banner-akses-katalog')

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

            <div class="toolbar-kanan-akses">
                @if ($tiketTambah?->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                    <span class="badge-akses badge-akses-aktif">
                        🔓 Izin Tambah 1x Aktif (s.d. {{ $tiketTambah->kedaluwarsa_pada?->format('H:i') }} WIB)
                    </span>
                    <button type="button" class="btn-utama" id="btnTambahProduk"
                            data-url-store="{{ route('kasir.produk.store') }}"
                            data-url-batal="{{ route('kasir.otorisasi-katalog.destroy', $tiketTambah->id_otorisasi) }}">
                        <span>＋</span> Tambah Produk (1x Pakai)
                    </button>
                    <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $tiketTambah->id_otorisasi) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-tarik-akses">Kunci Kembali</button>
                    </form>
                @elseif ($tiketTambah?->status === \App\Models\PermintaanOtorisasi::STATUS_MENUNGGU)
                    <span class="badge-akses badge-akses-pending">
                        ⏳ Tambah Menunggu ACC (#{{ $tiketTambah->kode_referensi }})
                    </span>
                    <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $tiketTambah->id_otorisasi) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-tarik-akses">Tarik Request</button>
                    </form>
                @else
                    <button type="button" class="btn-minta-akses-katalog btn-minta-akses-utama"
                            data-konteks="{{ $konteksProduk }}"
                            data-aksi="{{ \App\Models\PermintaanOtorisasi::AKSI_TAMBAH }}"
                            data-id-target=""
                            data-label="Tambah Produk Baru">
                        🔒 Minta Akses Tambah Produk
                    </button>
                @endif
            </div>
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
                        <th>Aksi (Izin 1x Manajer)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produk as $item)
                        @php
                            $menipis = $item->stok_produk < 10;
                            $jmlAktif = $item->jumlahReservasiAktif();
                            $tiketEdit = $petaAkses[\App\Services\OtorisasiKatalogService::kunciAkses(\App\Models\PermintaanOtorisasi::AKSI_UBAH, $item->id_produk)] ?? null;
                            $tiketHapus = $petaAkses[\App\Services\OtorisasiKatalogService::kunciAkses(\App\Models\PermintaanOtorisasi::AKSI_HAPUS, $item->id_produk)] ?? null;
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
                                <div class="aksi-katalog-kolom">
                                    <div class="baris-status-akses">
                                        @if ($tiketEdit?->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                                            <button type="button" class="btn-aksi btn-edit btnEditProduk"
                                                    data-url-update="{{ route('kasir.produk.update', $item->id_produk) }}"
                                                    data-url-batal="{{ route('kasir.otorisasi-katalog.destroy', $tiketEdit->id_otorisasi) }}"
                                                    data-nama-asli="{{ $item->nama_produk }}"
                                                    data-stok="{{ $item->stok_produk }}"
                                                    data-harga="{{ (int) $item->harga_produk }}">
                                                🔓 Edit Sekarang (1x)
                                            </button>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $tiketEdit->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Batal Akses</button>
                                            </form>
                                        @elseif ($tiketEdit?->status === \App\Models\PermintaanOtorisasi::STATUS_MENUNGGU)
                                            <span class="badge-akses badge-akses-pending">⏳ Edit Pending</span>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $tiketEdit->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Tarik</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn-minta-akses-katalog"
                                                    data-konteks="{{ $konteksProduk }}"
                                                    data-aksi="{{ \App\Models\PermintaanOtorisasi::AKSI_UBAH }}"
                                                    data-id-target="{{ $item->id_produk }}"
                                                    data-label="Ubah Produk: {{ $item->nama_produk }} (#{{ $item->id_produk }})">
                                                🔒 Minta Edit
                                            </button>
                                        @endif

                                        @if ($tiketHapus?->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                                            <form method="POST" action="{{ route('kasir.produk.destroy', $item->id_produk) }}"
                                                  class="formHapusProduk"
                                                  data-confirm="Hapus produk '{{ $item->nama_produk }}' menggunakan hak akses 1x pakai?"
                                                  data-confirm-title="Konfirmasi Hapus Produk (1x Pakai)"
                                                  data-confirm-item="{{ $item->nama_produk }}"
                                                  data-confirm-impact="{{ $jmlAktif > 0 ? 'Perhatian: Produk ini masih dipesan pada ' . $jmlAktif . ' reservasi aktif sehingga penghapusan akan ditolak server.' : 'Setelah dihapus, hak akses otomatis terkunci kembali.' }}"
                                                  data-confirm-label="Ya, Hapus Produk">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-aksi btn-hapus">
                                                    🔓 Hapus (1x)
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $tiketHapus->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Batal Akses</button>
                                            </form>
                                        @elseif ($tiketHapus?->status === \App\Models\PermintaanOtorisasi::STATUS_MENUNGGU)
                                            <span class="badge-akses badge-akses-pending">⏳ Hapus Pending</span>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $tiketHapus->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Tarik</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn-minta-akses-katalog btn-minta-hapus"
                                                    data-konteks="{{ $konteksProduk }}"
                                                    data-aksi="{{ \App\Models\PermintaanOtorisasi::AKSI_HAPUS }}"
                                                    data-id-target="{{ $item->id_produk }}"
                                                    data-label="Hapus Produk: {{ $item->nama_produk }} (#{{ $item->id_produk }})">
                                                🔒 Minta Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah / Edit Produk (Hanya Aktif Saat Memiliki Izin 1x Pakai) -->
    <div class="modal-backdrop {{ $bukaUlangModalProduk ? 'tampil' : '' }}" id="modalProduk">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalProduk">{{ old('_method', 'POST') === 'PUT' ? 'Perbarui Stok & Harga Produk (1x Pakai)' : 'Tambah Produk Baru (1x Pakai)' }}</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal aria-label="Tutup">&times;</button>
            </div>

            <form id="formProduk" method="POST" action="{{ old('_url_action_produk', route('kasir.produk.store')) }}">
                @csrf
                <input type="hidden" name="_method" id="methodProduk" value="{{ old('_method', 'POST') }}">
                <input type="hidden" name="_url_action_produk" id="inputUrlActionProduk" value="{{ old('_url_action_produk', route('kasir.produk.store')) }}">
                <input type="hidden" name="_url_batal_produk" id="inputUrlBatalProduk" value="{{ old('_url_batal_produk', '') }}">

                <div class="form-grup">
                    <label class="form-label" for="inputNamaProduk">Nama Produk</label>
                    <input type="text" id="inputNamaProduk" name="nama_produk" class="form-input" value="{{ old('nama_produk') }}" placeholder="Contoh: Vitamin Rambut Keratin 100ml" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputStokProduk">Jumlah Stok (pcs)</label>
                        <input type="number" id="inputStokProduk" name="stok_produk" class="form-input" min="0" value="{{ old('stok_produk') }}" placeholder="Contoh: 20" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputHargaProduk">Harga Jual (Rp)</label>
                        <input type="number" id="inputHargaProduk" name="harga_produk" class="form-input" min="0" step="1" value="{{ old('harga_produk') }}" placeholder="Contoh: 65000" required>
                    </div>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-kunci-modal" id="btnBatalKunciProduk">Batalkan &amp; Kunci Akses</button>
                    <button type="submit" class="btn-utama">Simpan Produk (1x Pakai)</button>
                </div>
            </form>
        </div>
    </div>

    @include('Karyawan.Kasir.partials.modal-akses-katalog', ['konteksDefault' => $konteksProduk])
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/produk.js'])
@endpush
