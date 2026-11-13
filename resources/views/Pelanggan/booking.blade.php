@extends('pelanggan.layout')

@section('judul', 'Booking Perawatan - Salon Rengganis')

@push('styles')
    @vite(['resources/css/pelanggan/booking.css'])
@endpush

@section('isi')
    <!-- Header Ringkas (Task Mode: Fokus Langsung ke Pemesanan) -->
    <div class="booking-header-ringkas">
        <div>
            <h1 class="booking-judul-ringkas">Buat Reservasi Perawatan</h1>
            <p class="booking-sub-ringkas">Pilih perawatan favorit Anda, tentukan jam kedatangan, dan datang tanpa antre.</p>
        </div>
        <span class="lencana-jam-buka">🕘 Jam Operasional: 09:00 – 21:00 WIB</span>
    </div>

    @if ($errors->any())
        <div class="error-msg" role="alert">
            @foreach ($errors->all() as $error)
                <div>&bull; {{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($kebijakan->kebijakan_aktif && $pelanggan->sedangDiblokir())
        <div class="kotak-blokir">
            <h2>Fitur Booking {{ $pelanggan->diblokir_permanen ? 'Diblokir (Menunggu Konfirmasi Manajer)' : 'Diblokir Sementara' }}</h2>
            <p>
                Mohon maaf, Anda tidak dapat mengirim permintaan booking baru karena:
                <strong>{{ $pelanggan->alasan_blokir ?: "Mencapai batas {$kebijakan->batas_pelanggaran} kali pelanggaran reservasi." }}</strong>
            </p>
            <div class="info-blokir-detail">
                @if ($pelanggan->diblokir_permanen)
                    <span>Status: <strong>Blokir Tahap {{ max(3, (int) $pelanggan->jumlah_kali_blokir) }} (Tetap diblokir sampai Manajer membuka manual)</strong></span>
                @elseif ($pelanggan->diblokir_sampai)
                    <span>Berakhir pada: <strong>{{ $pelanggan->diblokir_sampai->format('d/m/Y H:i') }} WIB</strong></span>
                    <span>Sisa waktu: <strong>{{ $pelanggan->sisaHariBlokir() }} hari lagi</strong></span>
                @endif
            </div>
            <p class="catatan-blokir">
                @if ($pelanggan->diblokir_permanen)
                    Silakan hubungi Manajer Salon Rengganis untuk mengajukan pembukaan blokir akun secara manual.
                @else
                    Setelah masa blokir berakhir, jumlah pelanggaran aktif Anda akan direset otomatis ke 0.
                @endif
            </p>
        </div>
    @else
        @if ($kebijakan->kebijakan_aktif && $pelanggan->jumlah_pelanggaran > 0)
            <div class="peringatan-pinalti">
                <strong>Perhatian Kebijakan Salon:</strong> Anda saat ini memiliki
                <strong>{{ $pelanggan->jumlah_pelanggaran }} dari {{ $kebijakan->batas_pelanggaran }} pelanggaran</strong>.
                Pastikan Anda hadir atau membatalkan jadwal minimal {{ $kebijakan->batas_batal_jam }} jam sebelum jam kedatangan agar akun tidak diblokir selama {{ $kebijakan->lama_blokir_hari }} hari.
            </div>
        @endif

        <form method="POST"
              action="{{ route('pelanggan.booking.simpan') }}"
              id="formBooking"
              class="booking-portal-form"
              data-url-petugas="{{ route('pelanggan.booking.petugas') }}">
            @csrf

            <!-- Navigasi Stepper Wizard 3 Langkah Bersih -->
            <nav class="stepper-booking" aria-label="Langkah Pemesanan Perawatan">
                <button type="button" class="stepper-item aktif" data-step-target="1" id="tabStep1">
                    <span class="stepper-nomor">1</span>
                    <div class="stepper-teks">
                        <strong>Pilih Perawatan</strong>
                        <small id="subStep1">Pilih layanan &amp; petugas</small>
                    </div>
                </button>
                <span class="stepper-garis" aria-hidden="true"></span>
                <button type="button" class="stepper-item" data-step-target="2" id="tabStep2">
                    <span class="stepper-nomor">2</span>
                    <div class="stepper-teks">
                        <strong>Tanggal &amp; Jam</strong>
                        <small id="subStep2">{{ $tglTerpilihAwal }} &bull; {{ $jamTerpilihAwal }} WIB</small>
                    </div>
                </button>
                <span class="stepper-garis" aria-hidden="true"></span>
                <button type="button" class="stepper-item" data-step-target="3" id="tabStep3">
                    <span class="stepper-nomor">3</span>
                    <div class="stepper-teks">
                        <strong>Add-On &amp; Promo</strong>
                        <small id="subStep3">Opsional &amp; konfirmasi</small>
                    </div>
                </button>
            </nav>

            <div class="booking-portal-grid">
                <!-- =================================================
                     KOLOM KIRI: WIZARD 3 LANGKAH TERARAH
                     ================================================= -->
                <div class="portal-kiri">

                    <!-- LANGKAH 1: Pilih Kategori Layanan (Stylist vs Therapist) + Chip Pilihan Aktif -->
                    <section class="portal-kartu step-panel aktif" data-step-panel="1" id="panelStep1">
                        <div class="portal-kartu-kepala">
                            <span class="langkah-no">1</span>
                            <div>
                                <h2 class="portal-seksi-judul">Pilih Perawatan &amp; Petugas</h2>
                                <p class="portal-seksi-sub">
                                    Klik kartu perawatan untuk memilih. Durasi pengerjaan otomatis dijumlahkan saat menentukan jadwal.
                                </p>
                            </div>
                        </div>

                        <!-- Bar Filter Kategori Rapi (Tidak Terpotong) -->
                        <div class="filter-kategori-bar" role="tablist" aria-label="Kategori Layanan Salon">
                            <button type="button" class="tab-kat-item aktif" data-filter-jabatan="semua">
                                ✨ Semua ({{ $layanan->count() }})
                            </button>

                            <button type="button" class="tab-kat-item" data-filter-jabatan="Stylist">
                                💇‍♀️ Hair Stylist ({{ $jmlStylistLayanan }})
                            </button>

                            <button type="button" class="tab-kat-item" data-filter-jabatan="Therapist">
                                🌿 Spa Therapist ({{ $jmlTherapistLayanan }})
                            </button>

                            <button type="button" class="tab-kat-item tab-kat-terpilih" data-filter-jabatan="terpilih" id="tabLayananTerpilih">
                                ✅ Dipilih (<span id="badgeTabLayananTerpilih">0</span>)
                            </button>
                        </div>

                        <!-- Bar Chip Layanan yang Sedang Terpilih (Anti-Lupa Saat Pindah Halaman) -->
                        <div class="bar-chip-terpilih" id="barChipTerpilih" hidden>
                            <div class="bar-chip-kepala">
                                <span class="bar-chip-judul" id="judulChipTerpilih">Perawatan terpilih:</span>
                                <button type="button" class="btn-reset-pilihan" id="btnResetSemuaLayanan">Hapus Semua</button>
                            </div>
                            <div class="daftar-chip-terpilih" id="daftarChipTerpilih"></div>
                        </div>

                        <div class="baris-filter-katalog">
                            <input type="text"
                                   id="searchInput"
                                   class="search-input tanpa-margin"
                                   placeholder="Cari nama perawatan (contoh: Smoothing, Potong Rambut, Facial, Massage...)">
                        </div>

                        <div class="daftar-opsi-modern" id="daftarLayananGrid">
                            @foreach ($layanan as $item)
                                @php
                                    $jabatan       = $item->jabatan_petugas;
                                    $daftarPetugas = $petugas->get($jabatan, collect());
                                    $dipilih       = in_array((int) $item->id_layanan, $daftarLayananAwal, true);
                                    $petugasLama   = old('petugas.' . $item->id_layanan, '');
                                    $durasiMenit   = (int) ($item->durasi_menit ?: 60);
                                    $jedaMenit     = (int) ($item->jeda_menit ?? 15);
                                @endphp

                                <div class="opsi-blok {{ $dipilih ? 'terpilih' : '' }} {{ $errors->has('petugas.' . $item->id_layanan) ? 'galat' : '' }}"
                                     data-id-layanan="{{ $item->id_layanan }}"
                                     data-nama="{{ $item->nama_layanan }}"
                                     data-jabatan="{{ $jabatan }}">
                                    <label class="opsi">
                                        <input type="checkbox"
                                               name="layanan[]"
                                               value="{{ $item->id_layanan }}"
                                               data-nama-layanan="{{ $item->nama_layanan }}"
                                               data-jabatan="{{ $jabatan }}"
                                               data-harga="{{ (float) $item->harga_layanan }}"
                                               data-durasi="{{ $durasiMenit }}"
                                               data-jeda="{{ $jedaMenit }}"
                                               @checked($dipilih)>

                                        <div class="opsi-info">
                                            <span class="opsi-nama">{{ $item->nama_layanan }}</span>
                                            <span class="opsi-meta-ringkas">
                                                ⏱ {{ $durasiMenit }} menit &bull; {{ $jabatan }}
                                            </span>
                                        </div>

                                        <div class="opsi-kanan-harga">
                                            <span class="opsi-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</span>
                                            <span class="indikator-pilih">{{ $dipilih ? '✓ Terpilih' : '+ Pilih' }}</span>
                                        </div>
                                    </label>

                                    <div class="pilih-petugas">
                                        <label for="petugas_{{ $item->id_layanan }}" class="petugas-label">
                                            Preferensi {{ $jabatan }} (opsional):
                                        </label>
                                        <select id="petugas_{{ $item->id_layanan }}"
                                                name="petugas[{{ $item->id_layanan }}]"
                                                class="select-petugas"
                                                data-jabatan="{{ $jabatan }}"
                                                data-pilihan-lama="{{ $petugasLama }}"
                                                data-tersedia="{{ $daftarPetugas->count() }}"
                                                @disabled(! $dipilih)>
                                            <option value="">🤖 Pilihkan {{ $jabatan }} Otomatis ({{ $daftarPetugas->count() }} tersedia)</option>
                                            @foreach ($daftarPetugas as $staf)
                                                <option value="{{ $staf->id_karyawan }}" @selected((string) $petugasLama === (string) $staf->id_karyawan)>
                                                    {{ $staf->nama_staff }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="tidak-ditemukan" id="tidakDitemukan">Layanan yang Anda cari tidak ditemukan pada kategori ini.</p>

                        <!-- Navigasi Halaman (Pagination) Layanan -->
                        <div class="katalog-paginasi" id="paginasiLayanan">
                            <span class="info-halaman-katalog" id="infoHalamanLayanan">Menampilkan layanan</span>
                            <div class="tombol-halaman-grup">
                                <button type="button" class="btn-halaman" id="btnPrevLayanan">&laquo; Sebelumnya</button>
                                <span class="indikator-halaman" id="teksHalLayanan">1 / 1</span>
                                <button type="button" class="btn-halaman" id="btnNextLayanan">Berikutnya &raquo;</button>
                            </div>
                        </div>
                    </section>

                    <!-- LANGKAH 2: Pilih Tanggal & Jam Kedatangan (Bersih & Terfokus) -->
                    <section class="portal-kartu step-panel" data-step-panel="2" id="panelStep2">
                        <div class="portal-kartu-kepala">
                            <span class="langkah-no">2</span>
                            <div>
                                <h2 class="portal-seksi-judul">Pilih Tanggal &amp; Jam Kedatangan</h2>
                                <p class="portal-seksi-sub">
                                    Pilih tanggal dan klik slot jam yang tersedia (jam operasional <strong>09:00 – 21:00 WIB</strong>). Sistem otomatis mencocokkan ketersediaan petugas dengan durasi perawatan Anda.
                                </p>
                            </div>
                        </div>

                        <div class="jadwal-baris-tanggal">
                            <div class="kolom-input-tanggal">
                                <label for="tgl_booking" class="label">1. Tanggal Kedatangan</label>
                                <input type="date"
                                       id="tgl_booking"
                                       name="tgl_booking"
                                       class="input-tanggal-modern"
                                       value="{{ $tglTerpilihAwal }}"
                                       min="{{ now()->toDateString() }}"
                                       required>
                                <div class="chip-tanggal-grup">
                                    <button type="button" class="chip-tanggal" data-tgl="{{ now()->toDateString() }}">
                                        Hari Ini ({{ now()->format('d/m') }})
                                    </button>
                                    <button type="button" class="chip-tanggal" data-tgl="{{ now()->addDay()->toDateString() }}">
                                        Besok ({{ now()->addDay()->format('d/m') }})
                                    </button>
                                    <button type="button" class="chip-tanggal" data-tgl="{{ now()->addDays(2)->toDateString() }}">
                                        Lusa ({{ now()->addDays(2)->format('d/m') }})
                                    </button>
                                </div>
                            </div>

                            <div class="kolom-ringkasan-waktu-terpilih">
                                <span class="label">Ringkasan Waktu Kedatangan</span>
                                <div class="kotak-jam-aktif-besar">
                                    <div class="jam-aktif-utama">
                                        <span class="jam-aktif-ikon">🕒</span>
                                        <div>
                                            <strong id="teksJamTerpilihBesar">{{ $jamTerpilihAwal }} WIB</strong>
                                            <small id="teksEstimasiSelesaiStep2">Pilih slot jam di bawah</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-toggle-menit-kustom" id="btnToggleMenitKustom" aria-expanded="false">
                                        ⚙️ Pilih Menit Spesifik
                                    </button>
                                </div>

                                <!-- Panel Lipat (Collapsible) untuk Menit Spesifik -->
                                <div class="panel-menit-kustom" id="panelMenitKustom" hidden>
                                    <label for="jam_booking" class="label-menit-kecil">Ketik jam &amp; menit bebas (09:00 – 20:30):</label>
                                    <div class="baris-time-picker">
                                        <input type="time"
                                               id="jam_booking"
                                               name="jam_booking"
                                               class="input-waktu-modern"
                                               value="{{ $jamTerpilihAwal }}"
                                               min="09:00"
                                               max="20:30"
                                               step="60"
                                               required>
                                        <div class="kotak-Menit-Cepat" aria-label="Atur Menit Cepat">
                                            <span class="label-menit-kecil">Menit:</span>
                                            <div class="grup-menit-cepat">
                                                <button type="button" class="btn-menit-cepat" data-menit="00">:00</button>
                                                <button type="button" class="btn-menit-cepat" data-menit="15">:15</button>
                                                <button type="button" class="btn-menit-cepat" data-menit="30">:30</button>
                                                <button type="button" class="btn-menit-cepat" data-menit="45">:45</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="slot-jam-wadah">
                            <div class="slot-jam-header">
                                <span class="label tanpa-margin">2. Pilih Slot Jam Tersedia</span>
                                <small class="status-ketersediaan" id="statusKetersediaan" aria-live="polite">
                                    Menampilkan ketersediaan Stylist &amp; Therapist...
                                </small>
                            </div>

                            <div class="grid-slot-jam" id="gridSlotJam" role="radiogroup" aria-label="Pintasan Jam Kedatangan">
                                @foreach ($jamOperasional as $jam)
                                    <button type="button"
                                            class="chip-jam {{ $jamTerpilihAwal === $jam ? 'aktif' : '' }}"
                                            data-jam="{{ $jam }}">
                                        <span class="chip-jam-angka">{{ $jam }}</span>
                                        <span class="chip-jam-status">Tersedia</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="step-navigasi-bawah">
                            <button type="button" class="btn-step-kembali" data-lanjut-step="1">
                                &larr; Kembali ke Pilihan Layanan
                            </button>
                        </div>
                    </section>

                    <!-- LANGKAH 3: Produk Add-On Tambahan (Opsional) -->
                    <section class="portal-kartu step-panel" data-step-panel="3" id="panelStep3">
                        <div class="portal-kartu-kepala">
                            <span class="langkah-no">3</span>
                            <div>
                                <h2 class="portal-seksi-judul">Produk Perawatan Tambahan / Add-On (Opsional)</h2>
                                <p class="portal-seksi-sub">
                                    Tambahkan produk resmi Salon Rengganis bila diinginkan, atau langsung tekan tombol <strong>Konfirmasi &amp; Buat Reservasi</strong> di panel kanan.
                                </p>
                            </div>
                        </div>

                        @if ($produk->isEmpty())
                            <div class="kotak-info-kosong">Saat ini belum ada produk tambahan yang tersedia.</div>
                        @else
                            <div class="toolbar-produk-addon">
                                <input type="text"
                                       id="searchProdukInput"
                                       class="search-input tanpa-margin"
                                       placeholder="Cari dari {{ $produk->count() }} produk (ketik nama vitamin, serum, shampoo, masker...)">
                                <div class="filter-produk-grup" role="tablist" aria-label="Filter Produk Add-On">
                                    <button type="button" class="chip-filter-produk aktif" data-filter-produk="semua">
                                        Semua ({{ $produk->count() }})
                                    </button>
                                    <button type="button" class="chip-filter-produk" data-filter-produk="terpilih" id="btnFilterProdukTerpilih">
                                        Sudah Dipilih (0)
                                    </button>
                                </div>
                            </div>

                            <div class="daftar-produk-addon" id="daftarProdukGrid">
                                @foreach ($produk as $prod)
                                    @php
                                        $qtyLama = (int) old('produk_addon.' . $prod->id_produk, 0);
                                    @endphp
                                    <div class="kartu-addon {{ $qtyLama > 0 ? 'aktif' : '' }}"
                                         data-id-produk="{{ $prod->id_produk }}"
                                         data-nama-produk="{{ strtolower($prod->nama_produk) }}">
                                        <div class="addon-info">
                                            <strong class="addon-nama">{{ $prod->nama_produk }}</strong>
                                            <span class="addon-harga">Rp {{ number_format($prod->harga_produk, 0, ',', '.') }}</span>
                                            <span class="addon-meta">Stok tersedia: {{ $prod->stok_produk }} pcs</span>
                                        </div>
                                        <div class="addon-kontrol">
                                            <button type="button" class="btn-qty-addon" data-aksi="kurang" aria-label="Kurangi jumlah {{ $prod->nama_produk }}">-</button>
                                            <input type="number"
                                                   name="produk_addon[{{ $prod->id_produk }}]"
                                                   class="input-qty-addon"
                                                   min="0"
                                                   max="{{ $prod->stok_produk }}"
                                                   value="{{ $qtyLama }}"
                                                   data-nama="{{ $prod->nama_produk }}"
                                                   data-harga="{{ (float) $prod->harga_produk }}"
                                                   data-stok="{{ (int) $prod->stok_produk }}"
                                                   aria-label="Jumlah {{ $prod->nama_produk }}">
                                            <button type="button" class="btn-qty-addon" data-aksi="tambah" aria-label="Tambah jumlah {{ $prod->nama_produk }}">+</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="tidak-ditemukan" id="produkTidakDitemukan">Produk yang Anda cari tidak ditemukan.</p>

                            <!-- Navigasi Halaman (Pagination) Produk Add-On -->
                            <div class="katalog-paginasi" id="paginasiProduk">
                                <span class="info-halaman-katalog" id="infoHalamanProduk">Menampilkan produk</span>
                                <div class="tombol-halaman-grup">
                                    <button type="button" class="btn-halaman" id="btnPrevProduk">&laquo; Sebelumnya</button>
                                    <span class="indikator-halaman" id="teksHalProduk">1 / 1</span>
                                    <button type="button" class="btn-halaman" id="btnNextProduk">Berikutnya &raquo;</button>
                                </div>
                            </div>
                        @endif

                        <div class="step-navigasi-bawah">
                            <button type="button" class="btn-step-kembali" data-lanjut-step="2">
                                &larr; Kembali ke Jadwal
                            </button>
                        </div>
                    </section>
                </div>

                <!-- =================================================
                     KOLOM KANAN: STICKY BOOKING SUMMARY & SATU CTA UTAMA
                     ================================================= -->
                <aside class="portal-kanan" id="kartuRingkasanSticky">
                    <div class="kartu-ringkasan-sticky">
                        <div class="ringkasan-kepala">
                            <h3>Ringkasan Reservasi</h3>
                            <span class="lencana-jadwal-live" id="lencanaJadwalLive">
                                {{ $tglTerpilihAwal }} &bull; {{ $jamTerpilihAwal }} WIB
                            </span>
                        </div>

                        <!-- Empty State Ringkas Saat Belum Ada Layanan Dipilih -->
                        <div class="pilihan-kosong-live" id="ringkasanKosongState">
                            <span class="kosong-ikon" aria-hidden="true">🌸</span>
                            <strong>Belum ada perawatan dipilih</strong>
                            <span>Klik kartu perawatan di sebelah kiri untuk melihat rincian durasi, promo, dan estimasi biaya.</span>
                        </div>

                        <!-- Konten Ringkasan Aktif (Tampil Begitu Ada Layanan Dipilih) -->
                        <div id="kontenRingkasanAktif" hidden>
                            <div class="rincian-pilihan-live" id="daftarPilihanLive"></div>

                            <!-- Bagian Promo & Voucer (Maks 2 Tampil Awal, Tanpa Scroll di dalam Scroll) -->
                            <div class="seksi-promo-sticky">
                                <div class="kepala-promo-sticky">
                                    <label for="kode_promo" class="label-promo-kecil">Voucer &amp; Promo Spesial</label>
                                    @if ($promoAktif->count() > 2)
                                        <button type="button" class="btn-lihat-semua-promo" id="btnLihatSemuaPromo" data-terbuka="0">
                                            Lihat semua ({{ $promoAktif->count() }})
                                        </button>
                                    @endif
                                </div>

                                @if ($promoAktif->isNotEmpty())
                                    <div class="daftar-promo-booking" id="daftarPromoBooking">
                                        @foreach ($promoAktif as $indeksPromo => $pr)
                                            <div class="kartu-promo-item {{ $indeksPromo >= 2 ? 'promo-ekstra' : '' }}"
                                                 data-kode="{{ $pr->kode_promo }}"
                                                 data-nama="{{ $pr->nama_promo }}"
                                                 data-tipe="{{ $pr->tipe_diskon }}"
                                                 data-nilai="{{ (float) $pr->nilai_diskon }}"
                                                 data-min="{{ (float) $pr->min_transaksi }}"
                                                 @if ($indeksPromo >= 2) hidden @endif>
                                                <div class="promo-kiri">
                                                    <div class="promo-baris-kode">
                                                        <span class="lencana-kode-promo">{{ $pr->kode_promo }}</span>
                                                        <span class="promo-potongan-teks">
                                                            @if ($pr->tipe_diskon === \App\Models\Promo::TIPE_PERSENTASE)
                                                                Hemat {{ number_format($pr->nilai_diskon, 0) }}%
                                                            @else
                                                                Hemat Rp {{ number_format($pr->nilai_diskon, 0, ',', '.') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <strong class="promo-nama-item">{{ $pr->nama_promo }}</strong>
                                                    <span class="promo-syarat-item">
                                                        Min. Rp {{ number_format($pr->min_transaksi, 0, ',', '.') }}
                                                        &bull; s/d {{ $pr->berlaku_sampai->format('d/m/Y') }}
                                                    </span>
                                                </div>
                                                <button type="button" class="btn-pilih-promo" data-kode="{{ $pr->kode_promo }}">
                                                    Pakai
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="baris-input-promo">
                                    <input type="text"
                                           id="kode_promo"
                                           name="kode_promo"
                                           class="input-kode-promo"
                                           value="{{ old('kode_promo') }}"
                                           placeholder="Ketik kode promo...">
                                    <button type="button" id="btnHapusPromo" class="btn-reset-promo">Reset</button>
                                </div>
                                <small class="pesan-status-promo" id="pesanStatusPromo" aria-live="polite"></small>
                            </div>

                            <!-- Kalkulasi Tagihan -->
                            <div class="summary-box summary-box-rinci" aria-live="polite">
                                <div class="summary-baris">
                                    <span id="jumlahPilihan">Subtotal Layanan (0 layanan &bull; 0 mnt)</span>
                                    <strong id="ringkasanLayananNominal">Rp 0</strong>
                                </div>
                                <div class="summary-baris" id="barisRingkasanProduk" hidden>
                                    <span id="jumlahProdukAddon">Produk Add-On (0 item)</span>
                                    <strong id="ringkasanProdukNominal">Rp 0</strong>
                                </div>
                                <div class="summary-baris summary-baris-diskon" id="barisRingkasanPromo" hidden>
                                    <span id="labelRingkasanPromo">Potongan Promo</span>
                                    <strong id="ringkasanPromoNominal">- Rp 0</strong>
                                </div>
                                <div class="summary-baris summary-baris-utama">
                                    <div>
                                        <span class="summary-text">Estimasi Total Bayar</span>
                                        <small class="estimasi-jadwal-teks" id="estimasiJadwalTeks"></small>
                                    </div>
                                    <div class="summary-total" id="totalHarga">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        <small class="info-validasi" id="infoValidasi"></small>

                        <!-- Satu CTA Utama Kontekstual Sesuai Langkah -->
                        <button type="button" id="btnLanjutStepRingkasan" class="btn btn-penuh btn-konfirmasi-modern">
                            Lanjut Pilih Tanggal &amp; Jam &rarr;
                        </button>

                        <button type="submit" id="submitBtn" class="btn btn-penuh btn-konfirmasi-modern" hidden>
                            Konfirmasi &amp; Buat Reservasi Sekarang
                        </button>
                    </div>
                </aside>
            </div>

            <!-- Bar Melayang Khusus Layar Ponsel / Tablet (Mobile Sticky Checkout Bar) -->
            <div class="bar-bawah-mobile" id="barBawahMobile" aria-live="polite">
                <div class="bar-bawah-info">
                    <span class="bar-bawah-meta" id="metaMobileBar">0 layanan dipilih</span>
                    <strong class="bar-bawah-total" id="totalMobileBar">Rp 0</strong>
                </div>
                <a href="#kartuRingkasanSticky" class="btn-bar-bawah-mobile" id="btnAksiMobileBar">
                    Lihat Ringkasan &rarr;
                </a>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/booking.js'])
@endpush
