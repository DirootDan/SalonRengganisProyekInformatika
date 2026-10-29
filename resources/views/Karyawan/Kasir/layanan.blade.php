@extends('Karyawan.Kasir.layout')

@section('judul', 'Kelola Layanan - Kasir Salon Rengganis')
@section('header', 'Katalog Layanan Salon')
@section('sub_header', 'Kelola daftar perawatan salon, durasi pengerjaan, harga layanan, dan jabatan petugas yang menangani.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/layanan.css'])
@endpush

@section('isi')
    <div class="panel-kartu">
        <div class="toolbar-atas">
            <div class="toolbar-kiri">
                <input type="text" id="cariLayanan" class="input-cari" placeholder="Cari nama perawatan...">
                <select id="filterPetugas" class="select-filter">
                    <option value="">Semua Petugas</option>
                    <option value="Stylist">Ditangani Stylist</option>
                    <option value="Therapist">Ditangani Therapist</option>
                </select>
            </div>
            <button type="button" class="btn-utama" id="btnTambahLayanan" data-url-store="{{ route('kasir.layanan.store') }}">
                <span>＋</span> Tambah Layanan
            </button>
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Layanan</th>
                        <th>Kategori</th>
                        <th>Jabatan Petugas</th>
                        <th>Durasi &amp; Jeda</th>
                        <th>Harga Layanan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($layanan as $item)
                        @php
                            $jmlAktif = $item->jumlahReservasiAktif();
                        @endphp
                        <tr class="baris-layanan"
                            data-nama="{{ strtolower($item->nama_layanan) }}"
                            data-jabatan="{{ $item->jabatan_petugas }}">
                            <td><strong>#{{ $item->id_layanan }}</strong></td>
                            <td>
                                <div class="nama-layanan-sel">
                                    <strong>{{ $item->nama_layanan }}</strong>
                                </div>
                            </td>
                            <td>{{ $item->kategori }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($item->jabatan_petugas) }}">
                                    {{ $item->jabatan_petugas }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $item->durasi_menit }} menit</strong><br>
                                <small>+ Jeda {{ $item->jeda_menit }} mnt</small>
                            </td>
                            <td class="kolom-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</td>
                            <td>
                                <div class="aksi-grup">
                                    <button type="button" class="btn-aksi btn-edit btnEditLayanan"
                                            data-url-update="{{ route('kasir.layanan.update', $item->id_layanan) }}"
                                            data-nama-asli="{{ $item->nama_layanan }}"
                                            data-kategori="{{ $item->kategori }}"
                                            data-durasi="{{ (int) $item->durasi_menit }}"
                                            data-jeda="{{ (int) $item->jeda_menit }}"
                                            data-harga="{{ (int) $item->harga_layanan }}">
                                        Edit
                                    </button>
                                    <form method="POST"
                                          action="{{ route('kasir.layanan.destroy', $item->id_layanan) }}"
                                          class="formHapusLayanan"
                                          data-confirm-title="Hapus Layanan Perawatan"
                                          data-confirm-item="{{ $item->nama_layanan }}"
                                          data-confirm-impact="{{ $jmlAktif > 0 ? 'Perhatian: Layanan ini masih memiliki ' . $jmlAktif . ' reservasi aktif (Menunggu/Diterima) sehingga penghapusan akan ditolak server.' : 'Layanan ini akan diarsipkan dari katalog aktif.' }}"
                                          data-confirm-label="Ya, Hapus Layanan"
                                          data-nama="{{ $item->nama_layanan }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-aksi btn-hapus">
                                            Hapus
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

    <!-- Modal Tambah / Edit Layanan -->
    <div class="modal-backdrop" id="modalLayanan">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalLayanan">Tambah Layanan Baru</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form id="formLayanan" method="POST" action="{{ route('kasir.layanan.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodLayanan" value="POST">

                <div class="form-grup">
                    <label class="form-label" for="inputNamaLayanan">Nama Layanan / Perawatan</label>
                    <input type="text" id="inputNamaLayanan" name="nama_layanan" class="form-input" placeholder="Contoh: Hair Spa Keratin" required>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputJabatanLayanan">Kategori Layanan (Menentukan Jabatan Petugas)</label>
                    <select id="inputJabatanLayanan" name="kategori" class="form-select" required>
                        <option value="Rambut">Rambut (Otomatis Ditangani Stylist)</option>
                        <option value="Perawatan">Perawatan Wajah, Kuku &amp; Tubuh (Otomatis Ditangani Therapist)</option>
                    </select>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputDurasiLayanan">Durasi Pengerjaan (Menit)</label>
                        <input type="number" id="inputDurasiLayanan" name="durasi_menit" class="form-input" min="15" max="480" step="5" placeholder="Contoh: 60" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputJedaLayanan">Jeda Persiapan (Menit)</label>
                        <input type="number" id="inputJedaLayanan" name="jeda_menit" class="form-input" min="0" max="120" step="5" value="15" required>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputHargaLayanan">Harga Layanan (Rp)</label>
                    <input type="number" id="inputHargaLayanan" name="harga_layanan" class="form-input" min="0" step="1" placeholder="Contoh: 120000" required>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" data-tutup-modal>Batal</button>
                    <button type="submit" class="btn-utama">Simpan Layanan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/layanan.js'])
@endpush
