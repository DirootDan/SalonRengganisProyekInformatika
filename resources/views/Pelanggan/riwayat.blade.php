@extends('pelanggan.layout')

@section('judul', 'Riwayat Reservasi - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Riwayat &amp; Pembatalan Reservasi</h1>
    <p class="sub-halaman">Pantau status permintaan booking Anda, lihat rincian perawatan, atau batalkan jadwal reservasi yang masih aktif.</p>

    @if ($errors->any())
        <div class="error-msg" role="alert">
            @foreach ($errors->all() as $error)
                <div>&bull; {{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($notifikasi->isNotEmpty())
        <div class="panel-notifikasi">
            <div class="notifikasi-header">
                <h3>Pemberitahuan Akun ({{ $notifikasi->where('dibaca', false)->count() }} belum dibaca)</h3>
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

    @php
        $jmlMenunggu = $reservasi->filter(fn ($item) => $item->statusTernormalisasi() === 'Menunggu')->count();
        $jmlDiterima = $reservasi->filter(fn ($item) => $item->statusTernormalisasi() === 'Diterima')->count();
        $jmlSelesai  = $reservasi->filter(fn ($item) => $item->statusTernormalisasi() === 'Selesai')->count();
        $jmlBatal    = $reservasi->filter(fn ($item) => in_array($item->statusTernormalisasi(), ['Dibatalkan', 'Ditolak', 'Hangus'], true))->count();
    @endphp

    @if ($reservasi->isNotEmpty())
        <div class="filter-riwayat-bar" role="tablist" aria-label="Filter Status Reservasi">
            <button type="button" class="tab-filter-riwayat aktif" data-filter-status="semua">
                Semua ({{ $reservasi->count() }})
            </button>
            <button type="button" class="tab-filter-riwayat" data-filter-status="Menunggu">
                Menunggu ({{ $jmlMenunggu }})
            </button>
            <button type="button" class="tab-filter-riwayat" data-filter-status="Diterima">
                Diterima ({{ $jmlDiterima }})
            </button>
            <button type="button" class="tab-filter-riwayat" data-filter-status="Selesai">
                Selesai ({{ $jmlSelesai }})
            </button>
            <button type="button" class="tab-filter-riwayat" data-filter-status="BatalGroup">
                Dibatalkan / Ditolak ({{ $jmlBatal }})
            </button>
        </div>
    @endif

    @forelse ($reservasi as $r)
        @php
            $status = $r->statusTernormalisasi();
            $kelasStatus = match ($status) {
                'Menunggu' => 'status-pending',
                'Diterima' => 'status-diterima',
                'Selesai'  => 'status-selesai',
                'Ditolak', 'Dibatalkan', 'Hangus' => 'status-batal',
                default    => 'status-pending',
            };
            $grupFilter = in_array($status, ['Dibatalkan', 'Ditolak', 'Hangus'], true) ? 'BatalGroup' : $status;
            $jamMulai   = $r->jam_booking ?: '10:00';
            $jamSelesai = $r->jam_selesai ?: '';

            $waktuJadwal = \Illuminate\Support\Carbon::parse($r->tgl_booking->toDateString() . ' ' . $jamMulai);
            $batasGratis = $waktuJadwal->copy()->subHours($kebijakan->batas_batal_jam);

            $statusAktifBisaBatal = in_array($status, ['Menunggu', 'Diterima'], true);
            $lewatBatasJamBatal   = $status === 'Diterima' && now()->gt($batasGratis);
            $bolehBatalSekarang   = $status === 'Menunggu' || ($status === 'Diterima' && ! $lewatBatasJamBatal);
        @endphp

        <div class="reservasi-item {{ $statusAktifBisaBatal ? 'aktif' : '' }}"
             data-status-item="{{ $status }}"
             data-grup-filter="{{ $grupFilter }}">
            <div class="reservasi-atas">
                <div class="reservasi-info-utama">
                    <div class="reservasi-tgl">
                        {{ $r->tgl_booking->format('d/m/Y') }} &bull; Pukul {{ $jamMulai }}{{ $jamSelesai ? ' - ' . $jamSelesai : '' }} WIB
                    </div>
                    <div class="reservasi-no">
                        Reservasi #{{ $r->id_reservasi }}
                        @if ($r->total_durasi_menit)
                            &bull; Estimasi {{ $r->total_durasi_menit }} menit
                        @endif
                        &bull; Total Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}
                    </div>
                </div>

                <div class="reservasi-kanan">
                    <span class="status {{ $kelasStatus }}">{{ $status }}</span>

                    @if ($bolehBatalSekarang)
                        <form method="POST"
                              action="{{ route('pelanggan.booking.batal', $r->id_reservasi) }}"
                              class="form-batal-booking form-batal-cepat"
                              data-id="{{ $r->id_reservasi }}"
                              data-confirm-title="Konfirmasi Pembatalan Reservasi"
                              data-confirm-item="Reservasi #{{ $r->id_reservasi }} ({{ $r->tgl_booking->format('d/m/Y') }} pukul {{ $jamMulai }} WIB)"
                              data-confirm-description="Apakah Anda yakin ingin membatalkan reservasi ini? Jadwal petugas akan dilepas dan stok produk add-on akan dikembalikan otomatis tanpa pinalti."
                              data-confirm-button="Ya, Batalkan Reservasi">
                            @csrf
                            <button type="submit" class="btn-batal-cepat" title="Batalkan Reservasi #{{ $r->id_reservasi }}">
                                ✕ Batalkan Booking
                            </button>
                        </form>
                    @endif

                    <button type="button" class="btn-toggle-rincian" aria-label="Buka atau tutup rincian reservasi #{{ $r->id_reservasi }}">
                        <span class="teks-toggle-rincian">Rincian</span>
                        <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
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
                                <span>
                                    <strong>{{ $l->nama_layanan }}</strong>
                                    @if ($l->pivot->jam_mulai && $l->pivot->jam_selesai)
                                        <small>({{ $l->pivot->jam_mulai }} - {{ $l->pivot->jam_selesai }} WIB)</small>
                                    @endif
                                </span>
                                @if ($l->pivot->id_karyawan && isset($petugas[$l->pivot->id_karyawan]))
                                    <small class="layanan-petugas">{{ $l->jabatan_petugas }}: {{ $petugas[$l->pivot->id_karyawan] }}</small>
                                @endif
                            </span>
                            <span>Rp {{ number_format($hargaTampil, 0, ',', '.') }}</span>
                        </li>
                    @endforeach

                    @foreach ($r->produkAddon as $prod)
                        @php
                            $hargaProd = $prod->pivot->harga_saat_ini > 0 ? $prod->pivot->harga_saat_ini : $prod->harga_produk;
                            $qtyProd   = (int) $prod->pivot->jumlah;
                        @endphp
                        <li>
                            <span class="layanan-info">
                                <span>Produk Add-On: <strong>{{ $prod->nama_produk }} ({{ $qtyProd }}x)</strong></span>
                            </span>
                            <span>Rp {{ number_format($hargaProd * $qtyProd, 0, ',', '.') }}</span>
                        </li>
                    @endforeach

                    @if ($r->kode_promo && (float) $r->nominal_diskon > 0)
                        <li>
                            <span class="layanan-info">
                                <span>Potongan Promo (<strong>{{ $r->kode_promo }}</strong>)</span>
                            </span>
                            <span>- Rp {{ number_format($r->nominal_diskon, 0, ',', '.') }}</span>
                        </li>
                    @endif
                </ul>

                <div class="reservasi-kaki-aksi">
                    <div class="panel-aksi-batal">
                        @if ($bolehBatalSekarang)
                            <form method="POST"
                                  action="{{ route('pelanggan.booking.batal', $r->id_reservasi) }}"
                                  class="form-batal-booking form-batal-detail"
                                  data-id="{{ $r->id_reservasi }}"
                                  data-confirm-title="Konfirmasi Pembatalan Reservasi"
                                  data-confirm-item="Reservasi #{{ $r->id_reservasi }} ({{ $r->tgl_booking->format('d/m/Y') }} pukul {{ $jamMulai }} WIB)"
                                  data-confirm-description="Apakah Anda yakin ingin membatalkan reservasi ini? Slot jadwal petugas akan dilepas dan stok produk add-on akan dikembalikan otomatis."
                                  data-confirm-button="Ya, Batalkan Reservasi">
                                @csrf
                                <div class="baris-input-batal">
                                    <input type="text"
                                           name="alasan_batal"
                                           class="input-alasan-batal"
                                           maxlength="200"
                                           placeholder="Alasan pembatalan (opsional, misal: jadwal berubah)...">
                                    <button type="submit" class="btn-batal-booking">
                                        ✕ Batalkan Reservasi Ini
                                    </button>
                                </div>
                                <small class="info-kebijakan-batal">
                                    @if ($status === 'Menunggu')
                                        Reservasi masih menunggu konfirmasi kasir dan dapat dibatalkan kapan saja tanpa pinalti.
                                    @else
                                        Pembatalan gratis tersedia hingga <strong>{{ $batasGratis->format('d/m/Y H:i') }} WIB</strong> ({{ $kebijakan->batas_batal_jam }} jam sebelum jadwal).
                                    @endif
                                </small>
                            </form>
                        @elseif ($lewatBatasJamBatal)
                            <div class="info-batal-terkunci">
                                <strong>Batas Pembatalan Online Berakhir:</strong>
                                Pembatalan mandiri hanya dapat dilakukan minimal {{ $kebijakan->batas_batal_jam }} jam sebelum jadwal kedatangan ({{ $batasGratis->format('d/m/Y H:i') }} WIB). Silakan hubungi kasir Salon Rengganis.
                            </div>
                        @endif
                    </div>

                    <div class="reservasi-total">
                        Total: Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}
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
