@extends('Karyawan.Kasir.layout')

@section('judul', 'Dashboard Transaksi - Kasir Salon Rengganis')
@section('header', 'Dashboard Transaksi (POS)')
@section('sub_header', 'Ringkasan reservasi, antrean terdekat, dan kasir pembayaran pelanggan.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/dashboard.css'])
@endpush

@section('isi')
    <!-- Ringkasan Angka (Menunggu, Diproses, Selesai) -->
    <div class="stat-kasir-grid">
        <a href="{{ route('kasir.permintaan', ['status' => 'Menunggu']) }}" class="stat-kasir-kartu">
            <span class="stat-kasir-ikon">📩</span>
            <div>
                <span class="stat-kasir-label">Permintaan Menunggu</span>
                <strong class="stat-kasir-nilai">{{ $jmlMenunggu }} Booking</strong>
            </div>
        </a>

        <a href="{{ route('kasir.antrean') }}" class="stat-kasir-kartu">
            <span class="stat-kasir-ikon">⏳</span>
            <div>
                <span class="stat-kasir-label">Sedang Diproses / Diterima</span>
                <strong class="stat-kasir-nilai">{{ $jmlDiproses }} Antrean</strong>
            </div>
        </a>

        <a href="{{ route('kasir.laporan') }}" class="stat-kasir-kartu">
            <span class="stat-kasir-ikon">✅</span>
            <div>
                <span class="stat-kasir-label">Reservasi Selesai</span>
                <strong class="stat-kasir-nilai">{{ $jmlSelesai }} Lunas</strong>
            </div>
        </a>
    </div>

    <!-- Antrean Terdekat yang Siap Dibayar -->
    <div class="panel-kartu panel-antrean-ringkas">
        <div class="toolbar-atas">
            <div>
                <h3 class="tanpa-margin">⏳ Antrean Aktif Terdekat (Siap Proses Bayar)</h3>
                <p class="sub-antrean">Menampilkan antrean berstatus Diterima terdekat. Untuk daftar lengkap, buka menu Antrean Hari Ini.</p>
            </div>
            <div class="aksi-grup">
                <a href="{{ route('kasir.permintaan') }}" class="btn-sekunder">📩 Permintaan Booking ({{ $jmlMenunggu }})</a>
                <a href="{{ route('kasir.antrean') }}" class="btn-sekunder">Lihat Semua Antrean &rarr;</a>
            </div>
        </div>

        <div class="antrean-list">
            @forelse ($reservasiAktif as $r)
                @php
                    $namaPelanggan = $r->pelanggan->nama_cust ?? 'Pelanggan';
                    $itemsJson = $r->layanan->map(fn ($l) => [
                        'id'        => 'L-' . $l->id_layanan,
                        'id_asli'   => $l->id_layanan,
                        'nama'      => $l->nama_layanan,
                        'harga'     => (int) ($l->pivot->harga_saat_ini ?: $l->harga_layanan),
                        'tipe'      => 'Layanan',
                    ])->values();
                @endphp
                <div class="antrean-kartu">
                    <div>
                        <span class="antrean-id">#{{ $r->id_reservasi }} &bull; {{ $r->tgl_booking->format('d/m/Y') }} ({{ $r->jam_booking }})</span>
                        <h4 class="antrean-nama">{{ $namaPelanggan }}</h4>
                        <p class="antrean-rincian">{{ $r->layanan->pluck('nama_layanan')->join(', ') }}</p>
                    </div>
                    <div class="antrean-kanan">
                        <span class="antrean-total">Rp {{ number_format($r->layanan->sum(fn ($l) => $l->pivot->harga_saat_ini ?: $l->harga_layanan), 0, ',', '.') }}</span>
                        <button type="button" class="btn-muat-booking"
                                data-id-reservasi="{{ $r->id_reservasi }}"
                                data-pelanggan="{{ $namaPelanggan }}"
                                data-items='@json($itemsJson)'>
                            📥 Muat ke Kasir
                        </button>
                    </div>
                </div>
            @empty
                <div class="antrean-kosong">Belum ada antrean berstatus Diterima yang menunggu pembayaran.</div>
            @endforelse
        </div>
    </div>

    <!-- Area Utama Point of Sale (2 Kolom) -->
    <div class="pos-grid">
        <!-- Kolom Kiri: Katalog Layanan & Produk -->
        <div class="panel-kartu">
            <div class="pos-tab-bar">
                <button type="button" class="pos-tab aktif" data-kategori="semua">Semua Item</button>
                <button type="button" class="pos-tab" data-kategori="Layanan">💇‍♀️ Layanan Perawatan</button>
                <button type="button" class="pos-tab" data-kategori="Produk">🧴 Produk Kecantikan</button>
            </div>

            <input type="text" id="cariItemPos" class="input-cari input-cari-penuh"
                   placeholder="🔍 Ketik nama layanan atau produk untuk ditambahkan ke keranjang...">

            <div class="katalog-grid">
                @foreach ($layanan as $l)
                    <button type="button" class="item-katalog"
                            data-id="L-{{ $l->id_layanan }}"
                            data-id-asli="{{ $l->id_layanan }}"
                            data-nama="{{ $l->nama_layanan }}"
                            data-harga="{{ (int) $l->harga_layanan }}"
                            data-kategori="Layanan">
                        <span class="katalog-badge badge-stylist">Layanan &bull; {{ $l->jabatan_petugas }}</span>
                        <strong class="katalog-nama">{{ $l->nama_layanan }}</strong>
                        <span class="katalog-harga">Rp {{ number_format($l->harga_layanan, 0, ',', '.') }}</span>
                    </button>
                @endforeach

                @foreach ($produk as $p)
                    <button type="button" class="item-katalog"
                            data-id="P-{{ $p->id_produk }}"
                            data-id-asli="{{ $p->id_produk }}"
                            data-nama="{{ $p->nama_produk }}"
                            data-harga="{{ (int) $p->harga_produk }}"
                            data-kategori="Produk">
                        <span class="katalog-badge badge-therapist">Produk &bull; Stok: {{ $p->stok_produk }}</span>
                        <strong class="katalog-nama">{{ $p->nama_produk }}</strong>
                        <span class="katalog-harga">Rp {{ number_format($p->harga_produk, 0, ',', '.') }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Kolom Kanan: Keranjang & Form Pembayaran Nyata -->
        <form method="POST" action="{{ route('kasir.bayar') }}" class="panel-kartu pos-keranjang" id="formTransaksiKasir">
            @csrf
            <input type="hidden" name="id_reservasi" id="posIdReservasi" value="">
            <div id="inputHiddenKeranjang"></div>

            <div class="keranjang-header">
                <h3>🧾 Tagihan &amp; Pembayaran</h3>
                <button type="button" class="btn-reset-keranjang" id="btnBersihkan">Kosongkan</button>
            </div>

            <div class="form-grup">
                <label class="form-label">Pelanggan / Reservasi</label>
                <input type="text" id="posNamaPelanggan" class="form-input" placeholder="Pelanggan Umum / Walk-in">
            </div>

            <div class="form-grup">
                <label class="form-label">Petugas yang Melayani (Walk-in)</label>
                <select name="id_petugas" id="posPetugas" class="form-select">
                    <option value="">-- Sesuai Reservasi / Pilih Petugas --</option>
                    @foreach ($petugas as $staf)
                        <option value="{{ $staf->id_karyawan }}">{{ $staf->nama_staff }} ({{ $staf->role_jabatan }})</option>
                    @endforeach
                </select>
            </div>

            <div class="daftar-keranjang" id="daftarKeranjang">
                <div class="keranjang-kosong">Belum ada layanan atau produk yang dipilih.</div>
            </div>

            <div class="ringkasan-bayar">
                <div class="form-grup">
                    <label class="form-label">Kode Voucer Promo (Opsional)</label>
                    <select name="kode_promo" id="posKodePromo" class="form-select">
                        <option value="" data-tipe="" data-nilai="0" data-min="0">-- Tanpa Promo --</option>
                        @foreach ($promoAktif as $pr)
                            <option value="{{ $pr->kode_promo }}"
                                    data-tipe="{{ $pr->tipe_diskon }}"
                                    data-nilai="{{ (float) $pr->nilai_diskon }}"
                                    data-min="{{ (float) $pr->min_transaksi }}">
                                {{ $pr->kode_promo }} - {{ $pr->nama_promo }} (Min. Rp {{ number_format($pr->min_transaksi, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="baris-total">
                    <span>Total Tagihan Akhir</span>
                    <strong id="teksTotalTagihan">Rp 0</strong>
                </div>

                <div class="form-grup">
                    <label class="form-label">Metode Pembayaran (Sesuai Pengaturan Manajer)</label>
                    <select name="metode_bayar" id="posMetodeBayar" class="form-select" required>
                        @foreach ($metodeAktif as $m)
                            <option value="{{ $m->nama_metode }}">{{ $m->ikon }} {{ $m->nama_metode }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-grup">
                    <label class="form-label">Nominal Uang Diterima (Rp)</label>
                    <input type="number" name="nominal_uang" id="posUangDiterima" class="form-input" min="0" step="1000" placeholder="Masukkan uang pelanggan" required>
                    <div class="nominal-cepat">
                        <button type="button" class="btn-uang-pas" id="btnUangPas">Uang Pas</button>
                        <button type="button" class="btn-nominal" data-nominal="50000">50.000</button>
                        <button type="button" class="btn-nominal" data-nominal="100000">100.000</button>
                        <button type="button" class="btn-nominal" data-nominal="200000">200.000</button>
                    </div>
                </div>

                <div class="baris-kembalian">
                    <span>Kembalian</span>
                    <strong id="teksKembalian">Rp 0</strong>
                </div>

                <button type="submit" class="btn-utama btn-bayar-penuh" id="btnProsesBayar">
                    🖨️ Selesaikan Pembayaran &amp; Cetak Struk
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/dashboard.js'])
@endpush
