document.addEventListener('DOMContentLoaded', () => {
    const modal         = document.getElementById('modalMetode');
    const judulModal    = document.getElementById('judulModalMetode');
    const form          = document.getElementById('formMetode');
    const btnTambah     = document.getElementById('btnTambahMetode');

    const inputNama     = document.getElementById('inputNamaMetode');
    const inputKategori = document.getElementById('inputKategoriMetode');
    const inputRek      = document.getElementById('inputRekMetode');
    const inputAn       = document.getElementById('inputAnMetode');

    document.querySelectorAll('.toggle-metode').forEach(chk => {
        chk.addEventListener('change', () => {
            const kartu = chk.closest('.kartu-metode');
            const teks  = kartu?.querySelector('.status-teks');
            const aktif = chk.checked;

            if (kartu) {
                kartu.classList.toggle('aktif', aktif);
                kartu.classList.toggle('nonaktif', !aktif);
            }
            if (teks) {
                teks.textContent = aktif ? '● Aktif di Kasir' : '○ Nonaktif';
            }

            window.tampilkanToast(
                `Metode "${chk.dataset.nama}" ${aktif ? 'diaktifkan' : 'dinonaktifkan'}.`
            );
        });
    });

    btnTambah?.addEventListener('click', () => {
        judulModal.textContent = 'Tambah Metode Pembayaran';
        form.reset();
        modal.classList.add('tampil');
    });

    document.querySelectorAll('.btnEditMetode').forEach(btn => {
        btn.addEventListener('click', () => {
            judulModal.textContent = 'Edit Rincian Pembayaran';
            inputNama.value        = btn.dataset.nama || '';
            inputKategori.value    = btn.dataset.kategori || 'Transfer Bank';
            inputRek.value         = btn.dataset.rek || '';
            inputAn.value          = btn.dataset.an || '';
            modal.classList.add('tampil');
        });
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        modal.classList.remove('tampil');
        window.tampilkanToast(`Pengaturan metode "${inputNama.value}" berhasil disimpan.`);
    });
});
