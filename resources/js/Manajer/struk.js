document.addEventListener('DOMContentLoaded', () => {
    const inputNama    = document.getElementById('inputNamaSalon');
    const inputSlogan  = document.getElementById('inputSloganSalon');
    const inputAlamat  = document.getElementById('inputAlamatSalon');
    const inputTelp    = document.getElementById('inputTelpSalon');
    const inputIg      = document.getElementById('inputIgSalon');
    const inputHeader  = document.getElementById('inputHeaderSalon');
    const inputPesan   = document.getElementById('inputPesanKaki');
    const pilihUkuran  = document.getElementById('pilihUkuranKertas');

    const cekLogo      = document.getElementById('cekTampilLogo');
    const cekPetugas   = document.getElementById('cekTampilPetugas');
    const cekKasir     = document.getElementById('cekTampilKasir');
    const cekSosmed    = document.getElementById('cekTampilSosmed');

    const kertasStruk     = document.getElementById('kertasStruk');
    const labelPratinjau  = document.getElementById('labelPratinjauStruk');
    const prevLogo        = document.getElementById('prevLogo');
    const prevNama        = document.getElementById('prevNamaSalon');
    const prevSlogan      = document.getElementById('prevSlogan');
    const prevAlamat      = document.getElementById('prevAlamat');
    const prevTelp        = document.getElementById('prevTelp');
    const prevHeader      = document.getElementById('prevHeader');
    const prevPesan       = document.getElementById('prevPesanKaki');
    const prevIg          = document.getElementById('prevIg');
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

    inputHeader?.addEventListener('input', () => {
        if (!prevHeader) return;
        const nilai = inputHeader.value.trim();
        prevHeader.textContent = nilai;
        prevHeader.style.display = nilai ? 'block' : 'none';
    });

    const terapkanUkuranKertas = (ukuran) => {
        const ukuranValid = ['58mm', '76mm', '80mm'].includes(ukuran) ? ukuran : '80mm';
        if (kertasStruk) {
            kertasStruk.classList.remove('kertas-58mm', 'kertas-76mm', 'kertas-80mm');
            kertasStruk.classList.add(`kertas-${ukuranValid}`);
            kertasStruk.dataset.ukuran = ukuranValid;
        }
        if (labelPratinjau) {
            const jenisKertas = ukuranValid === '76mm' ? 'IMPACT / DOT MATRIX' : 'THERMAL';
            labelPratinjau.textContent = `PRATINJAU KERTAS STRUK ${jenisKertas} (${ukuranValid.toUpperCase()})`;
        }
    };

    pilihUkuran?.addEventListener('change', () => {
        terapkanUkuranKertas(pilihUkuran.value);
    });

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

    const cetakHanyaStruk = () => {
        if (!kertasStruk) return;

        const ukuran = pilihUkuran?.value || kertasStruk.dataset.ukuran || '80mm';
        const fontSize = ukuran === '58mm' ? '11px' : (ukuran === '76mm' ? '12px' : '12.5px');
        const judulSize = ukuran === '58mm' ? '14px' : (ukuran === '76mm' ? '15.5px' : '16.5px');
        const paddingStruk = ukuran === '58mm' ? '4mm 2.5mm' : '5mm 4mm';

        let iframe = document.getElementById('iframeCetakStruk');
        if (iframe) {
            iframe.remove();
        }

        iframe = document.createElement('iframe');
        iframe.id = 'iframeCetakStruk';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.setAttribute('aria-hidden', 'true');
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow?.document;
        if (!doc) {
            window.print();
            return;
        }

        doc.open();
        doc.write(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Struk Nota (${ukuran}) - Salon Rengganis</title>
    <style>
        @page {
            size: ${ukuran} auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: ${ukuran};
            background: #ffffff;
            color: #111827;
            font-family: 'Courier New', Courier, monospace;
            font-size: ${fontSize};
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .kertas-struk {
            width: ${ukuran};
            max-width: ${ukuran};
            margin: 0 auto;
            padding: ${paddingStruk};
            background: #ffffff;
            color: #111827;
        }
        .struk-kepala {
            text-align: center;
        }
        .struk-logo {
            font-size: 22px;
            margin-bottom: 4px;
        }
        .struk-judul {
            font-size: ${judulSize};
            font-weight: 700;
            letter-spacing: 0.8px;
        }
        .struk-sub {
            font-size: 11px;
            color: #374151;
            margin-bottom: 4px;
        }
        .struk-alamat,
        .struk-telp,
        .struk-header-tambahan {
            font-size: 10.5px;
            color: #374151;
            line-height: 1.4;
        }
        .struk-header-tambahan {
            margin-top: 4px;
            font-style: italic;
        }
        .struk-garis {
            text-align: center;
            color: #6b7280;
            margin: 8px 0;
            overflow: hidden;
            white-space: nowrap;
        }
        .baris-struk {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            line-height: 1.5;
        }
        .struk-item {
            margin-bottom: 6px;
        }
        .prev-petugas {
            display: block;
            font-size: 10px;
            color: #4b5563;
        }
        .struk-grand-total {
            font-size: 13.5px;
            font-weight: 700;
            margin: 4px 0;
            padding: 4px 0;
            border-top: 1px dashed #6b7280;
            border-bottom: 1px dashed #6b7280;
        }
        .struk-kaki {
            text-align: center;
            font-size: 10.5px;
            color: #374151;
            line-height: 1.45;
        }
        .struk-kaki p {
            margin: 0 0 4px;
        }
        .struk-ig {
            font-weight: 700;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="kertas-struk">${kertasStruk.innerHTML}</div>
</body>
</html>`);
        doc.close();

        setTimeout(() => {
            const win = iframe.contentWindow;
            if (!win) return;
            win.focus();
            win.print();
        }, 120);
    };

    document.getElementById('btnUjiCetakStruk')?.addEventListener('click', cetakHanyaStruk);
});
