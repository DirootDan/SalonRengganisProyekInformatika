document.addEventListener('DOMContentLoaded', () => {
    const riwayatHeaders = document.querySelectorAll('.reservasi-atas');

    riwayatHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const parentItem = this.closest('.reservasi-item');
            parentItem.classList.toggle('aktif');
        });
    });

    // Konfirmasi sebelum membatalkan reservasi
    document.querySelectorAll('.form-batal-booking').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')) {
                e.preventDefault();
            }
        });
    });
});
