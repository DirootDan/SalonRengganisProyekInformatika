document.addEventListener('DOMContentLoaded', () => {
    const btnFilter = document.getElementById('btnTerapkanFilter');
    const btnCetak  = document.getElementById('btnCetakLaporan');

    btnFilter?.addEventListener('click', () => {
        const mulai   = document.getElementById('tglMulai')?.value;
        const selesai = document.getElementById('tglSelesai')?.value;
        window.tampilkanToast(`Rekap transaksi ditampilkan untuk ${mulai} s/d ${selesai}.`);
    });

    btnCetak?.addEventListener('click', () => {
        window.print();
    });
});
