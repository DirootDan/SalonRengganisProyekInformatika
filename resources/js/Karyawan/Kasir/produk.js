document.addEventListener('DOMContentLoaded', () => {
    const inputCari = document.getElementById('cariProduk');
    const selectStok = document.getElementById('filterStok');
    const barisProduk = document.querySelectorAll('.baris-produk');

    function saringProduk() {
        const kataKunci = (inputCari?.value || '').toLowerCase().trim();
        const status = selectStok?.value || '';

        barisProduk.forEach((baris) => {
            const nama = baris.getAttribute('data-nama') || '';
            const st = baris.getAttribute('data-status') || '';

            const cocokNama = nama.includes(kataKunci);
            const cocokStatus = status === '' || st === status;

            baris.style.display = (cocokNama && cocokStatus) ? '' : 'none';
        });
    }

    if (inputCari) inputCari.addEventListener('input', saringProduk);
    if (selectStok) selectStok.addEventListener('change', saringProduk);

    const modal = document.getElementById('modalProduk');
    const btnTambah = document.getElementById('btnTambahProduk');
    const judulModal = document.getElementById('judulModalProduk');
    const form = document.getElementById('formProduk');
    const methodInput = document.getElementById('methodProduk');

    const inputNama = document.getElementById('inputNamaProduk');
    const inputStok = document.getElementById('inputStokProduk');
    const inputHarga = document.getElementById('inputHargaProduk');

    function bukaModal() {
        if (modal) modal.classList.add('tampil');
    }

    function tutupModal() {
        if (modal) modal.classList.remove('tampil');
    }

    if (btnTambah) {
        btnTambah.addEventListener('click', () => {
            judulModal.textContent = 'Tambah Produk Baru';
            form.reset();
            form.action = btnTambah.getAttribute('data-url-store');
            methodInput.value = 'POST';
            bukaModal();
        });
    }

    document.querySelectorAll('.btnEditProduk').forEach((btn) => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Perbarui Stok & Harga Produk';
            form.action = btn.getAttribute('data-url-update');
            methodInput.value = 'PUT';
            inputNama.value = btn.getAttribute('data-nama-asli') || '';
            inputStok.value = btn.getAttribute('data-stok') || '0';
            inputHarga.value = btn.getAttribute('data-harga') || '0';
            bukaModal();
        });
    });

    document.querySelectorAll('[data-tutup-modal]').forEach((btn) => {
        btn.addEventListener('click', tutupModal);
    });

    document.querySelectorAll('.formHapusProduk').forEach((f) => {
        f.addEventListener('submit', (e) => {
            const nama = f.getAttribute('data-nama') || 'produk ini';
            if (!confirm(`Apakah Anda yakin ingin menghapus produk "${nama}"?`)) {
                e.preventDefault();
            }
        });
    });
});
