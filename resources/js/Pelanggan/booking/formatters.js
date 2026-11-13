export const escapeHtml = (teks) => String(teks ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

export const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
}).format(Math.max(0, Math.round(angka)));

export const tambahMenitKeJam = (jamStr, menitTambah) => {
    const bagian = (jamStr || '10:00').split(':').map(Number);
    const jam = Number.isNaN(bagian[0]) ? 10 : bagian[0];
    const mnt = Number.isNaN(bagian[1]) ? 0 : bagian[1];
    const totalMenit = (jam * 60) + mnt + menitTambah;
    const jamHasil = Math.floor(totalMenit / 60);
    const mntHasil = totalMenit % 60;

    return `${String(jamHasil).padStart(2, '0')}:${String(mntHasil).padStart(2, '0')}`;
};

export const muatKatalogPromo = (kartuPromoList) => {
    const katalogPromo = new Map();
    kartuPromoList.forEach((kartu) => {
        const kode = (kartu.dataset.kode || '').toUpperCase();
        if (!kode) return;
        katalogPromo.set(kode, {
            kode,
            nama: kartu.dataset.nama || kode,
            tipe: kartu.dataset.tipe || 'Nominal',
            nilai: parseFloat(kartu.dataset.nilai || '0'),
            min: parseFloat(kartu.dataset.min || '0'),
        });
    });

    return katalogPromo;
};

export const evaluasiPromoClient = ({ inputKodePromo, katalogPromo, kartuPromoList, pesanStatusPromo }, subtotal) => {
    const kode = (inputKodePromo?.value || '').toUpperCase().trim();
    kartuPromoList.forEach((k) => k.classList.toggle('terpilih', k.dataset.kode === kode));

    if (!kode) {
        if (pesanStatusPromo) {
            pesanStatusPromo.textContent = '';
            pesanStatusPromo.className = 'pesan-status-promo';
        }
        return { potongan: 0, kode: '' };
    }

    const promo = katalogPromo.get(kode);
    if (!promo) {
        if (pesanStatusPromo) {
            pesanStatusPromo.textContent = `Kode promo "${kode}" akan divalidasi oleh server saat pengajuan booking.`;
            pesanStatusPromo.className = 'pesan-status-promo info';
        }
        return { potongan: 0, kode };
    }

    if (subtotal < promo.min) {
        const kurang = promo.min - subtotal;
        if (pesanStatusPromo) {
            pesanStatusPromo.textContent = `Kurang ${formatRupiah(kurang)} lagi untuk memakai promo ${promo.kode} (Min. ${formatRupiah(promo.min)}).`;
            pesanStatusPromo.className = 'pesan-status-promo peringatan';
        }
        return { potongan: 0, kode };
    }

    let potongan = promo.tipe === 'Persentase'
        ? Math.round(subtotal * (Math.min(100, promo.nilai) / 100))
        : promo.nilai;

    potongan = Math.min(subtotal, Math.max(0, potongan));

    if (pesanStatusPromo) {
        pesanStatusPromo.textContent = `Promo ${promo.kode} diterapkan: hemat ${formatRupiah(potongan)}!`;
        pesanStatusPromo.className = 'pesan-status-promo sukses';
    }

    return { potongan, kode };
};
