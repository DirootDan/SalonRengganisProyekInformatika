<!-- Modal Pengajuan Hak Akses 1x Pakai Katalog ke Manajer -->
<div class="modal-backdrop" id="modalAksesKatalog" aria-hidden="true">
    <div class="modal-kotak">
        <div class="modal-header">
            <div>
                <h3>🔑 Ajukan Izin Akses 1x Pakai</h3>
                <small class="sub-modal-katalog">Permintaan akan dikirim ke halaman Persetujuan Manajer.</small>
            </div>
            <button type="button" class="btn-tutup-modal" id="btnTutupModalAksesKatalog" aria-label="Tutup">&times;</button>
        </div>

        <form method="POST" action="{{ route('kasir.otorisasi-katalog.store') }}" id="formAjukanAksesKatalog">
            @csrf
            <input type="hidden" name="konteks" id="inputKonteksAksesKatalog" value="{{ $konteksDefault }}">
            <input type="hidden" name="jenis_aksi" id="inputAksiAksesKatalog" value="ubah">
            <input type="hidden" name="id_target" id="inputTargetAksesKatalog" value="">

            <div class="ringkasan-target-akses">
                <span class="badge-akses badge-akses-kunci" id="badgeAksiModalKatalog">UBAH</span>
                <strong id="labelTargetModalKatalog">-</strong>
            </div>

            <div class="form-grup">
                <label class="form-label" for="inputAlasanAksesKatalog">Alasan Pengajuan Akses ke Manajer *</label>
                <textarea name="alasan_kasir" id="inputAlasanAksesKatalog" class="form-textarea" rows="3"
                          minlength="5" maxlength="500" required
                          placeholder="Wajib jelaskan alasan perubahan/penambahan/penghapusan (min. 5 karakter)..."></textarea>
            </div>

            <div class="modal-kaki">
                <button type="button" class="btn-sekunder" id="btnBatalModalAksesKatalog">Batal</button>
                <button type="submit" class="btn-utama">📨 Kirim Request ke Manajer</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Tersembunyi Pembatalan & Penguncian Kembali Hak Akses 1x Pakai -->
<form method="POST" action="" id="formKunciKembaliAkses" hidden>
    @csrf
    @method('DELETE')
</form>
