@extends('pelanggan.layout')

@section('judul', 'Riwayat Reservasi - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Riwayat &amp; Notifikasi Reservasi</h1>
    <p class="sub-halaman">Pantau status permintaan booking Anda serta pemberitahuan resmi dari Salon Rengganis.</p>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                ⚠️ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <!-- Kotak Notifikasi Dalam Aplikasi -->
    @if ($notifikasi->isNotEmpty())
        <div class="panel-notifikasi">
            <div class="notifikasi-header">
                <h3>🔔 Pemberitahuan Akun ({{ $notifikasi->where('dibaca', false)->count() }} belum dibaca)</h3>
                @if ($notifikasi->where('dibaca', false)->count() > 0)
                    <form method="POST" action="{{ route('pelanggan.notifikasi.baca') }}">
                        @csrf
                        <button type="submit" class="btn-tandai-baca">✓ Tandai Semua Sudah Dibaca</button>
                    </form>
                @endif
            </div>

            <div class="daftar-notifikasi">
                @foreach ($notifikasi as $n)
                    <div class="item-notifikasi {{ ! $n->dibaca ? 'belum-dibaca' : '' }} notif-{{ $n->tipe }}">
                        <div class="notif-atas">
                            <strong>{{ $n->judul }}</strong>
                            <span class="notif-waktu">
                                @if (! $n->dibaca)
                                    <span class="titik-baru">BARU</span>
                                @endif
                                {{ $n->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <p class="notif-pesan">{{ $n->pesan }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Daftar Riwayat Reservasi -->
    @forelse ($reservasi as $r)
        @php
            $status = $r->statusTernormalisasi();
            $kelasStatus = match ($status) {
                'Menunggu'   => 'status-pending',
                'Diterima'   => 'status-diterima',
                'Selesai'    => 'status-selesai',
                'Ditolak', 'Dibatalkan', 'Hangus' => 'status-batal',
                default      => 'status-pending',
            };
            $bolehBatal = in_array($status, ['Menunggu', 'Diterima'], true);
        @endphp

        <div class="reservasi-item">
            <div class="reservasi-atas">
                <div class="reservasi-info-utama">
                    <div class="reservasi-tgl">
                        {{ $r->tgl_booking->format('d/m/Y') }} &bull; Pukul {{ $r->jam_booking ?: '10:00' }} WIB
                    </div>
                    <div class="reservasi-no">Reservasi #{{ $r->id_reservasi }}</div>
                </div>
                <div class="reservasi-kanan">
                    <span class="status {{ $kelasStatus }}">{{ $status }}</span>
                    <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>

            <div class="reservasi-body">
                @if ($r->alasan_penolakan)
                    <div class="kotak-alasan">
                        <strong>Keterangan Status:</strong> {{ $r->alasan_penolakan }}
                    </div>
                @endif

                <ul class="reservasi-layanan">
                    @foreach ($r->layanan as $l)
                        @php
                            $hargaTampil = $l->pivot->harga_saat_ini > 0 ? $l->pivot->harga_saat_ini : $l->harga_layanan;
                        @endphp
                        <li>
                            <span class="layanan-info">
                                <span>{{ $l->nama_layanan }}</span>
                                @if ($l->pivot->id_karyawan && isset($petugas[$l->pivot->id_karyawan]))
                                    <small class="layanan-petugas">{{ $l->jabatan_petugas }}: {{ $petugas[$l->pivot->id_karyawan] }}</small>
                                @endif
                            </span>
                            <span>Rp {{ number_format($hargaTampil, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="reservasi-kaki-aksi">
                    @if ($bolehBatal)
                        <form method="POST" action="{{ route('pelanggan.booking.batal', $r->id_reservasi) }}" class="form-batal-booking">
                            @csrf
                            <button type="submit" class="btn-batal-booking">
                                ✕ Batalkan Reservasi (Min. {{ $kebijakan->batas_batal_jam }} Jam Sebelum Jadwal)
                            </button>
                        </form>
                    @else
                        <span></span>
                    @endif

                    <div class="reservasi-total">
                        Total: Rp {{ number_format($r->layanan->sum(fn ($l) => $l->pivot->harga_saat_ini > 0 ? $l->pivot->harga_saat_ini : $l->harga_layanan), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="kosong">
            Belum ada riwayat reservasi.<br>
            <a href="{{ route('pelanggan.booking') }}" class="btn">Buat Booking Pertama Anda</a>
        </div>
    @endforelse
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/riwayat.js'])
@endpush
