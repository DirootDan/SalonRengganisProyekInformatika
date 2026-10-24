document.addEventListener('DOMContentLoaded', () => {
    const searchInput     = document.getElementById('searchInput');
    const blokLayanan     = document.querySelectorAll('.opsi-blok');
    const checkboxes      = document.querySelectorAll('input[name="layanan[]"]');
    const submitBtn       = document.getElementById('submitBtn');
    const totalHargaEl    = document.getElementById('totalHarga');
    const jumlahPilihanEl = document.getElementById('jumlahPilihan');
    const infoValidasiEl  = document.getElementById('infoValidasi');
    const tidakDitemukan  = document.getElementById('tidakDitemukan');

    // 1. Fitur Pencarian Langsung
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const keyword = e.target.value.toLowerCase().trim();
            let jumlahTampil = 0;

            blokLayanan.forEach(blok => {
                const namaLayanan = (blok.dataset.nama || '').toLowerCase();
                const cocok = namaLayanan.includes(keyword);
                blok.style.display = cocok ? '' : 'none';
                if (cocok) jumlahTampil++;
            });

            if (tidakDitemukan) {
                tidakDitemukan.classList.toggle('tampil', jumlahTampil === 0);
            }
        });
    }

    // 2. Tampilkan pilihan petugas sesuai layanan yang dicentang
    const aturPilihanPetugas = (checkbox) => {
        const blok   = checkbox.closest('.opsi-blok');
        const select = blok ? blok.querySelector('.select-petugas') : null;

        if (blok) blok.classList.toggle('terpilih', checkbox.checked);

        if (select) {
            // Select yang dinonaktifkan tidak ikut terkirim ke server
            select.disabled = !checkbox.checked;
            select.required = checkbox.checked;
            if (!checkbox.checked) select.value = '';
        }
    };

    // 3. Kalkulasi Harga & Validasi Tombol
    const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(angka);

    const updateRingkasan = () => {
        let total = 0;
        let jumlah = 0;
        let petugasBelumDipilih = 0;

        checkboxes.forEach(checkbox => {
            if (!checkbox.checked) return;

            total += parseInt(checkbox.dataset.harga || 0, 10);
            jumlah++;

            const select = checkbox.closest('.opsi-blok')?.querySelector('.select-petugas');
            if (select && !select.value) petugasBelumDipilih++;
        });

        if (totalHargaEl) totalHargaEl.textContent = formatRupiah(total);
        if (jumlahPilihanEl) jumlahPilihanEl.textContent = `${jumlah} layanan dipilih`;

        const siap = jumlah > 0 && petugasBelumDipilih === 0;

        if (infoValidasiEl) {
            if (jumlah === 0) {
                infoValidasiEl.textContent = 'Pilih minimal satu layanan untuk melanjutkan.';
            } else if (petugasBelumDipilih > 0) {
                infoValidasiEl.textContent = `Masih ada ${petugasBelumDipilih} layanan yang belum ditentukan petugasnya.`;
            } else {
                infoValidasiEl.textContent = '';
            }
        }

        if (submitBtn) {
            submitBtn.disabled = !siap;
            submitBtn.style.opacity = siap ? '1' : '0.5';
        }
    };

    checkboxes.forEach(checkbox => {
        aturPilihanPetugas(checkbox);

        checkbox.addEventListener('change', () => {
            aturPilihanPetugas(checkbox);
            updateRingkasan();

            // Arahkan fokus ke daftar petugas agar pelanggan langsung memilih
            if (checkbox.checked) {
                const select = checkbox.closest('.opsi-blok')?.querySelector('.select-petugas');
                if (select) select.focus();
            }
        });
    });

    document.querySelectorAll('.select-petugas').forEach(select => {
        select.addEventListener('change', () => {
            select.closest('.opsi-blok')?.classList.remove('galat');
            updateRingkasan();
        });
    });

    updateRingkasan();
});
