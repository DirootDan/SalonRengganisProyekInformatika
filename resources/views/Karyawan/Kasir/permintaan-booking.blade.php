@extends('Karyawan.Kasir.layout')

@section('judul', 'Permintaan Booking - Kasir Salon Rengganis')
@section('header', 'Permintaan Booking Pelanggan')
@section('sub_header', 'Terima atau tolak permintaan reservasi pelanggan. Pelanggan akan menerima notifikasi otomatis.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/permintaan.css'])
@endpush

@section('isi')
    <!-- Tab Filter Status Booking -->
    <div class="tab-status-bar">
        @foreach (['Menunggu', 'Diterima', 'Ditolak', 'Selesai', 'Hangus', 'Dibatalkan', 'Semua'] as $tab)
            <a href="{{ route('kasir.permintaan', ['status' => $tab]) }}"
               class="tab-status-item {{ $statusTab === $tab ? 'aktif' : '' }}">
                {{ $tab }}
            </a>
        @endforeach
    </div>

    <div class="panel-kartu">
        <div class="toolbar-atas">
            <input type="text" id="cariBooking" class="input-cari" placeholder="🔍 Cari nama pelanggan atau nomor reservasi...">
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Jadwal Kedatangan</th>
                        <th>Pelanggan &amp; Catatan Pinalti</th>
                        <th>Layanan &amp; Petugas</th>
                        <th>Total Estimasi</th>
                        <th>Status</th>
                        <th>Aksi Kasir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarBooking as $r)
                        @php
                            $pel = $r->pelanggan;
                            $status = $r->statusTernormalisasi();
                        @endphp
                        <tr class="baris-booking" data-cari="{{ strtolower('#' . $r->id_reservasi . ' ' . ($pel->nama_cust ?? '')) }}">
                            <td><strong>#{{ $r->id_reservasi }}</strong></td>
                            <td>
                                <strong>{{ $r->tgl_booking->format('d/m/Y') }}</strong><br>
                                <small>Pukul {{ $r->jam_booking ?: '10:00' }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }} WIB</small>
                            </td>
                            <td>
                                <strong>{{ $pel->nama_cust ?? 'Pelanggan' }}</strong><br>
                                <small>{{ $pel->no_telp ?? '-' }}</small>
                                @if ($pel && $pel->sedangDiblokir())
                                    <div><span class="lencana-pinalti-bahaya">Sedang Diblokir ({{ $pel->jumlah_pelanggaran }}/{{ $kebijakan->batas_pelanggaran }})</span></div>
                                @elseif ($pel && $pel->jumlah_pelanggaran > 0)
                                    <div><span class="lencana-pinalti-peringatan">Pelanggaran: {{ $pel->jumlah_pelanggaran }}/{{ $kebijakan->batas_pelanggaran }}</span></div>
                                @endif
                            </td>
                            <td>
                                @foreach ($r->layanan as $l)
                                    <div class="item-layanan-kecil">
                                        &bull; <strong>{{ $l->nama_layanan }}</strong>
                                        @if ($l->pivot->id_karyawan && isset($petugasMap[$l->pivot->id_karyawan]))
                                            <small>({{ $l->jabatan_petugas }}: {{ $petugasMap[$l->pivot->id_karyawan] }})</small>
                                        @endif
                                    </div>
                                @endforeach
                                @foreach ($r->produkAddon as $p)
                                    <div class="item-layanan-kecil">
                                        + <small>Produk Add-On: <strong>{{ $p->nama_produk }} (x{{ $p->pivot->jumlah }})</strong></small>
                                    </div>
                                @endforeach
                                @if ($r->kode_promo)
                                    <div class="item-layanan-kecil">
                                        <small class="badge badge-aktif">Promo {{ $r->kode_promo }} (-Rp {{ number_format((float) $r->nominal_diskon, 0, ',', '.') }})</small>
                                    </div>
                                @endif
                            </td>
                            <td><strong>Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}</strong></td>
                            <td>
                                <span class="badge {{ $status === 'Selesai' || $status === 'Diterima' ? 'badge-aktif' : ($status === 'Menunggu' ? 'badge-pending' : 'badge-nonaktif') }}">
                                    {{ $status }}
                                </span>
                                @if ($r->alasan_penolakan)
                                    <small class="alasan-kecil">{{ $r->alasan_penolakan }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($status === 'Menunggu')
                                    <div class="aksi-grup">
                                        <form method="POST" action="{{ route('kasir.permintaan.terima', $r->id_reservasi) }}">
                                            @csrf
                                            <button type="submit" class="btn-aksi btn-edit">✓ Terima</button>
                                        </form>

                                        <button type="button" class="btn-aksi btn-hapus btnBukaModalTolak"
                                                data-id="{{ $r->id_reservasi }}"
                                                data-pelanggan="{{ $pel->nama_cust ?? 'Pelanggan' }}"
                                                data-url="{{ route('kasir.permintaan.tolak', $r->id_reservasi) }}">
                                            ✕ Tolak
                                        </button>
                                    </div>
                                @elseif ($status === 'Diterima')
                                    <a href="{{ route('kasir.dashboard', ['muat_reservasi' => $r->id_reservasi]) }}" class="btn-aksi btn-edit">Proses Bayar</a>
                                @else
                                    <span class="teks-selesai">Selesai / Ditutup</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="teks-tengah-abu">Tidak ada permintaan booking pada status "{{ $statusTab }}".</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi-wadah">
            {{ $daftarBooking->links() }}
        </div>
    </div>

    <!-- Modal Penolakan Booking dengan Alasan Wajib -->
    <div class="modal-backdrop" id="modalTolakBooking">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalTolak">Tolak Permintaan Booking</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form method="POST" action="" id="formTolakBooking">
                @csrf
                <div class="form-grup">
                    <label class="form-label">Alasan Penolakan (Dikirim ke Notifikasi Pelanggan)</label>
                    <textarea name="alasan_penolakan" id="inputAlasanTolak" class="form-textarea" rows="3"
                              placeholder="Contoh: Mohon maaf, petugas sedang penuh pada jam tersebut. Silakan pilih jam lain." required minlength="4"></textarea>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" data-tutup-modal>Batal</button>
                    <button type="submit" class="btn-utama">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/permintaan.js'])
@endpush
