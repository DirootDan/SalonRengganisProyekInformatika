document.addEventListener('DOMContentLoaded', () => {
    const cariInput     = document.getElementById('cariLayanan');
    const filterPetugas = document.getElementById('filterPetugas');
    const barisLayanan  = document.querySelectorAll('.baris-layanan');

    const modal          = document.getElementById('modalLayanan');
    const judulModal     = document.getElementById('judulModalLayanan');
    const form           = document.getElementById('formLayanan');
    const methodInput    = document.getElementById('methodLayanan');
    const btnTambah      = document.getElementById('btnTambahLayanan');

    const inputNama      = document.getElementById('inputNamaLayanan');
    const inputKategori  = document.getElementById('inputKategoriLayanan');
    const inputJabatan   = document.getElementById('inputJabatanLayanan');
    const inputHarga     = document.getElementById('inputHargaLayanan');
    const inputDurasi    = document.getElementById('inputDurasiLayanan');
    const inputJeda      = document.getElementById('inputJedaLayanan');
    const inputEksklusif = document.getElementById('inputEksklusifLayanan');

    inputKategori?.addEventListener('change', () => {
        if (inputKategori.value.includes('Wajah')) {
            inputJabatan.value = 'Therapist';
        } else {
            inputJabatan.value = 'Stylist';
        }
    });

    const saringLayanan = () => {
        const kataKunci = (cariInput?.value || '').toLowerCase().trim();
        const jabatan   = filterPetugas?.value || '';

        barisLayanan.forEach(baris => {
            const cocokNama    = (baris.dataset.nama || '').includes(kataKunci);
            const cocokJabatan = !jabatan || baris.dataset.jabatan === jabatan;
            baris.style.display = (cocokNama && cocokJabatan) ? '' : 'none';
        });
    };

    cariInput?.addEventListener('input', saringLayanan);
    filterPetugas?.addEventListener('change', saringLayanan);

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Layanan Baru';
        form.reset();
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        if (inputDurasi) inputDurasi.value = '';
        if (inputJeda) inputJeda.value = '15';
        if (inputEksklusif) inputEksklusif.value = '1';
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditLayanan').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Ubah Layanan';
            form.action            = btn.dataset.urlUpdate;
            methodInput.value      = 'PUT';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputKategori.value    = btn.dataset.kategori || 'Perawatan Rambut';
            inputJabatan.value     = btn.dataset.jabatan || 'Stylist';
            inputHarga.value       = btn.dataset.harga || '';
            if (inputDurasi) inputDurasi.value = btn.dataset.durasi || '60';
            if (inputJeda) inputJeda.value = btn.dataset.jeda || '15';
            if (inputEksklusif) inputEksklusif.value = btn.dataset.eksklusif || '1';
            modal.classList.add('tampil');
        });
    });
});
