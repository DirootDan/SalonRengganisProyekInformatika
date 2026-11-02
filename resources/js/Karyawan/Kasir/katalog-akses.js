export function inisialisasiModalAksesKatalog() {
    const modalEl = document.getElementById('modalAksesKatalog');
    const inputKonteks = document.getElementById('inputKonteksAksesKatalog');
    const inputAksi = document.getElementById('inputAksiAksesKatalog');
    const inputTarget = document.getElementById('inputTargetAksesKatalog');
    const badgeAksi = document.getElementById('badgeAksiModalKatalog');
    const labelTarget = document.getElementById('labelTargetModalKatalog');
    const inputAlasan = document.getElementById('inputAlasanAksesKatalog');
    const formKunci = document.getElementById('formKunciKembaliAkses');

    const aturModal = (tampil) => {
        if (!modalEl) return;
        modalEl.classList.toggle('tampil', tampil);
        modalEl.setAttribute('aria-hidden', tampil ? 'false' : 'true');
    };

    document.querySelectorAll('.btn-minta-akses-katalog').forEach((btn) => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            if (inputKonteks && d.konteks) inputKonteks.value = d.konteks;
            if (inputAksi) inputAksi.value = d.aksi || 'ubah';
            if (inputTarget) inputTarget.value = d.idTarget || '';
            if (badgeAksi) badgeAksi.textContent = (d.aksi || 'ubah').toUpperCase();
            if (labelTarget) labelTarget.textContent = d.label || '-';
            if (inputAlasan) inputAlasan.value = '';
            aturModal(true);
            inputAlasan?.focus();
        });
    });

    document.getElementById('btnTutupModalAksesKatalog')?.addEventListener('click', () => aturModal(false));
    document.getElementById('btnBatalModalAksesKatalog')?.addEventListener('click', () => aturModal(false));
    modalEl?.addEventListener('click', (e) => {
        if (e.target === modalEl) aturModal(false);
    });

    return {
        kunciKembaliAkses: (urlBatal) => {
            if (!formKunci || !urlBatal) return;
            formKunci.action = urlBatal;
            formKunci.submit();
        },
    };
}
