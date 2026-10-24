document.addEventListener('DOMContentLoaded', () => {
    // Efek animasi kemunculan bertahap pada kartu pintasan
    const items = document.querySelectorAll('.pintas-item');
    items.forEach((el, idx) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(12px)';
        setTimeout(() => {
            el.style.transition = 'all 0.35s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 50 * idx);
    });
});
