const UKURAN_SAH = ['58mm', '76mm', '80mm'];

function terapkanUkuranHalaman() {
    const ukuran = document.body.dataset.ukuran;
    if (!UKURAN_SAH.includes(ukuran)) return;

    const gaya = document.createElement('style');
    gaya.textContent = `@page { size: ${ukuran} auto; margin: 0; }`;
    document.head.appendChild(gaya);
}

terapkanUkuranHalaman();

window.addEventListener('load', () => window.print());

document.getElementById('btnCetakUlang')?.addEventListener('click', () => window.print());
