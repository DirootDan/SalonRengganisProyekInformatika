@extends('Manajer.layout')

@section('judul', 'Persetujuan Kasir & Audit Otorisasi - Salon Rengganis')
@section('header', 'Persetujuan Kasir & Log Perubahan')
@section('sub_header', 'Tinjau permintaan override harga POS, perubahan layanan reservasi, hak akses 1x pakai, serta log mandiri kasir.')

@push('styles')
    @vite(['resources/css/Manajer/persetujuan.css'])
@endpush

@section('isi')
    <div class="tab-bar-persetujuan" role="tablist">
        <button type="button" class="tab-persetujuan aktif" data-target="panelPending" role="tab" aria-selected="true">
            ⏳ Menunggu Keputusan ({{ $daftarPending->count() }})
        </button>
        <button type="button" class="tab-persetujuan" data-target="panelAudit" role="tab" aria-selected="false">
            📋 Log Audit Perubahan &amp; Riwayat ({{ $logPerubahan->count() }})
        </button>
    </div>

    <!-- TAB 1: ANTREAN MENUNGGU KEPUTUSAN MANAJER -->
    <section id="panelPending" class="panel-tab-konten">
        @forelse ($daftarPending as $item)
            @php
                $labelKonteks = match ($item->konteks) {
                    \App\Models\PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI => 'POS Transaksi (≥ Rp 150.000)',
                    \App\Models\PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN => 'Akses 1x Katalog Layanan',
                    \App\Models\PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK => 'Akses 1x Katalog Produk',
                    default => $item->konteks,
                };
                $layananAwal = collect($item->snapshot_awal['layanan'] ?? []);
                $layananUsulan = collect($item->snapshot_keranjang['layanan'] ?? []);
                $produkUsulan = collect($item->snapshot_keranjang['produk'] ?? []);
            @endphp

            <article class="kartu-otorisasi">
                <div class="kartu-otorisasi-atas">
                    <div>
                        <span class="lencana-konteks">{{ $labelKonteks }}</span>
                        <span class="kode-tiket">#{{ $item->kode_referensi }}</span>
                        @if ($item->sedang_dihold)
                            <span class="lencana-hold">⏸️ Sedang Di-Hold di POS</span>
                        @endif
                        <h3 class="judul-target-otorisasi">{{ $item->label_target }}</h3>
                        <small class="meta-pengajuan">
                            Diajukan oleh <strong>{{ $item->kasir?->nama_staff ?? 'Kasir' }}</strong>
                            &bull; {{ $item->created_at?->format('d/m/Y H:i') }} WIB
                            &bull; Aksi: <strong>{{ strtoupper($item->jenis_aksi) }}</strong>
                        </small>
                    </div>

                    @if ($item->konteks === \App\Models\PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI)
                        <div class="kotak-perbandingan-total">
                            <span class="label-kecil">Perubahan Total Tagihan</span>
                            <div class="baris-angka-diff">
                                <span class="angka-coret">Rp {{ number_format((float) $item->total_awal, 0, ',', '.') }}</span>
                                <span class="panah-diff">&rarr;</span>
                                <strong class="angka-baru">Rp {{ number_format((float) $item->total_akhir, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="kotak-alasan-kasir">
                    <span class="label-alasan">Alasan Pengajuan Kasir:</span>
                    <p>{{ $item->alasan_kasir }}</p>
                </div>

                @if ($item->konteks === \App\Models\PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI)
                    <div class="rincian-diff-grid">
                        <div class="kolom-diff">
                            <h4>Kondisi Awal (Harga Normal / Booking)</h4>
                            <ul class="daftar-rincian-item">
                                @forelse ($layananAwal as $lAwal)
                                    <li>
                                        <span>💇 {{ $lAwal['nama'] }}</span>
                                        <span>Rp {{ number_format((float) $lAwal['harga_normal'], 0, ',', '.') }}</span>
                                    </li>
                                @empty
                                    <li class="teks-redup">Tidak ada layanan awal</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="kolom-diff kolom-diff-usulan">
                            <h4>Usulan Perubahan Kasir</h4>
                            <ul class="daftar-rincian-item">
                                @foreach ($layananUsulan as $lUsul)
                                    <li>
                                        <span>
                                            💇 {{ $lUsul['nama'] }}
                                            @if (! empty($lUsul['di_override']))
                                                <em class="tag-override">Override</em>
                                            @endif
                                        </span>
                                        <span>
                                            @if (! empty($lUsul['di_override']))
                                                <del>Rp {{ number_format((float) $lUsul['harga_normal'], 0, ',', '.') }}</del>
                                            @endif
                                            <strong>Rp {{ number_format((float) $lUsul['harga_aktif'], 0, ',', '.') }}</strong>
                                        </span>
                                    </li>
                                @endforeach
                                @foreach ($produkUsulan as $pUsul)
                                    <li>
                                        <span>
                                            🧴 {{ $pUsul['nama'] }} (x{{ $pUsul['qty'] }})
                                            @if (! empty($pUsul['di_override']))
                                                <em class="tag-override">Override</em>
                                            @endif
                                        </span>
                                        <span>
                                            @if (! empty($pUsul['di_override']))
                                                <del>Rp {{ number_format((float) $pUsul['harga_normal'], 0, ',', '.') }}</del>
                                            @endif
                                            <strong>Rp {{ number_format((float) $pUsul['harga_aktif'] * (int) $pUsul['qty'], 0, ',', '.') }}</strong>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="aksi-keputusan-baris">
                    <form method="POST" action="{{ route('manajer.persetujuan.setujui', $item->id_otorisasi) }}" class="form-setujui-inline">
                        @csrf
                        <input type="text" name="catatan_manajer" class="form-input input-catatan-kecil"
                               placeholder="Catatan persetujuan (opsional)...">
                        <button type="submit" class="btn-setujui">✅ Setujui Pengajuan</button>
                    </form>

                    <form method="POST" action="{{ route('manajer.persetujuan.tolak', $item->id_otorisasi) }}" class="form-tolak-inline">
                        @csrf
                        <input type="text" name="catatan_manajer" class="form-input input-catatan-kecil"
                               placeholder="Wajib isi alasan penolakan untuk Kasir..." required minlength="3">
                        <button type="submit" class="btn-tolak">❌ Tolak &amp; Kembalikan Normal</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel-kartu kotak-kosong-persetujuan">
                ✅ Tidak ada permintaan otorisasi Kasir yang sedang menunggu keputusan.
            </div>
        @endforelse
    </section>

    <!-- TAB 2: LOG AUDIT PERUBAHAN MANDIRI (< Rp 150.000) & RIWAYAT KEPUTUSAN -->
    <section id="panelAudit" class="panel-tab-konten tersembunyi">
        <div class="panel-kartu">
            <div class="header-audit-filter">
                <div>
                    <h3 class="judul-seksi">🔍 Jejak Audit Perubahan Harga, Layanan &amp; Katalog</h3>
                    <p class="sub-seksi">Mencatat seluruh perubahan, termasuk override mandiri Kasir di bawah Rp 150.000 maupun persetujuan Manajer.</p>
                </div>
                <form method="GET" action="{{ route('manajer.persetujuan') }}" class="form-filter-tanggal">
                    <input type="hidden" name="tab" value="audit">
                    <input type="date" name="tanggal" value="{{ $tanggalFilter }}" class="form-input">
                    <button type="submit" class="btn-utama">Filter Tanggal</button>
                    @if ($tanggalFilter !== '')
                        <a href="{{ route('manajer.persetujuan', ['tab' => 'audit']) }}" class="btn-reset-filter">Reset</a>
                    @endif
                </form>
            </div>

            <div class="tabel-responsif">
                <table class="tabel-data">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Target / Nota</th>
                            <th>Kasir</th>
                            <th>Mekanisme Otorisasi</th>
                            <th>Nilai Awal &rarr; Akhir</th>
                            <th>Alasan Tercatat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logPerubahan as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <strong>{{ $log->label_target }}</strong>
                                    @if ($log->id_transaksi)
                                        <div class="sub-baris-tabel">Nota #INV-{{ $log->id_transaksi }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->kasir?->nama_staff ?? '-' }}</td>
                                <td>
                                    @if ($log->mekanisme_otorisasi === \App\Models\LogPerubahanOtorisasi::MEKANISME_MANDIRI_BAWAH_AMBANG)
                                        <span class="badge-mekanisme badge-mandiri">Mandiri (&lt; Rp 150rb)</span>
                                    @elseif ($log->mekanisme_otorisasi === \App\Models\LogPerubahanOtorisasi::MEKANISME_KREDENSIAL_LANGSUNG)
                                        <span class="badge-mekanisme badge-direct">Direct Override ({{ $log->manajer?->nama_staff ?? 'Manajer' }})</span>
                                    @else
                                        <span class="badge-mekanisme badge-dashboard">Approved Dashboard ({{ $log->manajer?->nama_staff ?? 'Manajer' }})</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="angka-coret">Rp {{ number_format((float) $log->nilai_awal, 0, ',', '.') }}</span>
                                    &rarr;
                                    <strong>Rp {{ number_format((float) $log->nilai_akhir, 0, ',', '.') }}</strong>
                                </td>
                                <td>{{ $log->alasan }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="sel-kosong">Belum ada catatan perubahan harga, layanan, atau katalog.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/persetujuan.js'])
@endpush
