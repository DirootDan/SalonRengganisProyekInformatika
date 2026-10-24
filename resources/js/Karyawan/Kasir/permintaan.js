document.addEventListener('DOMContentLoaded', () => {
    const cariInput    = document.getElementById('cariBooking');
    const barisBooking = document.querySelectorAll('.baris-booking');
    const modalTolak   = document.getElementById('modalTolakBooking');
    const formTolak    = document.getElementById('formTolakBooking');
    const judulTolak   = document.getElementById('judulModalTolak');
    const inputAlasan  = document.getElementById('inputAlasanTolak');

    cariInput?.addEventListener('input', () => {
        const kata = cariInput.value.toLowerCase().trim();
        barisBooking.forEach(b => {
            b.style.display = (b.dataset.cari || '').includes(kata) ? '' : 'none';
        });
    });

    document.querySelectorAll('.btnBukaModalTolak').forEach(btn => {
        btn.addEventListener('click', () => {
            formTolak.action       = btn.dataset.url;
            judulTolak.textContent = `Tolak Reservasi #${btn.dataset.id} (${btn.dataset.pelanggan})`;
            inputAlasan.value      = '';
            modalTolak.classList.add('tampil');
        });
    });
});
