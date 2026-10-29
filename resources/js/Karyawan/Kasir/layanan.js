document.addEventListener('DOMContentLoaded', () => {
    const inputCari = document.getElementById('cariLayanan');
    const selectPetugas = document.getElementById('filterPetugas');
    const barisLayanan = document.querySelectorAll('.baris-layanan');

    function saringTabel() {
        const kataKunci = (inputCari?.value || '').toLowerCase().trim();
        const petugas = selectPetugas?.value || '';

        barisLayanan.forEach((baris) => {
            const nama = baris.getAttribute('data-nama') || '';
            const jabatan = baris.getAttribute('data-jabatan') || '';

            const cocokNama = nama.includes(kataKunci);
            const cocokPetugas = petugas === '' || jabatan === petugas;

            baris.style.display = (cocokNama && cocokPetugas) ? '' : 'none';
        });
    }

    if (inputCari) inputCari.addEventListener('input', saringTabel);
    if (selectPetugas) selectPetugas.addEventListener('change', saringTabel);

    const modal = document.getElementById('modalLayanan');
    const btnTambah = document.getElementById('btnTambahLayanan');
    const judulModal = document.getElementById('judulModalLayanan');
    const form = document.getElementById('formLayanan');
    const methodInput = document.getElementById('methodLayanan');

    const inputNama = document.getElementById('inputNamaLayanan');
    const inputJabatan = document.getElementById('inputJabatanLayanan');
    const inputHarga = document.getElementById('inputHargaLayanan');
    const inputDurasi = document.getElementById('inputDurasiLayanan');
    const inputJeda = document.getElementById('inputJedaLayanan');

    function bukaModal() {
        if (modal) modal.classList.add('tampil');
    }

    function tutupModal() {
        if (modal) modal.classList.remove('tampil');
    }

    if (btnTambah) {
        btnTambah.addEventListener('click', () => {
            judulModal.textContent = 'Tambah Layanan Baru';
            form.reset();
            form.action = btnTambah.getAttribute('data-url-store');
            methodInput.value = 'POST';
            if (inputDurasi) inputDurasi.value = '';
            if (inputJeda) inputJeda.value = '15';
            bukaModal();
        });
    }

    document.querySelectorAll('.btnEditLayanan').forEach((btn) => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Perbarui Data Layanan';
            form.action = btn.getAttribute('data-url-update');
            methodInput.value = 'PUT';
            inputNama.value = btn.getAttribute('data-nama-asli') || '';
            inputJabatan.value = btn.getAttribute('data-kategori') || 'Rambut';
            inputHarga.value = btn.getAttribute('data-harga') || '';
            if (inputDurasi) inputDurasi.value = btn.getAttribute('data-durasi') || '60';
            if (inputJeda) inputJeda.value = btn.getAttribute('data-jeda') || '15';
            bukaModal();
        });
    });

    document.querySelectorAll('[data-tutup-modal]').forEach((btn) => {
        btn.addEventListener('click', tutupModal);
    });
});
