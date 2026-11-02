<!-- Modal Otorisasi Langsung di Tempat (Direct Override Kredensial Manajer) -->
<div class="modal-backdrop" id="modalDirectOverridePos" aria-hidden="true"
     data-url-direct="{{ route('kasir.otorisasi.direct') }}"
     data-detik-kunci-awal="{{ (int) $detikKunciDirect }}">
    <div class="modal-kotak">
        <div class="modal-header">
            <div>
                <h3>🔑 Otorisasi Manajer di Tempat</h3>
                <small class="sub-antrean">Verifikasi instan tanpa mengubah sesi login Kasir yang sedang aktif.</small>
            </div>
            <button type="button" class="btn-tutup-modal" id="btnTutupModalDirect" aria-label="Tutup">&times;</button>
        </div>

        <div class="ringkasan-direct-override">
            <div class="baris-ringkasan-direct">
                <span>Target Transaksi:</span>
                <strong id="directLabelTarget">-</strong>
            </div>
            <div class="baris-ringkasan-direct">
                <span>Perubahan Tagihan:</span>
                <span>
                    <del id="directTotalAwal">Rp 0</del> &rarr; <strong id="directTotalBaru" class="teks-merah-muda">Rp 0</strong>
                </span>
            </div>
            <div class="kotak-alasan-direct">
                <span>Alasan Kasir:</span>
                <p id="directAlasanKasir">-</p>
            </div>
        </div>

        <div id="pesanGalatDirect" class="peringatan-petugas-pos" hidden role="alert"></div>

        <div id="infoKunciDirect" class="kotak-kunci-rate-limit" hidden role="alert">
            🔒 Batas percobaan gagal tercapai. Input password dikunci sementara (<strong id="hitungMundurDirect">60</strong> detik). Silakan gunakan tombol <strong>Kirim ke Manajer &amp; Hold Transaksi</strong>.
        </div>

        <div id="grupFormDirectOverride">
            <div class="form-grup">
                <label class="form-label" for="inputUsernameManajerPos">Username Manajer</label>
                <input type="text" id="inputUsernameManajerPos" class="form-input" placeholder="Masukkan username akun Manajer" autocomplete="off">
            </div>

            <div class="form-grup">
                <label class="form-label" for="inputPasswordManajerPos">Password Manajer</label>
                <input type="password" id="inputPasswordManajerPos" class="form-input" placeholder="Masukkan password akun Manajer" autocomplete="new-password">
            </div>

            <div class="modal-aksi-bawah">
                <button type="button" class="btn-batal-modal" id="btnBatalDirect">Batal</button>
                <button type="button" class="btn-utama" id="btnKonfirmasiDirect">Verifikasi &amp; Setujui Sekarang</button>
            </div>
        </div>
    </div>
</div>
