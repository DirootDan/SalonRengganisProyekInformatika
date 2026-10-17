document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");
    const submitBtn = document.getElementById("submitBtn");

    if (!loginForm || !submitBtn) return;

    const defaultText = submitBtn.innerHTML;

    loginForm.addEventListener("submit", function () {
        
        submitBtn.innerHTML = "MEMPROSES...";
        submitBtn.disabled = true;
    });


    window.addEventListener("pageshow", function () {
        submitBtn.innerHTML = defaultText;
        submitBtn.disabled = false;
    });
});