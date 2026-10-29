@extends('pelanggan.layout')

@section('judul', 'Booking Perawatan - Salon Rengganis')

@push('styles')
    @vite(['resources/css/pelanggan/booking.css'])
@endpush

@section('isi')
    @php
        $jmlStylistLayanan   = $layanan->where('jabatan_petugas', 'Stylist')->count();
        $jmlTherapistLayanan = $layanan->where('jabatan_petugas', 'Therapist')->count();
        $jamTerpilihAwal     = substr((string) old('jam_booking', '10:00'), 0, 5);
        $tglTerpilihAwal     = old('tgl_booking', now()->toDateString());
    @endphp

    <!-- Hero Banner Booking ala Portal Reservasi Modern -->
    <div class="booking-hero">
        <div class="booking-hero-teks">
            <span class="booking-hero-lencana">Portal Reservasi Online &bull; Salon Rengganis</span>
            <h1 class="booking-hero-judul">Pilih Perawatan Stylist &amp; Therapist Favorit Anda</h1>
            <p class="booking-hero-sub">
                Nikmati kemudahan reservasi tanpa antre. Pilih jam kedatangan spesifik sesuai keinginan Anda, jelajahi kategori <strong>Hair Stylist</strong> atau <strong>Spa &amp; Beauty Therapist</strong>, serta tambahkan produk perawatan resmi.
            </p>
        </div>
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

            <div class="booking-portal-grid">
                <!-- =================================================
                     KOLOM KIRI: EKSPLORASI JADWAL, KATEGORI, & ADD-ON
                     ================================================= -->
                <div class="portal-kiri">

                    <!-- Seksi 1: Pilih Tanggal & Jam Kedatangan Dinamis (Bisa Menit Spesifik) -->
                    <section class="portal-kartu">
                        <div class="portal-kartu-kepala">
                            <span class="langkah-no">1</span>
                            <div>
                                <h2 class="portal-seksi-judul">Jadwal &amp; Jam Kedatangan Dinamis</h2>
                                <p class="portal-seksi-sub">
                                    Pilih tanggal serta <strong>jam dan menit spesifik</strong> kedatangan Anda (jam operasional salon <strong>09:00 – 21:00 WIB</strong>), atau gunakan tombol jam populer di bawah.
                                </p>
                            </div>
                        </div>

                        <div class="jadwal-baris-tanggal">
                            <div class="kolom-input-tanggal">
                                <label for="tgl_booking" class="label">Tanggal Kedatangan</label>
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

                            <div class="kolom-input-waktu-dinamis">
                                <label for="jam_booking" class="label">Jam &amp; Menit Kedatangan (Bebas Pilih Menit)</label>
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
                                        <span class="label-menit-kecil">Menit Cepat:</span>
                                        <div class="grup-menit-cepat">
                                            <button type="button" class="btn-menit-cepat" data-menit="00">:00</button>
                                            <button type="button" class="btn-menit-cepat" data-menit="15">:15</button>
                                            <button type="button" class="btn-menit-cepat" data-menit="30">:30</button>
                                            <button type="button" class="btn-menit-cepat" data-menit="45">:45</button>
                                        </div>
                                    </div>
                                </div>
                                <small class="petunjuk-waktu-spesifik">
                                    💡 Anda bebas mengetik menit spesifik (misal <strong>10:15</strong>, <strong>13:20</strong>, atau <strong>15:45</strong>). Sistem mengecek ketersediaan petugas secara otomatis.
                                </small>
                            </div>
                        </div>

                        <div class="slot-jam-wadah">
                            <div class="slot-jam-header">
                                <span class="label tanpa-margin">Pintasan Jam Populer (Klik untuk memilih cepat, atau ketik menit bebas di atas)</span>
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
                    </section>

                    <!-- Seksi 2: Pilih Kategori Layanan (Stylist vs Therapist) + Pagination Tanpa Scroll Panjang -->
                    <section class="portal-kartu">
                        <div class="portal-kartu-kepala">
                            <span class="langkah-no">2</span>
                            <div>
                                <h2 class="portal-seksi-judul">Pilih Kategori Perawatan &amp; Petugas</h2>
                                <p class="portal-seksi-sub">
                                    Jelajahi layanan berdasarkan kategori <strong>Stylist (Rambut)</strong> atau <strong>Therapist (Wajah &amp; Spa)</strong>. Anda bebas memilih petugas favorit atau membiarkan sistem memilihkan otomatis.
                                </p>
                            </div>
                        </div>

                        <!-- Tab Kategori Ala Traveloka Xperience -->
                        <div class="tab-kategori-booking" role="tablist" aria-label="Kategori Layanan Salon">
                            <button type="button" class="tab-kat-item aktif" data-filter-jabatan="semua">
                                <span class="tab-kat-ikon">✨</span>
                                <div class="tab-kat-teks">
                                    <strong>Semua Perawatan</strong>
                                    <small>{{ $layanan->count() }} pilihan layanan</small>
                                </div>
                            </button>

                            <button type="button" class="tab-kat-item tab-kat-stylist" data-filter-jabatan="Stylist">
                                <span class="tab-kat-ikon">💇‍♀️</span>
                                <div class="tab-kat-teks">
                                    <strong>Hair Studio &bull; Stylist</strong>
                                    <small>{{ $jmlStylistLayanan }} layanan rambut</small>
                                </div>
                            </button>

                            <button type="button" class="tab-kat-item tab-kat-therapist" data-filter-jabatan="Therapist">
                                <span class="tab-kat-ikon">🌿</span>
                                <div class="tab-kat-teks">
                                    <strong>Face &amp; Body Spa &bull; Therapist</strong>
                                    <small>{{ $jmlTherapistLayanan }} perawatan wajah &amp; tubuh</small>
                                </div>
                            </button>
                        </div>

                        <div class="baris-filter-katalog">
                            <input type="text"
                                   id="searchInput"
                                   class="search-input tanpa-margin"
                                   placeholder="Cari perawatan (contoh: Smoothing, Potong Rambut, Facial Glowing, Massage...)">
                        </div>

                        <div class="daftar-opsi-modern" id="daftarLayananGrid">
                            @foreach ($layanan as $item)
                                @php
                                    $jabatan       = $item->jabatan_petugas;
                                    $daftarPetugas = $petugas->get($jabatan, collect());
                                    $dipilih       = in_array($item->id_layanan, old('layanan', []));
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
                                            <div class="opsi-baris-atas">
                                                <span class="lencana-jabatan lencana-{{ strtolower($jabatan) }}">
                                                    {{ $jabatan === 'Stylist' ? '💇‍♀️ Hair Stylist' : '🌿 Spa Therapist' }}
                                                </span>
                                                <span class="lencana-durasi">{{ $durasiMenit }} menit</span>
                                            </div>
                                            <span class="opsi-nama">{{ $item->nama_layanan }}</span>
                                            <span class="opsi-kategori">Kategori {{ $item->kategori }}</span>
                                        </div>

                                        <div class="opsi-kanan-harga">
                                            <span class="opsi-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</span>
                                            <span class="indikator-pilih">{{ $dipilih ? '✓ Terpilih' : '+ Pilih' }}</span>
                                        </div>
                                    </label>

                                    <div class="pilih-petugas">
                                        <label for="petugas_{{ $item->id_layanan }}" class="petugas-label">
                                            Pilih {{ $jabatan }} untuk {{ $item->nama_layanan }}:
                                        </label>
                                        <select id="petugas_{{ $item->id_layanan }}"
                                                name="petugas[{{ $item->id_layanan }}]"
                                                class="select-petugas"
                                                data-jabatan="{{ $jabatan }}"
                                                data-pilihan-lama="{{ $petugasLama }}"
                                                data-tersedia="{{ $daftarPetugas->count() }}"
                                                @disabled(! $dipilih)>
                                            <option value="">-- Pilihkan {{ $jabatan }} Otomatis ({{ $daftarPetugas->count() }} tersedia) --</option>
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

                        <!-- Navigasi Halaman (Pagination) Layanan bila jumlah layanan banyak -->
                        <div class="katalog-paginasi" id="paginasiLayanan">
                            <span class="info-halaman-katalog" id="infoHalamanLayanan">Menampilkan layanan</span>
                            <div class="tombol-halaman-grup">
                                <button type="button" class="btn-halaman" id="btnPrevLayanan">&laquo; Sebelumnya</button>
                                <span class="indikator-halaman" id="teksHalLayanan">1 / 1</span>
                                <button type="button" class="btn-halaman" id="btnNextLayanan"> Berikutnya &raquo;</button>
                            </div>
                        </div>
                    </section>

                    <!-- Seksi 3: Produk Add-On Tambahan (Pencarian + Filter + Pagination Tanpa Scroll Panjang) -->
                    <section class="portal-kartu">
                        <div class="portal-kartu-kepala">
                            <span class="langkah-no">3</span>
                            <div>
                                <h2 class="portal-seksi-judul">Produk Perawatan Tambahan / Add-On (Opsional)</h2>
                                <p class="portal-seksi-sub">
                                    Cari dan pilih produk perawatan resmi Salon Rengganis. Tersedia fitur pencarian dan halaman agar nyaman meskipun terdapat ratusan produk.
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

                            <!-- Navigasi Halaman (Pagination) Produk Add-On (Maks 6 produk per halaman agar tidak perlu scroll panjang) -->
                            <div class="katalog-paginasi" id="paginasiProduk">
                                <span class="info-halaman-katalog" id="infoHalamanProduk">Menampilkan produk</span>
                                <div class="tombol-halaman-grup">
                                    <button type="button" class="btn-halaman" id="btnPrevProduk">&laquo; Sebelumnya</button>
                                    <span class="indikator-halaman" id="teksHalProduk">1 / 1</span>
                                    <button type="button" class="btn-halaman" id="btnNextProduk">Berikutnya &raquo;</button>
                                </div>
                            </div>
                        @endif
                    </section>
                </div>

                <!-- =================================================
                     KOLOM KANAN: STICKY BOOKING SUMMARY & CHECKOUT
                     ================================================= -->
                <aside class="portal-kanan">
                    <div class="kartu-ringkasan-sticky">
                        <div class="ringkasan-kepala">
                            <h3>Ringkasan Reservasi</h3>
                            <span class="lencana-jadwal-live" id="lencanaJadwalLive">
                                {{ $tglTerpilihAwal }} &bull; {{ $jamTerpilihAwal }} WIB
                            </span>
                        </div>

                        <!-- Daftar Item Terpilih Secara Live -->
                        <div class="rincian-pilihan-live" id="daftarPilihanLive">
                            <div class="pilihan-kosong-live">
                                Belum ada layanan yang dipilih. Silakan klik paket perawatan <strong>Stylist</strong> atau <strong>Therapist</strong> di sebelah kiri.
                            </div>
                        </div>

                        <!-- Bagian Promo & Voucer -->
                        <div class="seksi-promo-sticky">
                            <label for="kode_promo" class="label-promo-kecil">Voucer &amp; Promo Spesial</label>

                            @if ($promoAktif->isNotEmpty())
                                <div class="daftar-promo-booking" id="daftarPromoBooking">
                                    @foreach ($promoAktif as $pr)
                                        <div class="kartu-promo-item"
                                             data-kode="{{ $pr->kode_promo }}"
                                             data-nama="{{ $pr->nama_promo }}"
                                             data-tipe="{{ $pr->tipe_diskon }}"
                                             data-nilai="{{ (float) $pr->nilai_diskon }}"
                                             data-min="{{ (float) $pr->min_transaksi }}">
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
                            <div class="summary-baris">
                                <span id="jumlahProdukAddon">Produk Add-On (0 item)</span>
                                <strong id="ringkasanProdukNominal">Rp 0</strong>
                            </div>
                            <div class="summary-baris summary-baris-diskon" id="barisRingkasanPromo">
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

                        <small class="info-validasi" id="infoValidasi"></small>

                        <button type="submit" id="submitBtn" class="btn btn-penuh btn-konfirmasi-modern">
                            Konfirmasi &amp; Buat Reservasi Sekarang
                        </button>
                    </div>
                </aside>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/booking.js'])
@endpush
