export const formatRp = (angka) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
}).format(Math.max(0, Math.round(angka)));

export const hitungSubtotal = (keranjang) => {
    let sub = 0;
    keranjang.forEach((item) => {
        sub += item.harga * item.qty;
    });
    return sub;
};

export const hitungPotonganPromoDariSubtotal = (sub, selectPromo) => {
    if (!selectPromo || !selectPromo.value) return 0;
    const opt = selectPromo.selectedOptions[0];
    const tipe = opt?.dataset.tipe || '';
    const nilai = parseFloat(opt?.dataset.nilai || '0');
    const min = parseFloat(opt?.dataset.min || '0');
    if (sub < min) return 0;

    return tipe === 'Persentase' ? Math.round(sub * (nilai / 100)) : nilai;
};

export const hitungTotalAkhir = (keranjang, selectPromo) => {
    const sub = hitungSubtotal(keranjang);
    return Math.max(0, Math.round(sub - hitungPotonganPromoDariSubtotal(sub, selectPromo)));
};

export const apakahMetodeNonTunai = (selectMetodeBayar) => {
    const opt = selectMetodeBayar?.selectedOptions[0];
    const kat = (opt?.dataset.kategori || opt?.textContent || '').toLowerCase();

    return Boolean(selectMetodeBayar) && !kat.includes('tunai');
};
