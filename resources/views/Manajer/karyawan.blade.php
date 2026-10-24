@extends('Manajer.layout')

@section('judul', 'Kelola Karyawan - Salon Rengganis')
@section('header', 'Data Karyawan')
@section('sub_header', 'Kelola akun staf salon (Manajer, Kasir, Stylist, dan Therapist).')

@push('styles')
    @vite(['resources/css/Manajer/karyawan.css'])
@endpush

@section('isi')
    <div class="panel-kartu">
        <div class="toolbar-atas">
            <div class="toolbar-kiri">
                <input type="text" id="cariKaryawan" class="input-cari" placeholder="🔍 Cari nama, username, atau email staf...">
                <select id="filterJabatan" class="select-filter">
                    <option value="">Semua Jabatan</option>
                    <option value="Manajer">Manajer</option>
                    <option value="Kasir">Kasir</option>
                    <option value="Stylist">Stylist</option>
                    <option value="Therapist">Therapist</option>
                </select>
            </div>
            <button type="button" class="btn-utama" id="btnTambahKaryawan">
                <span>＋</span> Tambah Karyawan
            </button>
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data" id="tabelKaryawan">
                <thead>
                    <tr>
                        <th>Staf</th>
                        <th>Username Login</th>
                        <th>Alamat Email</th>
                        <th>Jabatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($karyawan as $staf)
                        <tr class="baris-karyawan"
                            data-nama="{{ strtolower($staf->nama_staff . ' ' . $staf->username . ' ' . $staf->email) }}"
                            data-jabatan="{{ $staf->role_jabatan }}">
                            <td>
                                <div class="sel-staf">
                                    <span class="avatar-kecil">{{ strtoupper(substr($staf->nama_staff, 0, 1)) }}</span>
                                    <strong>{{ $staf->nama_staff }}</strong>
                                </div>
                            </td>
                            <td><code>{{ $staf->username }}</code></td>
                            <td>{{ $staf->email }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($staf->role_jabatan) }}">
                                    {{ $staf->role_jabatan }}
                                </span>
                            </td>
                            <td>
                                <div class="aksi-grup">
                                    <button type="button" class="btn-aksi btn-edit btnEditKaryawan"
                                            data-id="{{ $staf->id_karyawan }}"
                                            data-nama-asli="{{ $staf->nama_staff }}"
                                            data-username="{{ $staf->username }}"
                                            data-email="{{ $staf->email }}"
                                            data-jabatan="{{ $staf->role_jabatan }}">
                                        ✏️ Edit
                                    </button>
                                    <button type="button" class="btn-aksi btn-hapus btnHapusKaryawan"
                                            data-nama-asli="{{ $staf->nama_staff }}">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah / Edit Karyawan -->
    <div class="modal-backdrop" id="modalKaryawan">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalKaryawan">Tambah Karyawan Baru</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form id="formKaryawan">
                <div class="form-grup">
                    <label class="form-label">Nama Lengkap Staf</label>
                    <input type="text" id="inputNamaStaf" class="form-input" placeholder="Contoh: Dewi Lestari" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Username Login</label>
                        <input type="text" id="inputUsernameStaf" class="form-input" placeholder="Contoh: dewi_stylist" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Jabatan</label>
                        <select id="inputJabatanStaf" class="form-select" required>
                            <option value="Stylist">Stylist</option>
                            <option value="Therapist">Therapist</option>
                            <option value="Kasir">Kasir</option>
                            <option value="Manajer">Manajer</option>
                        </select>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Alamat Email</label>
                    <input type="email" id="inputEmailStaf" class="form-input" placeholder="nama@salon.test" required>
                </div>

                <div class="form-grup">
                    <label class="form-label">Password Akun</label>
                    <input type="password" id="inputPasswordStaf" class="form-input" placeholder="Isi untuk membuat/mengganti password">
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" data-tutup-modal>Batal</button>
                    <button type="submit" class="btn-utama">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/karyawan.js'])
@endpush
