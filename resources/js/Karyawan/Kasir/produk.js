import {
    bukaModalAksesibel,
    perbaruiEmptyStateTabel,
    tutupModalAksesibel,
} from '../../components/staf-ui';
import { inisialisasiModalAksesKatalog } from './katalog-akses';

document.addEventListener('DOMContentLoaded', () => {
    const aksesCtrl = inisialisasiModalAksesKatalog();

    const inputCari = document.getElementById('cariProduk');
    const selectStok = document.getElementById('filterStok');
    const barisProduk = document.querySelectorAll('.baris-produk');
    const tbodyProduk = barisProduk[0]?.closest('tbody') || null;

    const saringProduk = () => {
        const kataKunci = (inputCari?.value || '').toLowerCase().trim();
        const status = selectStok?.value || '';
        let jumlahTampak = 0;

        barisProduk.forEach((baris) => {
            const cocokNama = (baris.dataset.nama || '').includes(kataKunci);
            const cocokStatus = status === '' || (baris.dataset.status || '') === status;
            const tampil = cocokNama && cocokStatus;
            baris.style.display = tampil ? '' : 'none';
            if (tampil) jumlahTampak += 1;
        });

        if (barisProduk.length > 0) {
            perbaruiEmptyStateTabel(tbodyProduk, jumlahTampak, 'Tidak ada produk yang cocok dengan pencarian atau filter.');
        }
    };

    inputCari?.addEventListener('input', saringProduk);
    selectStok?.addEventListener('change', saringProduk);

    const modal = document.getElementById('modalProduk');
    const btnTambah = document.getElementById('btnTambahProduk');
    const judulModal = document.getElementById('judulModalProduk');
    const form = document.getElementById('formProduk');
    const methodInput = document.getElementById('methodProduk');
    const inputUrlAction = document.getElementById('inputUrlActionProduk');
    const inputUrlBatal = document.getElementById('inputUrlBatalProduk');
    const btnBatalKunci = document.getElementById('btnBatalKunciProduk');

    const inputNama = document.getElementById('inputNamaProduk');
    const inputStok = document.getElementById('inputStokProduk');
    const inputHarga = document.getElementById('inputHargaProduk');

    const bukaModal = () => bukaModalAksesibel(modal, inputNama);
    const tutupModal = () => tutupModalAksesibel(modal);

    btnTambah?.addEventListener('click', () => {
        const urlStore = btnTambah.dataset.urlStore || '';
        const urlBatal = btnTambah.dataset.urlBatal || '';
        judulModal.textContent = 'Tambah Produk Baru (1x Pakai)';
        form.reset();
        form.action = urlStore;
        methodInput.value = 'POST';
        if (inputUrlAction) inputUrlAction.value = urlStore;
        if (inputUrlBatal) inputUrlBatal.value = urlBatal;
        bukaModal();
    });

    document.querySelectorAll('.btnEditProduk').forEach((btn) => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            judulModal.textContent = 'Perbarui Stok & Harga Produk (1x Pakai)';
            form.action = d.urlUpdate || '';
            methodInput.value = 'PUT';
            if (inputUrlAction) inputUrlAction.value = d.urlUpdate || '';
            if (inputUrlBatal) inputUrlBatal.value = d.urlBatal || '';
            inputNama.value = d.namaAsli || '';
            inputStok.value = d.stok || '0';
            inputHarga.value = d.harga || '0';
            bukaModal();
        });
    });

    btnBatalKunci?.addEventListener('click', () => {
        const urlBatal = inputUrlBatal?.value || '';
        if (urlBatal) aksesCtrl.kunciKembaliAkses(urlBatal);
        else tutupModal();
    });
});
