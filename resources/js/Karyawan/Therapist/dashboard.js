document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-selesai').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Tandai layanan perawatan ini sebagai Selesai dikerjakan?')) {
                e.preventDefault();
            }
        });
    });
});
