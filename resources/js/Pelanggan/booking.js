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

    if (!formBooking) return;

    const ITEM_LAYANAN_PER_HAL = 6;
    const ITEM_PRODUK_PER_HAL  = 6;

    let filterJabatanAktif = 'semua';
    let halLayananAktif    = 1;
    let filterProdukAktif  = 'semua';
    let halProdukAktif     = 1;

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
            const cocokTab    = filterJabatanAktif === 'semua' || jabatan === filterJabatanAktif;
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

        checkboxes.forEach(checkbox => {
            if (!checkbox.checked) return;

            const harga   = parseFloat(checkbox.dataset.harga || '0');
            const durasi  = parseInt(checkbox.dataset.durasi || '60', 10);
            const nama    = checkbox.dataset.namaLayanan || 'Layanan';
            const jabatan = checkbox.dataset.jabatan || 'Stylist';

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

            barisLiveHtml.push(`
                <div class="item-live-baris">
                    <div class="item-live-kiri">
                        <span class="item-live-nama">${nama}</span>
                        <span class="item-live-sub">${jabatan}: ${teksPetugas} &bull; ${durasi} mnt</span>
                    </div>
                    <span class="item-live-harga">${formatRupiah(harga)}</span>
                </div>
            `);
        });

        let subtotalProduk  = 0;
        let totalItemProduk = 0;

        inputProdukAddons.forEach(inp => {
            const stok = parseInt(inp.dataset.stok || '0', 10);
            let qty    = parseInt(inp.value || '0', 10);
            if (isNaN(qty) || qty < 0) qty = 0;
            if (qty > stok) qty = stok;
            inp.value = qty;

            const kartu = inp.closest('.kartu-addon');
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
                        <span class="item-live-harga">${formatRupiah(subProd)}</span>
                    </div>
                `);
            }
        });

        renderProdukBerhalaman();

        if (daftarPilihanEl) {
            daftarPilihanEl.innerHTML = barisLiveHtml.length > 0
                ? barisLiveHtml.join('')
                : '<div class="pilihan-kosong-live">Belum ada layanan yang dipilih. Silakan klik paket perawatan <strong>Stylist</strong> atau <strong>Therapist</strong> di sebelah kiri.</div>';
        }

        const subtotal = subtotalLayanan + subtotalProduk;
        const { potongan, kode } = evaluasiPromoClient(subtotal);
        const totalAkhir = Math.max(0, subtotal - potongan);

        if (jumlahPilihanEl) {
            jumlahPilihanEl.textContent = `Subtotal Layanan (${jumlahLayanan} layanan • ${totalDurasi} mnt)`;
        }
        if (ringkasanLayananEl) ringkasanLayananEl.textContent = formatRupiah(subtotalLayanan);

        if (jumlahProdukEl) {
            jumlahProdukEl.textContent = `Produk Add-On (${totalItemProduk} item)`;
        }
        if (ringkasanProdukEl) ringkasanProdukEl.textContent = formatRupiah(subtotalProduk);

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
        const diluarJamBuka = jamMulai < '09:00' || jamMulai > '20:30';
        const lewatJamTutup = totalDurasi > 0 && jamSelesai > '21:00';

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
                infoValidasiEl.textContent = 'Pilih minimal 1 paket layanan di sebelah kiri untuk melanjutkan.';
            } else if (lewatJamTutup) {
                infoValidasiEl.textContent = `Durasi ${totalDurasi} menit (selesai ${jamSelesai} WIB) melewati jam tutup 21:00 WIB. Pilih jam lebih awal.`;
            } else if (petugasKosong > 0) {
                infoValidasiEl.textContent = `Petugas untuk layanan terpilih penuh pada pukul ${jamMulai} WIB. Silakan pilih jam/menit lain.`;
            } else {
                infoValidasiEl.textContent = '';
            }
        }

        if (submitBtn) {
            submitBtn.disabled = !siap;
            submitBtn.style.opacity = siap ? '1' : '0.55';
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

            const data        = await res.json();
            const grupPetugas = data.petugas || {};
            const statusJam   = data.status_jam || {};

            chipJamList.forEach(chip => {
                const jamOpt   = chip.dataset.jam || '';
                const tersedia = statusJam[jamOpt] !== false;
                const statusEl = chip.querySelector('.chip-jam-status');

                chip.classList.toggle('penuh', !tersedia);
                chip.classList.toggle('aktif', jamOpt === jam);
                if (statusEl) {
                    statusEl.textContent = tersedia ? 'Tersedia' : 'Penuh';
                }
            });

            document.querySelectorAll('.select-petugas').forEach(select => {
                const jabatan      = select.dataset.jabatan || 'Stylist';
                const daftar       = grupPetugas[jabatan] || [];
                const nilaiSebelum = select.value || select.dataset.pilihanLama || '';

                select.dataset.tersedia = String(daftar.length);
                select.innerHTML = '';

                if (daftar.length === 0) {
                    const optKosong = document.createElement('option');
                    optKosong.value = '';
                    optKosong.textContent = `-- Semua ${jabatan} penuh pada pukul ${jam} --`;
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
                const jmlStylist   = (grupPetugas.Stylist || []).length;
                const jmlTherapist = (grupPetugas.Therapist || []).length;
                statusTersediaEl.textContent = `Pukul ${jam} WIB: ${jmlStylist} Stylist & ${jmlTherapist} Therapist tersedia`;
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

    formBooking.addEventListener('submit', () => {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses Reservasi Anda...';
        }
    });

    sinkronChipTanggal();
    sinkronUIWaktu();
    renderLayananBerhalaman();
    renderProdukBerhalaman();
    muatPetugasTersedia();
    updateRingkasan();
});
