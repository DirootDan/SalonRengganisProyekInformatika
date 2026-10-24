@extends('Manajer.layout')

@section('judul', 'Program Promo - Salon Rengganis')
@section('header', 'Program Promo & Diskon')
@section('sub_header', 'Kelola voucer diskon, potongan harga spesial, dan masa berlaku promo pelanggan.')

@push('styles')
    @vite(['resources/css/Manajer/promo.css'])
@endpush

@section('isi')
    <div class="panel-kartu">
        <div class="toolbar-atas">
            <div class="toolbar-kiri">
                <input type="text" id="cariPromo" class="input-cari" placeholder="🔍 Cari kode atau nama promo...">
                <select id="filterStatusPromo" class="select-filter">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Promo Aktif</option>
                    <option value="Berakhir">Sudah Berakhir</option>
                </select>
            </div>
            <button type="button" class="btn-utama" id="btnTambahPromo">
                <span>＋</span> Buat Promo Baru
            </button>
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>Kode Voucer</th>
                        <th>Nama Program Promo</th>
                        <th>Besar Potongan</th>
                        <th>Min. Transaksi</th>
                        <th>Berlaku Sampai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promo as $item)
                        <tr class="baris-promo"
                            data-nama="{{ strtolower($item->kode_promo . ' ' . $item->nama_promo) }}"
                            data-status="{{ $item->status }}">
                            <td><span class="kode-voucer">{{ $item->kode_promo }}</span></td>
                            <td><strong>{{ $item->nama_promo }}</strong></td>
                            <td class="nilai-potongan">
                                @if ($item->tipe_diskon === 'Persentase')
                                    Diskon {{ $item->nilai_diskon }}%
                                @else
                                    Rp {{ number_format($item->nilai_diskon, 0, ',', '.') }}
                                @endif
                            </td>
                            <td>Rp {{ number_format($item->min_transaksi, 0, ',', '.') }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->berlaku_sampai)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'Aktif' ? 'badge-aktif' : 'badge-nonaktif' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td>
                                <div class="aksi-grup">
                                    <button type="button" class="btn-aksi btn-edit btnEditPromo"
                                            data-kode="{{ $item->kode_promo }}"
                                            data-nama-asli="{{ $item->nama_promo }}"
                                            data-tipe="{{ $item->tipe_diskon }}"
                                            data-nilai="{{ $item->nilai_diskon }}"
                                            data-min="{{ $item->min_transaksi }}"
                                            data-sampai="{{ $item->berlaku_sampai }}"
                                            data-status="{{ $item->status }}">
                                        ✏️ Edit
                                    </button>
                                    <button type="button" class="btn-aksi btn-hapus btnHapusPromo"
                                            data-kode="{{ $item->kode_promo }}">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah / Edit Promo -->
    <div class="modal-backdrop" id="modalPromo">
        <div class="modal-kotak">
            <div class="modal-header">
                <h3 id="judulModalPromo">Buat Program Promo</h3>
                <button type="button" class="btn-tutup-modal" data-tutup-modal>&times;</button>
            </div>

            <form id="formPromo">
                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Kode Voucer</label>
                        <input type="text" id="inputKodePromo" class="form-input" placeholder="Contoh: CANTIK20" style="text-transform: uppercase;" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Status Promo</label>
                        <select id="inputStatusPromo" class="form-select">
                            <option value="Aktif">Aktif</option>
                            <option value="Berakhir">Nonaktif / Berakhir</option>
                        </select>
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-label">Nama Program Promo</label>
                    <input type="text" id="inputNamaPromo" class="form-input" placeholder="Contoh: Diskon Akhir Pekan" required>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Jenis Potongan</label>
                        <select id="inputTipePromo" class="form-select">
                            <option value="Persentase">Persentase (%)</option>
                            <option value="Nominal">Nominal Rupiah (Rp)</option>
                        </select>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Nilai Potongan</label>
                        <input type="number" id="inputNilaiPromo" class="form-input" min="1" placeholder="Contoh: 20 atau 25000" required>
                    </div>
                </div>

                <div class="form-dua-kolom">
                    <div class="form-grup">
                        <label class="form-label">Minimal Transaksi (Rp)</label>
                        <input type="number" id="inputMinPromo" class="form-input" min="0" step="5000" placeholder="Contoh: 150000" required>
                    </div>

                    <div class="form-grup">
                        <label class="form-label">Berlaku Sampai</label>
                        <input type="date" id="inputSampaiPromo" class="form-input" required>
                    </div>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="btn-sekunder" data-tutup-modal>Batal</button>
                    <button type="submit" class="btn-utama">Simpan Promo</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/promo.js'])
@endpush
