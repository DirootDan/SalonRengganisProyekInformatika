export const escapeHtml = (teks) => String(teks ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

export function inisialisasiOtorisasiPos(ctx) {
    const {
        keranjang, formTransaksi, inputIdReservasi, inputIdOtorisasi,
        inputPelangganEl, btnProsesBayar, formatRp,
        hitungPotonganPromoDariSubtotal, muatItemKeKeranjang,
    } = ctx;

    const el = {
        panel: document.getElementById('panelOtorisasiPos'),
        kepala: document.getElementById('kepalaOtorisasiPos'),
        grupAlasan: document.getElementById('grupAlasanOtorisasiPos'),
        inputAlasan: document.getElementById('inputAlasanPerubahanPos'),
        aksiApproval: document.getElementById('aksiWajibApprovalPos'),
        btnKirimHold: document.getElementById('btnKirimHoldManajer'),
        btnBukaDirect: document.getElementById('btnBukaDirectOverride'),
        modalHold: document.getElementById('modalHoldPos'),
        btnBukaHold: document.getElementById('btnBukaHoldPos'),
        btnTutupHold: document.getElementById('btnTutupModalHold'),
        modalDirect: document.getElementById('modalDirectOverridePos'),
        btnTutupDirect: document.getElementById('btnTutupModalDirect'),
        btnBatalDirect: document.getElementById('btnBatalDirect'),
        btnKonfirmDirect: document.getElementById('btnKonfirmasiDirect'),
        pesanGalatDirect: document.getElementById('pesanGalatDirect'),
        infoKunciDirect: document.getElementById('infoKunciDirect'),
        hitungMundur: document.getElementById('hitungMundurDirect'),
        grupFormDirect: document.getElementById('grupFormDirectOverride'),
        inputUserManajer: document.getElementById('inputUsernameManajerPos'),
        inputPassManajer: document.getElementById('inputPasswordManajerPos'),
    };

    const batasApproval = parseFloat(formTransaksi?.dataset.batasApproval || '150000');
    const urlBayar = formTransaksi?.dataset.urlBayar || formTransaksi?.action || '';
    const urlHold = formTransaksi?.dataset.urlHold || '';
    const urlDirect = el.modalDirect?.dataset.urlDirect || '';

    let itemAwalReservasi = [];
    let tiketDisetujuiAktif = null;
    let timerKunci = null;

    const buatSignatureKeranjang = () => {
        const layananSig = [];
        const produkSig = [];
        keranjang.forEach((it) => {
            if (it.kategori === 'Layanan') layananSig.push(`${it.idAsli}:${Math.round(it.harga)}`);
            else produkSig.push(`${it.idAsli}:${it.qty}:${Math.round(it.harga)}`);
        });
        return `${inputIdReservasi?.value || 'walkin'}|L:${layananSig.sort().join(',')}|P:${produkSig.sort().join(',')}`;
    };

    const hitungStatusPerubahan = (totalBaru) => {
        const adaReservasi = Boolean(inputIdReservasi?.value);
        let adaOverrideHarga = false;
        const idLayananSekarang = [];

        keranjang.forEach((it) => {
            if (Math.abs(it.harga - it.hargaNormal) >= 1) adaOverrideHarga = true;
            if (it.kategori === 'Layanan') idLayananSekarang.push(Number(it.idAsli));
        });

        const idLayananAwal = itemAwalReservasi
            .filter(i => (i.tipe || 'Layanan') === 'Layanan')
            .map(i => Number(i.id_asli))
            .sort((a, b) => a - b);
        idLayananSekarang.sort((a, b) => a - b);

        const adaUbahLayanan = adaReservasi && JSON.stringify(idLayananSekarang) !== JSON.stringify(idLayananAwal);
        const adaPerubahan = adaOverrideHarga || adaUbahLayanan;

        let subAwal = 0;
        if (adaReservasi && itemAwalReservasi.length > 0) {
            itemAwalReservasi.forEach(i => { subAwal += Number(i.harga_normal ?? i.harga ?? 0) * Number(i.qty || 1); });
        } else {
            keranjang.forEach(it => { subAwal += it.hargaNormal * it.qty; });
        }

        const totalAwal = Math.max(0, Math.round(subAwal - hitungPotonganPromoDariSubtotal(subAwal)));
        const nilaiAcuan = Math.max(totalAwal, totalBaru);

        return { adaPerubahan, totalAwal, totalBaru, nilaiAcuan, wajibApproval: adaPerubahan && nilaiAcuan >= batasApproval };
    };

    const terapkanTampilanPanel = (st) => {
        el.panel.hidden = false;
        el.panel.classList.remove('mode-mandiri', 'mode-wajib', 'mode-disetujui');

        if (!st.wajibApproval) {
            el.panel.classList.add('mode-mandiri');
            el.kepala.innerHTML = `<strong>📝 Perubahan Mandiri (&lt; ${formatRp(batasApproval)})</strong><span>Tanpa menunggu Manajer, wajib isi alasan untuk log audit harian.</span>`;
            el.grupAlasan.hidden = false;
            el.aksiApproval.hidden = true;
            btnProsesBayar.hidden = false;
            return;
        }

        if (tiketDisetujuiAktif) {
            el.panel.classList.add('mode-disetujui');
            el.kepala.innerHTML = `<strong>✅ Disetujui oleh ${escapeHtml(tiketDisetujuiAktif.namaManajer)} (#${escapeHtml(tiketDisetujuiAktif.kodeReferensi)})</strong><span>Alasan tercatat: "${escapeHtml(tiketDisetujuiAktif.alasan)}" — Silakan selesaikan pembayaran.</span>`;
            el.grupAlasan.hidden = true;
            el.aksiApproval.hidden = true;
            btnProsesBayar.hidden = false;
            return;
        }

        el.panel.classList.add('mode-wajib');
        el.kepala.innerHTML = `<strong>🔒 Wajib Persetujuan Manajer (Acuan ${formatRp(st.nilaiAcuan)} &ge; ${formatRp(batasApproval)})</strong><span>Tagihan awal ${formatRp(st.totalAwal)} &rarr; usulan ${formatRp(st.totalBaru)}. Isi alasan lalu pilih Hold atau Otorisasi di Tempat.</span>`;
        el.grupAlasan.hidden = false;
        el.aksiApproval.hidden = false;
        btnProsesBayar.hidden = true;
    };

    const perbaruiPanelOtorisasi = (totalBaru) => {
        if (!el.panel || !btnProsesBayar) return;
        if (keranjang.size === 0) {
            el.panel.hidden = true;
            btnProsesBayar.hidden = false;
            return;
        }

        const st = hitungStatusPerubahan(totalBaru);
        if (tiketDisetujuiAktif && tiketDisetujuiAktif.signature !== buatSignatureKeranjang()) {
            tiketDisetujuiAktif = null;
            if (inputIdOtorisasi) inputIdOtorisasi.value = '';
        }

        if (!st.adaPerubahan) {
            el.panel.hidden = true;
            btnProsesBayar.hidden = false;
            return;
        }
        terapkanTampilanPanel(st);
    };

    const mulaiHitungMundurKunci = (detikAwal) => {
        if (!el.infoKunciDirect || !el.grupFormDirect) return;
        let sisa = Math.ceil(Number(detikAwal || 0));
        if (timerKunci) clearInterval(timerKunci);

        if (sisa <= 0) {
            el.infoKunciDirect.hidden = true;
            el.grupFormDirect.hidden = false;
            return;
        }

        el.infoKunciDirect.hidden = false;
        el.grupFormDirect.hidden = true;
        if (el.hitungMundur) el.hitungMundur.textContent = String(sisa);

        timerKunci = setInterval(() => {
            sisa -= 1;
            if (el.hitungMundur) el.hitungMundur.textContent = String(Math.max(0, sisa));
            if (sisa <= 0) {
                clearInterval(timerKunci);
                el.infoKunciDirect.hidden = true;
                el.grupFormDirect.hidden = false;
                if (el.pesanGalatDirect) el.pesanGalatDirect.hidden = true;
            }
        }, 1000);
    };

    const validasiAlasanWajib = () => {
        const alasan = (el.inputAlasan?.value || '').trim();
        if (alasan.length >= 5) return alasan;
        window.tampilkanToast?.('Wajib mengisi alasan perubahan minimal 5 karakter terlebih dahulu.');
        el.inputAlasan?.focus();
        return null;
    };

    const aturModal = (modalEl, tampil) => {
        if (!modalEl) return;
        modalEl.classList.toggle('tampil', tampil);
        modalEl.setAttribute('aria-hidden', tampil ? 'false' : 'true');
    };

    const tutupModalDirect = () => {
        aturModal(el.modalDirect, false);
        if (el.inputPassManajer) el.inputPassManajer.value = '';
    };

    el.btnKirimHold?.addEventListener('click', () => {
        const alasan = validasiAlasanWajib();
        if (!alasan || !formTransaksi || !urlHold) return;

        let hiddenAlasan = formTransaksi.querySelector('input[name="alasan_kasir"]');
        if (!hiddenAlasan) {
            hiddenAlasan = document.createElement('input');
            hiddenAlasan.type = 'hidden';
            hiddenAlasan.name = 'alasan_kasir';
            formTransaksi.appendChild(hiddenAlasan);
        }
        hiddenAlasan.value = alasan;

        el.btnKirimHold.disabled = true;
        el.btnKirimHold.textContent = 'Menyimpan ke Hold...';
        formTransaksi.action = urlHold;
        formTransaksi.submit();
    });

    el.btnBukaDirect?.addEventListener('click', () => {
        const alasan = validasiAlasanWajib();
        if (!alasan || !el.modalDirect) return;

        const st = hitungStatusPerubahan(ctx.ambilTotalAkhir());
        document.getElementById('directLabelTarget').textContent = inputPelangganEl?.value || 'Pelanggan Walk-In / Umum';
        document.getElementById('directTotalAwal').textContent = formatRp(st.totalAwal);
        document.getElementById('directTotalBaru').textContent = formatRp(st.totalBaru);
        document.getElementById('directAlasanKasir').textContent = alasan;

        if (el.pesanGalatDirect) el.pesanGalatDirect.hidden = true;
        aturModal(el.modalDirect, true);
        el.inputUserManajer?.focus();
    });

    el.btnTutupDirect?.addEventListener('click', tutupModalDirect);
    el.btnBatalDirect?.addEventListener('click', tutupModalDirect);
    el.modalDirect?.addEventListener('click', e => { if (e.target === el.modalDirect) tutupModalDirect(); });

    const tampilkanGalatDirect = (pesan) => {
        if (!el.pesanGalatDirect) return;
        el.pesanGalatDirect.textContent = pesan;
        el.pesanGalatDirect.hidden = false;
    };

    el.btnKonfirmDirect?.addEventListener('click', async () => {
        const alasan = validasiAlasanWajib();
        const username = (el.inputUserManajer?.value || '').trim();
        const password = el.inputPassManajer?.value || '';

        if (!alasan || !username || !password) {
            tampilkanGalatDirect('Username dan Password Manajer wajib diisi.');
            return;
        }

        const fd = new FormData(formTransaksi);
        fd.set('alasan_kasir', alasan);
        fd.set('username_manajer', username);
        fd.set('password_manajer', password);

        el.btnKonfirmDirect.disabled = true;
        el.btnKonfirmDirect.textContent = 'Memverifikasi...';

        try {
            const res = await fetch(urlDirect, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const payload = await res.json();

            if (!res.ok || !payload.berhasil) {
                tampilkanGalatDirect(payload.message || 'Verifikasi Manajer gagal.');
                if (res.status === 429 || Number(payload.detik_terkunci || 0) > 0) {
                    mulaiHitungMundurKunci(payload.detik_terkunci || 60);
                }
                return;
            }

            if (inputIdOtorisasi) inputIdOtorisasi.value = String(payload.id_otorisasi);
            tiketDisetujuiAktif = {
                idOtorisasi: payload.id_otorisasi,
                kodeReferensi: payload.kode_referensi,
                namaManajer: payload.nama_manajer,
                alasan,
                signature: buatSignatureKeranjang(),
            };
            tutupModalDirect();
            perbaruiPanelOtorisasi(ctx.ambilTotalAkhir());
            window.tampilkanToast?.(payload.message);
        } catch {
            tampilkanGalatDirect('Gagal menghubungi server. Periksa koneksi jaringan.');
        } finally {
            el.btnKonfirmDirect.disabled = false;
            el.btnKonfirmDirect.textContent = 'Verifikasi & Setujui Sekarang';
        }
    });

    el.btnBukaHold?.addEventListener('click', () => aturModal(el.modalHold, true));
    el.btnTutupHold?.addEventListener('click', () => aturModal(el.modalHold, false));
    el.modalHold?.addEventListener('click', e => { if (e.target === el.modalHold) aturModal(el.modalHold, false); });

    document.querySelectorAll('.btn-muat-hold').forEach((btn) => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            itemAwalReservasi = JSON.parse(d.itemsAwal || '[]');
            muatItemKeKeranjang({
                idRes: d.idReservasi || '',
                pelanggan: d.pelanggan || '',
                kodePromo: d.kodePromo || '',
                items: JSON.parse(d.items || '[]'),
                idOtor: d.idOtorisasi || '',
            });
            if (el.inputAlasan) el.inputAlasan.value = d.alasan || '';

            if ((d.modeHold || 'disetujui') === 'disetujui') {
                tiketDisetujuiAktif = {
                    idOtorisasi: d.idOtorisasi || '',
                    kodeReferensi: d.kodeReferensi || '',
                    namaManajer: d.namaManajer || 'Manajer',
                    alasan: d.alasan || '',
                    signature: buatSignatureKeranjang(),
                };
                window.tampilkanToast?.(`Transaksi Hold #${d.kodeReferensi} (Disetujui ${tiketDisetujuiAktif.namaManajer}) siap dibayar.`);
            } else {
                tiketDisetujuiAktif = null;
                const pesan = d.modeHold === 'bayar_normal'
                    ? `Transaksi #${d.kodeReferensi} dimuat dengan harga normal awal.`
                    : 'Silakan sesuaikan harga/layanan & ajukan ulang alasan baru.';
                window.tampilkanToast?.(pesan);
            }
            aturModal(el.modalHold, false);
            perbaruiPanelOtorisasi(ctx.ambilTotalAkhir());
        });
    });

    const detikKunciAwal = parseInt(el.modalDirect?.dataset.detikKunciAwal || '0', 10);
    if (detikKunciAwal > 0) mulaiHitungMundurKunci(detikKunciAwal);

    return {
        setItemAwalReservasi: (items) => {
            itemAwalReservasi = Array.isArray(items) ? items : [];
            tiketDisetujuiAktif = null;
            if (inputIdOtorisasi) inputIdOtorisasi.value = '';
            if (el.inputAlasan) el.inputAlasan.value = '';
            if (formTransaksi && urlBayar) formTransaksi.action = urlBayar;
        },
        validasiSebelumSubmitBayar: (totalBaru) => {
            if (formTransaksi && urlBayar) formTransaksi.action = urlBayar;
            const st = hitungStatusPerubahan(totalBaru);
            if (!st.adaPerubahan) return true;
            if (!st.wajibApproval) return Boolean(validasiAlasanWajib());
            return Boolean(tiketDisetujuiAktif);
        },
        perbaruiPanelOtorisasi,
    };
}
