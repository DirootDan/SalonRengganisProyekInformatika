document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const checkboxes = document.querySelectorAll('input[name="layanan[]"]');
    const submitBtn = document.getElementById('submitBtn');

    // Validasi interaktif: tombol disable jika tidak ada layanan dipilih
    const checkValidation = () => {
        const isChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        submitBtn.disabled = !isChecked;
        submitBtn.style.opacity = isChecked ? '1' : '0.5';
    };

    checkboxes.forEach(box => {
        box.addEventListener('change', checkValidation);
    });

    // Jalankan pengecekan pertama kali saat halaman dimuat
    checkValidation();
});