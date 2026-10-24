document.addEventListener('DOMContentLoaded', () => {
    // Efek highlight dinamis saat kursor melewati item riwayat
    const items = document.querySelectorAll('.reservasi-item');
    
    items.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.borderColor = '#fce7f3';
            item.style.boxShadow = '0 10px 25px rgba(219, 39, 119, 0.1)';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.borderColor = 'transparent';
            item.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.03)';
        });
    });
});