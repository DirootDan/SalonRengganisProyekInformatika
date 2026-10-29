@extends('Manajer.layout')

@section('judul', 'Kebijakan Booking & Blokir - Salon Rengganis')
@section('header', 'Pengaturan Kebijakan Booking & Pinalti')
@section('sub_header', 'Atur parameter pelanggaran, lama pemblokiran, dan kelola pembukaan blokir pelanggan.')

@push('styles')
    @vite(['resources/css/Manajer/kebijakan.css'])
@endpush

@section('isi')
    <div class="kebijakan-grid">
        <!-- Form Pengaturan Parameter Kebijakan -->
        <div class="panel-kartu">
            <h3 class="judul-seksi-kebijakan">⚙️ Parameter Kebijakan Pinalti</h3>
            <p class="sub-seksi-kebijakan">
                Semua aturan pinalti reservasi diambil dari pengaturan ini secara dinamis tanpa mengubah kode program.
            </p>

            <form method="POST" action="{{ route('manajer.kebijakan.simpan') }}">
                @csrf
                @method('PUT')

                <div class="form-grup">
                    <label class="form-label">Rentang Perhitungan Kumulatif Pelanggaran (Hari Terakhir)</label>
                    <input type="number" name="rentang_hari_pelanggaran" class="form-input" min="1" max="365"
                           value="{{ $kebijakan->rentang_hari_pelanggaran ?? 90 }}" required>
                    <small class="petunjuk-input">Bawaan: 90 hari terakhir. Pelanggaran di luar rentang ini otomatis kedaluwarsa.</small>
                </div>

                <div class="form-grup">
                    <label class="form-label">Batas Maksimal Pelanggaran Sebelum Diblokir</label>
                    <input type="number" name="batas_pelanggaran" class="form-input" min="1" max="20"
                           value="{{ $kebijakan->batas_pelanggaran }}" required>
                    <small class="petunjuk-input">Bawaan: 5 kali pelanggaran dalam periode kumulatif.</small>
                </div>

                <div class="form-grup">
                    <label class="form-label">Durasi Blokir Pertama (Hari)</label>
                    <input type="number" name="lama_blokir_hari" class="form-input" min="1" max="90"
                           value="{{ $kebijakan->lama_blokir_hari }}" required>
                    <small class="petunjuk-input">Bawaan: 7 hari untuk pemblokiran ke-1.</small>
                </div>

                <div class="form-grup">
                    <label class="form-label">Durasi Blokir Kedua (Hari)</label>
                    <input type="number" name="lama_blokir_kedua_hari" class="form-input" min="1" max="180"
                           value="{{ $kebijakan->lama_blokir_kedua_hari ?? 30 }}" required>
                    <small class="petunjuk-input">Bawaan: 30 hari untuk pemblokiran ke-2.</small>
                </div>

                <div class="form-grup">
                    <label class="opsi-saklar-kebijakan">
                        <input type="checkbox" name="blokir_ketiga_permanen" value="1" @checked($kebijakan->blokir_ketiga_permanen ?? true)>
                        <span>Blokir Ketiga Tetap Diblokir sampai Manajer Membuka Manual</span>
                    </label>
                </div>

                <div class="form-grup">
                    <label class="form-label">Toleransi Keterlambatan Pembayaran (H + Hari)</label>
                    <input type="number" name="toleransi_bayar_hari" class="form-input" min="1" max="14"
                           value="{{ $kebijakan->toleransi_bayar_hari }}" required>
                    <small class="petunjuk-input">Bawaan: 1 hari (H+1 pukul 23:59 WIB). Jika belum lunas, otomatis berstatus Hangus.</small>
                </div>

                <div class="form-grup">
                    <label class="form-label">Batas Waktu Pembatalan Gratis oleh Pelanggan (Jam)</label>
                    <input type="number" name="batas_batal_jam" class="form-input" min="0" max="48"
                           value="{{ $kebijakan->batas_batal_jam }}" required>
                    <small class="petunjuk-input">Bawaan: minimal 3 jam sebelum jam booking.</small>
                </div>

                <div class="form-grup">
                    <label class="opsi-saklar-kebijakan">
                        <input type="checkbox" name="kebijakan_aktif" value="1" @checked($kebijakan->kebijakan_aktif)>
                        <span>Aktifkan Sistem Pinalti &amp; Pemblokiran Otomatis</span>
                    </label>
                </div>

                <button type="submit" class="btn-utama">💾 Simpan Kebijakan Booking</button>
            </form>
        </div>

        <!-- Daftar Pelanggan yang Sedang Diblokir -->
        <div class="panel-kartu">
            <h3 class="judul-seksi-kebijakan">🚫 Daftar Pelanggan yang Sedang Diblokir</h3>
            <p class="sub-seksi-kebijakan">
                Manajer dapat membuka blokir pelanggan lebih awal dengan wajib mencantumkan alasan pembukaan.
            </p>

            @forelse ($pelangganDiblokir as $p)
                <div class="kartu-terblokir">
                    <div class="info-terblokir">
                        <strong>{{ $p->nama_cust }}</strong>
                        <span>{{ $p->email }} &bull; {{ $p->no_telp }}</span>
                        <span class="lencana-pelanggaran">Pelanggaran: {{ $p->jumlah_pelanggaran }} / {{ $kebijakan->batas_pelanggaran }} (Blokir ke-{{ max(1, (int) $p->jumlah_kali_blokir) }})</span>
                        <small>{{ $p->keteranganBlokir() }}</small>
                    </div>

                    <form method="POST" action="{{ route('manajer.kebijakan.buka', $p->id_pelanggan) }}"
                          class="form-buka-blokir"
                          data-nama="{{ $p->nama_cust }}"
                          data-confirm-title="Konfirmasi Buka Blokir Pelanggan"
                          data-confirm-item="{{ $p->nama_cust }} ({{ $p->email }})">
                        @csrf
                        <input type="text" name="alasan_buka" class="form-input input-alasan-kecil"
                               placeholder="Wajib isi alasan buka blokir..." required minlength="5">
                        <button type="submit" class="btn-aksi btn-edit">Buka Blokir</button>
                    </form>
                </div>
            @empty
                <div class="kotak-kosong-kebijakan">
                    ✓ Saat ini tidak ada pelanggan yang sedang diblokir.
                </div>
            @endforelse

            <h3 class="judul-seksi-kebijakan spasi-atas">⚠️ Pelanggan dengan Catatan Pelanggaran</h3>
            <div class="tabel-Responsif">
                <table class="tabel-data">
                    <thead>
                        <tr>
                            <th>Nama Pelanggan</th>
                            <th>Jumlah Pelanggaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelangganPelanggar as $pp)
                            <tr>
                                <td><strong>{{ $pp->nama_cust }}</strong><br><small>{{ $pp->email }}</small></td>
                                <td><strong>{{ $pp->jumlah_pelanggaran }} / {{ $kebijakan->batas_pelanggaran }}</strong></td>
                                <td>
                                    @if ($pp->sedangDiblokir())
                                        <span class="badge badge-nonaktif">Diblokir</span>
                                    @else
                                        <span class="badge badge-manajer">Dalam Pengawasan</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="teks-tengah-abu">Belum ada pelanggan yang memiliki catatan pelanggaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Riwayat Log Pelanggaran & Pembukaan Blokir -->
    <div class="panel-kartu spasi-atas">
        <h3 class="judul-seksi-kebijakan">📜 Riwayat Pelanggaran &amp; Aktivitas Blokir</h3>
        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Jenis Kejadian</th>
                        <th>ID Reservasi</th>
                        <th>Keterangan Lengkap</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatPelanggaran as $rp)
                        <tr>
                            <td>{{ $rp->tanggal->format('d/m/Y') }}</td>
                            <td><strong>{{ $rp->pelanggan->nama_cust ?? 'Pelanggan' }}</strong></td>
                            <td>
                                <span class="badge {{ $rp->jenis === 'Pelanggaran' ? 'badge-nonaktif' : 'badge-aktif' }}">
                                    {{ $rp->jenis }}
                                </span>
                            </td>
                            <td>{{ $rp->id_reservasi ? '#' . $rp->id_reservasi : '-' }}</td>
                            <td>{{ $rp->keterangan }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="teks-tengah-abu">Belum ada riwayat pelanggaran tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/kebijakan.js'])
@endpush
