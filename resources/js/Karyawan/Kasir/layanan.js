import { inisialisasiModalAksesKatalog } from './katalog-akses';

document.addEventListener('DOMContentLoaded', () => {
    const aksesCtrl = inisialisasiModalAksesKatalog();

    const inputCari = document.getElementById('cariLayanan');
    const selectPetugas = document.getElementById('filterPetugas');
    const barisLayanan = document.querySelectorAll('.baris-layanan');

    const saringTabel = () => {
        const kataKunci = (inputCari?.value || '').toLowerCase().trim();
        const petugas = selectPetugas?.value || '';

        barisLayanan.forEach((baris) => {
            const cocokNama = (baris.dataset.nama || '').includes(kataKunci);
            const cocokPetugas = petugas === '' || (baris.dataset.jabatan || '') === petugas;
            baris.style.display = (cocokNama && cocokPetugas) ? '' : 'none';
        });
    };

    inputCari?.addEventListener('input', saringTabel);
    selectPetugas?.addEventListener('change', saringTabel);

    const modal = document.getElementById('modalLayanan');
    const btnTambah = document.getElementById('btnTambahLayanan');
    const judulModal = document.getElementById('judulModalLayanan');
    const form = document.getElementById('formLayanan');
    const methodInput = document.getElementById('methodLayanan');
    const inputUrlAction = document.getElementById('inputUrlActionLayanan');
    const inputUrlBatal = document.getElementById('inputUrlBatalLayanan');
    const btnBatalKunci = document.getElementById('btnBatalKunciLayanan');

    const inputNama = document.getElementById('inputNamaLayanan');
    const inputJabatan = document.getElementById('inputJabatanLayanan');
    const inputHarga = document.getElementById('inputHargaLayanan');
    const inputDurasi = document.getElementById('inputDurasiLayanan');
    const inputJeda = document.getElementById('inputJedaLayanan');

    const bukaModal = () => modal?.classList.add('tampil');
    const tutupModal = () => modal?.classList.remove('tampil');

    btnTambah?.addEventListener('click', () => {
        const urlStore = btnTambah.dataset.urlStore || '';
        const urlBatal = btnTambah.dataset.urlBatal || '';
        judulModal.textContent = 'Tambah Layanan Baru (1x Pakai)';
        form.reset();
        form.action = urlStore;
        methodInput.value = 'POST';
        if (inputUrlAction) inputUrlAction.value = urlStore;
        if (inputUrlBatal) inputUrlBatal.value = urlBatal;
        if (inputDurasi) inputDurasi.value = '';
        if (inputJeda) inputJeda.value = '15';
        bukaModal();
    });

    document.querySelectorAll('.btnEditLayanan').forEach((btn) => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            judulModal.textContent = 'Perbarui Data Layanan (1x Pakai)';
            form.action = d.urlUpdate || '';
            methodInput.value = 'PUT';
            if (inputUrlAction) inputUrlAction.value = d.urlUpdate || '';
            if (inputUrlBatal) inputUrlBatal.value = d.urlBatal || '';
            inputNama.value = d.namaAsli || '';

            const kategoriData = d.kategori || '';
            const adaOpsi = Array.from(inputJabatan.options).some(opt => opt.value === kategoriData);
            inputJabatan.value = adaOpsi
                ? kategoriData
                : (kategoriData.toLowerCase().includes('rambut') ? 'Perawatan Rambut' : 'Perawatan Wajah & Tubuh');

            inputHarga.value = d.harga || '';
            if (inputDurasi) inputDurasi.value = d.durasi || '60';
            if (inputJeda) inputJeda.value = d.jeda || '15';
            bukaModal();
        });
    });

    btnBatalKunci?.addEventListener('click', () => {
        const urlBatal = inputUrlBatal?.value || '';
        if (urlBatal) aksesCtrl.kunciKembaliAkses(urlBatal);
        else tutupModal();
    });

    document.querySelectorAll('[data-tutup-modal]').forEach(btn => btn.addEventListener('click', tutupModal));
    modal?.addEventListener('click', (e) => { if (e.target === modal) tutupModal(); });
});
