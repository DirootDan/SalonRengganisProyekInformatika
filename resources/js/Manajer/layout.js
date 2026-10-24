document.addEventListener('DOMContentLoaded', () => {
    // 1. Toggle Sidebar pada layar kecil (Mobile/Tablet)
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    const sidebar = document.getElementById('sidebar');

    if (btnToggleSidebar && sidebar) {
        btnToggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('buka');
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
