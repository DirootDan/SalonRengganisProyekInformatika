import { bukaModalAksesibel, perbaruiEmptyStateTabel } from '../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    const cariInput     = document.getElementById('cariKaryawan');
    const filterJabatan = document.getElementById('filterJabatan');
    const barisStaf     = document.querySelectorAll('.baris-karyawan');
    const tbodyStaf     = barisStaf[0]?.closest('tbody') || null;

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
        let jumlahTampak = 0;

        barisStaf.forEach(baris => {
            const cocokNama    = (baris.dataset.nama || '').includes(kataKunci);
            const cocokJabatan = !jabatan || baris.dataset.jabatan === jabatan;
            const tampil       = cocokNama && cocokJabatan;
            baris.style.display = tampil ? '' : 'none';
            if (tampil) jumlahTampak += 1;
        });

        if (barisStaf.length > 0) {
            perbaruiEmptyStateTabel(tbodyStaf, jumlahTampak, 'Tidak ada karyawan yang cocok dengan pencarian atau filter.');
        }
    };

    cariInput?.addEventListener('input', saringTabel);
    filterJabatan?.addEventListener('change', saringTabel);

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Karyawan Baru';
        form.reset();
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        inputPassword.required = true;
        bukaModalAksesibel(modal, inputNama);
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
            bukaModalAksesibel(modal, inputNama);
        });
    });
});
