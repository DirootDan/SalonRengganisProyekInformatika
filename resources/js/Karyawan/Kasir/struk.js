import {
    terapkanUkuranKertas,
    jalankanCetakStruk,
} from '../../components/struk-preview';

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
