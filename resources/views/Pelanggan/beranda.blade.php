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
                <div class="reservasi-item aktif">
                    <div class="reservasi-atas">
                        <div class="reservasi-info-utama">
                            <div class="reservasi-tgl">
                                {{ $r->tgl_booking->format('d/m/Y') }} &bull; Pukul {{ $r->jamMulaiTampil() }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }} WIB
                            </div>
                            <div class="reservasi-no">
                                Reservasi #{{ $r->id_reservasi }} &bull; {{ $r->layanan->pluck('nama_layanan')->join(', ') }}
                                &bull; Total Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="reservasi-kanan">
                            <span class="status {{ $r->kelasStatusBadge() }}">{{ $r->statusTernormalisasi() }}</span>
                            @if ($r->bolehDibatalkanPelanggan($kebijakan->batas_batal_jam))
                                <form method="POST"
                                      action="{{ route('pelanggan.booking.batal', $r->id_reservasi) }}"
                                      class="form-batal-booking form-batal-cepat"
                                      data-id="{{ $r->id_reservasi }}"
                                      data-confirm-title="Konfirmasi Pembatalan Reservasi"
                                      data-confirm-item="Reservasi #{{ $r->id_reservasi }} ({{ $r->tgl_booking->format('d/m/Y') }} pukul {{ $r->jamMulaiTampil() }} WIB)"
                                      data-confirm-description="Apakah Anda yakin ingin membatalkan reservasi ini? Pembatalan sebelum batas {{ $kebijakan->batas_batal_jam }} jam bebas pinalti (tidak hadir tanpa pembatalan dihitung pelanggaran)."
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
