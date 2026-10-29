@extends('Manajer.layout')

@section('judul', 'Kelola Karyawan - Salon Rengganis')
@section('header', 'Data Karyawan & Komisi')
@section('sub_header', 'Kelola akun staf salon (Manajer, Kasir, Stylist, dan Therapist) beserta persentase komisinya.')

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
            <button type="button" class="btn-utama" id="btnTambahKaryawan" data-url-simpan="{{ route('manajer.karyawan.simpan') }}">
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
                        <th>Komisi</th>
                        <th>Status</th>
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
                            <td><strong>{{ number_format($staf->persen_komisi, 0) }}%</strong></td>
                            <td>
                                <span class="badge {{ $staf->aktif ? 'badge-aktif' : 'badge-nonaktif' }}">
                                    {{ $staf->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="aksi-grup">
                                    <button type="button" class="btn-aksi btn-edit btnEditKaryawan"
                                            data-url-update="{{ route('manajer.karyawan.update', $staf->id_karyawan) }}"
                                            data-nama-asli="{{ $staf->nama_staff }}"
                                            data-username="{{ $staf->username }}"
                                            data-email="{{ $staf->email }}"
                                            data-jabatan="{{ $staf->role_jabatan }}"
                                            data-komisi="{{ (float) $staf->persen_komisi }}"
                                            data-aktif="{{ $staf->aktif ? '1' : '0' }}">
                                        ✏️ Ubah
                                    </button>

                                    <form method="POST" action="{{ route('manajer.karyawan.hapus', $staf->id_karyawan) }}"
                                          class="form-hapus-staf"
                                          data-nama="{{ $staf->nama_staff }}"
                                          data-booking="{{ $staf->jumlahBookingAktif() }}"
                                          data-confirm-title="Konfirmasi Nonaktifkan & Arsipkan Karyawan"
                                          data-confirm-item="{{ $staf->nama_staff }} ({{ $staf->role_jabatan }})">
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

    <!-- Modal Tambah / Edit Karyawan -->
    <div class="modal-backdrop" id="modalKaryawan">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalKaryawan">Tambah Karyawan Baru</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form method="POST" action="{{ route('manajer.karyawan.simpan') }}" id="formKaryawan">
                @csrf
                <input type="hidden" name="_method" id="methodKaryawan" value="POST">

                <div class="form-grup">
                    <label class="form-label">Nama Lengkap Staf</label>
                    <input type="text" name="nama_staff" id="inputNamaStaf" class="form-input" placeholder="Contoh: Dewi Lestari" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Username Login</label>
                        <input type="text" name="username" id="inputUsernameStaf" class="form-input" placeholder="Contoh: dewi_stylist" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Jabatan</label>
                        <select name="role_jabatan" id="inputJabatanStaf" class="form-select" required>
                            <option value="Stylist">Stylist</option>
                            <option value="Therapist">Therapist</option>
                            <option value="Kasir">Kasir</option>
                            <option value="Manajer">Manajer</option>
                        </select>
                    </div>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Persentase Komisi (%)</label>
                        <input type="number" name="persen_komisi" id="inputKomisiStaf" class="form-input" min="0" max="100" step="1" value="15" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Status Keaktifan</label>
                        <select name="aktif" id="inputAktifStaf" class="form-select">
                            <option value="1">Aktif Melayani</option>
                            <option value="0">Nonaktif / Cuti</option>
                        </select>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="inputEmailStaf" class="form-input" placeholder="nama@salon.test" required>
                </div>

                <div class="form-grup">
                    <label class="form-label">Kata Sandi Akun</label>
                    <input type="password" name="password" id="inputPasswordStaf" class="form-input" placeholder="Minimal 6 karakter (kosongkan jika tidak diubah)">
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
