document.addEventListener('DOMContentLoaded', () => {
    // 1. Toggle Sidebar pada layar kecil (Mobile/Tablet)
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
            if (e.key === 'Escape' && sidebar.classList.contains('buka')) {
                aturSidebar(false);
            }
        });
        sidebar.querySelectorAll('.nav-item').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 960) aturSidebar(false);
            });
        });
    }

    // 2. Fungsi Global Menampilkan Toast Notifikasi
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

    // 3. Penanganan Umum Modal Dialog (Buka & Tutup)
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
});
