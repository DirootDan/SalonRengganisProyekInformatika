@extends('Manajer.layout')

@section('judul', 'Kelola Layanan - Salon Rengganis')
@section('header', 'Daftar Layanan, Durasi & Kategori')
@section('sub_header', 'Atur kategori perawatan, harga, durasi pengerjaan nyata, waktu jeda persiapan, dan jabatan petugas.')

@push('styles')
    @vite(['resources/css/Manajer/layanan.css'])
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
            <button type="button" class="btn-utama" id="btnTambahLayanan" data-url-simpan="{{ route('manajer.layanan.simpan') }}">
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
                            data-nama="{{ strtolower($item->nama_layanan . ' ' . $item->kategori) }}"
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
                                <strong>{{ $item->durasi_menit }} menit</strong>
                                <br>
                                <small>+ Jeda {{ $item->jeda_menit }} mnt ({{ $item->petugas_eksklusif ? 'Eksklusif' : 'Fleksibel' }})</small>
                            </td>
                            <td class="kolom-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</td>
                            <td>
                                <div class="aksi-grup">
                                    <button type="button" class="btn-aksi btn-edit btnEditLayanan"
                                            data-url-update="{{ route('manajer.layanan.update', $item->id_layanan) }}"
                                            data-nama-asli="{{ $item->nama_layanan }}"
                                            data-kategori="{{ $item->kategori }}"
                                            data-harga="{{ (int) $item->harga_layanan }}"
                                            data-durasi="{{ (int) $item->durasi_menit }}"
                                            data-jeda="{{ (int) $item->jeda_menit }}"
                                            data-eksklusif="{{ $item->petugas_eksklusif ? '1' : '0' }}"
                                            data-jabatan="{{ $item->jabatan_petugas }}">
                                        Ubah
                                    </button>

                                    <form method="POST"
                                          action="{{ route('manajer.layanan.hapus', $item->id_layanan) }}"
                                          class="form-hapus-layanan"
                                          data-confirm-title="Hapus Layanan Perawatan"
                                          data-confirm-item="{{ $item->nama_layanan }}"
                                          data-confirm-impact="{{ $jmlAktif > 0 ? 'Perhatian: Layanan ini masih memiliki ' . $jmlAktif . ' reservasi aktif (Menunggu/Diterima) sehingga penghapusan akan ditolak server.' : 'Layanan ini akan diarsipkan dari daftar pemesanan baru, namun riwayat nota lama tetap tersimpan.' }}"
                                          data-confirm-label="Ya, Hapus Layanan"
                                          data-nama="{{ $item->nama_layanan }}">
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

    <!-- Modal Tambah / Edit Layanan -->
    <div class="modal-backdrop" id="modalLayanan">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalLayanan">Tambah Layanan Baru</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form method="POST" action="{{ route('manajer.layanan.simpan') }}" id="formLayanan">
                @csrf
                <input type="hidden" name="_method" id="methodLayanan" value="POST">

                <div class="form-grup">
                    <label class="form-label" for="inputNamaLayanan">Nama Layanan / Perawatan</label>
                    <input type="text" name="nama_layanan" id="inputNamaLayanan" class="form-input" placeholder="Contoh: Smoothing Keratin" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputKategoriLayanan">Kategori Layanan</label>
                        <select name="kategori" id="inputKategoriLayanan" class="form-select" required>
                            <option value="Perawatan Rambut">Perawatan Rambut (Stylist)</option>
                            <option value="Perawatan Wajah & Tubuh">Perawatan Wajah &amp; Tubuh (Therapist)</option>
                        </select>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputJabatanLayanan">Petugas yang Menangani</label>
                        <select name="jabatan_petugas" id="inputJabatanLayanan" class="form-select" required>
                            <option value="Stylist">Stylist</option>
                            <option value="Therapist">Therapist</option>
                        </select>
                    </div>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputDurasiLayanan">Durasi Pengerjaan (Menit)</label>
                        <input type="number" name="durasi_menit" id="inputDurasiLayanan" class="form-input" min="15" max="480" step="5" placeholder="Isi durasi (contoh: 60 atau 180)" required>
                        <small class="petunjuk-input">Contoh: Potong 45 mnt, Facial 60 mnt, Smoothing 180 mnt.</small>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputJedaLayanan">Jeda / Persiapan Setelah Selesai (Menit)</label>
                        <input type="number" name="jeda_menit" id="inputJedaLayanan" class="form-input" min="0" max="120" step="5" value="15" required>
                        <small class="petunjuk-input">Waktu bersih-bersih kursi/alat sebelum pelanggan berikutnya.</small>
                    </div>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputHargaLayanan">Harga Layanan (Rp)</label>
                        <input type="number" name="harga_layanan" id="inputHargaLayanan" class="form-input" min="0" step="1" placeholder="Contoh: 120000" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputEksklusifLayanan">Penguncian Jadwal Petugas</label>
                        <select name="petugas_eksklusif" id="inputEksklusifLayanan" class="form-select" required>
                            <option value="1">Eksklusif (Petugas fokus penuh sampai selesai)</option>
                            <option value="0">Fleksibel (Boleh diselingi saat waktu tunggu obat)</option>
                        </select>
                    </div>
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
    @vite(['resources/js/Manajer/layanan.js'])
@endpush
