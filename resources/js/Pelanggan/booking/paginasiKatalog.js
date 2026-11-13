/**
 * Utilitas generik untuk filter + paginasi katalog client-side tanpa duplikasi antara layanan & produk.
 */
export const terapkanPaginasiKatalog = ({
    items,
    perHalaman,
    halamanSaatIni,
    predikatCocok,
    labelSatuan,
    kosongEl,
    paginasiEl,
    infoEl,
    teksHalEl,
    btnPrev,
    btnNext,
}) => {
    const cocokList = items.filter(predikatCocok);
    const totalItem = cocokList.length;
    const totalHal = Math.max(1, Math.ceil(totalItem / perHalaman));
    const halAman = Math.min(totalHal, Math.max(1, halamanSaatIni));

    const indeksMulai = (halAman - 1) * perHalaman;
    const indeksAkhir = indeksMulai + perHalaman;
    const tampilSet = new Set(cocokList.slice(indeksMulai, indeksAkhir));

    items.forEach((item) => {
        item.style.display = tampilSet.has(item) ? '' : 'none';
    });

    kosongEl?.classList.toggle('tampil', totalItem === 0);
    if (paginasiEl) paginasiEl.style.display = totalItem > perHalaman ? 'flex' : 'none';
    if (infoEl) {
        const awal = totalItem === 0 ? 0 : indeksMulai + 1;
        const akhir = Math.min(totalItem, indeksAkhir);
        infoEl.textContent = `Menampilkan ${awal}–${akhir} dari ${totalItem} ${labelSatuan}`;
    }
    if (teksHalEl) teksHalEl.textContent = `Hal ${halAman} / ${totalHal}`;
    if (btnPrev) btnPrev.disabled = halAman <= 1;
    if (btnNext) btnNext.disabled = halAman >= totalHal;

    return halAman;
};
