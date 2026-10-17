document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");
    const submitBtn = document.getElementById("submitBtn");

    if (loginForm) {
        loginForm.addEventListener("submit", function() {
            // Mencegah double click dan mengubah status tombol
            submitBtn.innerHTML = "MEMPROSES...";
            submitBtn.style.opacity = "0.7";
            submitBtn.style.cursor = "not-allowed";
            submitBtn.disabled = true; // Menonaktifkan tombol sementara
        });
    }
});