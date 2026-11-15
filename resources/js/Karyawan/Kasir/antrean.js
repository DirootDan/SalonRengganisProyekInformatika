import { perbaruiEmptyStateGrid } from '../../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    const cariInput = document.getElementById('cariAntrean');
    const kartuList = document.querySelectorAll('.kartu-antrean-item');
    const wadahGrid = kartuList[0]?.parentElement || null;

    cariInput?.addEventListener('input', () => {
        const kata = cariInput.value.toLowerCase().trim();
        let jumlahTampak = 0;

        kartuList.forEach(k => {
            const tampil = (k.dataset.cari || '').includes(kata);
            k.style.display = tampil ? 'flex' : 'none';
            if (tampil) jumlahTampak += 1;
        });

        if (kartuList.length > 0) {
            perbaruiEmptyStateGrid(wadahGrid, jumlahTampak, 'Tidak ada antrean yang cocok dengan pencarian.');
        }
    });
});
