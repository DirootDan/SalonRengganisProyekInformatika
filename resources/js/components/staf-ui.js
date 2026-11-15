import { svgIkon } from './ikon-svg';

const SELECTOR_FOKUS = [
    'a[href]',
    'area[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

const POLA_GALAT = /^(pilih|wajib|gagal|harus|nama .* wajib)|tidak ditemukan|kurang|ditolak|belum|tidak valid/i;

const statusModal = new WeakMap();
const tumpukanModal = [];
let listenerKeyboardTerpasang = false;

const elemenTerlihat = (el) => {
    if (!el || el.hasAttribute('hidden')) return false;
    const gaya = window.getComputedStyle(el);
    if (gaya.display === 'none' || gaya.visibility === 'hidden') return false;
    return el.getClientRects().length > 0;
};

const daftarElemenFokus = (modalEl) => {
    const wadah = modalEl.querySelector('.modal-kotak') || modalEl;
    return Array.from(wadah.querySelectorAll(SELECTOR_FOKUS)).filter(elemenTerlihat);
};

const fokusTargetAwal = (modalEl, fokusEl) => {
    if (fokusEl && modalEl.contains(fokusEl) && !fokusEl.disabled && elemenTerlihat(fokusEl)) {
        fokusEl.focus();
        return;
    }

    const daftar = daftarElemenFokus(modalEl);
    if (daftar.length > 0) {
        const prioritas = daftar.find((el) => !el.classList.contains('btn-tutup-modal')) || daftar[0];
        prioritas.focus();
        return;
    }

    const kotak = modalEl.querySelector('.modal-kotak') || modalEl;
    if (!kotak.hasAttribute('tabindex')) kotak.setAttribute('tabindex', '-1');
    kotak.focus();
};

const pasangListenerKeyboardGlobal = () => {
    if (listenerKeyboardTerpasang) return;
    listenerKeyboardTerpasang = true;

    document.addEventListener('keydown', (e) => {
        const modalAktif = tumpukanModal[tumpukanModal.length - 1];
        if (!modalAktif || !modalAktif.classList.contains('tampil')) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            tutupModalAksesibel(modalAktif);
            return;
        }

        if (e.key === 'Tab') {
            const daftar = daftarElemenFokus(modalAktif);
            if (daftar.length === 0) {
                e.preventDefault();
                return;
            }

            const pertama = daftar[0];
            const terakhir = daftar[daftar.length - 1];
            const aktif = document.activeElement;

            if (e.shiftKey) {
                if (aktif === pertama || !modalAktif.contains(aktif)) {
                    e.preventDefault();
                    terakhir.focus();
                }
            } else if (aktif === terakhir || !modalAktif.contains(aktif)) {
                e.preventDefault();
                pertama.focus();
            }
        }
    });
};

const pastikanStatusModal = (modalEl) => {
    let data = statusModal.get(modalEl);
    if (!data) {
        data = { pemicuFokus: null, onClose: null, terikat: false };
        statusModal.set(modalEl, data);
    }
    return data;
};

/**
 * Menampilkan toast notifikasi global dengan peran ARIA dan gaya visual sesuai tingkat pesan.
 *
 * @param {string} pesan
 * @param {'sukses'|'galat'|null} [tipe=null]
 */
export function tampilkanToastGlobal(pesan, tipe = null) {
    const toast = document.getElementById('toastNotif');
    if (!toast) return;

    const teks = String(pesan ?? '').trim();
    const adalahGalat = tipe === 'galat' || (tipe !== 'sukses' && POLA_GALAT.test(teks));

    toast.classList.remove('toast-galat', 'toast-sukses');
    const teksEl = document.createElement('span');
    teksEl.textContent = teks;
    if (adalahGalat) {
        toast.classList.add('toast-galat');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.innerHTML = `${svgIkon('peringatan')} `;
    } else {
        toast.classList.add('toast-sukses');
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.innerHTML = `${svgIkon('tandaCek')} `;
    }
    toast.appendChild(teksEl);

    toast.classList.add('tampil');

    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => {
        toast.classList.remove('tampil');
    }, 3200);
}

/**
 * Membuka modal secara aksesibel: menyinkronkan aria-hidden, menyimpan fokus pemicu, dan memindahkan fokus ke dialog.
 *
 * @param {HTMLElement|null} modalEl
 * @param {HTMLElement|null} [fokusEl=null]
 */
export function bukaModalAksesibel(modalEl, fokusEl = null) {
    if (!modalEl) return;

    pasangListenerKeyboardGlobal();
    ikatModalAksesibel(modalEl);

    const data = pastikanStatusModal(modalEl);
    const aktif = document.activeElement;
    if (aktif instanceof HTMLElement && !modalEl.contains(aktif)) {
        data.pemicuFokus = aktif;
    }

    modalEl.classList.add('tampil');
    modalEl.setAttribute('aria-hidden', 'false');

    const indeksLama = tumpukanModal.indexOf(modalEl);
    if (indeksLama !== -1) tumpukanModal.splice(indeksLama, 1);
    tumpukanModal.push(modalEl);

    window.requestAnimationFrame(() => {
        if (modalEl.classList.contains('tampil')) {
            fokusTargetAwal(modalEl, fokusEl);
        }
    });
}

/**
 * Menutup modal secara aksesibel: menyinkronkan aria-hidden dan memulihkan fokus ke elemen pemicu.
 *
 * @param {HTMLElement|null} modalEl
 * @param {{ pulihkanFokus?: boolean, jalankanOnClose?: boolean }} [opsi]
 */
export function tutupModalAksesibel(modalEl, opsi = {}) {
    if (!modalEl) return;

    const { pulihkanFokus = true, jalankanOnClose = true } = opsi;
    const sedangTampil = modalEl.classList.contains('tampil') || modalEl.getAttribute('aria-hidden') === 'false';

    modalEl.classList.remove('tampil');
    modalEl.setAttribute('aria-hidden', 'true');

    const indeks = tumpukanModal.indexOf(modalEl);
    if (indeks !== -1) tumpukanModal.splice(indeks, 1);

    const data = pastikanStatusModal(modalEl);
    const pemicu = data.pemicuFokus;
    data.pemicuFokus = null;

    if (pulihkanFokus && pemicu instanceof HTMLElement && pemicu.isConnected) {
        pemicu.focus();
    } else if (pulihkanFokus && tumpukanModal.length > 0) {
        const modalSebelumnya = tumpukanModal[tumpukanModal.length - 1];
        window.requestAnimationFrame(() => fokusTargetAwal(modalSebelumnya, null));
    }

    if (sedangTampil && jalankanOnClose && typeof data.onClose === 'function') {
        data.onClose(modalEl);
    }
}

/**
 * Mengikat handler tutup backdrop, tombol [data-tutup-modal], dan keyboard Escape/Tab pada modal.
 *
 * @param {HTMLElement|null} modalEl
 * @param {((modalEl: HTMLElement) => void)|null} [onClose=null]
 */
export function ikatModalAksesibel(modalEl, onClose = null) {
    if (!modalEl) return;

    pasangListenerKeyboardGlobal();
    const data = pastikanStatusModal(modalEl);
    if (typeof onClose === 'function') {
        data.onClose = onClose;
    }

    if (data.terikat) return;
    data.terikat = true;

    modalEl.addEventListener('click', (e) => {
        if (e.target === modalEl) {
            tutupModalAksesibel(modalEl);
        }
    });

    modalEl.querySelectorAll('[data-tutup-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            tutupModalAksesibel(modalEl);
        });
    });

    if (modalEl.classList.contains('tampil')) {
        modalEl.setAttribute('aria-hidden', 'false');
        if (!tumpukanModal.includes(modalEl)) {
            tumpukanModal.push(modalEl);
        }
        window.requestAnimationFrame(() => fokusTargetAwal(modalEl, null));
    }
}

/**
 * Mengikat seluruh elemen .modal-backdrop pada halaman agar konsisten mendukung Escape, klik backdrop, dan focus trap.
 */
export function ikatSemuaModalAksesibel() {
    document.querySelectorAll('.modal-backdrop').forEach((modalEl) => {
        ikatModalAksesibel(modalEl);
    });
}

/**
 * Menampilkan atau menyembunyikan baris pesan kosong hasil pencarian/filter pada tabel.
 *
 * @param {HTMLElement|null} tbodyEl
 * @param {number} jumlahTampak
 * @param {string} [pesan='Tidak ada data yang cocok dengan pencarian atau filter.']
 */
export function perbaruiEmptyStateTabel(tbodyEl, jumlahTampak, pesan = 'Tidak ada data yang cocok dengan pencarian atau filter.') {
    if (!tbodyEl) return;

    const barisKosong = tbodyEl.querySelector('tr.tabel-kosong-filter');
    if (jumlahTampak > 0) {
        barisKosong?.remove();
        return;
    }

    const tabel = tbodyEl.closest('table');
    const jumlahKolom = Math.max(1, tabel?.querySelectorAll('thead th').length || 1);

    if (barisKosong) {
        const sel = barisKosong.querySelector('td');
        if (sel) {
            sel.colSpan = jumlahKolom;
            sel.textContent = pesan;
        }
        return;
    }

    const tr = document.createElement('tr');
    tr.className = 'tabel-kosong-filter';
    const td = document.createElement('td');
    td.colSpan = jumlahKolom;
    td.textContent = pesan;
    tr.appendChild(td);
    tbodyEl.appendChild(tr);
}

/**
 * Menampilkan atau menyembunyikan kartu pesan kosong hasil pencarian pada grid katalog/antrean.
 *
 * @param {HTMLElement|null} gridEl
 * @param {number} jumlahTampak
 * @param {string} [pesan='Tidak ada item yang cocok dengan pencarian atau filter.']
 */
export function perbaruiEmptyStateGrid(gridEl, jumlahTampak, pesan = 'Tidak ada item yang cocok dengan pencarian atau filter.') {
    if (!gridEl) return;

    const kotakKosong = gridEl.querySelector('.kartu-kosong-filter');
    if (jumlahTampak > 0) {
        kotakKosong?.remove();
        return;
    }

    if (kotakKosong) {
        kotakKosong.textContent = pesan;
        return;
    }

    const div = document.createElement('div');
    div.className = 'kartu-kosong-filter';
    div.setAttribute('role', 'status');
    div.textContent = pesan;
    gridEl.appendChild(div);
}

/**
 * Mengikat logika toggle sidebar mobile, Escape key, backdrop, dan auto-tutup menu nav staf.
 */
export function ikatSidebarStaf() {
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    const btnTutupSidebar = document.getElementById('btnTutupSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebar = document.getElementById('sidebar');

    if (!btnToggleSidebar || !sidebar) return;

    const aturSidebar = (terbuka) => {
        sidebar.classList.toggle('buka', terbuka);
        sidebarBackdrop?.classList.toggle('aktif', terbuka);
        btnToggleSidebar.setAttribute('aria-expanded', String(terbuka));
    };

    btnToggleSidebar.addEventListener('click', () => {
        aturSidebar(!sidebar.classList.contains('buka'));
    });
    btnTutupSidebar?.addEventListener('click', () => aturSidebar(false));
    sidebarBackdrop?.addEventListener('click', () => aturSidebar(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('buka') && !document.querySelector('.modal-backdrop.tampil')) {
            aturSidebar(false);
        }
    });
    sidebar.querySelectorAll('.nav-item').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 960) aturSidebar(false);
        });
    });
}
