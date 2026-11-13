import { escapeHtml, evaluasiPromoClient, formatRupiah, tambahMenitKeJam } from './formatters';

const aturHidden = (el, sembunyi) => {
    if (!el) return;
    if (sembunyi) el.setAttribute('hidden', '');
    else el.removeAttribute('hidden');
};

export const renderRingkasanBooking = (refs, state) => {
    let subtotalLayanan = 0;
    let jumlahLayanan = 0;
    let totalDurasi = 0;
    let petugasKosong = 0;
    const barisLiveHtml = [];
    const chipLayananHtml = [];

    refs.checkboxes.forEach((checkbox) => {
        if (!checkbox.checked) return;

        const idLayanan = checkbox.value;
        const harga = parseFloat(checkbox.dataset.harga || '0');
        const durasi = parseInt(checkbox.dataset.durasi || '60', 10);
        const nama = checkbox.dataset.namaLayanan || 'Layanan';
        const jabatan = checkbox.dataset.jabatan || 'Stylist';

        subtotalLayanan += harga;
        totalDurasi += durasi;
        jumlahLayanan++;

        const select = checkbox.closest('.opsi-blok')?.querySelector('.select-petugas');
        if (select && parseInt(select.dataset.tersedia || '1', 10) <= 0) petugasKosong++;

        const teksPetugas = select && select.value
            ? select.selectedOptions[0]?.textContent?.trim()
            : `${jabatan} Otomatis`;

        const namaAman = escapeHtml(nama);
        const jabatanAman = escapeHtml(jabatan);
        const petugasAman = escapeHtml(teksPetugas);
        const idAman = escapeHtml(idLayanan);

        chipLayananHtml.push(`
            <span class="chip-pilihan-item">
                <span>${namaAman} (${durasi} mnt)</span>
                <button type="button" class="btn-hapus-chip-layanan" data-hapus-layanan="${idAman}" aria-label="Batal pilih ${namaAman}">&times;</button>
            </span>
        `);

        barisLiveHtml.push(`
            <div class="item-live-baris">
                <div class="item-live-kiri">
                    <span class="item-live-nama">${namaAman}</span>
                    <span class="item-live-sub">${jabatanAman}: ${petugasAman} &bull; ${durasi} mnt</span>
                </div>
                <div class="item-live-kanan">
                    <span class="item-live-harga">${formatRupiah(harga)}</span>
                    <button type="button" class="btn-hapus-item-live" data-hapus-layanan="${idAman}" title="Hapus layanan">&times;</button>
                </div>
            </div>
        `);
    });

    if (refs.badgeTabLayananEl) refs.badgeTabLayananEl.textContent = String(jumlahLayanan);

    if (refs.barChipTerpilihEl && refs.daftarChipTerpilihEl) {
        aturHidden(refs.barChipTerpilihEl, jumlahLayanan === 0);
        if (jumlahLayanan > 0 && refs.judulChipTerpilihEl) {
            refs.judulChipTerpilihEl.textContent = `${jumlahLayanan} Perawatan Terpilih (${totalDurasi} menit):`;
        }
        refs.daftarChipTerpilihEl.innerHTML = jumlahLayanan > 0 ? chipLayananHtml.join('') : '';
    }

    let subtotalProduk = 0;
    let totalItemProduk = 0;

    refs.inputProdukAddons.forEach((inp) => {
        const stok = parseInt(inp.dataset.stok || '0', 10);
        let qty = parseInt(inp.value || '0', 10);
        if (Number.isNaN(qty) || qty < 0) qty = 0;
        if (qty > stok) qty = stok;
        inp.value = qty;

        const kartu = inp.closest('.kartu-addon');
        const idProduk = kartu?.dataset.idProduk || '';
        kartu?.classList.toggle('aktif', qty > 0);

        if (qty > 0) {
            const subProd = qty * parseFloat(inp.dataset.harga || '0');
            subtotalProduk += subProd;
            totalItemProduk += qty;

            barisLiveHtml.push(`
                <div class="item-live-baris">
                    <div class="item-live-kiri">
                        <span class="item-live-nama">+ ${escapeHtml(inp.dataset.nama || 'Produk')} (${qty}x)</span>
                        <span class="item-live-sub">Produk Perawatan Tambahan</span>
                    </div>
                    <div class="item-live-kanan">
                        <span class="item-live-harga">${formatRupiah(subProd)}</span>
                        <button type="button" class="btn-hapus-item-live" data-hapus-produk="${escapeHtml(idProduk)}" title="Hapus produk">&times;</button>
                    </div>
                </div>
            `);
        }
    });

    state.sinkronKatalog();

    if (refs.ringkasanKosongStateEl && refs.kontenRingkasanAktifEl) {
        aturHidden(refs.ringkasanKosongStateEl, jumlahLayanan > 0);
        aturHidden(refs.kontenRingkasanAktifEl, jumlahLayanan === 0);
    }

    if (refs.daftarPilihanEl) refs.daftarPilihanEl.innerHTML = barisLiveHtml.join('');

    const subtotal = subtotalLayanan + subtotalProduk;
    const { potongan, kode } = evaluasiPromoClient(refs.promoRefs, subtotal);
    const totalAkhir = Math.max(0, subtotal - potongan);

    if (refs.jumlahPilihanEl) refs.jumlahPilihanEl.textContent = `Subtotal Layanan (${jumlahLayanan} layanan • ${totalDurasi} mnt)`;
    if (refs.ringkasanLayananEl) refs.ringkasanLayananEl.textContent = formatRupiah(subtotalLayanan);

    aturHidden(refs.barisRingkasanProdukEl, totalItemProduk === 0);
    if (refs.jumlahProdukEl) refs.jumlahProdukEl.textContent = `Produk Add-On (${totalItemProduk} item)`;
    if (refs.ringkasanProdukEl) refs.ringkasanProdukEl.textContent = formatRupiah(subtotalProduk);

    aturHidden(refs.barisRingkasanPromoEl, potongan === 0);
    if (refs.labelPromoEl) refs.labelPromoEl.textContent = kode ? `Potongan Promo (${kode})` : 'Potongan Promo';
    if (refs.ringkasanPromoEl) refs.ringkasanPromoEl.textContent = `- ${formatRupiah(potongan)}`;
    if (refs.totalHargaEl) refs.totalHargaEl.textContent = formatRupiah(totalAkhir);

    const tglAktif = refs.inputTanggal?.value || '';
    const jamMulai = (refs.inputJam?.value || '10:00').slice(0, 5);
    const jamSelesai = tambahMenitKeJam(jamMulai, totalDurasi || 60);
    const [jamM, mntM] = jamMulai.split(':').map((n) => parseInt(n || '0', 10));
    const totalMenitMulai = (jamM * 60) + mntM;
    const diluarJamBuka = jamMulai < '09:00' || jamMulai > '20:30';
    const lewatJamTutup = totalDurasi > 0 && (totalMenitMulai + totalDurasi) > (21 * 60);

    if (refs.teksJamBesarEl) refs.teksJamBesarEl.textContent = `${jamMulai} WIB`;
    if (refs.teksEstimasiStep2El) {
        refs.teksEstimasiStep2El.textContent = jumlahLayanan > 0
            ? `Estimasi selesai sekitar ${jamSelesai} WIB (${totalDurasi} mnt)`
            : 'Pilih slot jam di bawah';
    }
    if (refs.subStep1El) refs.subStep1El.textContent = jumlahLayanan > 0 ? `${jumlahLayanan} perawatan (${totalDurasi} mnt)` : 'Pilih layanan & petugas';
    if (refs.subStep2El) refs.subStep2El.textContent = `${tglAktif} • ${jamMulai} WIB`;
    if (refs.subStep3El) refs.subStep3El.textContent = totalItemProduk > 0 ? `${totalItemProduk} item add-on dipilih` : 'Opsional & konfirmasi';
    if (refs.metaMobileBarEl) refs.metaMobileBarEl.textContent = jumlahLayanan > 0 ? `${jumlahLayanan} layanan (${totalDurasi} mnt) • ${jamMulai} WIB` : 'Belum ada layanan dipilih';
    if (refs.totalMobileBarEl) refs.totalMobileBarEl.textContent = formatRupiah(totalAkhir);
    if (refs.lencanaJadwalEl) refs.lencanaJadwalEl.textContent = jumlahLayanan > 0 ? `${tglAktif} • ${jamMulai} - ${jamSelesai} WIB` : `${tglAktif} • ${jamMulai} WIB`;
    if (refs.estimasiJadwalEl) refs.estimasiJadwalEl.textContent = jumlahLayanan > 0 ? `Estimasi selesai pukul ${jamSelesai} WIB` : '';

    const siap = jumlahLayanan > 0 && petugasKosong === 0 && !diluarJamBuka && !lewatJamTutup;

    if (refs.infoValidasiEl) {
        if (diluarJamBuka) {
            refs.infoValidasiEl.textContent = 'Jam kedatangan harus berada dalam jam operasional salon (09:00 s/d 20:30 WIB).';
        } else if (jumlahLayanan === 0) {
            refs.infoValidasiEl.textContent = 'Pilih minimal 1 perawatan di sebelah kiri untuk melanjutkan.';
        } else if (lewatJamTutup) {
            refs.infoValidasiEl.textContent = `Durasi ${totalDurasi} menit (selesai ${jamSelesai} WIB) melewati jam tutup 21:00 WIB. Pilih jam lebih awal.`;
        } else if (petugasKosong > 0) {
            refs.infoValidasiEl.textContent = `Petugas untuk layanan terpilih penuh pada pukul ${jamMulai} WIB. Silakan pilih jam/menit lain.`;
        } else {
            refs.infoValidasiEl.textContent = '';
        }
    }

    if (refs.btnLanjutStepRingkasan && refs.submitBtn) {
        if (state.stepAktif === 1) {
            aturHidden(refs.btnLanjutStepRingkasan, false);
            refs.btnLanjutStepRingkasan.disabled = jumlahLayanan === 0;
            refs.btnLanjutStepRingkasan.textContent = 'Lanjut Pilih Tanggal & Jam →';
            aturHidden(refs.submitBtn, true);
        } else if (state.stepAktif === 2) {
            aturHidden(refs.btnLanjutStepRingkasan, false);
            refs.btnLanjutStepRingkasan.disabled = !siap;
            refs.btnLanjutStepRingkasan.textContent = 'Lanjut ke Add-On & Konfirmasi →';
            aturHidden(refs.submitBtn, true);
        } else {
            aturHidden(refs.btnLanjutStepRingkasan, true);
            aturHidden(refs.submitBtn, false);
            refs.submitBtn.disabled = !siap;
        }
    } else if (refs.submitBtn) {
        refs.submitBtn.disabled = !siap;
    }
};
