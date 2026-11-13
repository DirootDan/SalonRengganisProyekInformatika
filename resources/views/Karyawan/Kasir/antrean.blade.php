@extends('Karyawan.Kasir.layout')

@section('judul', 'Antrean Hari Ini - Kasir Salon Rengganis')
@section('header', 'Antrean Aktif Hari Ini')
@section('sub_header', 'Daftar seluruh reservasi berstatus Diterima yang sedang berlangsung atau menunggu giliran.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/antrean.css'])
@endpush

@section('isi')
    <div class="panel-kartu">
        <div class="toolbar-atas">
            <input type="text" id="cariAntrean" class="input-cari" placeholder="🔍 Cari nama pelanggan atau layanan...">
            <a href="{{ route('kasir.dashboard') }}" class="btn-utama">🖥️ Buka Kasir Pembayaran</a>
        </div>

        <div class="antrean-penuh-grid">
            @forelse ($antrean as $r)
                <div class="kartu-antrean-item" data-cari="{{ strtolower($r->namaPelangganTampil() . ' ' . $r->layanan->pluck('nama_layanan')->join(' ')) }}">
                    <div class="antrean-item-atas">
                        <span class="nomor-urut">#{{ $r->id_reservasi }}</span>
                        <span class="jadwal-jam">
                            {{ $r->tgl_booking->format('d/m/Y') }} &bull; {{ $r->jamMulaiTampil() }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }} WIB
                            @if ($r->apakahJadwalTerlewat())
                                <span class="badge badge-terlewat">Jadwal Terlewat</span>
                            @endif
                        </span>
                    </div>

                    <h3 class="nama-pelanggan-antrean">{{ $r->namaPelangganTampil() }}</h3>
                    <p class="telp-pelanggan-antrean">{{ $r->pelanggan?->no_telp ?? 'Walk-In' }}</p>

                    <ul class="rincian-layanan-antrean">
                        @foreach ($r->layanan as $l)
                            <li class="baris-layanan-antrean">
                                <div class="baris-layanan-atas">
                                    <span>
                                        <strong>{{ $l->nama_layanan }}</strong>
                                        @if ($l->pivot->id_karyawan && isset($petugasMap[$l->pivot->id_karyawan]))
                                            <small>({{ $l->jabatan_petugas }}: {{ $petugasMap[$l->pivot->id_karyawan] }})</small>
                                        @endif
                                    </span>
                                    <span class="badge {{ $l->pivot->status_layanan === 'Selesai' ? 'badge-aktif' : 'badge-pending' }}">
                                        {{ $l->pivot->status_layanan ?: 'Menunggu' }}
                                    </span>
                                </div>
                                @if ($l->pivot->status_layanan !== 'Selesai')
                                    <form method="POST"
                                          action="{{ route('kasir.antrean.ganti-petugas', [$r->id_reservasi, $l->id_layanan]) }}"
                                          class="form-ganti-petugas-antrean">
                                        @csrf
                                        <label class="sr-only" for="gantiPetugas_{{ $r->id_reservasi }}_{{ $l->id_layanan }}">
                                            Alihkan {{ $l->jabatan_petugas }}
                                        </label>
                                        <select id="gantiPetugas_{{ $r->id_reservasi }}_{{ $l->id_layanan }}"
                                                name="id_karyawan"
                                                class="select-ganti-petugas"
                                                required>
                                            <option value="">-- Ganti {{ $l->jabatan_petugas }} --</option>
                                            @foreach ($petugasPerJabatan->get($l->jabatan_petugas, collect()) as $kandidat)
                                                <option value="{{ $kandidat->id_karyawan }}" @selected((int) $l->pivot->id_karyawan === (int) $kandidat->id_karyawan)>
                                                    {{ $kandidat->nama_staff }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn-ganti-petugas">Alihkan</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                        @foreach ($r->produkAddon as $p)
                            <li>
                                <span>+ Produk Add-On: <strong>{{ $p->nama_produk }} (x{{ $p->pivot->jumlah }})</strong></span>
                                <span class="badge badge-aktif">Rp {{ number_format((float) $p->pivot->harga_saat_ini * (int) $p->pivot->jumlah, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                        @if ($r->kode_promo)
                            <li>
                                <span>Promo: <strong>{{ $r->kode_promo }}</strong></span>
                                <span class="badge badge-aktif">-Rp {{ number_format((float) $r->nominal_diskon, 0, ',', '.') }}</span>
                            </li>
                        @endif
                    </ul>

                    <div class="antrean-item-kaki">
                        <strong>Total: Rp {{ number_format($r->estimasiTotalAkhir(), 0, ',', '.') }}</strong>
                        @if ($r->semuaLayananSudahSelesai())
                            <a href="{{ route('kasir.dashboard', ['muat_reservasi' => $r->id_reservasi]) }}" class="btn-aksi btn-edit">Proses di POS</a>
                        @else
                            <span class="badge badge-pending">Menunggu Petugas Selesai</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="teks-tengah-abu">Saat ini tidak ada antrean aktif yang menunggu pelayanan.</div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/antrean.js'])
@endpush
