@vite(['resources/css/components/confirm-modal.css', 'resources/js/components/confirm-modal.js'])

<div id="modalKonfirmasi"
     class="cm-backdrop"
     role="alertdialog"
     aria-modal="true"
     aria-labelledby="cmJudul"
     aria-describedby="cmDeskripsi"
     aria-hidden="true"
     hidden>
    <div class="cm-dialog" role="document">
        <div class="cm-header">
            <div class="cm-ikon-lingkaran cm-ikon-bahaya" id="cmWadahIkon" aria-hidden="true">
                <svg class="cm-svg-ikon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <path d="M12 9v4"/>
                    <path d="M12 17h.01"/>
                </svg>
            </div>
            <div class="cm-header-teks">
                <h2 id="cmJudul" class="cm-judul">Konfirmasi Tindakan</h2>
                <p id="cmItemBadge" class="cm-item-nama"></p>
            </div>
            <button type="button" class="cm-btn-silang" id="cmBtnSilang" aria-label="Tutup dialog konfirmasi">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>

        <div class="cm-body">
            <p id="cmDeskripsi" class="cm-deskripsi">
                Apakah Anda yakin ingin melanjutkan tindakan ini?
            </p>
            <div id="cmDampakKotak" class="cm-dampak" hidden>
                <svg class="cm-dampak-ikon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" x2="12" y1="8" y2="12"/>
                    <line x1="12" x2="12.01" y1="16" y2="16"/>
                </svg>
                <span id="cmDampakTeks"></span>
            </div>
        </div>

        <div class="cm-footer">
            <button type="button" id="cmBtnBatal" class="cm-btn cm-btn-batal">
                Batal
            </button>
            <button type="button" id="cmBtnKonfirmasi" class="cm-btn cm-btn-bahaya">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>
