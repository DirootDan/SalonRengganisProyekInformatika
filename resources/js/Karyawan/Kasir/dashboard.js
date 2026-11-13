import { inisialisasiOtorisasiPos } from './pos-otorisasi';
import {
    apakahMetodeNonTunai,
    formatRp,
    hitungPotonganPromoDariSubtotal,
    hitungTotalAkhir,
} from './dashboard/kalkulasi';
import {
    buatElemenBarisKeranjang,
    perbaruiPeringatanPetugas,
    sinkronInputHidden,
} from './dashboard/keranjangRenderer';

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
    modalAntreanPos?.addEventListener('click', (e) => { if (e.target === modalAntreanPos) aturModalAntrean(false); });
    document.querySelectorAll('[data-buka-hold]').forEach((btn) => {
        btn.addEventListener('click', () => document.getElementById('btnBukaHoldPos')?.click());
    });

    const ambilTotal = () => hitungTotalAkhir(keranjang, selectPromo);

    const hitungKembalian = () => {
        const total = ambilTotal();
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

    const renderKeranjang = () => {
        if (!daftarKeranjangEl) return;
        sinkronInputHidden(hiddenContainer, keranjang);
        perbaruiPeringatanPetugas(peringatanPetugas, keranjang);

        if (keranjang.size === 0) {
            daftarKeranjangEl.innerHTML = '<div class="keranjang-kosong">Belum ada layanan atau produk yang dipilih.</div>';
            teksTotalEl.textContent = 'Rp 0';
            hitungKembalian();
            otorisasiCtrl?.perbaruiPanelOtorisasi(0);
            return;
        }

        daftarKeranjangEl.innerHTML = '';
        keranjang.forEach((item, id) => {
            daftarKeranjangEl.appendChild(buatElemenBarisKeranjang(item, id, daftarPetugasAktif));
        });

        const total = ambilTotal();
        teksTotalEl.textContent = formatRp(total);
        if (apakahMetodeNonTunai(selectMetodeBayar) && inputUangEl) inputUangEl.value = total;
        hitungKembalian();
        otorisasiCtrl?.perbaruiPanelOtorisasi(total);
    };

    const muatItemKeKeranjang = ({ idRes = '', pelanggan = '', kodePromo = '', tipePromo = '', nilaiPromo = 0, items = [], idOtor = '' }) => {
        keranjang.clear();
        if (inputIdReservasi) inputIdReservasi.value = idRes;
        if (inputIdOtorisasi) inputIdOtorisasi.value = idOtor;

        if (inputPelangganEl) {
            inputPelangganEl.readOnly = Boolean(idRes);
            inputPelangganEl.required = !idRes;
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
        items.forEach((it) => {
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
                let opt = Array.from(selectPromo.options).find((o) => o.value === kodePromo);
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
        ambilTotalAkhir: ambilTotal,
        hitungPotonganPromoDariSubtotal: (sub) => hitungPotonganPromoDariSubtotal(sub, selectPromo),
        muatItemKeKeranjang,
    });

    itemKatalogList.forEach((btn) => {
        btn.addEventListener('click', () => {
            const { id, idAsli, nama, kategori = 'Layanan', jabatan = 'Stylist' } = btn.dataset;
            const harga = parseInt(btn.dataset.harga || '0', 10);
            if (keranjang.has(id)) {
                if (kategori === 'Produk') keranjang.get(id).qty += 1;
            } else {
                const idPetugasDefault = selectPetugasPos?.value || '';
                const stafDefault = idPetugasDefault
                    ? daftarPetugasAktif.find((s) => String(s.id_karyawan) === String(idPetugasDefault))
                    : null;
                const idPetugasCocok = (kategori === 'Layanan' && stafDefault && stafDefault.role_jabatan === jabatan)
                    ? idPetugasDefault
                    : '';

                keranjang.set(id, {
                    idAsli, nama, hargaNormal: harga, harga, sedangEditHarga: false,
                    qty: 1, kategori, jabatan,
                    idPetugas: kategori === 'Layanan' ? idPetugasCocok : null,
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
        sinkronInputHidden(hiddenContainer, keranjang);
        perbaruiPeringatanPetugas(peringatanPetugas, keranjang);
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
        const aksiMap = [
            ['.btn-buka-override', (it) => { it.sedangEditHarga = true; }],
            ['.btn-batal-override', (it) => { it.sedangEditHarga = false; }],
            ['.btn-reset-override', (it) => { it.harga = it.hargaNormal; it.sedangEditHarga = false; }],
        ];
        const btnSimpan = e.target.closest('.btn-simpan-override');
        if (btnSimpan) { terapkanOverrideBaris(btnSimpan.dataset.id); return; }

        for (const [selector, mutasi] of aksiMap) {
            const btn = e.target.closest(selector);
            if (btn) {
                const it = keranjang.get(btn.dataset.id);
                if (it) { mutasi(it); renderKeranjang(); }
                return;
            }
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

    document.querySelectorAll('.btn-muat-booking').forEach((btn) => {
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
        itemKatalogList.forEach((item) => {
            const cocokKat = kategoriAktif === 'semua' || item.dataset.kategori === kategoriAktif;
            const cocokNama = (item.dataset.nama || '').toLowerCase().includes(kata);
            item.style.display = (cocokKat && cocokNama) ? 'flex' : 'none';
        });
    };

    tabBtns.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabBtns.forEach((t) => t.classList.remove('aktif'));
            tab.classList.add('aktif');
            kategoriAktif = tab.dataset.kategori || 'semua';
            saringKatalog();
        });
    });

    cariInput?.addEventListener('input', saringKatalog);
    selectPromo?.addEventListener('change', renderKeranjang);
    selectMetodeBayar?.addEventListener('change', () => {
        if (apakahMetodeNonTunai(selectMetodeBayar) && inputUangEl) inputUangEl.value = ambilTotal();
        hitungKembalian();
    });
    inputUangEl?.addEventListener('input', hitungKembalian);
    document.getElementById('btnUangPas')?.addEventListener('click', () => {
        if (inputUangEl) inputUangEl.value = ambilTotal();
        hitungKembalian();
    });
    document.querySelectorAll('.btn-nominal').forEach((btn) => {
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
        if (!inputIdReservasi?.value && !(inputPelangganEl?.value || '').trim()) {
            e.preventDefault();
            window.tampilkanToast?.('Nama pelanggan walk-in wajib diisi untuk transaksi tanpa reservasi.');
            inputPelangganEl?.focus();
            return;
        }
        if (!otorisasiCtrl.validasiSebelumSubmitBayar(ambilTotal())) {
            e.preventDefault();
            return;
        }
        if (btnProsesBayar) {
            btnProsesBayar.disabled = true;
            btnProsesBayar.textContent = 'Memproses Transaksi...';
        }
    });
});
