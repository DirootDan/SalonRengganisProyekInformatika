@extends('pelanggan.layout')

@section('judul', 'Beranda - Salon Rengganis')

@section('isi')
    <div class="kartu-sambutan">
        <h1 class="judul-halaman">Selamat Datang, {{ auth('pelanggan')->user()->nama_cust }}!</h1>
        <p class="sub-halaman">Kami siap memberikan pelayanan terbaik untuk kecantikan dan perawatan Anda hari ini.</p>
    </div>

    @if (isset($reservasiAktif) && $reservasiAktif->isNotEmpty())
        <div class="panel-notifikasi">
            <div class="notifikasi-header">
                <h3>Jadwal Booking Aktif Anda ({{ $reservasiAktif->count() }} Reservasi)</h3>
                <a href="{{ route('pelanggan.riwayat') }}" class="btn-tandai-baca">Lihat Semua Riwayat &rarr;</a>
            </div>

            @foreach ($reservasiAktif as $r)
                @php
                    $status = $r->statusTernormalisasi();
                    $jamMulai = $r->jam_booking ?: '10:00';
                    $waktuJadwal = \Illuminate\Support\Carbon::parse($r->tgl_booking->toDateString() . ' ' . $jamMulai);
                    $batasGratis = $waktuJadwal->copy()->subHours($kebijakan->batas_batal_jam);
                    $bolehBatal = $status === 'Menunggu' || ($status === 'Diterima' && now()->lte($batasGratis));
                @endphp
                <div class="reservasi-item aktif">
                    <div class="reservasi-atas">
                        <div class="reservasi-info-utama">
                            <div class="reservasi-tgl">
                                {{ $r->tgl_booking->format('d/m/Y') }} &bull; Pukul {{ $jamMulai }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }} WIB
                            </div>
                            <div class="reservasi-no">
                                Reservasi #{{ $r->id_reservasi }} &bull; {{ $r->layanan->pluck('nama_layanan')->join(', ') }}
                                &bull; Total Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="reservasi-kanan">
                            <span class="status {{ $status === 'Diterima' ? 'status-diterima' : 'status-pending' }}">{{ $status }}</span>
                            @if ($bolehBatal)
                                <form method="POST"
                                      action="{{ route('pelanggan.booking.batal', $r->id_reservasi) }}"
                                      class="form-batal-booking form-batal-cepat"
                                      data-id="{{ $r->id_reservasi }}"
                                      data-confirm-title="Konfirmasi Pembatalan Reservasi"
                                      data-confirm-item="Reservasi #{{ $r->id_reservasi }} ({{ $r->tgl_booking->format('d/m/Y') }} pukul {{ $jamMulai }} WIB)"
                                      data-confirm-description="Apakah Anda yakin ingin membatalkan reservasi ini? Jadwal petugas akan dilepas dan stok produk add-on akan dikembalikan otomatis."
                                      data-confirm-button="Ya, Batalkan Reservasi">
                                    @csrf
                                    <button type="submit" class="btn-batal-cepat">✕ Batalkan Booking</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="kosong">
        <p>Nikmati perawatan rambut, wajah, dan tubuh bersama Stylist serta Therapist berpengalaman di Salon Rengganis.</p>
        <a href="{{ route('pelanggan.layanan') }}" class="btn">Lihat Daftar Layanan</a>
    </div>
@endsection
