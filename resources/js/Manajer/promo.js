document.addEventListener('DOMContentLoaded', () => {
    const cariInput   = document.getElementById('cariPromo');
    const filterStatus= document.getElementById('filterStatusPromo');
    const barisPromo  = document.querySelectorAll('.baris-promo');

    const modal       = document.getElementById('modalPromo');
    const judulModal  = document.getElementById('judulModalPromo');
    const form        = document.getElementById('formPromo');
    const btnTambah   = document.getElementById('btnTambahPromo');

    const inputKode   = document.getElementById('inputKodePromo');
    const inputNama   = document.getElementById('inputNamaPromo');
    const inputTipe   = document.getElementById('inputTipePromo');
    const inputNilai  = document.getElementById('inputNilaiPromo');
    const inputMin    = document.getElementById('inputMinPromo');
    const inputSampai = document.getElementById('inputSampaiPromo');
    const inputStatus = document.getElementById('inputStatusPromo');

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
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditPromo').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Edit Program Promo';
            inputKode.value        = btn.dataset.kode || '';
            inputNama.value        = btn.dataset.namaAsli || '';
            inputTipe.value        = btn.dataset.tipe || 'Persentase';
            inputNilai.value       = btn.dataset.nilai || '';
            inputMin.value         = btn.dataset.min || '';
            inputSampai.value      = btn.dataset.sampai || '';
            inputStatus.value      = btn.dataset.status || 'Aktif';
            modal.classList.add('tampil');
        });
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        modal.classList.remove('tampil');
        window.tampilkanToast(`Promo "${inputKode.value.toUpperCase()}" berhasil disimpan.`);
    });

    document.querySelectorAll('.btnHapusPromo').forEach(btn => {
        btn.addEventListener('click', () => {
            const kode = btn.dataset.kode;
            if (confirm(`Hapus voucer promo "${kode}"?`)) {
                window.tampilkanToast(`Voucer promo "${kode}" berhasil dihapus.`);
            }
        });
    });
});
