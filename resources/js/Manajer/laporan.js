document.addEventListener('DOMContentLoaded', () => {
    const btnCetak = document.getElementById('btnCetakLaporan');

    btnCetak?.addEventListener('click', () => {
        window.print();
    });
});
