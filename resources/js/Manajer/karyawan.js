document.addEventListener('DOMContentLoaded', () => {
    const cariInput     = document.getElementById('cariKaryawan');
    const filterJabatan = document.getElementById('filterJabatan');
    const barisStaf     = document.querySelectorAll('.baris-karyawan');

    const modal        = document.getElementById('modalKaryawan');
    const judulModal   = document.getElementById('judulModalKaryawan');
    const form         = document.getElementById('formKaryawan');
    const methodInput  = document.getElementById('methodKaryawan');
    const btnTambah    = document.getElementById('btnTambahKaryawan');

    const inputNama     = document.getElementById('inputNamaStaf');
    const inputUsername = document.getElementById('inputUsernameStaf');
    const inputEmail    = document.getElementById('inputEmailStaf');
    const inputJabatan  = document.getElementById('inputJabatanStaf');
    const inputKomisi   = document.getElementById('inputKomisiStaf');
    const inputAktif    = document.getElementById('inputAktifStaf');
    const inputPassword = document.getElementById('inputPasswordStaf');

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

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Karyawan Baru';
        form.reset();
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        inputPassword.required = true;
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditKaryawan').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent  = 'Ubah Data Karyawan';
            form.action             = btn.dataset.urlUpdate;
            methodInput.value       = 'PUT';
            inputNama.value         = btn.dataset.namaAsli || '';
            inputUsername.value     = btn.dataset.username || '';
            inputEmail.value        = btn.dataset.email || '';
            inputJabatan.value      = btn.dataset.jabatan || 'Stylist';
            inputKomisi.value       = btn.dataset.komisi || '15';
            inputAktif.value        = btn.dataset.aktif || '1';
            inputPassword.value     = '';
            inputPassword.required  = false;
            modal.classList.add('tampil');
        });
    });

    document.querySelectorAll('.form-hapus-staf').forEach(f => {
        f.addEventListener('submit', (e) => {
            const nama = f.dataset.nama;
            if (!confirm(`Apakah Anda yakin ingin menonaktifkan/menghapus karyawan "${nama}"?`)) {
                e.preventDefault();
            }
        });
    });
});
