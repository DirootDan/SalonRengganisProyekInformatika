<!-- Modal Daftar Transaksi Hold & Status Approval Manajer -->
<div class="modal-backdrop" id="modalHoldPos" aria-hidden="true">
    <div class="modal-kotak modal-kotak-lebar">
        <div class="modal-header">
            <div>
                <h3>⏸️ Daftar Transaksi Hold &amp; Status Approval ({{ $daftarHold->count() }})</h3>
                <small class="sub-antrean">Pantau respons persetujuan Manajer atau lanjutkan transaksi yang telah diputuskan.</small>
            </div>
            <button type="button" class="btn-tutup-modal" id="btnTutupModalHold" aria-label="Tutup">&times;</button>
        </div>

        <div class="antrean-list-modal">
            @forelse ($daftarHold as $h)
                <div class="kartu-hold-pos {{ $h->status === \App\Models\PermintaanOtorisasi::STATUS_DITOLAK ? 'kartu-hold-ditolak' : ($h->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI ? 'kartu-hold-disetujui' : '') }}">
                    <div class="kartu-hold-kepala">
                        <div>
                            <span class="antrean-id">#{{ $h->kode_referensi }} &bull; {{ $h->created_at?->format('H:i') }} WIB</span>
                            @if ($h->status === \App\Models\PermintaanOtorisasi::STATUS_MENUNGGU)
                                <span class="badge-hold badge-hold-pending">⏳ Menunggu Approval Manajer</span>
                            @elseif ($h->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                                <span class="badge-hold badge-hold-setuju">✅ Disetujui ({{ $h->manajer?->nama_staff ?? 'Manajer' }})</span>
                            @elseif ($h->status === \App\Models\PermintaanOtorisasi::STATUS_DITOLAK)
                                <span class="badge-hold badge-hold-tolak">❌ Request Ditolak Manajer</span>
                            @endif
                            <h4 class="antrean-nama">{{ $h->label_target }}</h4>
                        </div>
                        <div class="hold-nominal-kanan">
                            @if ((float) $h->total_awal !== (float) $h->total_akhir)
                                <del class="teks-coret-kecil">Rp {{ number_format((float) $h->total_awal, 0, ',', '.') }}</del>
                            @endif
                            <strong class="antrean-total">Rp {{ number_format((float) $h->total_akhir, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <p class="hold-alasan-kasir">Alasan Kasir: <em>"{{ $h->alasan_kasir }}"</em></p>

                    @if ($h->status === \App\Models\PermintaanOtorisasi::STATUS_DITOLAK)
                        <div class="kotak-info-penolakan-hold" role="alert">
                            <strong>Alasan Penolakan Manajer:</strong> {{ $h->catatan_manajer ?: 'Tidak memenuhi ketentuan potongan harga.' }}
                            <div class="sub-pulih-normal">Nilai layanan &amp; harga pada transaksi ini telah dikembalikan otomatis ke harga normal awal (Rp {{ number_format((float) $h->total_awal, 0, ',', '.') }}).</div>
                        </div>
                    @endif

                    <div class="aksi-hold-baris">
                        @if ($h->status === \App\Models\PermintaanOtorisasi::STATUS_DISETUJUI)
                            <button type="button" class="btn-muat-hold btn-hold-utama"
                                    data-mode-hold="disetujui"
                                    data-id-otorisasi="{{ $h->id_otorisasi }}"
                                    data-kode-referensi="{{ $h->kode_referensi }}"
                                    data-id-reservasi="{{ $h->id_reservasi ?? '' }}"
                                    data-pelanggan="{{ $h->label_target }}"
                                    data-kode-promo="{{ $holdViewMap->get($h->id_otorisasi)['kode_promo_aktif'] ?? '' }}"
                                    data-alasan="{{ $h->alasan_kasir }}"
                                    data-nama-manajer="{{ $h->manajer?->nama_staff ?? 'Manajer' }}"
                                    data-items='@json($holdViewMap->get($h->id_otorisasi)["items_hold"] ?? [])'
                                    data-items-awal='@json($holdViewMap->get($h->id_otorisasi)["items_awal"] ?? [])'>
                                Muat &amp; Selesaikan Pembayaran
                            </button>
                        @elseif ($h->status === \App\Models\PermintaanOtorisasi::STATUS_DITOLAK)
                            <button type="button" class="btn-muat-hold btn-hold-normal"
                                    data-mode-hold="bayar_normal"
                                    data-id-otorisasi="{{ $h->id_otorisasi }}"
                                    data-kode-referensi="{{ $h->kode_referensi }}"
                                    data-id-reservasi="{{ $h->id_reservasi ?? '' }}"
                                    data-pelanggan="{{ $h->label_target }}"
                                    data-kode-promo="{{ $holdViewMap->get($h->id_otorisasi)['kode_promo_awal'] ?? '' }}"
                                    data-items='@json($holdViewMap->get($h->id_otorisasi)["items_awal"] ?? [])'
                                    data-items-awal='@json($holdViewMap->get($h->id_otorisasi)["items_awal"] ?? [])'>
                                Lanjut Bayar Harga Normal
                            </button>
                            <button type="button" class="btn-muat-hold btn-hold-ulang"
                                    data-mode-hold="ajukan_ulang"
                                    data-id-otorisasi="{{ $h->id_otorisasi }}"
                                    data-kode-referensi="{{ $h->kode_referensi }}"
                                    data-id-reservasi="{{ $h->id_reservasi ?? '' }}"
                                    data-pelanggan="{{ $h->label_target }}"
                                    data-kode-promo="{{ $holdViewMap->get($h->id_otorisasi)['kode_promo_awal'] ?? '' }}"
                                    data-items='@json($holdViewMap->get($h->id_otorisasi)["items_awal"] ?? [])'
                                    data-items-awal='@json($holdViewMap->get($h->id_otorisasi)["items_awal"] ?? [])'>
                                Ajukan Ulang Request
                            </button>
                        @endif

                        <form method="POST" action="{{ route('kasir.otorisasi.batal', $h->id_otorisasi) }}"
                              data-confirm="Batalkan transaksi Hold #{{ $h->kode_referensi }} ({{ $h->label_target }})? Request juga akan otomatis ditarik dari antrean Manajer."
                              data-confirm-title="Batalkan Request / Void Transaksi Hold"
                              data-confirm-label="Ya, Void Transaksi Hold">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-void-hold">Batalkan Request / Void Transaksi Hold</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="antrean-kosong">
                    Belum ada transaksi yang sedang di-hold atau menunggu persetujuan Manajer.
                </div>
            @endforelse
        </div>
    </div>
</div>
