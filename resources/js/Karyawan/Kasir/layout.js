import { ikatSemuaModalAksesibel, tampilkanToastGlobal } from '../../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    const btnTutupSidebar = document.getElementById('btnTutupSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebar = document.getElementById('sidebar');

    const aturSidebar = (terbuka) => {
        if (!sidebar) return;
        sidebar.classList.toggle('buka', terbuka);
        sidebarBackdrop?.classList.toggle('aktif', terbuka);
        btnToggleSidebar?.setAttribute('aria-expanded', String(terbuka));
    };

    if (btnToggleSidebar && sidebar) {
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
