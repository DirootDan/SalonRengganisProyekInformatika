import { escapeHtml, inisialisasiOtorisasiPos } from './pos-otorisasi';

document.addEventListener('DOMContentLoaded', () => {
    const keranjang = new Map();

    const daftarKeranjangEl = document.getElementById('daftarKeranjang');
    const hiddenContainer   = document.getElementById('inputHiddenKeranjang');
    const inputIdReservasi  = document.getElementById('posIdReservasi');
    const inputIdOtorisasi  = document.getElementById('posIdOtorisasi');
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
    const posGridEl         = document.querySelector('.pos-grid');

    const daftarPetugasAktif = JSON.parse(formTransaksi?.dataset.petugas || '[]');
    let kategoriAktif = 'semua';
    let otorisasiCtrl = null;

    const aturModalAntrean = (tampil) => {
        if (!modalAntreanPos) return;
        modalAntreanPos.classList.toggle('tampil', tampil);
        modalAntreanPos.setAttribute('aria-hidden', tampil ? 'false' : 'true');
    };

    document.getElementById('btnBukaModalAntrean')?.addEventListener('click', () => aturModalAntrean(true));
    document.getElementById('btnPilihAntreanKecil')?.addEventListener('click', () => aturModalAntrean(true));
    document.getElementById('btnTutupModalAntrean')?.addEventListener('click', () => aturModalAntrean(false));
    modalAntreanPos?.addEventListener('click', e => { if (e.target === modalAntreanPos) aturModalAntrean(false); });

    const formatRp = (angka) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(Math.max(0, Math.round(angka)));

    const hitungSubtotal = () => {
        let sub = 0;
        keranjang.forEach(item => { sub += item.harga * item.qty; });
        return sub;
    };

    const hitungPotonganPromoDariSubtotal = (sub) => {
        if (!selectPromo || !selectPromo.value) return 0;
        const opt = selectPromo.selectedOptions[0];
        const tipe = opt?.dataset.tipe || '';
        const nilai = parseFloat(opt?.dataset.nilai || '0');
        const min = parseFloat(opt?.dataset.min || '0');
        if (sub < min) return 0;
        return tipe === 'Persentase' ? Math.round(sub * (nilai / 100)) : nilai;
    };

    const hitungTotalAkhir = () => {
        const sub = hitungSubtotal();
        return Math.max(0, Math.round(sub - hitungPotonganPromoDariSubtotal(sub)));
    };

    const apakahMetodeNonTunai = () => {
        const opt = selectMetodeBayar?.selectedOptions[0];
        const kat = (opt?.dataset.kategori || opt?.textContent || '').toLowerCase();
        return Boolean(selectMetodeBayar) && !kat.includes('tunai');
    };

    const hitungKembalian = () => {
        const total = hitungTotalAkhir();
        if (btnPecahanEl) {
            const bulat500 = total > 0 ? Math.ceil(total / 500) * 500 : 0;
            btnPecahanEl.dataset.nominal = String(bulat500);
            btnPecahanEl.textContent = bulat500 > 0 ? formatRp(bulat500) : 'Pecahan Terdekat';
        }
        const selisih = parseFloat(inputUangEl?.value || '0') - total;
        if (teksKembalianEl) {
            teksKembalianEl.textContent = selisih >= 0 ? formatRp(selisih) : 'Uang belum cukup';
        }
    };

    const perbaruiPeringatanPetugas = () => {
        if (!peringatanPetugas) return;
        const daftar = [];
        keranjang.forEach(item => {
            if (item.kategori === 'Layanan' && item.petugasNonaktif) {
                daftar.push(`Petugas "${escapeHtml(item.namaPetugasLama || 'Sebelumnya')}" pada layanan "${escapeHtml(item.nama)}" sudah dinonaktifkan/dihapus. Silakan pilih ${escapeHtml(item.jabatan || 'petugas')} pengganti yang aktif.`);
            }
        });
        peringatanPetugas.innerHTML = daftar.map(p => `<div>&bull; ${p}</div>`).join('');
        peringatanPetugas.hidden = daftar.length === 0;
    };

    const tambahHidden = (nama, nilai) => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = nama;
        inp.value = String(nilai);
        hiddenContainer.appendChild(inp);
    };

    const sinkronInputHidden = () => {
        if (!hiddenContainer) return;
        hiddenContainer.innerHTML = '';
        keranjang.forEach((item) => {
            const adaOverride = Math.abs(item.harga - item.hargaNormal) >= 1;
            if (item.kategori === 'Layanan') {
                tambahHidden('layanan_ids[]', item.idAsli);
                if (item.idPetugas) tambahHidden(`petugas_layanan[${item.idAsli}]`, item.idPetugas);
                if (adaOverride) tambahHidden(`harga_override_layanan[${item.idAsli}]`, Math.round(item.harga));
            } else {
                tambahHidden(`produk_items[${item.idAsli}]`, item.qty);
                if (adaOverride) tambahHidden(`harga_override_produk[${item.idAsli}]`, Math.round(item.harga));
            }
        });
    };

    const buatDropdownPetugasLayanan = (item, idKeranjang) => {
        const jabatan = item.jabatan || 'Stylist';
        const opsiHtml = daftarPetugasAktif
            .filter(s => s.role_jabatan === jabatan)
            .map(staf => {
                const terpilih = String(staf.id_karyawan) === String(item.idPetugas || '') ? 'selected' : '';
                return `<option value="${escapeHtml(staf.id_karyawan)}" ${terpilih}>${escapeHtml(staf.nama_staff)} (${escapeHtml(staf.role_jabatan)} &bull; Komisi ${escapeHtml(staf.persen_komisi)}%)</option>`;
            }).join('');
        const kelasGalat = item.petugasNonaktif ? 'select-petugas-pos-galat' : '';
        const labelKosong = item.petugasNonaktif ? `-- Wajib Pilih ${escapeHtml(jabatan)} Pengganti Aktif --` : `-- Pilih ${escapeHtml(jabatan)} --`;

        return `<div class="keranjang-petugas-baris">
            <label class="label-petugas-kecil">Petugas ${escapeHtml(jabatan)}:</label>
            <select class="select-petugas-pos ${kelasGalat}" data-id-keranjang="${escapeHtml(idKeranjang)}">
                <option value="">${labelKosong}</option>${opsiHtml}
            </select>
        </div>`;
    };

    const buatKontrolOverrideHarga = (item, id) => {
        const idAman = escapeHtml(id);
        if (item.sedangEditHarga) {
            return `<div class="baris-override-harga">
                <input type="number" class="input-harga-override" data-id="${idAman}" value="${Math.round(item.harga)}" min="0" step="500" aria-label="Harga override manual">
                <button type="button" class="btn-simpan-override" data-id="${idAman}">Terapkan</button>
                <button type="button" class="btn-batal-override" data-id="${idAman}">Batal</button>
            </div>`;
        }
        const adaOverride = Math.abs(item.harga - item.hargaNormal) >= 1;
        return `<div class="baris-aksi-harga">
            <button type="button" class="btn-buka-override" data-id="${idAman}">✏️ Ubah Harga</button>
            ${adaOverride ? `<button type="button" class="btn-reset-override" data-id="${idAman}">Reset (${formatRp(item.hargaNormal)})</button>` : ''}
        </div>`;
    };

    const renderKeranjang = () => {
        if (!daftarKeranjangEl) return;
        sinkronInputHidden();
        perbaruiPeringatanPetugas();

        if (keranjang.size === 0) {
            daftarKeranjangEl.innerHTML = '<div class="keranjang-kosong">Belum ada layanan atau produk yang dipilih.</div>';
            teksTotalEl.textContent = 'Rp 0';
            hitungKembalian();
            otorisasiCtrl?.perbaruiPanelOtorisasi(0);
            return;
        }

        daftarKeranjangEl.innerHTML = '';
        keranjang.forEach((item, id) => {
            const adaOverride = Math.abs(item.harga - item.hargaNormal) >= 1;
            const baris = document.createElement('div');
            baris.className = 'item-keranjang';
            baris.innerHTML = `<div class="keranjang-info">
                <span class="keranjang-nama">${escapeHtml(item.nama)}</span>
                <span class="keranjang-sub">
                    ${item.qty} x ${adaOverride ? `<del class="harga-awal-coret">${formatRp(item.hargaNormal)}</del> ` : ''}${formatRp(item.harga)}
                    ${adaOverride ? '<span class="badge-harga-override">Harga Override</span>' : ''}
                </span>
                ${buatKontrolOverrideHarga(item, id)}
                ${item.kategori === 'Layanan' ? buatDropdownPetugasLayanan(item, id) : ''}
            </div>
            <div class="keranjang-kontrol">
                <button type="button" class="btn-qty" data-aksi="kurang" data-id="${escapeHtml(id)}">-</button>
                <strong>${item.qty}</strong>
                <button type="button" class="btn-qty" data-aksi="tambah" data-id="${escapeHtml(id)}">+</button>
            </div>`;
            daftarKeranjangEl.appendChild(baris);
        });

        const total = hitungTotalAkhir();
        teksTotalEl.textContent = formatRp(total);
        if (apakahMetodeNonTunai() && inputUangEl) inputUangEl.value = total;
        hitungKembalian();
        otorisasiCtrl?.perbaruiPanelOtorisasi(total);
    };

    const muatItemKeKeranjang = ({ idRes = '', pelanggan = '', kodePromo = '', tipePromo = '', nilaiPromo = 0, items = [], idOtor = '' }) => {
        keranjang.clear();
        if (inputIdReservasi) inputIdReservasi.value = idRes;
        if (inputIdOtorisasi) inputIdOtorisasi.value = idOtor;

        if (inputPelangganEl) {
            inputPelangganEl.readOnly = Boolean(idRes);
            if (idRes) {
                inputPelangganEl.value = pelanggan.includes('Reservasi #') ? pelanggan : `${pelanggan} (Reservasi #${idRes})`;
            } else {
                const bersih = pelanggan.replace(/^Walk-In\s*-\s*/i, '');
                inputPelangganEl.value = bersih === 'Pelanggan Umum' ? '' : bersih;
            }
        }
        if (labelModeEl) {
            labelModeEl.textContent = idRes ? `Mode: Pembayaran Reservasi #${idRes}` : 'Mode: Pelanggan Walk-In / Umum';
        }

        let petugasPertama = '';
        items.forEach(it => {
            const isLayanan = (it.tipe || 'Layanan') === 'Layanan';
            const aktif = it.petugas_aktif !== false;
            if (isLayanan && it.id_petugas && aktif && !petugasPertama) petugasPertama = String(it.id_petugas);

            keranjang.set(it.id, {
                idAsli: it.id_asli,
                nama: it.nama,
                hargaNormal: Number(it.harga_normal ?? it.harga ?? 0),
                harga: Number(it.harga ?? 0),
                sedangEditHarga: false,
                qty: parseInt(it.qty || '1', 10),
                kategori: it.tipe || 'Layanan',
                jabatan: it.jabatan_petugas || 'Stylist',
                idPetugas: (isLayanan && aktif && it.id_petugas) ? String(it.id_petugas) : '',
                namaPetugasLama: it.nama_petugas || '',
                petugasNonaktif: isLayanan && Boolean(it.id_petugas) && !aktif,
            });
        });

        if (selectPetugasPos) selectPetugasPos.value = petugasPertama;
        if (selectPromo) {
            if (kodePromo) {
                let opt = Array.from(selectPromo.options).find(o => o.value === kodePromo);
                if (!opt) {
                    opt = document.createElement('option');
                    opt.value = kodePromo;
                    opt.dataset.tipe = tipePromo || 'Nominal';
                    opt.dataset.nilai = String(nilaiPromo);
                    opt.dataset.min = '0';
                    opt.textContent = `${kodePromo} (Promo Reservasi)`;
                    selectPromo.appendChild(opt);
                }
                selectPromo.value = kodePromo;
            } else {
                selectPromo.value = '';
            }
        }
        renderKeranjang();
    };

    otorisasiCtrl = inisialisasiOtorisasiPos({
        keranjang, formTransaksi, inputIdReservasi, inputIdOtorisasi, inputPelangganEl,
        selectPromo, btnProsesBayar, formatRp,
        ambilTotalAkhir: () => hitungTotalAkhir(),
        hitungPotonganPromoDariSubtotal, muatItemKeKeranjang,
    });

    itemKatalogList.forEach(btn => {
        btn.addEventListener('click', () => {
            const { id, idAsli, nama, kategori = 'Layanan', jabatan = 'Stylist' } = btn.dataset;
            const harga = parseInt(btn.dataset.harga || '0', 10);
            if (keranjang.has(id)) {
                if (kategori === 'Produk') keranjang.get(id).qty += 1;
            } else {
                keranjang.set(id, {
                    idAsli, nama, hargaNormal: harga, harga, sedangEditHarga: false,
                    qty: 1, kategori, jabatan,
                    idPetugas: kategori === 'Layanan' ? (selectPetugasPos?.value || '') : null,
                    petugasNonaktif: false,
                });
            }
            renderKeranjang();
        });
    });

    daftarKeranjangEl?.addEventListener('change', (e) => {
        const select = e.target.closest('.select-petugas-pos');
        const item = select ? keranjang.get(select.dataset.idKeranjang) : null;
        if (!item) return;
        item.idPetugas = select.value || null;
        if (item.idPetugas) {
            item.petugasNonaktif = false;
            select.classList.remove('select-petugas-pos-galat');
            if (selectPetugasPos) selectPetugasPos.value = item.idPetugas;
        }
        sinkronInputHidden();
        perbaruiPeringatanPetugas();
    });

    const terapkanOverrideBaris = (id) => {
        const item = keranjang.get(id);
        const inp = daftarKeranjangEl?.querySelector(`.input-harga-override[data-id="${CSS.escape(id)}"]`);
        if (!item || !inp) return;
        const baru = Math.max(0, Math.round(parseFloat(inp.value || '0')));
        if (Number.isNaN(baru)) return;
        item.harga = baru;
        item.sedangEditHarga = false;
        renderKeranjang();
    };

    daftarKeranjangEl?.addEventListener('keydown', (e) => {
        const inp = e.target.closest('.input-harga-override');
        if (inp && e.key === 'Enter') {
            e.preventDefault();
            terapkanOverrideBaris(inp.dataset.id);
        }
    });

    daftarKeranjangEl?.addEventListener('click', (e) => {
        const btnBuka = e.target.closest('.btn-buka-override');
        if (btnBuka) {
            const it = keranjang.get(btnBuka.dataset.id);
            if (it) { it.sedangEditHarga = true; renderKeranjang(); }
            return;
        }
        const btnSimpan = e.target.closest('.btn-simpan-override');
        if (btnSimpan) { terapkanOverrideBaris(btnSimpan.dataset.id); return; }

        const btnBatal = e.target.closest('.btn-batal-override');
        if (btnBatal) {
            const it = keranjang.get(btnBatal.dataset.id);
            if (it) { it.sedangEditHarga = false; renderKeranjang(); }
            return;
        }
        const btnReset = e.target.closest('.btn-reset-override');
        if (btnReset) {
            const it = keranjang.get(btnReset.dataset.id);
            if (it) { it.harga = it.hargaNormal; it.sedangEditHarga = false; renderKeranjang(); }
            return;
        }
        const btnQty = e.target.closest('.btn-qty');
        const item = btnQty ? keranjang.get(btnQty.dataset.id) : null;
        if (!item) return;
        if (btnQty.dataset.aksi === 'tambah') {
            if (item.kategori === 'Produk') item.qty += 1;
        } else {
            item.qty -= 1;
            if (item.qty <= 0) keranjang.delete(btnQty.dataset.id);
        }
        renderKeranjang();
    });

    document.querySelectorAll('.btn-muat-booking').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            const items = JSON.parse(d.items || '[]');
            otorisasiCtrl.setItemAwalReservasi(items);
            muatItemKeKeranjang({
                idRes: d.idReservasi || '', pelanggan: d.pelanggan || '',
                kodePromo: d.kodePromo || '', tipePromo: d.tipePromo || '',
                nilaiPromo: parseFloat(d.nilaiPromo || '0'), items,
            });
            aturModalAntrean(false);
            window.tampilkanToast?.(`Reservasi #${d.idReservasi} (${d.pelanggan}) dimuat ke POS.`);
        });
    });

    const saringKatalog = () => {
        const kata = (cariInput?.value || '').toLowerCase().trim();
        itemKatalogList.forEach(item => {
            const cocokKat = kategoriAktif === 'semua' || item.dataset.kategori === kategoriAktif;
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
        if (apakahMetodeNonTunai() && inputUangEl) inputUangEl.value = hitungTotalAkhir();
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
        otorisasiCtrl.setItemAwalReservasi([]);
        muatItemKeKeranjang({});
        if (inputUangEl) inputUangEl.value = '';
    });

    const idMuatOtomatis = posGridEl?.dataset.muatOtomatis || '';
    if (idMuatOtomatis) {
        document.querySelector(`.btn-muat-booking[data-id-reservasi="${CSS.escape(idMuatOtomatis)}"]`)?.click();
    }

    formTransaksi?.addEventListener('submit', (e) => {
        if (keranjang.size === 0) {
            e.preventDefault();
            window.tampilkanToast?.('Pilih reservasi atau tambahkan item ke keranjang terlebih dahulu.');
            return;
        }
        if (!otorisasiCtrl.validasiSebelumSubmitBayar(hitungTotalAkhir())) {
            e.preventDefault();
            return;
        }
        if (btnProsesBayar) {
            btnProsesBayar.disabled = true;
            btnProsesBayar.textContent = 'Memproses Transaksi...';
        }
    });
});
