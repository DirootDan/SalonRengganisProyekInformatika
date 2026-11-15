import { ikatSemuaModalAksesibel, tampilkanToastGlobal } from '../components/staf-ui';

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

    // 2. Fungsi Global Menampilkan Toast Notifikasi
    window.tampilkanToast = tampilkanToastGlobal;

    // 3. Penanganan Umum Modal Dialog yang Aksesibel (Backdrop, Tombol Tutup, Escape, Focus Trap)
    ikatSemuaModalAksesibel();
});
