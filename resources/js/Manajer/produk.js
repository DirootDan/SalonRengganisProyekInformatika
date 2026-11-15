import { bukaModalAksesibel, perbaruiEmptyStateTabel } from '../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    const cariInput   = document.getElementById('cariProduk');
    const filterStok  = document.getElementById('filterStok');
    const barisProduk = document.querySelectorAll('.baris-produk');
    const tbodyProduk = barisProduk[0]?.closest('tbody') || null;

    const modal       = document.getElementById('modalProduk');
    const judulModal  = document.getElementById('judulModalProduk');
    const form        = document.getElementById('formProduk');
    const methodInput = document.getElementById('methodProduk');
    const btnTambah   = document.getElementById('btnTambahProduk');

    const inputNama   = document.getElementById('inputNamaProduk');
    const inputStok   = document.getElementById('inputStokProduk');
    const inputHarga  = document.getElementById('inputHargaProduk');

    const saringProduk = () => {
        const kataKunci = (cariInput?.value || '').toLowerCase().trim();
        const status    = filterStok?.value || '';
        let jumlahTampak = 0;

        barisProduk.forEach(baris => {
            const cocokNama   = (baris.dataset.nama || '').includes(kataKunci);
            const cocokStatus = !status || baris.dataset.status === status;
            const tampil      = cocokNama && cocokStatus;
            baris.style.display = tampil ? '' : 'none';
            if (tampil) jumlahTampak += 1;
        });

        if (barisProduk.length > 0) {
            perbaruiEmptyStateTabel(tbodyProduk, jumlahTampak, 'Tidak ada produk yang cocok dengan pencarian atau filter.');
        }
    };

    cariInput?.addEventListener('input', saringProduk);
    filterStok?.addEventListener('change', saringProduk);

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Produk Baru';
        form.reset();
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        bukaModalAksesibel(modal, inputNama);
    });

    document.querySelectorAll('.btnEditProduk').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Ubah Stok & Harga Produk';
            form.action            = btn.dataset.urlUpdate;
            methodInput.value      = 'PUT';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputStok.value        = btn.dataset.stok || '0';
            inputHarga.value       = btn.dataset.harga || '0';
            bukaModalAksesibel(modal, inputNama);
        });
    });
});
