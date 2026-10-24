document.addEventListener('DOMContentLoaded', () => {
    const keranjang = new Map();

    const daftarKeranjangEl = document.getElementById('daftarKeranjang');
    const hiddenContainer   = document.getElementById('inputHiddenKeranjang');
    const inputIdReservasi  = document.getElementById('posIdReservasi');
    const selectPromo       = document.getElementById('posKodePromo');
    const teksTotalEl       = document.getElementById('teksTotalTagihan');
    const teksKembalianEl   = document.getElementById('teksKembalian');
    const inputUangEl       = document.getElementById('posUangDiterima');
    const inputPelangganEl  = document.getElementById('posNamaPelanggan');
    const cariInput         = document.getElementById('cariItemPos');
    const itemKatalogList   = document.querySelectorAll('.item-katalog');
    const tabBtns           = document.querySelectorAll('.pos-tab');
    const formTransaksi     = document.getElementById('formTransaksiKasir');
    const btnProsesBayar    = document.getElementById('btnProsesBayar');

    let kategoriAktif = 'semua';

    const formatRp = (angka) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(angka);

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

    const hitungKembalian = () => {
        const total = hitungTotalAkhir();
        const bayar = parseInt(inputUangEl?.value || '0', 10);
        const selisih = bayar - total;
        if (teksKembalianEl) {
            teksKembalianEl.textContent = selisih >= 0 ? formatRp(selisih) : 'Uang belum cukup';
        }
    };

    const sinkronInputHidden = () => {
        if (!hiddenContainer) return;
        hiddenContainer.innerHTML = '';

        keranjang.forEach((item) => {
            if (item.kategori === 'Layanan') {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'layanan_ids[]';
                inp.value = item.idAsli;
                hiddenContainer.appendChild(inp);
            } else if (item.kategori === 'Produk') {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = `produk_items[${item.idAsli}]`;
                inp.value = item.qty;
                hiddenContainer.appendChild(inp);
            }
        });
    };

    const renderKeranjang = () => {
        if (!daftarKeranjangEl) return;
        sinkronInputHidden();

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
            baris.innerHTML = `
                <div class="keranjang-info">
                    <span class="keranjang-nama">${item.nama}</span>
                    <span class="keranjang-sub">${item.qty} x ${formatRp(item.harga)}</span>
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
        hitungKembalian();
    };

    itemKatalogList.forEach(btn => {
        btn.addEventListener('click', () => {
            const id       = btn.dataset.id;
            const idAsli   = btn.dataset.idAsli;
            const nama     = btn.dataset.nama;
            const harga    = parseInt(btn.dataset.harga || '0', 10);
            const kategori = btn.dataset.kategori || 'Layanan';

            if (keranjang.has(id)) {
                keranjang.get(id).qty += 1;
            } else {
                keranjang.set(id, { idAsli, nama, harga, qty: 1, kategori });
            }
            renderKeranjang();
        });
    });

    daftarKeranjangEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-qty');
        if (!btn) return;

        const id   = btn.dataset.id;
        const aksi = btn.dataset.aksi;
        const item = keranjang.get(id);
        if (!item) return;

        if (aksi === 'tambah') {
            item.qty += 1;
        } else {
            item.qty -= 1;
            if (item.qty <= 0) keranjang.delete(id);
        }
        renderKeranjang();
    });

    document.querySelectorAll('.btn-muat-booking').forEach(btn => {
        btn.addEventListener('click', () => {
            keranjang.clear();
            const idRes     = btn.dataset.idReservasi || '';
            const pelanggan = btn.dataset.pelanggan || '';
            const items     = JSON.parse(btn.dataset.items || '[]');

            if (inputIdReservasi) inputIdReservasi.value = idRes;
            if (inputPelangganEl) inputPelangganEl.value = `${pelanggan} (Reservasi #${idRes})`;

            items.forEach(it => {
                keranjang.set(it.id, {
                    idAsli: it.id_asli,
                    nama: it.nama,
                    harga: it.harga,
                    qty: 1,
                    kategori: it.tipe || 'Layanan',
                });
            });

            renderKeranjang();
            window.tampilkanToast(`Reservasi #${idRes} (${pelanggan}) siap diproses pembayarannya.`);
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
        if (inputUangEl) inputUangEl.value = '';
        renderKeranjang();
    });

    // Anti-Bug #4: Cegah klik ganda pada tombol bayar
    formTransaksi?.addEventListener('submit', (e) => {
        if (keranjang.size === 0) {
            e.preventDefault();
            window.tampilkanToast('Pilih reservasi atau tambahkan item ke keranjang terlebih dahulu.');
            return;
        }
        if (btnProsesBayar) {
            btnProsesBayar.disabled = true;
            btnProsesBayar.textContent = '⏳ Memproses Transaksi...';
        }
    });
});
