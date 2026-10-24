document.addEventListener('DOMContentLoaded', () => {
    const inputNama    = document.getElementById('inputNamaSalon');
    const inputSlogan  = document.getElementById('inputSloganSalon');
    const inputAlamat  = document.getElementById('inputAlamatSalon');
    const inputTelp    = document.getElementById('inputTelpSalon');
    const inputIg      = document.getElementById('inputIgSalon');
    const inputPesan   = document.getElementById('inputPesanKaki');

    const cekLogo      = document.getElementById('cekTampilLogo');
    const cekPetugas   = document.getElementById('cekTampilPetugas');
    const cekKasir     = document.getElementById('cekTampilKasir');
    const cekSosmed    = document.getElementById('cekTampilSosmed');

    const prevLogo     = document.getElementById('prevLogo');
    const prevNama     = document.getElementById('prevNamaSalon');
    const prevSlogan   = document.getElementById('prevSlogan');
    const prevAlamat   = document.getElementById('prevAlamat');
    const prevTelp     = document.getElementById('prevTelp');
    const prevPesan    = document.getElementById('prevPesanKaki');
    const prevIg       = document.getElementById('prevIg');
    const prevBarisKasir  = document.getElementById('prevBarisKasir');
    const prevBarisIg     = document.getElementById('prevBarisIg');
    const prevPetugasList = document.querySelectorAll('.prev-petugas');

    const ikatTeks = (input, target, bawaan) => {
        input?.addEventListener('input', () => {
            if (target) target.textContent = input.value.trim() || bawaan;
        });
    };

    ikatTeks(inputNama, prevNama, 'SALON RENGGANIS');
    ikatTeks(inputSlogan, prevSlogan, '');
    ikatTeks(inputAlamat, prevAlamat, '');
    ikatTeks(inputTelp, prevTelp, '-');
    ikatTeks(inputIg, prevIg, '-');
    ikatTeks(inputPesan, prevPesan, '');

    cekLogo?.addEventListener('change', () => {
        if (prevLogo) prevLogo.style.display = cekLogo.checked ? 'block' : 'none';
    });

    cekKasir?.addEventListener('change', () => {
        if (prevBarisKasir) prevBarisKasir.style.display = cekKasir.checked ? 'flex' : 'none';
    });

    cekSosmed?.addEventListener('change', () => {
        if (prevBarisIg) prevBarisIg.style.display = cekSosmed.checked ? 'block' : 'none';
    });

    cekPetugas?.addEventListener('change', () => {
        prevPetugasList.forEach(el => {
            el.style.display = cekPetugas.checked ? 'block' : 'none';
        });
    });

    document.getElementById('btnUjiCetakStruk')?.addEventListener('click', () => {
        window.print();
    });
});
