const UKURAN_KERTAS_SAH = ['58mm', '76mm', '80mm'];

function normalisasiUkuran(ukuran) {
    return UKURAN_KERTAS_SAH.includes(ukuran) ? ukuran : '80mm';
}

function terapkanUkuranKertas(kertasStruk, labelPratinjau, ukuranRaw) {
    const ukuran = normalisasiUkuran(ukuranRaw);
    document.documentElement.style.setProperty('--lebar-cetak-struk', ukuran);

    if (kertasStruk) {
        kertasStruk.classList.remove('kertas-58mm', 'kertas-76mm', 'kertas-80mm');
        kertasStruk.classList.add(`kertas-${ukuran}`);
        kertasStruk.dataset.ukuran = ukuran;
    }

    if (labelPratinjau) {
        const jenis = ukuran === '76mm' ? 'IMPACT / DOT MATRIX' : 'THERMAL';
        labelPratinjau.textContent = `PRATINJAU KERTAS STRUK ${jenis} (${ukuran.toUpperCase()})`;
    }
}

function jalankanCetakStruk(kertasStruk, pilihUkuran) {
    if (!kertasStruk) {
        window.tampilkanToast?.('Pratinjau kertas struk tidak ditemukan untuk dicetak.');
        return;
    }

    const ukuran = normalisasiUkuran(pilihUkuran?.value || kertasStruk.dataset.ukuran);
    document.documentElement.style.setProperty('--lebar-cetak-struk', ukuran);

    try {
        window.print();
    } catch {
        window.tampilkanToast?.('Gagal membuka dialog cetak browser. Periksa pengaturan printer Anda.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const pilihUkuran = document.getElementById('pilihUkuranKertas');
    const kertasStruk = document.getElementById('kertasStruk');
    const labelPratinjau = document.getElementById('labelPratinjauStruk');
    const btnCetak = document.getElementById('btnUjiCetakStruk');

    if (pilihUkuran) {
        terapkanUkuranKertas(kertasStruk, labelPratinjau, pilihUkuran.value);
        pilihUkuran.addEventListener('change', () => {
            terapkanUkuranKertas(kertasStruk, labelPratinjau, pilihUkuran.value);
        });
    }

    btnCetak?.addEventListener('click', () => jalankanCetakStruk(kertasStruk, pilihUkuran));
});
