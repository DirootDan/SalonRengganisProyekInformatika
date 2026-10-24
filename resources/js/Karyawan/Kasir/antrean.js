document.addEventListener('DOMContentLoaded', () => {
    const cariInput = document.getElementById('cariAntrean');
    const kartuList = document.querySelectorAll('.kartu-antrean-item');

    cariInput?.addEventListener('input', () => {
        const kata = cariInput.value.toLowerCase().trim();
        kartuList.forEach(k => {
            k.style.display = (k.dataset.cari || '').includes(kata) ? 'flex' : 'none';
        });
    });
});
