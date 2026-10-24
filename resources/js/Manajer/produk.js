document.addEventListener('DOMContentLoaded', () => {
    const cariInput   = document.getElementById('cariProduk');
    const filterStok  = document.getElementById('filterStok');
    const barisProduk = document.querySelectorAll('.baris-produk');

    const modal       = document.getElementById('modalProduk');
    const judulModal  = document.getElementById('judulModalProduk');
    const form        = document.getElementById('formProduk');
    const btnTambah   = document.getElementById('btnTambahProduk');

    const inputNama   = document.getElementById('inputNamaProduk');
    const inputStok   = document.getElementById('inputStokProduk');
    const inputHarga  = document.getElementById('inputHargaProduk');

    const saringProduk = () => {
        const kataKunci = (cariInput?.value || '').toLowerCase().trim();
        const status    = filterStok?.value || '';

        barisProduk.forEach(baris => {
            const cocokNama   = (baris.dataset.nama || '').includes(kataKunci);
            const cocokStatus = !status || baris.dataset.status === status;
            baris.style.display = (cocokNama && cocokStatus) ? '' : 'none';
        });
    };

    cariInput?.addEventListener('input', saringProduk);
    filterStok?.addEventListener('change', saringProduk);

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Produk Baru';
        form.reset();
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditProduk').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Edit Stok & Harga Produk';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputStok.value        = btn.dataset.stok || '0';
            inputHarga.value       = btn.dataset.harga || '0';
            modal.classList.add('tampil');
        });
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        modal.classList.remove('tampil');
        window.tampilkanToast(`Produk "${inputNama.value}" berhasil disimpan.`);
    });

    document.querySelectorAll('.btnHapusProduk').forEach(btn => {
        btn.addEventListener('click', () => {
            const nama = btn.dataset.namaAsli;
            if (confirm(`Hapus produk "${nama}" dari katalog?`)) {
                window.tampilkanToast(`Produk "${nama}" berhasil dihapus.`);
            }
        });
    });
});
