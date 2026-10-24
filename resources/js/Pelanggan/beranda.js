document.addEventListener('DOMContentLoaded', () => {
    // Animasi masuk berurutan untuk kartu layanan
    const cards = document.querySelectorAll('.kartu');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});