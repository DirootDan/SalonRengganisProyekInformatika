/**
 * Komponen Modal Konfirmasi (<x-confirm-modal>)
 * Menggantikan seluruh dialog confirm() bawaan browser di Manajer, Kasir, dan Pelanggan.
 */

const SELEKTOR_FORM_DESTRUKTIF = [
    'form[data-confirm-title]',
    'form[data-confirm-item]',
    '.form-hapus-staf',
    '.form-hapus-layanan',
    '.form-hapus-produk',
    '.form-hapus-promo',
    '.form-buka-blokir',
    '.form-toggle-metode',
    '.formHapusLayanan',
    '.formHapusProduk',
    '.form-batal-booking',
    '.form-hapus-foto',
    '.form-selesai',
].join(', ');

document.addEventListener('DOMContentLoaded', () => {
    const backdrop = document.getElementById('modalKonfirmasi');
    if (!backdrop) return;

    const elJudul = document.getElementById('cmJudul');
    const elItemBadge = document.getElementById('cmItemBadge');
    const elDeskripsi = document.getElementById('cmDeskripsi');
    const elDampakKotak = document.getElementById('cmDampakKotak');
    const elDampakTeks = document.getElementById('cmDampakTeks');
    const elWadahIkon = document.getElementById('cmWadahIkon');
    const btnBatal = document.getElementById('cmBtnBatal');
    const btnSilang = document.getElementById('cmBtnSilang');
    const btnKonfirmasi = document.getElementById('cmBtnKonfirmasi');

    let formTarget = null;
    let pemicuTerakhir = null;
    let sedangMengirim = false;

    const susunOpsiDariForm = (form) => {
        const ds = form.dataset;
        const namaItem = ds.confirmItem || ds.nama || ds.kode || ds.id || '';
        const judulDefault = form.classList.contains('form-buka-blokir')
            ? 'Konfirmasi Buka Blokir Pelanggan'
            : (form.classList.contains('form-batal-booking')
                ? 'Konfirmasi Pembatalan Reservasi'
                : (form.classList.contains('form-toggle-metode')
                    ? 'Konfirmasi Status Metode Pembayaran'
                    : 'Konfirmasi Penghapusan Data'));

        let deskripsiDefault = 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        let dampakDefault = ds.confirmImpact || '';
        let labelTombol = ds.confirmButton || 'Ya, Lanjutkan';
        let varian = ds.confirmVariant || 'bahaya';

        if (form.classList.contains('form-hapus-staf')) {
            const jmlBooking = parseInt(ds.booking || '0', 10);
            deskripsiDefault = `Karyawan "${namaItem}" akan dinonaktifkan dan diarsipkan dari daftar staf aktif.`;
            dampakDefault = jmlBooking > 0
                ? `Perhatian: Karyawan ini masih memiliki ${jmlBooking} reservasi aktif (Menunggu/Diterima). Server akan menolak penghapusan sebelum reservasi dialihkan.`
                : 'Histori komisi dan transaksi lama milik karyawan ini tetap tersimpan dengan aman.';
            labelTombol = 'Ya, Nonaktifkan & Arsipkan';
        } else if (form.classList.contains('form-hapus-layanan') || form.classList.contains('formHapusLayanan')) {
            deskripsiDefault = `Layanan "${namaItem}" akan dihapus dari katalog perawatan aktif.`;
            dampakDefault = ds.confirmImpact || 'Histori nota transaksi masa lalu tetap aman. Layanan tidak dapat dihapus bila masih terikat reservasi aktif.';
            labelTombol = 'Ya, Hapus Layanan';
        } else if (form.classList.contains('form-hapus-produk') || form.classList.contains('formHapusProduk')) {
            deskripsiDefault = `Produk "${namaItem}" akan diarsipkan dari daftar inventaris aktif.`;
            dampakDefault = ds.confirmImpact || 'Histori penjualan pada nota lama tetap terjaga.';
            labelTombol = 'Ya, Hapus Produk';
        } else if (form.classList.contains('form-hapus-promo')) {
            deskripsiDefault = `Program promo "${namaItem}" akan dihapus dan tidak dapat dipilih lagi oleh pelanggan.`;
            dampakDefault = ds.confirmImpact || 'Reservasi lama yang sudah menyimpan snapshot promo ini tidak akan berubah.';
            labelTombol = 'Ya, Hapus Promo';
        } else if (form.classList.contains('form-buka-blokir')) {
            deskripsiDefault = `Blokir akun pelanggan "${namaItem}" akan dibuka dan jumlah pelanggaran aktifnya direset ke 0.`;
            dampakDefault = 'Tindakan ini akan dicatat di log audit pelanggaran beserta nama Manajer.';
            labelTombol = 'Ya, Buka Blokir';
            varian = 'peringatan';
        } else if (form.classList.contains('form-toggle-metode')) {
            deskripsiDefault = ds.confirmDescription || `Ubah status aktif metode pembayaran "${namaItem}"?`;
            labelTombol = ds.confirmButton || 'Ya, Ubah Status';
            varian = 'peringatan';
        } else if (form.classList.contains('form-batal-booking')) {
            deskripsiDefault = `Reservasi #${namaItem} akan dibatalkan secara permanen.`;
            dampakDefault = 'Stok produk add-on pada reservasi ini akan dikembalikan otomatis ke gudang salon.';
            labelTombol = 'Ya, Batalkan Reservasi';
        } else if (form.classList.contains('form-hapus-foto')) {
            deskripsiDefault = 'Foto profil Anda saat ini akan dihapus dan dikembalikan ke avatar inisial.';
            labelTombol = 'Ya, Hapus Foto';
        } else if (form.classList.contains('form-selesai')) {
            deskripsiDefault = `Tandai layanan perawatan "${namaItem || 'ini'}" sebagai Selesai dikerjakan?`;
            labelTombol = 'Ya, Tandai Selesai';
            varian = 'peringatan';
        }

        return {
            judul: ds.confirmTitle || judulDefault,
            item: namaItem,
            deskripsi: ds.confirmDescription || deskripsiDefault,
            dampak: dampakDefault,
            labelTombol: ds.confirmButton || labelTombol,
            varian,
        };
    };

    const bukaModal = (form, elemenPemicu) => {
        formTarget = form;
        pemicuTerakhir = elemenPemicu || document.activeElement;
        sedangMengirim = false;

        const opsi = susunOpsiDariForm(form);

        elJudul.textContent = opsi.judul;
        elItemBadge.textContent = opsi.item ? `Item: ${opsi.item}` : '';
        elItemBadge.hidden = !opsi.item;
        elDeskripsi.textContent = opsi.deskripsi;

        if (opsi.dampak) {
            elDampakTeks.textContent = opsi.dampak;
            elDampakKotak.hidden = false;
        } else {
            elDampakTeks.textContent = '';
            elDampakKotak.hidden = true;
        }

        btnKonfirmasi.disabled = false;
        btnBatal.disabled = false;
        btnKonfirmasi.textContent = opsi.labelTombol;
        btnKonfirmasi.dataset.labelAsli = opsi.labelTombol;

        btnKonfirmasi.classList.toggle('cm-btn-peringatan', opsi.varian === 'peringatan');
        btnKonfirmasi.classList.toggle('cm-btn-bahaya', opsi.varian !== 'peringatan');
        elWadahIkon.classList.toggle('cm-ikon-peringatan', opsi.varian === 'peringatan');
        elWadahIkon.classList.toggle('cm-ikon-bahaya', opsi.varian !== 'peringatan');

        backdrop.hidden = false;
        backdrop.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            backdrop.classList.add('cm-terbuka');
            btnBatal.focus();
        });
    };

    const tutupModal = () => {
        if (sedangMengirim) return;
        backdrop.classList.remove('cm-terbuka');
        backdrop.setAttribute('aria-hidden', 'true');
        backdrop.hidden = true;
        formTarget = null;

        if (pemicuTerakhir && typeof pemicuTerakhir.focus === 'function') {
            pemicuTerakhir.focus();
        }
    };

    // Intercept submit pada seluruh form destruktif
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.confirmed === 'true') return;
        if (!form.matches(SELEKTOR_FORM_DESTRUKTIF)) return;

        e.preventDefault();
        bukaModal(form, e.submitter || document.activeElement);
    });

    btnBatal.addEventListener('click', tutupModal);
    btnSilang.addEventListener('click', tutupModal);

    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) {
            tutupModal();
        }
    });

    btnKonfirmasi.addEventListener('click', () => {
        if (!formTarget || sedangMengirim) return;

        // Jika form butuh input alasan (misal buka blokir pelanggan), validasi dulu
        const inputAlasan = formTarget.querySelector('input[name="alasan_buka"]');
        if (inputAlasan && (!inputAlasan.value || inputAlasan.value.trim().length < 4)) {
            sedangMengirim = false;
            tutupModal();
            inputAlasan.focus();
            inputAlasan.reportValidity();
            return;
        }

        sedangMengirim = true;
        btnKonfirmasi.disabled = true;
        btnBatal.disabled = true;
        btnKonfirmasi.textContent = 'Sedang Memproses...';

        formTarget.dataset.confirmed = 'true';
        formTarget.submit();
    });

    // Penanganan Keyboard: Escape untuk menutup & Focus Trap (Tab / Shift+Tab)
    document.addEventListener('keydown', (e) => {
        if (backdrop.hidden) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            tutupModal();
            return;
        }

        if (e.key === 'Tab') {
            const elemenFokus = [btnSilang, btnBatal, btnKonfirmasi].filter((el) => el && !el.disabled);
            if (elemenFokus.length === 0) return;

            const pertama = elemenFokus[0];
            const terakhir = elemenFokus[elemenFokus.length - 1];

            if (e.shiftKey && document.activeElement === pertama) {
                e.preventDefault();
                terakhir.focus();
            } else if (!e.shiftKey && document.activeElement === terakhir) {
                e.preventDefault();
                pertama.focus();
            }
        }
    });
});
