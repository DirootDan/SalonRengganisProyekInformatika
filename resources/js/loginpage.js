document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    if (!loginForm || !submitBtn) return;

    const defaultText = submitBtn.innerHTML;

    loginForm.addEventListener('submit', (e) => {
        setTimeout(() => {
            if (e.defaultPrevented || submitBtn.disabled) return;
            submitBtn.innerHTML = 'MEMPROSES...';
            submitBtn.disabled = true;
        }, 0);
    });

    window.addEventListener('pageshow', () => {
        submitBtn.innerHTML = defaultText;
        submitBtn.disabled = false;
    });
});