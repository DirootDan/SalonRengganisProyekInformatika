document.addEventListener('DOMContentLoaded', () => {
    // Ambil semua header reservasi
    const riwayatHeaders = document.querySelectorAll('.reservasi-atas');
    
    riwayatHeaders.forEach(header => {
        header.addEventListener('click', function() {
            // Cari elemen kotak utama (.reservasi-item) dari header yang diklik
            const parentItem = this.closest('.reservasi-item');
            
            // Tambahkan atau hapus kelas 'aktif' untuk mentrigger CSS
            parentItem.classList.toggle('aktif');
        });
    });
});