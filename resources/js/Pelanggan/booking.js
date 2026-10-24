document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const opsiItems = document.querySelectorAll('.opsi');
    const checkboxes = document.querySelectorAll('input[name="layanan[]"]');
    const submitBtn = document.getElementById('submitBtn');
    const totalHargaEl = document.getElementById('totalHarga');
    const jumlahPilihanEl = document.getElementById('jumlahPilihan');

    // 1. Fitur Live Search
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase();
            
            opsiItems.forEach(item => {
                const namaLayanan = item.getAttribute('data-nama').toLowerCase();
                if (namaLayanan.includes(keyword)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // 2. Kalkulasi Harga & Validasi Tombol
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    const updateRingkasan = () => {
        let total = 0;
        let count = 0;
        let isChecked = false;

        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                isChecked = true;
                total += parseInt(checkbox.getAttribute('data-harga') || 0);
                count++;
            }
        });

        if (totalHargaEl) totalHargaEl.textContent = formatRupiah(total);
        if (jumlahPilihanEl) jumlahPilihanEl.textContent = `${count} Layanan Dipilih`;

        if (submitBtn) {
            submitBtn.disabled = !isChecked;
            submitBtn.style.opacity = isChecked ? '1' : '0.5';
        }
    };

    checkboxes.forEach(box => {
        box.addEventListener('change', updateRingkasan);
    });

    updateRingkasan();
});