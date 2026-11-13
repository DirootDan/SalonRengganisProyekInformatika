import { escapeHtml } from '../pos-otorisasi';
import { formatRp } from './kalkulasi';

export const perbaruiPeringatanPetugas = (peringatanPetugasEl, keranjang) => {
    if (!peringatanPetugasEl) return;
    const daftar = [];
    keranjang.forEach((item) => {
        if (item.kategori === 'Layanan' && item.petugasNonaktif) {
            daftar.push(
                `Petugas "${escapeHtml(item.namaPetugasLama || 'Sebelumnya')}" pada layanan "${escapeHtml(item.nama)}" sudah dinonaktifkan/dihapus. Silakan pilih ${escapeHtml(item.jabatan || 'petugas')} pengganti yang aktif.`
            );
        }
    });
    peringatanPetugasEl.innerHTML = daftar.map((p) => `<div>&bull; ${p}</div>`).join('');
    peringatanPetugasEl.hidden = daftar.length === 0;
};

export const sinkronInputHidden = (hiddenContainer, keranjang) => {
    if (!hiddenContainer) return;
    hiddenContainer.innerHTML = '';

    const tambahHidden = (nama, nilai) => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = nama;
        inp.value = String(nilai);
        hiddenContainer.appendChild(inp);
    };

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

const buatDropdownPetugasLayanan = (item, idKeranjang, daftarPetugasAktif) => {
    const jabatan = item.jabatan || 'Stylist';
    const opsiHtml = daftarPetugasAktif
        .filter((s) => s.role_jabatan === jabatan)
        .map((staf) => {
            const terpilih = String(staf.id_karyawan) === String(item.idPetugas || '') ? 'selected' : '';
            return `<option value="${escapeHtml(staf.id_karyawan)}" ${terpilih}>${escapeHtml(staf.nama_staff)} (${escapeHtml(staf.role_jabatan)} &bull; Komisi ${escapeHtml(staf.persen_komisi)}%)</option>`;
        })
        .join('');
    const kelasGalat = item.petugasNonaktif ? 'select-petugas-pos-galat' : '';
    const labelKosong = item.petugasNonaktif
        ? `-- Wajib Pilih ${escapeHtml(jabatan)} Pengganti Aktif --`
        : `-- Pilih ${escapeHtml(jabatan)} --`;

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

export const buatElemenBarisKeranjang = (item, id, daftarPetugasAktif) => {
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
        ${item.kategori === 'Layanan' ? buatDropdownPetugasLayanan(item, id, daftarPetugasAktif) : ''}
    </div>
    <div class="keranjang-kontrol">
        <button type="button" class="btn-qty" data-aksi="kurang" data-id="${escapeHtml(id)}">-</button>
        <strong>${item.qty}</strong>
        <button type="button" class="btn-qty" data-aksi="tambah" data-id="${escapeHtml(id)}">+</button>
    </div>`;

    return baris;
};
