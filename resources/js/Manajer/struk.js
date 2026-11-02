const UKURAN_KERTAS_SAH = ['58mm', '76mm', '80mm'];
const KELAS_TERSEMBUNYI = 'elemen-tersembunyi';

function setVisibilitas(elemen, tampil) {
    if (!elemen) return;
    elemen.classList.toggle(KELAS_TERSEMBUNYI, !tampil);
}

function normalisasiUkuran(ukuran) {
    return UKURAN_KERTAS_SAH.includes(ukuran) ? ukuran : '80mm';
}

function ikatInputPratinjau(idInput, idTarget, nilaiBawaan) {
    const input = document.getElementById(idInput);
    const target = document.getElementById(idTarget);
    if (!input || !target) return;

    const sinkron = () => {
        target.textContent = input.value.trim() || nilaiBawaan;
    };

    input.addEventListener('input', sinkron);
    sinkron();
}

function ikatInputHeader(idInput, idTarget) {
    const input = document.getElementById(idInput);
    const target = document.getElementById(idTarget);
    if (!input || !target) return;

    const sinkron = () => {
        const nilai = input.value.trim();
        target.textContent = nilai;
        setVisibilitas(target, nilai.length > 0);
    };

    input.addEventListener('input', sinkron);
    sinkron();
}

function ikatOpsiCeklis(idCheckbox, selektorTarget) {
    const ceklis = document.getElementById(idCheckbox);
    if (!ceklis) return;

    const daftarTarget = document.querySelectorAll(selektorTarget);
    const sinkron = () => {
        daftarTarget.forEach(el => setVisibilitas(el, ceklis.checked));
    };

    ceklis.addEventListener('change', sinkron);
    sinkron();
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

function validasiFormStrukClient(form) {
    const wajibList = [
        { id: 'inputNamaSalon', pesan: 'Nama salon pada struk wajib diisi.' },
        { id: 'inputAlamatSalon', pesan: 'Alamat lengkap salon wajib diisi.' },
        { id: 'inputTelpSalon', pesan: 'Nomor telepon / WhatsApp salon wajib diisi.' },
    ];

    for (const item of wajibList) {
        const el = document.getElementById(item.id);
        if (el && el.value.trim() === '') {
            el.classList.add('input-galat');
            el.focus();
            window.tampilkanToast?.(item.pesan);
            return false;
        }
        el?.classList.remove('input-galat');
    }

    return form.checkValidity();
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
    const formStruk = document.getElementById('formStruk');
    const pilihUkuran = document.getElementById('pilihUkuranKertas');
    const kertasStruk = document.getElementById('kertasStruk');
    const labelPratinjau = document.getElementById('labelPratinjauStruk');
    const btnSimpan = document.getElementById('btnSimpanStruk');
    const btnCetak = document.getElementById('btnUjiCetakStruk');

    ikatInputPratinjau('inputNamaSalon', 'prevNamaSalon', 'SALON RENGGANIS');
    ikatInputPratinjau('inputSloganSalon', 'prevSlogan', '');
    ikatInputPratinjau('inputAlamatSalon', 'prevAlamat', '');
    ikatInputPratinjau('inputTelpSalon', 'prevTelp', '-');
    ikatInputPratinjau('inputIgSalon', 'prevIg', '-');
    ikatInputPratinjau('inputPesanKaki', 'prevPesanKaki', '');
    ikatInputHeader('inputHeaderSalon', 'prevHeader');

    ikatOpsiCeklis('cekTampilLogo', '#prevLogo');
    ikatOpsiCeklis('cekTampilKasir', '#prevBarisKasir');
    ikatOpsiCeklis('cekTampilSosmed', '#prevBarisIg');
    ikatOpsiCeklis('cekTampilPetugas', '.prev-petugas');

    if (pilihUkuran) {
        terapkanUkuranKertas(kertasStruk, labelPratinjau, pilihUkuran.value);
        pilihUkuran.addEventListener('change', () => {
            terapkanUkuranKertas(kertasStruk, labelPratinjau, pilihUkuran.value);
        });
    }

    formStruk?.addEventListener('submit', (e) => {
        if (!validasiFormStrukClient(formStruk)) {
            e.preventDefault();
            return;
        }
        if (btnSimpan) {
            btnSimpan.disabled = true;
            btnSimpan.textContent = 'Menyimpan Format Nota...';
        }
    });

    btnCetak?.addEventListener('click', () => jalankanCetakStruk(kertasStruk, pilihUkuran));
});
