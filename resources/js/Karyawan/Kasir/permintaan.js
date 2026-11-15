import { bukaModalAksesibel, perbaruiEmptyStateTabel } from '../../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    const cariInput    = document.getElementById('cariBooking');
    const barisBooking = document.querySelectorAll('.baris-booking');
    const tbodyBooking = barisBooking[0]?.closest('tbody') || null;
    const modalTolak   = document.getElementById('modalTolakBooking');
    const formTolak    = document.getElementById('formTolakBooking');
    const judulTolak   = document.getElementById('judulModalTolak');
    const inputAlasan  = document.getElementById('inputAlasanTolak');

    cariInput?.addEventListener('input', () => {
        const kata = cariInput.value.toLowerCase().trim();
        let jumlahTampak = 0;

        barisBooking.forEach(b => {
            const tampil = (b.dataset.cari || '').includes(kata);
            b.style.display = tampil ? '' : 'none';
            if (tampil) jumlahTampak += 1;
        });

        if (barisBooking.length > 0) {
            perbaruiEmptyStateTabel(tbodyBooking, jumlahTampak, 'Tidak ada permintaan reservasi yang cocok dengan pencarian.');
        }
    });

    document.querySelectorAll('.btnBukaModalTolak').forEach(btn => {
        btn.addEventListener('click', () => {
            formTolak.action       = btn.dataset.url;
            judulTolak.textContent = `Tolak Reservasi #${btn.dataset.id} (${btn.dataset.pelanggan})`;
            inputAlasan.value      = '';
            bukaModalAksesibel(modalTolak, inputAlasan);
        });
    });
});
