document.addEventListener('DOMContentLoaded', () => {
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    const sidebar = document.getElementById('sidebar');

    if (btnToggleSidebar && sidebar) {
        btnToggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('buka');
        });
    }

    window.tampilkanToast = (pesan) => {
        const toast = document.getElementById('toastNotif');
        if (!toast) return;

        toast.textContent = `✅ ${pesan}`;
        toast.classList.add('tampil');

        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(() => {
            toast.classList.remove('tampil');
        }, 3200);
    };

    document.querySelectorAll('[data-tutup-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) modal.classList.remove('tampil');
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove('tampil');
            }
        });
    });

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
