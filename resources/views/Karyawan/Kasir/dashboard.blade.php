@extends('Karyawan.Kasir.layout')

@section('judul', 'Dashboard Transaksi - Kasir Salon Rengganis')
@section('header', 'Meja Kasir & Pembayaran (POS)')
@section('sub_header', 'Fokus memproses pembayaran pelanggan walk-in maupun reservasi yang telah diterima.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/dashboard.css'])
@endpush

@section('isi')
    @php
        $petugasJson = $petugas->map(fn ($s) => [
            'id_karyawan'   => (int) $s->id_karyawan,
            'nama_staff'    => $s->nama_staff,
            'role_jabatan'  => $s->role_jabatan,
            'persen_komisi' => (float) $s->persen_komisi,
        ])->values();
    @endphp

    <!-- Area Utama Point of Sale (Fokus 2 Kolom Tanpa Gangguan Panel Antrean di Atas) -->
    <div class="pos-grid" data-muat-otomatis="{{ request()->query('muat_reservasi', '') }}">
        <!-- Kolom Kiri: Katalog Layanan & Produk -->
        <div class="panel-kartu pos-katalog-panel">
            <div class="pos-tab-bar">
                <button type="button" class="pos-tab aktif" data-kategori="semua">Semua Item</button>
                <button type="button" class="pos-tab" data-kategori="Layanan">Layanan Perawatan ({{ $layanan->count() }})</button>
                <button type="button" class="pos-tab" data-kategori="Produk">Produk Kecantikan ({{ $produk->count() }})</button>
            </div>

            <input type="text" id="cariItemPos" class="input-cari input-cari-penuh"
                   placeholder="Ketik nama layanan atau produk untuk ditambahkan ke keranjang...">

            <div class="katalog-grid">
                @foreach ($layanan as $l)
                    <button type="button" class="item-katalog"
                            data-id="L-{{ $l->id_layanan }}"
                            data-id-asli="{{ $l->id_layanan }}"
                            data-nama="{{ $l->nama_layanan }}"
                            data-harga="{{ (int) $l->harga_layanan }}"
                            data-jabatan="{{ $l->jabatan_petugas }}"
                            data-kategori="Layanan">
                        <span class="katalog-badge badge-stylist">{{ $l->jabatan_petugas }} &bull; {{ $l->durasi_menit }} mnt</span>
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
        @php
            $adaHoldDitolak = $daftarHold->contains('status', \App\Models\PermintaanOtorisasi::STATUS_DITOLAK);
            $adaHoldDisetujui = $daftarHold->contains('status', \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI);
            $kelasTombolHold = $adaHoldDitolak ? 'ada-ditolak' : ($adaHoldDisetujui ? 'ada-disetujui' : '');
        @endphp
        <form method="POST"
              action="{{ route('kasir.bayar') }}"
              data-url-bayar="{{ route('kasir.bayar') }}"
              data-url-hold="{{ route('kasir.otorisasi.hold') }}"
              data-batas-approval="{{ $batasWajibApproval }}"
              class="panel-kartu pos-keranjang"
              id="formTransaksiKasir"
              data-petugas='@json($petugasJson)'>
            @csrf
            <input type="hidden" name="id_reservasi" id="posIdReservasi" value="">
            <input type="hidden" name="id_otorisasi" id="posIdOtorisasi" value="">
            <input type="hidden" name="keranjang_disinkronkan" value="1">
            <div id="inputHiddenKeranjang"></div>

            <div class="keranjang-header">
                <div>
                    <h3>Tagihan &amp; Pembayaran</h3>
                    <small class="sub-keranjang" id="labelModeTransaksi">Mode: Pelanggan Walk-In / Umum</small>
                </div>
                <button type="button" class="btn-reset-keranjang" id="btnBersihkan">Reset / Walk-In</button>
            </div>

            <div id="peringatanPetugasPos" class="peringatan-petugas-pos" hidden role="alert"></div>

            <div class="form-grup">
                <div class="label-baris-pelanggan">
                    <label class="form-label tanpa-margin" for="posNamaPelanggan">Nama Pelanggan / Reservasi <span style="color:#db2777">*</span></label>
                    <div class="grup-tautan-pos">
                        <button type="button" class="tautan-hold-pos {{ $kelasTombolHold }}" id="btnBukaHoldPos">
                            ⏸️ Transaksi Hold ({{ $daftarHold->count() }})
                        </button>
                        <button type="button" class="tautan-pilih-antrean" id="btnPilihAntreanKecil">
                            Muat dari Antrean ({{ $reservasiAktif->count() }})
                        </button>
                    </div>
                </div>
                <input type="text" name="nama_pelanggan_walkin" id="posNamaPelanggan" class="form-input" placeholder="Wajib diisi: Ketik nama pelanggan walk-in..." required>
            </div>

            <div class="form-grup">
                <label class="form-label" for="posPetugas">Petugas Default (Untuk Transaksi Walk-in)</label>
                <select name="id_petugas" id="posPetugas" class="form-select">
                    <option value="">-- Pilih Petugas per Layanan di Keranjang --</option>
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
                    <label class="form-label" for="posKodePromo">Kode Voucer Promo (Otomatis dari Reservasi / Opsional)</label>
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
                    <label class="form-label" for="posMetodeBayar">Metode Pembayaran (Sesuai Pengaturan Manajer)</label>
                    <select name="metode_bayar" id="posMetodeBayar" class="form-select" required>
                        @foreach ($metodeAktif as $m)
                            <option value="{{ $m->nama_metode }}" data-kategori="{{ $m->kategori }}">
                                {{ $m->nama_metode }} ({{ $m->kategori }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-grup">
                    <label class="form-label" for="posUangDiterima">Nominal Uang Diterima (Rp)</label>
                    <input type="number" name="nominal_uang" id="posUangDiterima" class="form-input" min="0" step="any" placeholder="Masukkan nominal pembayaran (bisa Rp pas/pecahan)" required>
                    <div class="nominal-cepat">
                        <button type="button" class="btn-uang-pas" id="btnUangPas">Uang Pas</button>
                        <button type="button" class="btn-nominal" id="btnPecahanTerdekat" data-nominal="0">Pecahan Terdekat</button>
                        <button type="button" class="btn-nominal" data-nominal="50000">50.000</button>
                        <button type="button" class="btn-nominal" data-nominal="100000">100.000</button>
                        <button type="button" class="btn-nominal" data-nominal="200000">200.000</button>
                    </div>
                </div>

                <div class="baris-kembalian">
                    <span>Kembalian</span>
                    <strong id="teksKembalian">Rp 0</strong>
                </div>

                <!-- Panel Kontekstual Otorisasi Perubahan Layanan / Override Harga -->
                <div id="panelOtorisasiPos" class="panel-otorisasi-pos" hidden>
                    <div id="kepalaOtorisasiPos" class="kepala-otorisasi-pos"></div>

                    <div id="grupAlasanOtorisasiPos" class="form-grup spasi-alasan-pos">
                        <label class="form-label" for="inputAlasanPerubahanPos" id="labelAlasanPerubahanPos">
                            Alasan Perubahan Harga / Layanan *
                        </label>
                        <input type="text" name="alasan_perubahan" id="inputAlasanPerubahanPos" class="form-input"
                               placeholder="Wajib isi alasan (min. 5 karakter)..." maxlength="500">
                    </div>

                    <div id="aksiWajibApprovalPos" class="aksi-wajib-approval-pos" hidden>
                        <button type="button" class="btn-hold-kirim" id="btnKirimHoldManajer">
                            ⏸️ Kirim ke Manajer &amp; Hold Transaksi
                        </button>
                        <button type="button" class="btn-direct-override" id="btnBukaDirectOverride">
                            🔑 Otorisasi Manajer di Tempat
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-utama btn-bayar-penuh" id="btnProsesBayar">
                    Selesaikan Pembayaran &amp; Cetak Struk
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Pemilih Antrean Aktif (Hanya Muncul Saat Dibutuhkan agar POS Tidak Penuh/Sesak) -->
    <div class="modal-backdrop" id="modalPilihAntreanPos" aria-hidden="true">
        <div class="modal-kotak modal-kotak-lebar">
            <div class="modal-header">
                <div>
                    <h3>Pilih Reservasi Siap Bayar ({{ $reservasiAktif->count() }})</h3>
                    <small class="sub-antrean">Petugas, layanan, produk add-on, dan promo dari reservasi akan langsung terisi otomatis ke keranjang POS.</small>
                </div>
                <button type="button" class="btn-tutup-modal" id="btnTutupModalAntrean" aria-label="Tutup">&times;</button>
            </div>

            <div class="antrean-list-modal">
                @forelse ($reservasiAktif as $r)
                    @php
                        $namaPelanggan = $r->pelanggan?->nama_cust ?? 'Pelanggan';
                        $layananItems = $r->layanan->map(function ($l) use ($semuaPetugasMap) {
                            $idStaf = $l->pivot->id_karyawan ? (int) $l->pivot->id_karyawan : null;
                            $stafObj = $idStaf ? $semuaPetugasMap->get($idStaf) : null;
                            $stafAktif = $stafObj && $stafObj->aktif && $stafObj->deleted_at === null;

                            return [
                                'id'              => 'L-' . $l->id_layanan,
                                'id_asli'         => (int) $l->id_layanan,
                                'nama'            => $l->nama_layanan,
                                'harga'           => (int) ($l->pivot->harga_saat_ini ?: $l->harga_layanan),
                                'qty'             => 1,
                                'tipe'            => 'Layanan',
                                'jabatan_petugas' => $l->jabatan_petugas,
                                'id_petugas'      => $idStaf,
                                'nama_petugas'    => $stafObj?->nama_staff,
                                'petugas_aktif'   => $stafAktif,
                            ];
                        });

                        $produkItems = $r->produkAddon->map(fn ($p) => [
                            'id'      => 'P-' . $p->id_produk,
                            'id_asli' => (int) $p->id_produk,
                            'nama'    => $p->nama_produk,
                            'harga'   => (int) ($p->pivot->harga_saat_ini ?: $p->harga_produk),
                            'qty'     => (int) $p->pivot->jumlah,
                            'tipe'    => 'Produk',
                        ]);

                        $itemsJson = $layananItems->concat($produkItems)->values();
                    @endphp
                    <div class="antrean-kartu">
                        <div>
                            <span class="antrean-id">
                                #{{ $r->id_reservasi }} &bull; {{ $r->tgl_booking->format('d/m/Y') }} ({{ $r->jam_booking }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }})
                            </span>
                            <h4 class="antrean-nama">{{ $namaPelanggan }}</h4>
                            <p class="antrean-rincian">
                                {{ $r->layanan->pluck('nama_layanan')->join(', ') }}
                                @if ($r->produkAddon->isNotEmpty())
                                    &bull; +{{ $r->produkAddon->sum('pivot.jumlah') }} Produk Add-On
                                @endif
                                @if ($r->kode_promo)
                                    &bull; Promo: <strong>{{ $r->kode_promo }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="antrean-kanan">
                            <span class="antrean-total">Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}</span>
                            <button type="button" class="btn-muat-booking"
                                    data-id-reservasi="{{ $r->id_reservasi }}"
                                    data-pelanggan="{{ $namaPelanggan }}"
                                    data-kode-promo="{{ $r->kode_promo ?? '' }}"
                                    data-tipe-promo="{{ $r->tipe_diskon_promo ?? '' }}"
                                    data-nilai-promo="{{ (float) ($r->nilai_diskon_promo ?? 0) }}"
                                    data-nominal-diskon="{{ (float) ($r->nominal_diskon ?? 0) }}"
                                    data-items='@json($itemsJson)'>
                                Muat ke Kasir
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="antrean-kosong">
                        Belum ada antrean berstatus Diterima yang menunggu pembayaran.
                        <a href="{{ route('kasir.permintaan') }}">Cek Permintaan Booking &rarr;</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @include('Karyawan.Kasir.partials.modal-hold-pos')
    @include('Karyawan.Kasir.partials.modal-direct-override-pos')
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/dashboard.js'])
@endpush
