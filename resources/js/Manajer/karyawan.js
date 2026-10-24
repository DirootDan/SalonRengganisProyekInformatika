document.addEventListener('DOMContentLoaded', () => {
    const cariInput     = document.getElementById('cariKaryawan');
    const filterJabatan = document.getElementById('filterJabatan');
    const barisStaf     = document.querySelectorAll('.baris-karyawan');

    const modal       = document.getElementById('modalKaryawan');
    const judulModal  = document.getElementById('judulModalKaryawan');
    const form        = document.getElementById('formKaryawan');
    const btnTambah   = document.getElementById('btnTambahKaryawan');

    const inputNama     = document.getElementById('inputNamaStaf');
    const inputUsername = document.getElementById('inputUsernameStaf');
    const inputEmail    = document.getElementById('inputEmailStaf');
    const inputJabatan  = document.getElementById('inputJabatanStaf');
    const inputPassword = document.getElementById('inputPasswordStaf');

    // 1. Pencarian & Filter Jabatan
    const saringTabel = () => {
        const kataKunci = (cariInput?.value || '').toLowerCase().trim();
        const jabatan   = filterJabatan?.value || '';

        barisStaf.forEach(baris => {
            const cocokNama    = (baris.dataset.nama || '').includes(kataKunci);
            const cocokJabatan = !jabatan || baris.dataset.jabatan === jabatan;
            baris.style.display = (cocokNama && cocokJabatan) ? '' : 'none';
        });
    };

    cariInput?.addEventListener('input', saringTabel);
    filterJabatan?.addEventListener('change', saringTabel);

    // 2. Buka Modal Tambah
    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Karyawan Baru';
        form.reset();
        modal.classList.add('tampil');
    });

    // 3. Buka Modal Edit
    document.querySelectorAll('.btnEditKaryawan').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent  = 'Edit Data Karyawan';
            inputNama.value         = btn.dataset.namaAsli || '';
            inputUsername.value     = btn.dataset.username || '';
            inputEmail.value        = btn.dataset.email || '';
            inputJabatan.value      = btn.dataset.jabatan || 'Stylist';
            inputPassword.value     = '';
            modal.classList.add('tampil');
        });
    });

    // 4. Simulasi Simpan & Hapus
    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        modal.classList.remove('tampil');
        window.tampilkanToast(`Data karyawan "${inputNama.value}" berhasil disimpan.`);
    });

    document.querySelectorAll('.btnHapusKaryawan').forEach(btn => {
        btn.addEventListener('click', () => {
            const nama = btn.dataset.namaAsli;
            if (confirm(`Apakah Anda yakin ingin menghapus karyawan "${nama}"?`)) {
                window.tampilkanToast(`Karyawan "${nama}" telah dihapus dari daftar.`);
            }
        });
    });
});
