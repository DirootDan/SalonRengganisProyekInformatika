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

    // Elemen Paginasi Layanan
    const paginasiLayananEl  = document.getElementById('paginasiLayanan');
    const infoHalLayananEl   = document.getElementById('infoHalamanLayanan');
    const teksHalLayananEl   = document.getElementById('teksHalLayanan');
    const btnPrevLayanan     = document.getElementById('btnPrevLayanan');
    const btnNextLayanan     = document.getElementById('btnNextLayanan');

    // Elemen Produk Add-On (Search + Filter + Paginasi)
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

    // Elemen Promo & Ringkasan Kanan
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

    // Elemen Stepper Wizard, Chip Layanan Terpilih, Menit Kustom, & Bar Mobile
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

    const ITEM_LAYANAN_PER_HAL = 6;
    const ITEM_PRODUK_PER_HAL  = 6;

    let filterJabatanAktif = 'semua';
    let halLayananAktif    = 1;
    let filterProdukAktif  = 'semua';
    let halProdukAktif     = 1;
    let stepAktif          = 1;

    // =====================================================
    // 0. NAVIGASI STEPPER WIZARD 3 LANGKAH
    // =====================================================
    const sinkronStepperVisual = () => {
        stepPanelList.forEach(panel => {
            const no = parseInt(panel.dataset.stepPanel || '1', 10);
            panel.classList.toggle('aktif', no === stepAktif);
        });

        stepperItemList.forEach(item => {
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
            const targetEl = document.getElementById(`panelStep${stepAktif}`);
            targetEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    stepperItemList.forEach(item => {
        item.addEventListener('click', () => {
            bukaStep(parseInt(item.dataset.stepTarget || '1', 10), false);
        });
    });

    document.querySelectorAll('[data-lanjut-step]').forEach(btn => {
        btn.addEventListener('click', () => {
            bukaStep(parseInt(btn.dataset.lanjutStep || '1', 10), true);
        });
    });

    btnLanjutStepRingkasan?.addEventListener('click', () => {
        if (stepAktif < 3) {
            bukaStep(stepAktif + 1, true);
        }
    });

    btnLihatSemuaPromo?.addEventListener('click', () => {
        const sedangTerbuka = btnLihatSemuaPromo.dataset.terbuka === '1';
        const statusBaru    = !sedangTerbuka;
        btnLihatSemuaPromo.dataset.terbuka = statusBaru ? '1' : '0';
        btnLihatSemuaPromo.textContent = statusBaru
            ? 'Sembunyikan'
            : `Lihat semua (${kartuPromoList.length})`;
        promoEkstraList.forEach(el => {
            if (statusBaru) {
                el.removeAttribute('hidden');
            } else {
                el.setAttribute('hidden', '');
            }
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

    const katalogPromo = new Map();
    kartuPromoList.forEach(kartu => {
        const kode = (kartu.dataset.kode || '').toUpperCase();
        if (kode) {
            katalogPromo.set(kode, {
                kode,
                nama: kartu.dataset.nama || kode,
                tipe: kartu.dataset.tipe || 'Nominal',
                nilai: parseFloat(kartu.dataset.nilai || '0'),
                min: parseFloat(kartu.dataset.min || '0'),
            });
        }
    });

    const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Math.max(0, Math.round(angka)));

    const tambahMenitKeJam = (jamStr, menitTambah) => {
        const bagian = (jamStr || '10:00').split(':').map(Number);
        const jam    = isNaN(bagian[0]) ? 10 : bagian[0];
        const mnt    = isNaN(bagian[1]) ? 0 : bagian[1];
        const totalMenit = (jam * 60) + mnt + menitTambah;
        const jamHasil   = Math.floor(totalMenit / 60);
        const mntHasil   = totalMenit % 60;
        return `${String(jamHasil).padStart(2, '0')}:${String(mntHasil).padStart(2, '0')}`;
    };

    // =====================================================
    // 1. FILTER & PAGINASI LAYANAN (TANPA SCROLL PANJANG)
    // =====================================================
    const renderLayananBerhalaman = () => {
        const keyword = (searchInput?.value || '').toLowerCase().trim();

        const cocokList = blokLayanan.filter(blok => {
            const namaLayanan = (blok.dataset.nama || '').toLowerCase();
            const jabatan     = blok.dataset.jabatan || '';
            const cb          = blok.querySelector('input[name="layanan[]"]');
            const cocokTab    = filterJabatanAktif === 'semua'
                || (filterJabatanAktif === 'terpilih' && Boolean(cb?.checked))
                || jabatan === filterJabatanAktif;
            const cocokKata   = namaLayanan.includes(keyword);
            return cocokTab && cocokKata;
        });

        const totalItem = cocokList.length;
        const totalHal  = Math.max(1, Math.ceil(totalItem / ITEM_LAYANAN_PER_HAL));
        if (halLayananAktif > totalHal) halLayananAktif = totalHal;
        if (halLayananAktif < 1) halLayananAktif = 1;

        const indeksMulai = (halLayananAktif - 1) * ITEM_LAYANAN_PER_HAL;
        const indeksAkhir = indeksMulai + ITEM_LAYANAN_PER_HAL;
        const tampilSet   = new Set(cocokList.slice(indeksMulai, indeksAkhir));

        blokLayanan.forEach(blok => {
            blok.style.display = tampilSet.has(blok) ? '' : 'none';
        });

        tidakDitemukan?.classList.toggle('tampil', totalItem === 0);

        if (paginasiLayananEl) {
            paginasiLayananEl.style.display = totalItem > ITEM_LAYANAN_PER_HAL ? 'flex' : 'none';
        }
        if (infoHalLayananEl) {
            const awal  = totalItem === 0 ? 0 : indeksMulai + 1;
            const akhir = Math.min(totalItem, indeksAkhir);
            infoHalLayananEl.textContent = `Menampilkan ${awal}–${akhir} dari ${totalItem} perawatan`;
        }
        if (teksHalLayananEl) {
            teksHalLayananEl.textContent = `Hal ${halLayananAktif} / ${totalHal}`;
        }
        if (btnPrevLayanan) btnPrevLayanan.disabled = halLayananAktif <= 1;
        if (btnNextLayanan) btnNextLayanan.disabled = halLayananAktif >= totalHal;
    };

    tabKategoriList.forEach(tab => {
        tab.addEventListener('click', () => {
            tabKategoriList.forEach(t => t.classList.remove('aktif'));
            tab.classList.add('aktif');
            filterJabatanAktif = tab.dataset.filterJabatan || 'semua';
            halLayananAktif    = 1;
            renderLayananBerhalaman();
        });
    });

    searchInput?.addEventListener('input', () => {
        halLayananAktif = 1;
        renderLayananBerhalaman();
    });

    btnPrevLayanan?.addEventListener('click', () => {
        halLayananAktif--;
        renderLayananBerhalaman();
    });

    btnNextLayanan?.addEventListener('click', () => {
        halLayananAktif++;
        renderLayananBerhalaman();
    });

    // =====================================================
    // 2. FILTER & PAGINASI PRODUK ADD-ON (SKALABEL 100+ PRODUK)
    // =====================================================
    const renderProdukBerhalaman = () => {
        const keyword = (searchProdukInput?.value || '').toLowerCase().trim();

        let jmlProdukTerpilih = 0;
        kartuProdukList.forEach(kartu => {
            const inp = kartu.querySelector('.input-qty-addon');
            const qty = parseInt(inp?.value || '0', 10);
            if (qty > 0) jmlProdukTerpilih++;
        });

        if (btnFilterTerpilih) {
            btnFilterTerpilih.textContent = `Sudah Dipilih (${jmlProdukTerpilih})`;
        }

        const cocokProduk = kartuProdukList.filter(kartu => {
            const nama = (kartu.dataset.namaProduk || '').toLowerCase();
            const inp  = kartu.querySelector('.input-qty-addon');
            const qty  = parseInt(inp?.value || '0', 10);

            const cocokFilter = filterProdukAktif === 'semua' || qty > 0;
            const cocokKata   = nama.includes(keyword);
            return cocokFilter && cocokKata;
        });

        const totalProduk = cocokProduk.length;
        const totalHal    = Math.max(1, Math.ceil(totalProduk / ITEM_PRODUK_PER_HAL));
        if (halProdukAktif > totalHal) halProdukAktif = totalHal;
        if (halProdukAktif < 1) halProdukAktif = 1;

        const indeksMulai = (halProdukAktif - 1) * ITEM_PRODUK_PER_HAL;
        const indeksAkhir = indeksMulai + ITEM_PRODUK_PER_HAL;
        const tampilSet   = new Set(cocokProduk.slice(indeksMulai, indeksAkhir));

        kartuProdukList.forEach(kartu => {
            kartu.style.display = tampilSet.has(kartu) ? '' : 'none';
        });

        produkKosongEl?.classList.toggle('tampil', totalProduk === 0);

        if (paginasiProdukEl) {
            paginasiProdukEl.style.display = totalProduk > ITEM_PRODUK_PER_HAL ? 'flex' : 'none';
        }
        if (infoHalProdukEl) {
            const awal  = totalProduk === 0 ? 0 : indeksMulai + 1;
            const akhir = Math.min(totalProduk, indeksAkhir);
            infoHalProdukEl.textContent = `Menampilkan ${awal}–${akhir} dari ${totalProduk} produk`;
        }
        if (teksHalProdukEl) {
            teksHalProdukEl.textContent = `Hal ${halProdukAktif} / ${totalHal}`;
        }
        if (btnPrevProduk) btnPrevProduk.disabled = halProdukAktif <= 1;
        if (btnNextProduk) btnNextProduk.disabled = halProdukAktif >= totalHal;
    };

    searchProdukInput?.addEventListener('input', () => {
        halProdukAktif = 1;
        renderProdukBerhalaman();
    });

    chipFilterProduk.forEach(chip => {
        chip.addEventListener('click', () => {
            chipFilterProduk.forEach(c => c.classList.remove('aktif'));
            chip.classList.add('aktif');
            filterProdukAktif = chip.dataset.filterProduk || 'semua';
            halProdukAktif    = 1;
            renderProdukBerhalaman();
        });
    });

    btnPrevProduk?.addEventListener('click', () => {
        halProdukAktif--;
        renderProdukBerhalaman();
    });

    btnNextProduk?.addEventListener('click', () => {
        halProdukAktif++;
        renderProdukBerhalaman();
    });

    // =====================================================
    // 3. SINKRONISASI TANGGAL & WAKTU DINAMIS (MENIT SPESIFIK)
    // =====================================================
    const sinkronChipTanggal = () => {
        const tglAktif = inputTanggal?.value || '';
        chipTanggalList.forEach(chip => {
            chip.classList.toggle('aktif', chip.dataset.tgl === tglAktif);
        });
    };

    const sinkronUIWaktu = () => {
        const jamNilai = (inputJam?.value || '10:00').slice(0, 5);
        const mntBagian = jamNilai.split(':')[1] || '00';

        chipJamList.forEach(chip => {
            chip.classList.toggle('aktif', chip.dataset.jam === jamNilai);
        });

        btnMenitCepatList.forEach(btn => {
            btn.classList.toggle('aktif', btn.dataset.menit === mntBagian);
        });
    };

    chipTanggalList.forEach(chip => {
        chip.addEventListener('click', () => {
            if (!inputTanggal) return;
            inputTanggal.value = chip.dataset.tgl || '';
            sinkronChipTanggal();
            muatPetugasTersedia();
            updateRingkasan();
        });
    });

    chipJamList.forEach(chip => {
        chip.addEventListener('click', () => {
            if (chip.classList.contains('penuh') || !inputJam) return;
            inputJam.value = chip.dataset.jam || '10:00';
            sinkronUIWaktu();
            muatPetugasTersedia();
            updateRingkasan();
        });
    });

    btnMenitCepatList.forEach(btn => {
        btn.addEventListener('click', () => {
            if (!inputJam) return;
            const jamSekarang = (inputJam.value || '10:00').split(':')[0] || '10';
            const menitBaru   = btn.dataset.menit || '00';
            let gabungan      = `${jamSekarang.padStart(2, '0')}:${menitBaru}`;
            if (gabungan > '20:30') gabungan = '20:30';
            if (gabungan < '09:00') gabungan = '09:00';

            inputJam.value = gabungan;
            sinkronUIWaktu();
            muatPetugasTersedia();
            updateRingkasan();
        });
    });

    // =====================================================
    // 4. PILIHAN PETUGAS, PROMO, DAN RINGKASAN LIVE
    // =====================================================
    const aturPilihanPetugas = (checkbox) => {
        const blok      = checkbox.closest('.opsi-blok');
        const select    = blok?.querySelector('.select-petugas');
        const indikator = blok?.querySelector('.indikator-pilih');

        blok?.classList.toggle('terpilih', checkbox.checked);
        if (indikator) {
            indikator.textContent = checkbox.checked ? '✓ Terpilih' : '+ Pilih';
        }

        if (select) {
            select.disabled = !checkbox.checked;
            if (!checkbox.checked) select.value = '';
        }
    };

    const evaluasiPromoClient = (subtotal) => {
        const kode = (inputKodePromo?.value || '').toUpperCase().trim();
        kartuPromoList.forEach(k => k.classList.toggle('terpilih', k.dataset.kode === kode));

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

    const updateRingkasan = () => {
        let subtotalLayanan = 0;
        let jumlahLayanan   = 0;
        let totalDurasi     = 0;
        let petugasKosong   = 0;
        const barisLiveHtml = [];
        const chipLayananHtml = [];

        checkboxes.forEach(checkbox => {
            if (!checkbox.checked) return;

            const idLayanan = checkbox.value;
            const harga     = parseFloat(checkbox.dataset.harga || '0');
            const durasi    = parseInt(checkbox.dataset.durasi || '60', 10);
            const nama      = checkbox.dataset.namaLayanan || 'Layanan';
            const jabatan   = checkbox.dataset.jabatan || 'Stylist';

            subtotalLayanan += harga;
            totalDurasi     += durasi;
            jumlahLayanan++;

            const select = checkbox.closest('.opsi-blok')?.querySelector('.select-petugas');
            if (select && parseInt(select.dataset.tersedia || '1', 10) <= 0) {
                petugasKosong++;
            }

            const teksPetugas = select && select.value
                ? select.selectedOptions[0]?.textContent?.trim()
                : `${jabatan} Otomatis`;

            chipLayananHtml.push(`
                <span class="chip-pilihan-item">
                    <span>${nama} (${durasi} mnt)</span>
                    <button type="button" class="btn-hapus-chip-layanan" data-hapus-layanan="${idLayanan}" aria-label="Batal pilih ${nama}">&times;</button>
                </span>
            `);

            barisLiveHtml.push(`
                <div class="item-live-baris">
                    <div class="item-live-kiri">
                        <span class="item-live-nama">${nama}</span>
                        <span class="item-live-sub">${jabatan}: ${teksPetugas} &bull; ${durasi} mnt</span>
                    </div>
                    <div class="item-live-kanan">
                        <span class="item-live-harga">${formatRupiah(harga)}</span>
                        <button type="button" class="btn-hapus-item-live" data-hapus-layanan="${idLayanan}" title="Hapus layanan">&times;</button>
                    </div>
                </div>
            `);
        });

        if (badgeTabLayananEl) {
            badgeTabLayananEl.textContent = String(jumlahLayanan);
        }

        if (barChipTerpilihEl && daftarChipTerpilihEl) {
            if (jumlahLayanan > 0) {
                barChipTerpilihEl.removeAttribute('hidden');
                if (judulChipTerpilihEl) {
                    judulChipTerpilihEl.textContent = `${jumlahLayanan} Perawatan Terpilih (${totalDurasi} menit):`;
                }
                daftarChipTerpilihEl.innerHTML = chipLayananHtml.join('');
            } else {
                barChipTerpilihEl.setAttribute('hidden', '');
                daftarChipTerpilihEl.innerHTML = '';
            }
        }

        let subtotalProduk  = 0;
        let totalItemProduk = 0;

        inputProdukAddons.forEach(inp => {
            const stok = parseInt(inp.dataset.stok || '0', 10);
            let qty    = parseInt(inp.value || '0', 10);
            if (isNaN(qty) || qty < 0) qty = 0;
            if (qty > stok) qty = stok;
            inp.value = qty;

            const kartu    = inp.closest('.kartu-addon');
            const idProduk = kartu?.dataset.idProduk || '';
            kartu?.classList.toggle('aktif', qty > 0);

            if (qty > 0) {
                const hargaSatuan = parseFloat(inp.dataset.harga || '0');
                const subProd     = qty * hargaSatuan;
                subtotalProduk   += subProd;
                totalItemProduk  += qty;

                barisLiveHtml.push(`
                    <div class="item-live-baris">
                        <div class="item-live-kiri">
                            <span class="item-live-nama">+ ${inp.dataset.nama || 'Produk'} (${qty}x)</span>
                            <span class="item-live-sub">Produk Perawatan Tambahan</span>
                        </div>
                        <div class="item-live-kanan">
                            <span class="item-live-harga">${formatRupiah(subProd)}</span>
                            <button type="button" class="btn-hapus-item-live" data-hapus-produk="${idProduk}" title="Hapus produk">&times;</button>
                        </div>
                    </div>
                `);
            }
        });

        renderLayananBerhalaman();
        renderProdukBerhalaman();

        // Tampilkan Empty State saat belum ada layanan dipilih, sembunyikan rincian Rp 0
        if (ringkasanKosongStateEl && kontenRingkasanAktifEl) {
            if (jumlahLayanan > 0) {
                ringkasanKosongStateEl.setAttribute('hidden', '');
                kontenRingkasanAktifEl.removeAttribute('hidden');
            } else {
                ringkasanKosongStateEl.removeAttribute('hidden');
                kontenRingkasanAktifEl.setAttribute('hidden', '');
            }
        }

        if (daftarPilihanEl) {
            daftarPilihanEl.innerHTML = barisLiveHtml.join('');
        }

        const subtotal = subtotalLayanan + subtotalProduk;
        const { potongan, kode } = evaluasiPromoClient(subtotal);
        const totalAkhir = Math.max(0, subtotal - potongan);

        if (jumlahPilihanEl) {
            jumlahPilihanEl.textContent = `Subtotal Layanan (${jumlahLayanan} layanan • ${totalDurasi} mnt)`;
        }
        if (ringkasanLayananEl) ringkasanLayananEl.textContent = formatRupiah(subtotalLayanan);

        if (barisRingkasanProdukEl) {
            if (totalItemProduk > 0) {
                barisRingkasanProdukEl.removeAttribute('hidden');
            } else {
                barisRingkasanProdukEl.setAttribute('hidden', '');
            }
        }
        if (jumlahProdukEl) {
            jumlahProdukEl.textContent = `Produk Add-On (${totalItemProduk} item)`;
        }
        if (ringkasanProdukEl) ringkasanProdukEl.textContent = formatRupiah(subtotalProduk);

        if (barisRingkasanPromoEl) {
            if (potongan > 0) {
                barisRingkasanPromoEl.removeAttribute('hidden');
            } else {
                barisRingkasanPromoEl.setAttribute('hidden', '');
            }
        }
        if (labelPromoEl) {
            labelPromoEl.textContent = kode ? `Potongan Promo (${kode})` : 'Potongan Promo';
        }
        if (ringkasanPromoEl) {
            ringkasanPromoEl.textContent = `- ${formatRupiah(potongan)}`;
        }

        if (totalHargaEl) totalHargaEl.textContent = formatRupiah(totalAkhir);

        const tglAktif      = inputTanggal?.value || '';
        const jamMulai      = (inputJam?.value || '10:00').slice(0, 5);
        const jamSelesai    = tambahMenitKeJam(jamMulai, totalDurasi || 60);
        const [jamM, mntM]  = jamMulai.split(':').map(n => parseInt(n || '0', 10));
        const totalMenitMulai = (jamM * 60) + mntM;
        const diluarJamBuka = jamMulai < '09:00' || jamMulai > '20:30';
        const lewatJamTutup = totalDurasi > 0 && (totalMenitMulai + totalDurasi) > (21 * 60);

        if (teksJamBesarEl) {
            teksJamBesarEl.textContent = `${jamMulai} WIB`;
        }
        if (teksEstimasiStep2El) {
            teksEstimasiStep2El.textContent = jumlahLayanan > 0
                ? `Estimasi selesai sekitar ${jamSelesai} WIB (${totalDurasi} mnt)`
                : 'Pilih slot jam di bawah';
        }

        if (subStep1El) {
            subStep1El.textContent = jumlahLayanan > 0
                ? `${jumlahLayanan} perawatan (${totalDurasi} mnt)`
                : 'Pilih layanan & petugas';
        }
        if (subStep2El) {
            subStep2El.textContent = `${tglAktif} • ${jamMulai} WIB`;
        }
        if (subStep3El) {
            subStep3El.textContent = totalItemProduk > 0
                ? `${totalItemProduk} item add-on dipilih`
                : 'Opsional & konfirmasi';
        }

        if (metaMobileBarEl) {
            metaMobileBarEl.textContent = jumlahLayanan > 0
                ? `${jumlahLayanan} layanan (${totalDurasi} mnt) • ${jamMulai} WIB`
                : 'Belum ada layanan dipilih';
        }
        if (totalMobileBarEl) {
            totalMobileBarEl.textContent = formatRupiah(totalAkhir);
        }

        if (lencanaJadwalEl) {
            lencanaJadwalEl.textContent = jumlahLayanan > 0
                ? `${tglAktif} • ${jamMulai} - ${jamSelesai} WIB`
                : `${tglAktif} • ${jamMulai} WIB`;
        }

        if (estimasiJadwalEl) {
            estimasiJadwalEl.textContent = jumlahLayanan > 0
                ? `Estimasi selesai pukul ${jamSelesai} WIB`
                : '';
        }

        const siap = jumlahLayanan > 0 && petugasKosong === 0 && !diluarJamBuka && !lewatJamTutup;

        if (infoValidasiEl) {
            if (diluarJamBuka) {
                infoValidasiEl.textContent = 'Jam kedatangan harus berada dalam jam operasional salon (09:00 s/d 20:30 WIB).';
            } else if (jumlahLayanan === 0) {
                infoValidasiEl.textContent = 'Pilih minimal 1 perawatan di sebelah kiri untuk melanjutkan.';
            } else if (lewatJamTutup) {
                infoValidasiEl.textContent = `Durasi ${totalDurasi} menit (selesai ${jamSelesai} WIB) melewati jam tutup 21:00 WIB. Pilih jam lebih awal.`;
            } else if (petugasKosong > 0) {
                infoValidasiEl.textContent = `Petugas untuk layanan terpilih penuh pada pukul ${jamMulai} WIB. Silakan pilih jam/menit lain.`;
            } else {
                infoValidasiEl.textContent = '';
            }
        }

        // Satu CTA Utama Kontekstual Sesuai Langkah (Tanpa Tombol Ganda Bersaing)
        if (btnLanjutStepRingkasan && submitBtn) {
            if (stepAktif === 1) {
                btnLanjutStepRingkasan.removeAttribute('hidden');
                btnLanjutStepRingkasan.disabled = jumlahLayanan === 0;
                btnLanjutStepRingkasan.textContent = 'Lanjut Pilih Tanggal & Jam →';
                submitBtn.setAttribute('hidden', '');
            } else if (stepAktif === 2) {
                btnLanjutStepRingkasan.removeAttribute('hidden');
                btnLanjutStepRingkasan.disabled = !siap;
                btnLanjutStepRingkasan.textContent = 'Lanjut ke Add-On & Konfirmasi →';
                submitBtn.setAttribute('hidden', '');
            } else {
                btnLanjutStepRingkasan.setAttribute('hidden', '');
                submitBtn.removeAttribute('hidden');
                submitBtn.disabled = !siap;
            }
        } else if (submitBtn) {
            submitBtn.disabled = !siap;
        }
    };

    // =====================================================
    // 5. MUAT KETERSEDIAAN PETUGAS PADA JAM & MENIT SPESIFIK
    // =====================================================
    const muatPetugasTersedia = async () => {
        const urlDasar = formBooking.dataset.urlPetugas;
        const tgl      = inputTanggal?.value;
        const jam      = (inputJam?.value || '').slice(0, 5);

        if (!urlDasar || !tgl || !/^\d{2}:\d{2}$/.test(jam)) return;

        const parameter = new URLSearchParams({ tgl_booking: tgl, jam_booking: jam });
        checkboxes.forEach(cb => {
            if (cb.checked) parameter.append('layanan[]', cb.value);
        });

        if (statusTersediaEl) {
            statusTersediaEl.textContent = `Memeriksa slot pukul ${jam} WIB...`;
        }

        try {
            const res = await fetch(`${urlDasar}?${parameter.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;

            const data              = await res.json();
            const grupPetugas       = data.petugas || {};
            const petugasPerLayanan = data.petugas_per_layanan || {};
            const statusJam         = data.status_jam || {};
            const lewatTutupServer  = Boolean(data.melewati_jam_tutup);

            chipJamList.forEach(chip => {
                const jamOpt   = chip.dataset.jam || '';
                const tersedia = statusJam[jamOpt] !== false;
                const statusEl = chip.querySelector('.chip-jam-status');

                chip.disabled = !tersedia;
                chip.setAttribute('aria-disabled', tersedia ? 'false' : 'true');
                chip.classList.toggle('penuh', !tersedia);
                chip.classList.toggle('aktif', jamOpt === jam);
                if (statusEl) {
                    statusEl.textContent = tersedia ? 'Tersedia' : 'Penuh / Tutup';
                }
            });

            document.querySelectorAll('.select-petugas').forEach(select => {
                const jabatan      = select.dataset.jabatan || 'Stylist';
                const idLayanan    = select.closest('.opsi-blok')?.dataset.idLayanan || '';
                const daftar       = (idLayanan && Array.isArray(petugasPerLayanan[idLayanan]))
                    ? petugasPerLayanan[idLayanan]
                    : (grupPetugas[jabatan] || []);
                const nilaiSebelum = select.value || select.dataset.pilihanLama || '';

                select.dataset.tersedia = String(daftar.length);
                select.innerHTML = '';

                if (daftar.length === 0) {
                    const optKosong = document.createElement('option');
                    optKosong.value = '';
                    optKosong.textContent = lewatTutupServer
                        ? `-- Jadwal melewati jam tutup salon (21:00 WIB) --`
                        : `-- Semua ${jabatan} penuh pada pukul ${jam} --`;
                    select.appendChild(optKosong);
                } else {
                    const optOtomatis = document.createElement('option');
                    optOtomatis.value = '';
                    optOtomatis.textContent = `-- Pilihkan ${jabatan} Otomatis (${daftar.length} tersedia) --`;
                    select.appendChild(optOtomatis);

                    daftar.forEach(staf => {
                        const opt = document.createElement('option');
                        opt.value = staf.id_karyawan;
                        opt.textContent = staf.nama_staff;
                        if (String(staf.id_karyawan) === String(nilaiSebelum)) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    });
                }
            });

            if (statusTersediaEl) {
                if (lewatTutupServer && data.pesan_jam_tutup) {
                    statusTersediaEl.textContent = data.pesan_jam_tutup;
                } else {
                    const jmlStylist   = (grupPetugas.Stylist || []).length;
                    const jmlTherapist = (grupPetugas.Therapist || []).length;
                    statusTersediaEl.textContent = `Pukul ${jam} WIB: ${jmlStylist} Stylist & ${jmlTherapist} Therapist tersedia`;
                }
            }

            updateRingkasan();
        } catch {
            if (statusTersediaEl) {
                statusTersediaEl.textContent = 'Jadwal siap dipilih';
            }
        }
    };

    inputTanggal?.addEventListener('change', () => {
        sinkronChipTanggal();
        muatPetugasTersedia();
    });

    inputJam?.addEventListener('change', () => {
        sinkronUIWaktu();
        muatPetugasTersedia();
        updateRingkasan();
    });

    inputJam?.addEventListener('input', () => {
        sinkronUIWaktu();
        updateRingkasan();
    });

    checkboxes.forEach(checkbox => {
        aturPilihanPetugas(checkbox);
        checkbox.addEventListener('change', () => {
            aturPilihanPetugas(checkbox);
            muatPetugasTersedia();
            updateRingkasan();
        });
    });

    document.querySelectorAll('.select-petugas').forEach(select => {
        select.addEventListener('change', () => {
            select.closest('.opsi-blok')?.classList.remove('galat');
            updateRingkasan();
        });
    });

    document.querySelectorAll('.btn-qty-addon').forEach(btn => {
        btn.addEventListener('click', () => {
            const kartu = btn.closest('.kartu-addon');
            const inp   = kartu?.querySelector('.input-qty-addon');
            if (!inp) return;

            const stok = parseInt(inp.dataset.stok || '0', 10);
            let nilai  = parseInt(inp.value || '0', 10);
            if (btn.dataset.aksi === 'tambah' && nilai < stok) nilai++;
            if (btn.dataset.aksi === 'kurang' && nilai > 0) nilai--;
            inp.value = nilai;
            updateRingkasan();
        });
    });

    inputProdukAddons.forEach(inp => {
        inp.addEventListener('input', updateRingkasan);
    });

    document.querySelectorAll('.btn-pilih-promo').forEach(btn => {
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
        checkboxes.forEach(cb => {
            cb.checked = false;
            aturPilihanPetugas(cb);
        });
        muatPetugasTersedia();
        updateRingkasan();
    });

    formBooking.addEventListener('click', (e) => {
        const btnHapusLayanan = e.target.closest('[data-hapus-layanan]');
        if (btnHapusLayanan) {
            const idTarget = btnHapusLayanan.dataset.hapusLayanan;
            checkboxes.forEach(cb => {
                if (String(cb.value) === String(idTarget)) {
                    cb.checked = false;
                    aturPilihanPetugas(cb);
                }
            });
            muatPetugasTersedia();
            updateRingkasan();
            return;
        }

        const btnHapusProduk = e.target.closest('[data-hapus-produk]');
        if (btnHapusProduk) {
            const idProd = btnHapusProduk.dataset.hapusProduk;
            const kartu  = document.querySelector(`.kartu-addon[data-id-produk="${idProd}"]`);
            const inp    = kartu?.querySelector('.input-qty-addon');
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

    const indeksTerpilihAwal = blokLayanan.findIndex(b => b.classList.contains('terpilih'));
    if (indeksTerpilihAwal >= 0) {
        halLayananAktif = Math.floor(indeksTerpilihAwal / ITEM_LAYANAN_PER_HAL) + 1;
    }

    sinkronStepperVisual();
    sinkronChipTanggal();
    sinkronUIWaktu();
    renderLayananBerhalaman();
    renderProdukBerhalaman();
    muatPetugasTersedia();
    updateRingkasan();
});
