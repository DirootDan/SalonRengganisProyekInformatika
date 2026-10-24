document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-buka-blokir').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Buka blokir pelanggan ini dan reset jumlah pelanggarannya ke 0?')) {
                e.preventDefault();
            }
        });
    });
});
