document.addEventListener('DOMContentLoaded', () => {
    const keranjang = new Map();

    const daftarKeranjangEl = document.getElementById('daftarKeranjang');
    const hiddenContainer   = document.getElementById('inputHiddenKeranjang');
    const inputIdReservasi  = document.getElementById('posIdReservasi');
    const selectPromo       = document.getElementById('posKodePromo');
    const selectMetodeBayar = document.getElementById('posMetodeBayar');
    const selectPetugasPos  = document.getElementById('posPetugas');
    const peringatanPetugas = document.getElementById('peringatanPetugasPos');
    const teksTotalEl       = document.getElementById('teksTotalTagihan');
    const teksKembalianEl   = document.getElementById('teksKembalian');
    const inputUangEl       = document.getElementById('posUangDiterima');
    const btnPecahanEl      = document.getElementById('btnPecahanTerdekat');
    const inputPelangganEl  = document.getElementById('posNamaPelanggan');
    const cariInput         = document.getElementById('cariItemPos');
    const itemKatalogList   = document.querySelectorAll('.item-katalog');
    const tabBtns           = document.querySelectorAll('.pos-tab');
    const formTransaksi     = document.getElementById('formTransaksiKasir');
    const btnProsesBayar    = document.getElementById('btnProsesBayar');
    const labelModeEl       = document.getElementById('labelModeTransaksi');
    const modalAntreanPos   = document.getElementById('modalPilihAntreanPos');
    const btnBukaAntrean    = document.getElementById('btnBukaModalAntrean');
    const btnPilihKecil     = document.getElementById('btnPilihAntreanKecil');
    const btnTutupAntrean   = document.getElementById('btnTutupModalAntrean');
    const posGridEl         = document.querySelector('.pos-grid');

    const bukaModalAntrean = () => {
        if (!modalAntreanPos) return;
        modalAntreanPos.classList.add('tampil');
        modalAntreanPos.setAttribute('aria-hidden', 'false');
    };

    const tutupModalAntrean = () => {
        if (!modalAntreanPos) return;
        modalAntreanPos.classList.remove('tampil');
        modalAntreanPos.setAttribute('aria-hidden', 'true');
    };

    btnBukaAntrean?.addEventListener('click', bukaModalAntrean);
    btnPilihKecil?.addEventListener('click', bukaModalAntrean);
    btnTutupAntrean?.addEventListener('click', tutupModalAntrean);
    modalAntreanPos?.addEventListener('click', (e) => {
        if (e.target === modalAntreanPos) tutupModalAntrean();
    });

    const daftarPetugasAktif = JSON.parse(formTransaksi?.dataset.petugas || '[]');
    let kategoriAktif = 'semua';

    const formatRp = (angka) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Math.max(0, Math.round(angka)));

    const hitungSubtotal = () => {
        let sub = 0;
        keranjang.forEach(item => {
            sub += item.harga * item.qty;
        });
        return sub;
    };

    const hitungTotalAkhir = () => {
        const sub = hitungSubtotal();
        if (!selectPromo || !selectPromo.value) return sub;

        const opt   = selectPromo.selectedOptions[0];
        const tipe  = opt?.dataset.tipe || '';
        const nilai = parseFloat(opt?.dataset.nilai || '0');
        const min   = parseFloat(opt?.dataset.min || '0');

        if (sub < min) return sub;

        if (tipe === 'Persentase') {
            return Math.max(0, Math.round(sub - (sub * (nilai / 100))));
        }
        return Math.max(0, sub - nilai);
    };

    const apakahMetodeNonTunai = () => {
        if (!selectMetodeBayar) return false;
        const opt = selectMetodeBayar.selectedOptions[0];
        const kat = (opt?.dataset.kategori || opt?.textContent || '').toLowerCase();
        return !kat.includes('tunai');
    };

    const perbaruiTombolPecahan = (total) => {
        if (!btnPecahanEl) return;
        const bulat500 = total > 0 ? Math.ceil(total / 500) * 500 : 0;
        btnPecahanEl.dataset.nominal = String(bulat500);
        btnPecahanEl.textContent = bulat500 > 0 ? formatRp(bulat500) : 'Pecahan Terdekat';
    };

    const hitungKembalian = () => {
        const total = hitungTotalAkhir();
        perbaruiTombolPecahan(total);
        const bayar = parseFloat(inputUangEl?.value || '0');
        const selisih = bayar - total;
        if (teksKembalianEl) {
            teksKembalianEl.textContent = selisih >= 0 ? formatRp(selisih) : 'Uang belum cukup';
        }
    };

    const perbaruiPeringatanPetugas = () => {
        if (!peringatanPetugas) return;

        const daftarBermasalah = [];
        keranjang.forEach(item => {
            if (item.kategori === 'Layanan' && item.petugasNonaktif) {
                daftarBermasalah.push(
                    `Petugas "${item.namaPetugasLama || 'Sebelumnya'}" pada layanan "${item.nama}" sudah dinonaktifkan/dihapus. Silakan pilih ${item.jabatan || 'petugas'} pengganti yang aktif.`
                );
            }
        });

        if (daftarBermasalah.length > 0) {
            peringatanPetugas.innerHTML = daftarBermasalah.map(p => `<div>&bull; ${p}</div>`).join('');
            peringatanPetugas.hidden = false;
        } else {
            peringatanPetugas.innerHTML = '';
            peringatanPetugas.hidden = true;
        }
    };

    const sinkronInputHidden = () => {
        if (!hiddenContainer) return;
        hiddenContainer.innerHTML = '';

        keranjang.forEach((item) => {
            if (item.kategori === 'Layanan') {
                const inpLayanan = document.createElement('input');
                inpLayanan.type  = 'hidden';
                inpLayanan.name  = 'layanan_ids[]';
                inpLayanan.value = item.idAsli;
                hiddenContainer.appendChild(inpLayanan);

                if (item.idPetugas) {
                    const inpPetugas = document.createElement('input');
                    inpPetugas.type  = 'hidden';
                    inpPetugas.name  = `petugas_layanan[${item.idAsli}]`;
                    inpPetugas.value = item.idPetugas;
                    hiddenContainer.appendChild(inpPetugas);
                }
            } else if (item.kategori === 'Produk') {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = `produk_items[${item.idAsli}]`;
                inp.value = item.qty;
                hiddenContainer.appendChild(inp);
            }
        });
    };

    const buatDropdownPetugasLayanan = (item, idKeranjang) => {
        const jabatan = item.jabatan || 'Stylist';
        const kandidat = daftarPetugasAktif.filter(s => s.role_jabatan === jabatan);

        const opsiHtml = kandidat.map(staf => {
            const terpilih = String(staf.id_karyawan) === String(item.idPetugas || '') ? 'selected' : '';
            return `<option value="${staf.id_karyawan}" ${terpilih}>${staf.nama_staff} (${staf.role_jabatan} • Komisi ${staf.persen_komisi}%)</option>`;
        }).join('');

        const kelasGalat = item.petugasNonaktif ? 'select-petugas-pos-galat' : '';
        const labelOpsiKosong = item.petugasNonaktif
            ? `-- Wajib Pilih ${jabatan} Pengganti Aktif --`
            : `-- Pilih ${jabatan} --`;

        return `
            <div class="keranjang-petugas-baris">
                <label class="label-petugas-kecil">Petugas ${jabatan}:</label>
                <select class="select-petugas-pos ${kelasGalat}" data-id-keranjang="${idKeranjang}">
                    <option value="">${labelOpsiKosong}</option>
                    ${opsiHtml}
                </select>
            </div>
        `;
    };

    const renderKeranjang = () => {
        if (!daftarKeranjangEl) return;
        sinkronInputHidden();
        perbaruiPeringatanPetugas();

        if (keranjang.size === 0) {
            daftarKeranjangEl.innerHTML = '<div class="keranjang-kosong">Belum ada layanan atau produk yang dipilih.</div>';
            teksTotalEl.textContent = 'Rp 0';
            hitungKembalian();
            return;
        }

        daftarKeranjangEl.innerHTML = '';
        keranjang.forEach((item, id) => {
            const baris = document.createElement('div');
            baris.className = 'item-keranjang';

            const htmlPetugas = item.kategori === 'Layanan'
                ? buatDropdownPetugasLayanan(item, id)
                : '';

            baris.innerHTML = `
                <div class="keranjang-info">
                    <span class="keranjang-nama">${item.nama}</span>
                    <span class="keranjang-sub">${item.qty} x ${formatRp(item.harga)}</span>
                    ${htmlPetugas}
                </div>
                <div class="keranjang-kontrol">
                    <button type="button" class="btn-qty" data-aksi="kurang" data-id="${id}">-</button>
                    <strong>${item.qty}</strong>
                    <button type="button" class="btn-qty" data-aksi="tambah" data-id="${id}">+</button>
                </div>
            `;
            daftarKeranjangEl.appendChild(baris);
        });

        teksTotalEl.textContent = formatRp(hitungTotalAkhir());
        if (apakahMetodeNonTunai() && inputUangEl) {
            inputUangEl.value = hitungTotalAkhir();
        }
        hitungKembalian();
    };

    itemKatalogList.forEach(btn => {
        btn.addEventListener('click', () => {
            const id       = btn.dataset.id;
            const idAsli   = btn.dataset.idAsli;
            const nama     = btn.dataset.nama;
            const harga    = parseInt(btn.dataset.harga || '0', 10);
            const kategori = btn.dataset.kategori || 'Layanan';
            const jabatan  = btn.dataset.jabatan || 'Stylist';

            if (keranjang.has(id)) {
                if (kategori === 'Produk') {
                    keranjang.get(id).qty += 1;
                }
            } else {
                const defaultPetugas = selectPetugasPos?.value || '';
                keranjang.set(id, {
                    idAsli,
                    nama,
                    harga,
                    qty: 1,
                    kategori,
                    jabatan,
                    idPetugas: kategori === 'Layanan' ? defaultPetugas : null,
                    petugasNonaktif: false,
                });
            }
            renderKeranjang();
        });
    });

    daftarKeranjangEl?.addEventListener('change', (e) => {
        const select = e.target.closest('.select-petugas-pos');
        if (!select) return;

        const idKeranjang = select.dataset.idKeranjang;
        const item = keranjang.get(idKeranjang);
        if (!item) return;

        item.idPetugas = select.value || null;
        if (item.idPetugas) {
            item.petugasNonaktif = false;
            select.classList.remove('select-petugas-pos-galat');
        }

        sinkronInputHidden();
        perbaruiPeringatanPetugas();

        if (selectPetugasPos && item.idPetugas) {
            selectPetugasPos.value = item.idPetugas;
        }
    });

    daftarKeranjangEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-qty');
        if (!btn) return;

        const id   = btn.dataset.id;
        const aksi = btn.dataset.aksi;
        const item = keranjang.get(id);
        if (!item) return;

        if (aksi === 'tambah') {
            if (item.kategori === 'Produk') item.qty += 1;
        } else {
            item.qty -= 1;
            if (item.qty <= 0) keranjang.delete(id);
        }
        renderKeranjang();
    });

    document.querySelectorAll('.btn-muat-booking').forEach(btn => {
        btn.addEventListener('click', () => {
            keranjang.clear();
            const idRes      = btn.dataset.idReservasi || '';
            const pelanggan  = btn.dataset.pelanggan || '';
            const kodePromo  = btn.dataset.kodePromo || '';
            const tipePromo  = btn.dataset.tipePromo || '';
            const nilaiPromo = parseFloat(btn.dataset.nilaiPromo || '0');
            const items      = JSON.parse(btn.dataset.items || '[]');

            if (inputIdReservasi) inputIdReservasi.value = idRes;
            if (inputPelangganEl) inputPelangganEl.value = `${pelanggan} (Reservasi #${idRes})`;
            if (labelModeEl) labelModeEl.textContent = `Mode: Pembayaran Reservasi #${idRes}`;
            tutupModalAntrean();

            let idPetugasPertamaAktif = '';

            items.forEach(it => {
                const isLayanan = (it.tipe || 'Layanan') === 'Layanan';
                const punyaPetugas = Boolean(it.id_petugas);
                const petugasAktif = it.petugas_aktif !== false;

                if (isLayanan && punyaPetugas && petugasAktif && !idPetugasPertamaAktif) {
                    idPetugasPertamaAktif = String(it.id_petugas);
                }

                keranjang.set(it.id, {
                    idAsli: it.id_asli,
                    nama: it.nama,
                    harga: it.harga,
                    qty: parseInt(it.qty || '1', 10),
                    kategori: it.tipe || 'Layanan',
                    jabatan: it.jabatan_petugas || 'Stylist',
                    idPetugas: (isLayanan && petugasAktif && it.id_petugas) ? String(it.id_petugas) : '',
                    namaPetugasLama: it.nama_petugas || '',
                    petugasNonaktif: isLayanan && punyaPetugas && !petugasAktif,
                });
            });

            if (selectPetugasPos) {
                selectPetugasPos.value = idPetugasPertamaAktif;
            }

            if (selectPromo) {
                if (kodePromo) {
                    let optAda = Array.from(selectPromo.options).find(o => o.value === kodePromo);
                    if (!optAda) {
                        optAda = document.createElement('option');
                        optAda.value = kodePromo;
                        optAda.dataset.tipe = tipePromo || 'Nominal';
                        optAda.dataset.nilai = String(nilaiPromo);
                        optAda.dataset.min = '0';
                        optAda.textContent = `${kodePromo} (Promo Reservasi)`;
                        selectPromo.appendChild(optAda);
                    }
                    selectPromo.value = kodePromo;
                } else {
                    selectPromo.value = '';
                }
            }

            renderKeranjang();
            window.tampilkanToast?.(`Reservasi #${idRes} (${pelanggan}) dimuat lengkap beserta petugas, produk, dan promo.`);
        });
    });

    const saringKatalog = () => {
        const kata = (cariInput?.value || '').toLowerCase().trim();
        itemKatalogList.forEach(item => {
            const cocokKat  = kategoriAktif === 'semua' || item.dataset.kategori === kategoriAktif;
            const cocokNama = (item.dataset.nama || '').toLowerCase().includes(kata);
            item.style.display = (cocokKat && cocokNama) ? 'flex' : 'none';
        });
    };

    tabBtns.forEach(tab => {
        tab.addEventListener('click', () => {
            tabBtns.forEach(t => t.classList.remove('aktif'));
            tab.classList.add('aktif');
            kategoriAktif = tab.dataset.kategori || 'semua';
            saringKatalog();
        });
    });

    cariInput?.addEventListener('input', saringKatalog);
    selectPromo?.addEventListener('change', renderKeranjang);
    selectMetodeBayar?.addEventListener('change', () => {
        if (apakahMetodeNonTunai() && inputUangEl) {
            inputUangEl.value = hitungTotalAkhir();
        }
        hitungKembalian();
    });
    inputUangEl?.addEventListener('input', hitungKembalian);

    document.getElementById('btnUangPas')?.addEventListener('click', () => {
        if (inputUangEl) inputUangEl.value = hitungTotalAkhir();
        hitungKembalian();
    });

    document.querySelectorAll('.btn-nominal').forEach(btn => {
        btn.addEventListener('click', () => {
            if (inputUangEl) inputUangEl.value = btn.dataset.nominal;
            hitungKembalian();
        });
    });

    document.getElementById('btnBersihkan')?.addEventListener('click', () => {
        keranjang.clear();
        if (inputIdReservasi) inputIdReservasi.value = '';
        if (inputPelangganEl) inputPelangganEl.value = '';
        if (labelModeEl) labelModeEl.textContent = 'Mode: Pelanggan Walk-In / Umum';
        if (inputUangEl) inputUangEl.value = '';
        if (selectPromo) selectPromo.value = '';
        if (selectPetugasPos) selectPetugasPos.value = '';
        renderKeranjang();
    });

    // Jika kasir diarahkan dari halaman Antrean / Permintaan dengan ?muat_reservasi=ID, muat otomatis ke POS
    const idMuatOtomatis = posGridEl?.dataset.muatOtomatis || '';
    if (idMuatOtomatis) {
        const tombolTarget = document.querySelector(`.btn-muat-booking[data-id-reservasi="${idMuatOtomatis}"]`);
        tombolTarget?.click();
    }

    formTransaksi?.addEventListener('submit', (e) => {
        if (keranjang.size === 0) {
            e.preventDefault();
            window.tampilkanToast?.('Pilih reservasi atau tambahkan item ke keranjang terlebih dahulu.');
            return;
        }
        if (btnProsesBayar) {
            btnProsesBayar.disabled = true;
            btnProsesBayar.textContent = 'Memproses Transaksi...';
        }
    });
});
