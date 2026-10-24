document.addEventListener('DOMContentLoaded', () => {
    const btnFilter = document.getElementById('btnTerapkanFilter');
    const btnCetak  = document.getElementById('btnCetakLaporan');
    const btnCsv    = document.getElementById('btnEksporCsv');

    btnFilter?.addEventListener('click', () => {
        const mulai   = document.getElementById('tglMulai')?.value;
        const selesai = document.getElementById('tglSelesai')?.value;
        window.tampilkanToast(`Laporan diperbarui untuk periode ${mulai} s/d ${selesai}.`);
    });

    btnCetak?.addEventListener('click', () => {
        window.print();
    });

    btnCsv?.addEventListener('click', () => {
        window.tampilkanToast('Berkas laporan CSV sedang diunduh.');
    });
});
