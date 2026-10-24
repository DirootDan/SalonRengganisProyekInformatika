document.addEventListener('DOMContentLoaded', () => {
    const modal         = document.getElementById('modalMetode');
    const judulModal    = document.getElementById('judulModalMetode');
    const form          = document.getElementById('formMetode');
    const methodInput   = document.getElementById('methodMetode');
    const btnTambah     = document.getElementById('btnTambahMetode');

    const inputNama     = document.getElementById('inputNamaMetode');
    const inputKategori = document.getElementById('inputKategoriMetode');
    const inputRek      = document.getElementById('inputRekMetode');
    const inputAn       = document.getElementById('inputAnMetode');

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Metode Pembayaran';
        form.reset();
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditMetode').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Ubah Rincian Pembayaran';
            form.action            = btn.dataset.urlUpdate;
            methodInput.value      = 'PUT';
            inputNama.value        = btn.dataset.nama || '';
            inputKategori.value    = btn.dataset.kategori || 'Transfer Bank';
            inputRek.value         = btn.dataset.rek || '';
            inputAn.value          = btn.dataset.an || '';
            modal.classList.add('tampil');
        });
    });
});
