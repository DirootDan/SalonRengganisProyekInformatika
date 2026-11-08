const TEKS_PROSES = 'Memproses...';

const kunciTombol = (tombol) => {
    if (tombol.dataset.teksAsli === undefined) {
        tombol.dataset.teksAsli = tombol.innerHTML;
    }
    tombol.disabled = true;
    tombol.innerHTML = TEKS_PROSES;
};

const pulihkanTombol = (tombol) => {
    if (tombol.dataset.teksAsli === undefined) return;
    tombol.innerHTML = tombol.dataset.teksAsli;
    tombol.disabled = false;
    delete tombol.dataset.teksAsli;
};

const tombolPengirim = (form, submitter) => {
    if (submitter && submitter.form === form) return submitter;
    return form.querySelector('button[type="submit"], input[type="submit"]');
};

// Pengecekan ditunda satu tick agar semua handler lain (validasi, modal konfirmasi) selesai lebih dulu.
// Jika form dibatalkan (validasi gagal / menunggu konfirmasi), tombol tidak dikunci.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.method.toLowerCase() === 'get' || form.hasAttribute('data-tanpa-loading')) return;

    const tombol = tombolPengirim(form, e.submitter);
    if (!tombol) return;

    setTimeout(() => {
        if (e.defaultPrevented || tombol.disabled) return;
        kunciTombol(tombol);
    }, 0);
});

// Tombol "Kembali" browser: pastikan tombol tidak terjebak dalam status memproses.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[data-teks-asli]').forEach(pulihkanTombol);
});
