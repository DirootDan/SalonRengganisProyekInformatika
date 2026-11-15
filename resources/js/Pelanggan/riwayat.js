import { ikatNavigasiTabAria } from '../components/aria-widget';
import { perbaruiEmptyStateGrid } from '../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Toggle buka/tutup rincian kartu reservasi (kecuali saat menekan tombol Batalkan Booking)
    const riwayatHeaders = document.querySelectorAll('.reservasi-atas');

    riwayatHeaders.forEach((header) => {
        header.addEventListener('click', (e) => {
            if (e.target.closest('.form-batal-cepat')) {
                return;
            }
            const parentItem = header.closest('.reservasi-item');
            if (parentItem) {
                parentItem.classList.toggle('aktif');
            }
        });
    });

    // 2. Filter tab status reservasi
    const tombolTab = document.querySelectorAll('.tab-filter-riwayat');
    const daftarKartu = document.querySelectorAll('.reservasi-item');
    const wadahRiwayat = daftarKartu[0]?.parentElement || null;

    ikatNavigasiTabAria(tombolTab);

    tombolTab.forEach((btn) => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filterStatus || 'semua';
            let jumlahTampak = 0;

            tombolTab.forEach((b) => b.classList.remove('aktif'));
            btn.classList.add('aktif');

            daftarKartu.forEach((kartu) => {
                const cocok = filter === 'semua'
                    || kartu.dataset.statusItem === filter
                    || kartu.dataset.grupFilter === filter;

                kartu.style.display = cocok ? '' : 'none';
                if (cocok) jumlahTampak += 1;
            });

            if (daftarKartu.length > 0) {
                perbaruiEmptyStateGrid(wadahRiwayat, jumlahTampak, 'Belum ada riwayat reservasi pada kategori ini.');
            }
        });
    });
});
