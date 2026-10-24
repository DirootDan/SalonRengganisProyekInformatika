document.addEventListener('DOMContentLoaded', () => {
    const cariInput   = document.getElementById('cariProduk');
    const filterStok  = document.getElementById('filterStok');
    const barisProduk = document.querySelectorAll('.baris-produk');

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
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditProduk').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Ubah Stok & Harga Produk';
            form.action            = btn.dataset.urlUpdate;
            methodInput.value      = 'PUT';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputStok.value        = btn.dataset.stok || '0';
            inputHarga.value       = btn.dataset.harga || '0';
            modal.classList.add('tampil');
        });
    });

    document.querySelectorAll('.form-hapus-produk').forEach(f => {
        f.addEventListener('submit', (e) => {
            const nama = f.dataset.nama;
            if (!confirm(`Hapus produk "${nama}" dari katalog?`)) {
                e.preventDefault();
            }
        });
    });
});
