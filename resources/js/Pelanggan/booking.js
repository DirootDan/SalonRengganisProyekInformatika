import { muatKatalogPromo } from './booking/formatters';
import { terapkanPaginasiKatalog } from './booking/paginasiKatalog';
import { buatPengelolaJadwalAjax } from './booking/jadwalAjax';
import { renderRingkasanBooking } from './booking/ringkasanUI';

document.addEventListener('DOMContentLoaded', () => {
    const formBooking        = document.getElementById('formBooking');
    const inputTanggal       = document.getElementById('tgl_booking');
    const inputJam           = document.getElementById('jam_booking');
    const chipTanggalList    = document.querySelectorAll('.chip-tanggal');
    const chipJamList        = document.querySelectorAll('.chip-jam');
    const btnMenitCepatList  = document.querySelectorAll('.btn-menit-cepat');
    const tabKategoriList    = document.querySelectorAll('.tab-kat-item');
    const statusTersediaEl   = document.getElementById('statusKetersediaan');
    const searchInput        = document.getElementById('searchInput');
    const blokLayanan        = Array.from(document.querySelectorAll('.opsi-blok'));
    const checkboxes         = document.querySelectorAll('input[name="layanan[]"]');

    const paginasiLayananEl  = document.getElementById('paginasiLayanan');
    const infoHalLayananEl   = document.getElementById('infoHalamanLayanan');
    const teksHalLayananEl   = document.getElementById('teksHalLayanan');
    const btnPrevLayanan     = document.getElementById('btnPrevLayanan');
    const btnNextLayanan     = document.getElementById('btnNextLayanan');

    const searchProdukInput  = document.getElementById('searchProdukInput');
    const chipFilterProduk   = document.querySelectorAll('.chip-filter-produk');
    const btnFilterTerpilih  = document.getElementById('btnFilterProdukTerpilih');
    const kartuProdukList    = Array.from(document.querySelectorAll('.kartu-addon'));
    const inputProdukAddons  = document.querySelectorAll('.input-qty-addon');
    const produkKosongEl     = document.getElementById('produkTidakDitemukan');
    const paginasiProdukEl   = document.getElementById('paginasiProduk');
    const infoHalProdukEl    = document.getElementById('infoHalamanProduk');
    const teksHalProdukEl    = document.getElementById('teksHalProduk');
    const btnPrevProduk      = document.getElementById('btnPrevProduk');
    const btnNextProduk      = document.getElementById('btnNextProduk');

    const inputKodePromo     = document.getElementById('kode_promo');
    const btnHapusPromo      = document.getElementById('btnHapusPromo');
    const pesanStatusPromo   = document.getElementById('pesanStatusPromo');
    const kartuPromoList     = document.querySelectorAll('.kartu-promo-item');
    const submitBtn          = document.getElementById('submitBtn');
    const totalHargaEl       = document.getElementById('totalHarga');
    const jumlahPilihanEl    = document.getElementById('jumlahPilihan');
    const ringkasanLayananEl = document.getElementById('ringkasanLayananNominal');
    const jumlahProdukEl     = document.getElementById('jumlahProdukAddon');
    const ringkasanProdukEl  = document.getElementById('ringkasanProdukNominal');
    const labelPromoEl       = document.getElementById('labelRingkasanPromo');
    const ringkasanPromoEl   = document.getElementById('ringkasanPromoNominal');
    const estimasiJadwalEl   = document.getElementById('estimasiJadwalTeks');
    const lencanaJadwalEl    = document.getElementById('lencanaJadwalLive');
    const daftarPilihanEl    = document.getElementById('daftarPilihanLive');
    const infoValidasiEl     = document.getElementById('infoValidasi');
    const tidakDitemukan     = document.getElementById('tidakDitemukan');

    const stepperItemList         = document.querySelectorAll('.stepper-item');
    const stepPanelList           = document.querySelectorAll('.step-panel');
    const subStep1El              = document.getElementById('subStep1');
    const subStep2El              = document.getElementById('subStep2');
    const subStep3El              = document.getElementById('subStep3');
    const badgeTabLayananEl       = document.getElementById('badgeTabLayananTerpilih');
    const barChipTerpilihEl       = document.getElementById('barChipTerpilih');
    const judulChipTerpilihEl     = document.getElementById('judulChipTerpilih');
    const daftarChipTerpilihEl    = document.getElementById('daftarChipTerpilih');
    const btnResetSemuaLayanan    = document.getElementById('btnResetSemuaLayanan');
    const btnToggleMenitKustom    = document.getElementById('btnToggleMenitKustom');
    const panelMenitKustom        = document.getElementById('panelMenitKustom');
    const teksJamBesarEl          = document.getElementById('teksJamTerpilihBesar');
    const teksEstimasiStep2El     = document.getElementById('teksEstimasiSelesaiStep2');
    const metaMobileBarEl         = document.getElementById('metaMobileBar');
    const totalMobileBarEl        = document.getElementById('totalMobileBar');
    const ringkasanKosongStateEl  = document.getElementById('ringkasanKosongState');
    const kontenRingkasanAktifEl  = document.getElementById('kontenRingkasanAktif');
    const barisRingkasanProdukEl  = document.getElementById('barisRingkasanProduk');
    const barisRingkasanPromoEl   = document.getElementById('barisRingkasanPromo');
    const btnLanjutStepRingkasan  = document.getElementById('btnLanjutStepRingkasan');
    const btnLihatSemuaPromo      = document.getElementById('btnLihatSemuaPromo');
    const promoEkstraList         = document.querySelectorAll('.promo-ekstra');

    if (!formBooking) return;

    const ITEM_PER_HAL = 6;
    const katalogPromo = muatKatalogPromo(kartuPromoList);

    let filterJabatanAktif = 'semua';
    let halLayananAktif    = 1;
    let filterProdukAktif  = 'semua';
    let halProdukAktif     = 1;
    let stepAktif          = 1;

    const renderLayananBerhalaman = () => {
        const keyword = (searchInput?.value || '').toLowerCase().trim();
        halLayananAktif = terapkanPaginasiKatalog({
            items: blokLayanan,
            perHalaman: ITEM_PER_HAL,
            halamanSaatIni: halLayananAktif,
            labelSatuan: 'perawatan',
            kosongEl: tidakDitemukan,
            paginasiEl: paginasiLayananEl,
            infoEl: infoHalLayananEl,
            teksHalEl: teksHalLayananEl,
            btnPrev: btnPrevLayanan,
            btnNext: btnNextLayanan,
            predikatCocok: (blok) => {
                const nama = (blok.dataset.nama || '').toLowerCase();
                const jabatan = blok.dataset.jabatan || '';
                const cb = blok.querySelector('input[name="layanan[]"]');
                const cocokTab = filterJabatanAktif === 'semua'
                    || (filterJabatanAktif === 'terpilih' && Boolean(cb?.checked))
                    || jabatan === filterJabatanAktif;
                return cocokTab && nama.includes(keyword);
            },
        });
    };

    const renderProdukBerhalaman = () => {
        const keyword = (searchProdukInput?.value || '').toLowerCase().trim();
        const jmlProdukTerpilih = kartuProdukList.reduce((acc, kartu) => {
            const qty = parseInt(kartu.querySelector('.input-qty-addon')?.value || '0', 10);
            return acc + (qty > 0 ? 1 : 0);
        }, 0);
        if (btnFilterTerpilih) btnFilterTerpilih.textContent = `Sudah Dipilih (${jmlProdukTerpilih})`;

        halProdukAktif = terapkanPaginasiKatalog({
            items: kartuProdukList,
            perHalaman: ITEM_PER_HAL,
            halamanSaatIni: halProdukAktif,
            labelSatuan: 'produk',
            kosongEl: produkKosongEl,
            paginasiEl: paginasiProdukEl,
            infoEl: infoHalProdukEl,
            teksHalEl: teksHalProdukEl,
            btnPrev: btnPrevProduk,
            btnNext: btnNextProduk,
            predikatCocok: (kartu) => {
                const nama = (kartu.dataset.namaProduk || '').toLowerCase();
                const qty = parseInt(kartu.querySelector('.input-qty-addon')?.value || '0', 10);
                return (filterProdukAktif === 'semua' || qty > 0) && nama.includes(keyword);
            },
        });
    };

    const ringkasanRefs = {
        checkboxes,
        inputProdukAddons,
        inputTanggal,
        inputJam,
        badgeTabLayananEl,
        barChipTerpilihEl,
        judulChipTerpilihEl,
        daftarChipTerpilihEl,
        ringkasanKosongStateEl,
        kontenRingkasanAktifEl,
        daftarPilihanEl,
        jumlahPilihanEl,
        ringkasanLayananEl,
        barisRingkasanProdukEl,
        jumlahProdukEl,
        ringkasanProdukEl,
        barisRingkasanPromoEl,
        labelPromoEl,
        ringkasanPromoEl,
        totalHargaEl,
        teksJamBesarEl,
        teksEstimasiStep2El,
        subStep1El,
        subStep2El,
        subStep3El,
        metaMobileBarEl,
        totalMobileBarEl,
        lencanaJadwalEl,
        estimasiJadwalEl,
        infoValidasiEl,
        btnLanjutStepRingkasan,
        submitBtn,
        promoRefs: { inputKodePromo, katalogPromo, kartuPromoList, pesanStatusPromo },
    };

    const updateRingkasan = () => renderRingkasanBooking(ringkasanRefs, {
        stepAktif,
        sinkronKatalog: () => {
            renderLayananBerhalaman();
            renderProdukBerhalaman();
        },
    });

    const sinkronStepperVisual = () => {
        stepPanelList.forEach((panel) => {
            panel.classList.toggle('aktif', parseInt(panel.dataset.stepPanel || '1', 10) === stepAktif);
        });
        stepperItemList.forEach((item) => {
            const no = parseInt(item.dataset.stepTarget || '1', 10);
            item.classList.toggle('aktif', no === stepAktif);
            item.classList.toggle('selesai', no < stepAktif);
        });
    };

    const bukaStep = (nomor, gulir = true) => {
        stepAktif = Math.min(3, Math.max(1, nomor));
        sinkronStepperVisual();
        updateRingkasan();
        if (gulir) {
            document.getElementById(`panelStep${stepAktif}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    stepperItemList.forEach((item) => {
        item.addEventListener('click', () => bukaStep(parseInt(item.dataset.stepTarget || '1', 10), false));
    });
    document.querySelectorAll('[data-lanjut-step]').forEach((btn) => {
        btn.addEventListener('click', () => bukaStep(parseInt(btn.dataset.lanjutStep || '1', 10), true));
    });
    btnLanjutStepRingkasan?.addEventListener('click', () => {
        if (stepAktif < 3) bukaStep(stepAktif + 1, true);
    });

    btnLihatSemuaPromo?.addEventListener('click', () => {
        const statusBaru = btnLihatSemuaPromo.dataset.terbuka !== '1';
        btnLihatSemuaPromo.dataset.terbuka = statusBaru ? '1' : '0';
        btnLihatSemuaPromo.textContent = statusBaru ? 'Sembunyikan' : `Lihat semua (${kartuPromoList.length})`;
        promoEkstraList.forEach((el) => {
            if (statusBaru) el.removeAttribute('hidden');
            else el.setAttribute('hidden', '');
        });
    });

    btnToggleMenitKustom?.addEventListener('click', () => {
        if (!panelMenitKustom) return;
        const sedangTersembunyi = panelMenitKustom.hasAttribute('hidden');
        if (sedangTersembunyi) {
            panelMenitKustom.removeAttribute('hidden');
            btnToggleMenitKustom.setAttribute('aria-expanded', 'true');
            btnToggleMenitKustom.textContent = '✕ Tutup Menit Spesifik';
        } else {
            panelMenitKustom.setAttribute('hidden', '');
            btnToggleMenitKustom.setAttribute('aria-expanded', 'false');
            btnToggleMenitKustom.textContent = '⚙️ Pilih Menit Spesifik';
        }
    });

    tabKategoriList.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabKategoriList.forEach((t) => t.classList.remove('aktif'));
            tab.classList.add('aktif');
            filterJabatanAktif = tab.dataset.filterJabatan || 'semua';
            halLayananAktif = 1;
            renderLayananBerhalaman();
        });
    });
    searchInput?.addEventListener('input', () => { halLayananAktif = 1; renderLayananBerhalaman(); });
    btnPrevLayanan?.addEventListener('click', () => { halLayananAktif--; renderLayananBerhalaman(); });
    btnNextLayanan?.addEventListener('click', () => { halLayananAktif++; renderLayananBerhalaman(); });

    searchProdukInput?.addEventListener('input', () => { halProdukAktif = 1; renderProdukBerhalaman(); });
    chipFilterProduk.forEach((chip) => {
        chip.addEventListener('click', () => {
            chipFilterProduk.forEach((c) => c.classList.remove('aktif'));
            chip.classList.add('aktif');
            filterProdukAktif = chip.dataset.filterProduk || 'semua';
            halProdukAktif = 1;
            renderProdukBerhalaman();
        });
    });
    btnPrevProduk?.addEventListener('click', () => { halProdukAktif--; renderProdukBerhalaman(); });
    btnNextProduk?.addEventListener('click', () => { halProdukAktif++; renderProdukBerhalaman(); });

    const aturPilihanPetugas = (checkbox) => {
        const blok = checkbox.closest('.opsi-blok');
        const select = blok?.querySelector('.select-petugas');
        const indikator = blok?.querySelector('.indikator-pilih');

        blok?.classList.toggle('terpilih', checkbox.checked);
        if (indikator) indikator.textContent = checkbox.checked ? '✓ Terpilih' : '+ Pilih';
        if (select) {
            select.disabled = !checkbox.checked;
            if (!checkbox.checked) select.value = '';
        }
    };

    const jadwalCtrl = buatPengelolaJadwalAjax({
        formBooking,
        inputTanggal,
        inputJam,
        checkboxes,
        chipTanggalList,
        chipJamList,
        btnMenitCepatList,
        statusTersediaEl,
        onJadwalBerubah: updateRingkasan,
    });

    checkboxes.forEach((checkbox) => {
        aturPilihanPetugas(checkbox);
        checkbox.addEventListener('change', () => {
            aturPilihanPetugas(checkbox);
            jadwalCtrl.muatPetugasTersedia();
            updateRingkasan();
        });
    });

    document.querySelectorAll('.select-petugas').forEach((select) => {
        select.addEventListener('change', () => {
            select.closest('.opsi-blok')?.classList.remove('galat');
            updateRingkasan();
        });
    });

    document.querySelectorAll('.btn-qty-addon').forEach((btn) => {
        btn.addEventListener('click', () => {
            const inp = btn.closest('.kartu-addon')?.querySelector('.input-qty-addon');
            if (!inp) return;
            const stok = parseInt(inp.dataset.stok || '0', 10);
            let nilai = parseInt(inp.value || '0', 10);
            if (btn.dataset.aksi === 'tambah' && nilai < stok) nilai++;
            if (btn.dataset.aksi === 'kurang' && nilai > 0) nilai--;
            inp.value = nilai;
            updateRingkasan();
        });
    });

    inputProdukAddons.forEach((inp) => inp.addEventListener('input', updateRingkasan));
    document.querySelectorAll('.btn-pilih-promo').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (inputKodePromo) {
                inputKodePromo.value = btn.dataset.kode || '';
                updateRingkasan();
            }
        });
    });
    inputKodePromo?.addEventListener('input', updateRingkasan);
    btnHapusPromo?.addEventListener('click', () => {
        if (inputKodePromo) {
            inputKodePromo.value = '';
            updateRingkasan();
        }
    });

    btnResetSemuaLayanan?.addEventListener('click', () => {
        checkboxes.forEach((cb) => {
            cb.checked = false;
            aturPilihanPetugas(cb);
        });
        jadwalCtrl.muatPetugasTersedia();
        updateRingkasan();
    });

    formBooking.addEventListener('click', (e) => {
        const btnHapusLayanan = e.target.closest('[data-hapus-layanan]');
        if (btnHapusLayanan) {
            const idTarget = btnHapusLayanan.dataset.hapusLayanan;
            checkboxes.forEach((cb) => {
                if (String(cb.value) === String(idTarget)) {
                    cb.checked = false;
                    aturPilihanPetugas(cb);
                }
            });
            jadwalCtrl.muatPetugasTersedia();
            updateRingkasan();
            return;
        }

        const btnHapusProduk = e.target.closest('[data-hapus-produk]');
        if (btnHapusProduk) {
            const idProd = btnHapusProduk.dataset.hapusProduk;
            const inp = document.querySelector(`.kartu-addon[data-id-produk="${CSS.escape(idProd)}"] .input-qty-addon`);
            if (inp) {
                inp.value = '0';
                updateRingkasan();
            }
        }
    });

    formBooking.addEventListener('submit', () => {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses Reservasi Anda...';
        }
    });

    const indeksTerpilihAwal = blokLayanan.findIndex((b) => b.classList.contains('terpilih'));
    if (indeksTerpilihAwal >= 0) {
        halLayananAktif = Math.floor(indeksTerpilihAwal / ITEM_PER_HAL) + 1;
    }

    sinkronStepperVisual();
    jadwalCtrl.sinkronChipTanggal();
    jadwalCtrl.sinkronUIWaktu();
    renderLayananBerhalaman();
    renderProdukBerhalaman();
    jadwalCtrl.muatPetugasTersedia();
    updateRingkasan();
});
