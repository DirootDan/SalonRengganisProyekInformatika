@extends('Karyawan.Kasir.layout')

@section('judul', 'Kelola Layanan - Kasir Salon Rengganis')
@section('header', 'Katalog Layanan Salon')
@section('sub_header', 'Lihat daftar perawatan salon atau ajukan izin 1x pakai ke Manajer untuk perubahan data layanan.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/layanan.css'])
@endpush

@section('isi')
    @include('Karyawan.Kasir.partials.banner-akses-katalog')

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

            <div class="toolbar-kanan-akses">
                @if ($tiketTambah?->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                    <span class="badge-akses badge-akses-aktif">
                        🔓 Izin Tambah 1x Aktif (s.d. {{ $tiketTambah->kedaluwarsa_pada?->format('H:i') }} WIB)
                    </span>
                    <button type="button" class="btn-utama" id="btnTambahLayanan"
                            data-url-store="{{ route('kasir.layanan.store') }}"
                            data-url-batal="{{ route('kasir.otorisasi-katalog.destroy', $tiketTambah->id_otorisasi) }}">
                        <span>＋</span> Tambah Layanan (1x Pakai)
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
                            data-konteks="{{ $konteksLayanan }}"
                            data-aksi="{{ \App\Models\PermintaanOtorisasi::AKSI_TAMBAH }}"
                            data-id-target=""
                            data-label="Tambah Layanan Baru">
                        🔒 Minta Akses Tambah Layanan
                    </button>
                @endif
            </div>
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
                        <th>Aksi (Izin 1x Manajer)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($layanan as $item)
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
                                <div class="aksi-katalog-kolom">
                                    <div class="baris-status-akses">
                                        @if ($item->tiket_edit?->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                                            <button type="button" class="btn-aksi btn-edit btnEditLayanan"
                                                    data-url-update="{{ route('kasir.layanan.update', $item->id_layanan) }}"
                                                    data-url-batal="{{ route('kasir.otorisasi-katalog.destroy', $item->tiket_edit->id_otorisasi) }}"
                                                    data-nama-asli="{{ $item->nama_layanan }}"
                                                    data-kategori="{{ $item->kategori }}"
                                                    data-durasi="{{ (int) $item->durasi_menit }}"
                                                    data-jeda="{{ (int) $item->jeda_menit }}"
                                                    data-harga="{{ (int) $item->harga_layanan }}">
                                                🔓 Edit Sekarang (1x)
                                            </button>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $item->tiket_edit->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Batal Akses</button>
                                            </form>
                                        @elseif ($item->tiket_edit?->status === \App\Models\PermintaanOtorisasi::STATUS_MENUNGGU)
                                            <span class="badge-akses badge-akses-pending">⏳ Edit Pending</span>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $item->tiket_edit->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Tarik</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn-minta-akses-katalog"
                                                    data-konteks="{{ $konteksLayanan }}"
                                                    data-aksi="{{ \App\Models\PermintaanOtorisasi::AKSI_UBAH }}"
                                                    data-id-target="{{ $item->id_layanan }}"
                                                    data-label="Ubah Layanan: {{ $item->nama_layanan }} (#{{ $item->id_layanan }})">
                                                🔒 Minta Edit
                                            </button>
                                        @endif

                                        @if ($item->tiket_hapus?->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                                            <form method="POST"
                                                  action="{{ route('kasir.layanan.destroy', $item->id_layanan) }}"
                                                  class="formHapusLayanan"
                                                  data-confirm="Hapus layanan '{{ $item->nama_layanan }}' menggunakan hak akses 1x pakai?"
                                                  data-confirm-title="Hapus Layanan (1x Pakai)"
                                                  data-confirm-item="{{ $item->nama_layanan }}"
                                                  data-confirm-impact="{{ $item->pesanDampakHapusKasir() }}"
                                                  data-confirm-label="Ya, Hapus Layanan">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-aksi btn-hapus">
                                                    🔓 Hapus (1x)
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $item->tiket_hapus->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Batal Akses</button>
                                            </form>
                                        @elseif ($item->tiket_hapus?->status === \App\Models\PermintaanOtorisasi::STATUS_MENUNGGU)
                                            <span class="badge-akses badge-akses-pending">⏳ Hapus Pending</span>
                                            <form method="POST" action="{{ route('kasir.otorisasi-katalog.destroy', $item->tiket_hapus->id_otorisasi) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-tarik-akses">Tarik</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn-minta-akses-katalog btn-minta-hapus"
                                                    data-konteks="{{ $konteksLayanan }}"
                                                    data-aksi="{{ \App\Models\PermintaanOtorisasi::AKSI_HAPUS }}"
                                                    data-id-target="{{ $item->id_layanan }}"
                                                    data-label="Hapus Layanan: {{ $item->nama_layanan }} (#{{ $item->id_layanan }})">
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

    <!-- Modal Tambah / Edit Layanan (Hanya Aktif Saat Memiliki Izin 1x Pakai) -->
    <div class="modal-backdrop {{ $bukaUlangModalLayanan ? 'tampil' : '' }}" id="modalLayanan">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalLayanan">{{ old('_method', 'POST') === 'PUT' ? 'Perbarui Data Layanan (1x Pakai)' : 'Tambah Layanan Baru (1x Pakai)' }}</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal aria-label="Tutup">&times;</button>
            </div>

            <form id="formLayanan" method="POST" action="{{ old('_url_action_layanan', route('kasir.layanan.store')) }}">
                @csrf
                <input type="hidden" name="_method" id="methodLayanan" value="{{ old('_method', 'POST') }}">
                <input type="hidden" name="_url_action_layanan" id="inputUrlActionLayanan" value="{{ old('_url_action_layanan', route('kasir.layanan.store')) }}">
                <input type="hidden" name="_url_batal_layanan" id="inputUrlBatalLayanan" value="{{ old('_url_batal_layanan', '') }}">

                <div class="form-grup">
                    <label class="form-label" for="inputNamaLayanan">Nama Layanan / Perawatan</label>
                    <input type="text" id="inputNamaLayanan" name="nama_layanan" class="form-input" value="{{ old('nama_layanan') }}" placeholder="Contoh: Hair Spa Keratin" required>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputJabatanLayanan">Kategori Layanan (Menentukan Jabatan Petugas)</label>
                    <select id="inputJabatanLayanan" name="kategori" class="form-select" required>
                        @foreach (\App\Models\Layanan::KATEGORI_LIST as $namaKategori => $namaJabatan)
                            <option value="{{ $namaKategori }}" @selected(old('kategori') === $namaKategori)>
                                {{ $namaKategori }} (Otomatis Ditangani {{ $namaJabatan }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label" for="inputDurasiLayanan">Durasi Pengerjaan (Menit)</label>
                        <input type="number" id="inputDurasiLayanan" name="durasi_menit" class="form-input" min="15" max="480" step="5" value="{{ old('durasi_menit') }}" placeholder="Contoh: 60" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="inputJedaLayanan">Jeda Persiapan (Menit)</label>
                        <input type="number" id="inputJedaLayanan" name="jeda_menit" class="form-input" min="0" max="120" step="5" value="{{ old('jeda_menit', 15) }}" required>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="inputHargaLayanan">Harga Layanan (Rp)</label>
                    <input type="number" id="inputHargaLayanan" name="harga_layanan" class="form-input" min="0" step="1" value="{{ old('harga_layanan') }}" placeholder="Contoh: 120000" required>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-kunci-modal" id="btnBatalKunciLayanan">Batalkan &amp; Kunci Akses</button>
                    <button type="submit" class="btn-utama">Simpan Layanan (1x Pakai)</button>
                </div>
            </form>
        </div>
    </div>

    @include('Karyawan.Kasir.partials.modal-akses-katalog', ['konteksDefault' => $konteksLayanan])
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/layanan.js'])
@endpush
