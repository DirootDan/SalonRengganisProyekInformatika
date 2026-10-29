document.addEventListener('DOMContentLoaded', () => {
    const cariInput    = document.getElementById('cariPromo');
    const filterStatus = document.getElementById('filterStatusPromo');
    const barisPromo   = document.querySelectorAll('.baris-promo');

    const modal        = document.getElementById('modalPromo');
    const judulModal   = document.getElementById('judulModalPromo');
    const form         = document.getElementById('formPromo');
    const methodInput  = document.getElementById('methodPromo');
    const btnTambah    = document.getElementById('btnTambahPromo');

    const inputKode    = document.getElementById('inputKodePromo');
    const inputNama    = document.getElementById('inputNamaPromo');
    const inputTipe    = document.getElementById('inputTipePromo');
    const inputNilai   = document.getElementById('inputNilaiPromo');
    const inputMin     = document.getElementById('inputMinPromo');
    const inputSampai  = document.getElementById('inputSampaiPromo');
    const inputStatus  = document.getElementById('inputStatusPromo');

    const saringPromo = () => {
        const kataKunci = (cariInput?.value || '').toLowerCase().trim();
        const status    = filterStatus?.value || '';

        barisPromo.forEach(baris => {
            const cocokNama   = (baris.dataset.nama || '').includes(kataKunci);
            const cocokStatus = !status || baris.dataset.status === status;
            baris.style.display = (cocokNama && cocokStatus) ? '' : 'none';
        });
    };

    cariInput?.addEventListener('input', saringPromo);
    filterStatus?.addEventListener('change', saringPromo);

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Buat Program Promo Baru';
        form.reset();
        form.action       = btnTambah.dataset.urlSimpan;
        methodInput.value = 'POST';
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditPromo').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Ubah Program Promo';
            form.action            = btn.dataset.urlUpdate;
            methodInput.value      = 'PUT';
            inputKode.value        = btn.dataset.kode || '';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputTipe.value        = btn.dataset.tipe || 'Persentase';
            inputNilai.value       = btn.dataset.nilai || '';
            inputMin.value         = btn.dataset.min || '';
            inputSampai.value      = btn.dataset.sampai || '';
            inputStatus.value      = btn.dataset.aktif || '1';
            modal.classList.add('tampil');
        });
    });
});
