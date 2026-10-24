@extends('pelanggan.layout')

@section('judul', 'Profil Saya - Salon Rengganis')

@push('styles')
    @vite(['resources/css/pelanggan/profil.css'])
@endpush

@section('isi')
    <h1 class="judul-halaman">Profil Saya</h1>
    <p class="sub-halaman">Kelola informasi pribadi dan keamanan akun Salon Rengganis Anda.</p>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                ⚠️ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="profil-wrapper">
        <!-- Kartu Ringkasan Pengguna -->
        <aside class="profil-kartu-info">
            <div class="profil-avatar-besar" id="avatarInisial">
                {{ strtoupper(substr($pelanggan->nama_cust, 0, 1)) }}
            </div>
            <h2 class="profil-nama" id="previewNama">{{ $pelanggan->nama_cust }}</h2>
            <span class="profil-badge">Pelanggan Setia</span>

            <div class="profil-detail-list">
                <div class="profil-detail-item">
                    <span class="detail-label">Email Terdaftar</span>
                    <span class="detail-val" id="previewEmail">{{ $pelanggan->email }}</span>
                </div>
                <div class="profil-detail-item">
                    <span class="detail-label">No. Telepon</span>
                    <span class="detail-val" id="previewTelp">{{ $pelanggan->no_telp }}</span>
                </div>
                <div class="profil-detail-item">
                    <span class="detail-label">Bergabung Sejak</span>
                    <span class="detail-val">
                        {{ $pelanggan->created_at ? $pelanggan->created_at->translatedFormat('d F Y') : '-' }}
                    </span>
                </div>
            </div>
        </aside>

        <!-- Area Pengaturan Tab (Edit Data Diri & Ganti Password) -->
        <section class="profil-konten">
            @php
                $bukaTabPassword = $errors->has('password_lama') || $errors->has('password');
            @endphp

            <div class="profil-tabs">
                <button type="button" class="tab-btn {{ !$bukaTabPassword ? 'aktif' : '' }}" data-target="tab-biodata">
                    👤 Data Diri
                </button>
                <button type="button" class="tab-btn {{ $bukaTabPassword ? 'aktif' : '' }}" data-target="tab-password">
                    🔒 Ganti Password
                </button>
            </div>

            <!-- Panel 1: Edit Data Diri -->
            <div class="tab-panel {{ !$bukaTabPassword ? 'aktif' : '' }}" id="tab-biodata">
                <div class="panel-header">
                    <h3>Informasi Pribadi</h3>
                    <p>Pastikan nomor telepon dan email Anda aktif untuk mempermudah konfirmasi reservasi.</p>
                </div>

                <form method="POST" action="{{ route('pelanggan.profil.update') }}" id="formBiodata">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="nama_cust" class="label">Nama Lengkap</label>
                        <input type="text" id="nama_cust" name="nama_cust" class="input-profil"
                               value="{{ old('nama_cust', $pelanggan->nama_cust) }}"
                               placeholder="Masukkan nama lengkap Anda" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="input-profil"
                               value="{{ old('email', $pelanggan->email) }}"
                               placeholder="contoh@email.com" required>
                    </div>

                    <div class="form-group">
                        <label for="no_telp" class="label">Nomor Telepon / WhatsApp</label>
                        <input type="text" id="no_telp" name="no_telp" class="input-profil"
                               value="{{ old('no_telp', $pelanggan->no_telp) }}"
                               placeholder="Contoh: 081234567890" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn" id="btnSimpanBiodata">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            <!-- Panel 2: Ganti Password -->
            <div class="tab-panel {{ $bukaTabPassword ? 'aktif' : '' }}" id="tab-password">
                <div class="panel-header">
                    <h3>Ubah Password Akun</h3>
                    <p>Gunakan kombinasi minimal 6 karakter agar akun Anda tetap aman.</p>
                </div>

                <form method="POST" action="{{ route('pelanggan.profil.password') }}" id="formPassword">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="password_lama" class="label">Password Saat Ini</label>
                        <div class="input-password-wrap">
                            <input type="password" id="password_lama" name="password_lama" class="input-profil"
                                   placeholder="Masukkan password saat ini" required>
                            <button type="button" class="toggle-pw" data-input="password_lama" aria-label="Tampilkan Password">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="label">Password Baru</label>
                        <div class="input-password-wrap">
                            <input type="password" id="password" name="password" class="input-profil"
                                   placeholder="Minimal 6 karakter" minlength="6" required>
                            <button type="button" class="toggle-pw" data-input="password" aria-label="Tampilkan Password">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="label">Konfirmasi Password Baru</label>
                        <div class="input-password-wrap">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="input-profil"
                                   placeholder="Ulangi password baru" minlength="6" required>
                            <button type="button" class="toggle-pw" data-input="password_confirmation" aria-label="Tampilkan Password">👁️</button>
                        </div>
                        <small id="pwMatchInfo" class="pw-match-info"></small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn" id="btnSimpanPassword">Perbarui Password</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/profil.js'])
@endpush
