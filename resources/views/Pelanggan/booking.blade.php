@extends('pelanggan.layout')

@section('judul', 'Booking - Salon Rengganis')

@push('styles')
    @vite(['resources/css/pelanggan/booking.css'])
@endpush

@section('isi')
    <h1 class="judul-halaman">Booking Perawatan</h1>
    <p class="sub-halaman">Pilih tanggal, layanan, petugas, dan jam kedatangan Anda tanpa perlu membayar di muka.</p>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                ⚠️ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    @if ($kebijakan->kebijakan_aktif && $pelanggan->sedangDiblokir())
        <!-- Kotak Peringatan Blokir Sementara -->
        <div class="kotak-blokir">
            <h2>🚫 Fitur Booking Diblokir Sementara</h2>
            <p>
                Mohon maaf, Anda sementara tidak dapat mengirim permintaan booking baru karena:
                <strong>{{ $pelanggan->alasan_blokir ?: "Mencapai batas {$kebijakan->batas_pelanggaran} kali pelanggaran reservasi." }}</strong>
            </p>
            <div class="info-blokir-detail">
                <span>📅 Berakhir pada: <strong>{{ $pelanggan->diblokir_sampai->format('d/m/Y H:i') }} WIB</strong></span>
                <span>⏳ Sisa waktu: <strong>{{ $pelanggan->sisaHariBlokir() }} hari lagi</strong></span>
            </div>
            <p class="catatan-blokir">
                Setelah masa blokir berakhir, jumlah pelanggaran Anda akan direset otomatis ke 0. Jika terdapat kekeliruan, silakan hubungi pihak Salon Rengganis.
            </p>
        </div>
    @else
        @if ($kebijakan->kebijakan_aktif && $pelanggan->jumlah_pelanggaran > 0)
            <div class="peringatan-pinalti">
                ⚠️ <strong>Perhatian Kebijakan Salon:</strong> Anda saat ini memiliki
                <strong>{{ $pelanggan->jumlah_pelanggaran }} dari {{ $kebijakan->batas_pelanggaran }} pelanggaran</strong>.
                Pastikan Anda hadir atau membatalkan jadwal minimal {{ $kebijakan->batas_batal_jam }} jam sebelum jam kedatangan agar akun tidak diblokir selama {{ $kebijakan->lama_blokir_hari }} hari.
            </div>
        @endif

        <form method="POST" action="{{ route('pelanggan.booking.simpan') }}"
              id="formBooking"
              class="form-box"
              data-url-petugas="{{ route('pelanggan.booking.petugas') }}">
            @csrf

            <!-- Langkah 1: Tanggal & Jam Kedatangan -->
            <div class="langkah-judul"><span class="langkah-no">1</span> Pilih Tanggal &amp; Jam Kedatangan</div>
            <div class="baris-waktu">
                <div>
                    <label for="tgl_booking" class="label">Tanggal Kedatangan</label>
                    <input type="date" id="tgl_booking" name="tgl_booking"
                           value="{{ old('tgl_booking', now()->toDateString()) }}"
                           min="{{ now()->toDateString() }}" required>
                </div>

                <div>
                    <label for="jam_booking" class="label">Jam Kedatangan (Operasional)</label>
                    <select id="jam_booking" name="jam_booking" class="select-jam" required>
                        @foreach ($jamOperasional as $jam)
                            <option value="{{ $jam }}" @selected(old('jam_booking', '10:00') === $jam)>
                                Pukul {{ $jam }} WIB
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <small class="status-ketersediaan" id="statusKetersediaan">
                ✓ Menampilkan petugas yang tersedia untuk jadwal yang dipilih.
            </small>

            <!-- Langkah 2: Layanan & Petugas -->
            <div class="langkah-judul"><span class="langkah-no">2</span> Pilih Layanan &amp; Petugas</div>
            <p class="langkah-ket">
                Kategori <strong>Perawatan Rambut</strong> otomatis menampilkan <strong>Stylist</strong>, sedangkan <strong>Perawatan Wajah &amp; Tubuh</strong> menampilkan <strong>Therapist</strong> yang tidak sedang menangani pelanggan lain pada jam tersebut.
            </p>

            <input type="text" id="searchInput" class="search-input" placeholder="Ketik nama perawatan (contoh: Potong Rambut, Facial...)">

            <div class="daftar-opsi">
                @foreach ($layanan as $item)
                    @php
                        $jabatan       = $item->jabatan_petugas;
                        $daftarPetugas = $petugas->get($jabatan, collect());
                        $dipilih       = in_array($item->id_layanan, old('layanan', []));
                        $petugasLama   = old('petugas.' . $item->id_layanan);
                    @endphp

                    <div class="opsi-blok {{ $errors->has('petugas.' . $item->id_layanan) ? 'galat' : '' }}"
                         data-nama="{{ $item->nama_layanan }}"
                         data-jabatan="{{ $jabatan }}">
                        <label class="opsi">
                            <input type="checkbox" name="layanan[]" value="{{ $item->id_layanan }}"
                                   data-harga="{{ $item->harga_layanan }}"
                                   @checked($dipilih)>

                            <div class="opsi-info">
                                <span class="opsi-nama">{{ $item->nama_layanan }}</span>
                                <span class="opsi-kategori">{{ $item->kategori }}</span>
                                <span class="opsi-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</span>
                            </div>

                            <span class="lencana-jabatan lencana-{{ strtolower($jabatan) }}">{{ $jabatan }}</span>
                        </label>

                        <div class="pilih-petugas">
                            <label for="petugas_{{ $item->id_layanan }}" class="petugas-label">Pilih {{ $jabatan }}</label>
                            <select id="petugas_{{ $item->id_layanan }}"
                                    name="petugas[{{ $item->id_layanan }}]"
                                    class="select-petugas"
                                    data-jabatan="{{ $jabatan }}"
                                    data-pilihan-lama="{{ $petugasLama }}"
                                    @disabled(! $dipilih)>
                                <option value="">-- Pilih {{ $jabatan }} --</option>
                                @foreach ($daftarPetugas as $staf)
                                    <option value="{{ $staf->id_karyawan }}" @selected($petugasLama == $staf->id_karyawan)>
                                        {{ $staf->nama_staff }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="tidak-ditemukan" id="tidakDitemukan">Layanan yang Anda cari tidak ditemukan.</p>

            <!-- Ringkasan Total Harga -->
            <div class="summary-box">
                <div class="summary-text" id="jumlahPilihan">0 layanan dipilih</div>
                <div class="summary-total" id="totalHarga">Rp 0</div>
            </div>
            <small class="info-validasi" id="infoValidasi"></small>

            <button type="submit" id="submitBtn" class="btn btn-penuh">KIRIM PERMINTAAN BOOKING</button>
        </form>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/booking.js'])
@endpush
