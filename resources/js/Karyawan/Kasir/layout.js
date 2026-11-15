import { ikatSemuaModalAksesibel, ikatSidebarStaf, tampilkanToastGlobal } from '../../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');

    ikatSidebarStaf();
    window.tampilkanToast = tampilkanToastGlobal;
    ikatSemuaModalAksesibel();

    const urlBadge = sidebar?.dataset.urlBadge;
    const badgeMenungguEl = document.getElementById('badgeNotifMenunggu');
    const badgeAntreanEl = document.getElementById('badgeNotifAntrean');

    const perbaruiSatuBadge = (el, jumlah, kelasAktif) => {
        if (!el) return;
        const angka = Math.max(0, parseInt(jumlah || '0', 10));
        el.textContent = angka > 99 ? '99+' : String(angka);
        el.classList.toggle(kelasAktif, angka > 0);
        el.classList.toggle('kosong', angka === 0);
    };

    const segarkanBadgeSidebar = async () => {
        if (!urlBadge || document.hidden) return;
        try {
            const res = await fetch(urlBadge, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            perbaruiSatuBadge(badgeMenungguEl, data.menunggu, 'ada-acc');
            perbaruiSatuBadge(badgeAntreanEl, data.antrean, 'ada-antrean');
        } catch {
            // Abaikan error jaringan sementara saat polling latar belakang
        }
    };

    if (urlBadge && (badgeMenungguEl || badgeAntreanEl)) {
        setInterval(segarkanBadgeSidebar, 20000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) segarkanBadgeSidebar();
        });
    }
});
