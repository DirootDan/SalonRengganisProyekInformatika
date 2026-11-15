import { bukaModalAksesibel, tutupModalAksesibel } from '../../components/staf-ui';

export function inisialisasiModalAksesKatalog() {
    const modalEl = document.getElementById('modalAksesKatalog');
    const inputKonteks = document.getElementById('inputKonteksAksesKatalog');
    const inputAksi = document.getElementById('inputAksiAksesKatalog');
    const inputTarget = document.getElementById('inputTargetAksesKatalog');
    const badgeAksi = document.getElementById('badgeAksiModalKatalog');
    const labelTarget = document.getElementById('labelTargetModalKatalog');
    const inputAlasan = document.getElementById('inputAlasanAksesKatalog');
    const formKunci = document.getElementById('formKunciKembaliAkses');

    document.querySelectorAll('.btn-minta-akses-katalog').forEach((btn) => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            if (inputKonteks && d.konteks) inputKonteks.value = d.konteks;
            if (inputAksi) inputAksi.value = d.aksi || 'ubah';
            if (inputTarget) inputTarget.value = d.idTarget || '';
            if (badgeAksi) badgeAksi.textContent = (d.aksi || 'ubah').toUpperCase();
            if (labelTarget) labelTarget.textContent = d.label || '-';
            if (inputAlasan) inputAlasan.value = '';
            bukaModalAksesibel(modalEl, inputAlasan);
        });
    });

    document.getElementById('btnTutupModalAksesKatalog')?.addEventListener('click', () => tutupModalAksesibel(modalEl));
    document.getElementById('btnBatalModalAksesKatalog')?.addEventListener('click', () => tutupModalAksesibel(modalEl));

    return {
        kunciKembaliAkses: (urlBatal) => {
            if (!formKunci || !urlBatal) return;
            formKunci.action = urlBatal;
            formKunci.submit();
        },
    };
}
