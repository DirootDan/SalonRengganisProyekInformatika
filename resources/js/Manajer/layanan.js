document.addEventListener('DOMContentLoaded', () => {
    const cariInput     = document.getElementById('cariLayanan');
    const filterPetugas = document.getElementById('filterPetugas');
    const barisLayanan  = document.querySelectorAll('.baris-layanan');

    const modal       = document.getElementById('modalLayanan');
    const judulModal  = document.getElementById('judulModalLayanan');
    const form        = document.getElementById('formLayanan');
    const btnTambah   = document.getElementById('btnTambahLayanan');

    const inputNama    = document.getElementById('inputNamaLayanan');
    const inputJabatan = document.getElementById('inputJabatanLayanan');
    const inputHarga   = document.getElementById('inputHargaLayanan');

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
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditLayanan').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Edit Layanan';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputJabatan.value     = btn.dataset.jabatan || 'Stylist';
            inputHarga.value       = btn.dataset.harga || '';
            modal.classList.add('tampil');
        });
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        modal.classList.remove('tampil');
        window.tampilkanToast(`Layanan "${inputNama.value}" berhasil disimpan.`);
    });

    document.querySelectorAll('.btnHapusLayanan').forEach(btn => {
        btn.addEventListener('click', () => {
            const nama = btn.dataset.namaAsli;
            if (confirm(`Hapus layanan "${nama}" dari daftar?`)) {
                window.tampilkanToast(`Layanan "${nama}" berhasil dihapus.`);
            }
        });
    });
});
