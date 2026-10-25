document.addEventListener('DOMContentLoaded', () => {
    // 1. Navigasi Tab (Data Diri & Ganti Password)
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');

            tabBtns.forEach(b => b.classList.remove('aktif'));
            tabPanels.forEach(p => p.classList.remove('aktif'));

            btn.classList.add('aktif');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('aktif');
            }
        });
    });

    // 2. Live Preview Kartu Profil Samping saat mengetik Biodata
    const inputNama = document.getElementById('nama_cust');
    const inputEmail = document.getElementById('email');
    const inputTelp = document.getElementById('no_telp');

    const previewNama = document.getElementById('previewNama');
    const previewEmail = document.getElementById('previewEmail');
    const previewTelp = document.getElementById('previewTelp');
    // Hanya huruf inisial yang diubah, agar foto profil (<img>) tidak ikut terhapus
    const avatarHuruf = document.getElementById('avatarHuruf');

    if (inputNama && previewNama) {
        inputNama.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            previewNama.textContent = val || 'Nama Pelanggan';
            if (avatarHuruf) {
                avatarHuruf.textContent = val ? val.charAt(0).toUpperCase() : '?';
            }
        });
    }

    if (inputEmail && previewEmail) {
        inputEmail.addEventListener('input', (e) => {
            previewEmail.textContent = e.target.value.trim() || '-';
        });
    }

    if (inputTelp && previewTelp) {
        inputTelp.addEventListener('input', (e) => {
            previewTelp.textContent = e.target.value.trim() || '-';
        });
    }

    // 3. Tombol Tampilkan / Sembunyikan Password
    const toggleBtns = document.querySelectorAll('.toggle-pw');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const inputId = btn.getAttribute('data-input');
            const inputEl = document.getElementById(inputId);
            if (!inputEl) return;

            if (inputEl.type === 'password') {
                inputEl.type = 'text';
                btn.textContent = '🙈';
            } else {
                inputEl.type = 'password';
                btn.textContent = '👁️';
            }
        });
    });

    // 4. Indikator Kecocokan Password Baru & Konfirmasi
    const pwBaru = document.getElementById('password');
    const pwKonfirmasi = document.getElementById('password_confirmation');
    const pwMatchInfo = document.getElementById('pwMatchInfo');

    const cekKecocokanPassword = () => {
        if (!pwBaru || !pwKonfirmasi || !pwMatchInfo) return;

        const valBaru = pwBaru.value;
        const valKonf = pwKonfirmasi.value;

        if (!valKonf) {
            pwMatchInfo.textContent = '';
            pwMatchInfo.className = 'pw-match-info';
            return;
        }

        if (valBaru === valKonf) {
            pwMatchInfo.textContent = '✓ Konfirmasi kata sandi cocok';
            pwMatchInfo.className = 'pw-match-info cocok';
        } else {
            pwMatchInfo.textContent = '✕ Konfirmasi kata sandi belum cocok';
            pwMatchInfo.className = 'pw-match-info tidak-cocok';
        }
    };

    if (pwBaru && pwKonfirmasi) {
        pwBaru.addEventListener('input', cekKecocokanPassword);
        pwKonfirmasi.addEventListener('input', cekKecocokanPassword);
    }

    // 5. Foto Profil: pilih file, validasi di browser, dan preview sebelum disimpan
    const inputFoto = document.getElementById('inputFoto');
    const btnPilihFoto = document.getElementById('btnPilihFoto');
    const btnSimpanFoto = document.getElementById('btnSimpanFoto');
    const avatarImg = document.getElementById('avatarImg');

    if (inputFoto && btnPilihFoto && btnSimpanFoto && avatarImg && avatarHuruf) {
        // Simpan kondisi awal supaya bisa dikembalikan jika file yang dipilih tidak valid
        const srcAwal = avatarImg.getAttribute('src') || '';
        const adaFotoAwal = !avatarImg.hidden;
        let urlPreview = null;

        const resetPreview = () => {
            inputFoto.value = '';
            avatarImg.setAttribute('src', srcAwal);
            avatarImg.hidden = !adaFotoAwal;
            avatarHuruf.hidden = adaFotoAwal;
            btnSimpanFoto.hidden = true;
        };

        btnPilihFoto.addEventListener('click', () => inputFoto.click());

        inputFoto.addEventListener('change', () => {
            const file = inputFoto.files[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert('Format foto harus JPG, PNG, atau WEBP.');
                resetPreview();
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran foto maksimal 2 MB.');
                resetPreview();
                return;
            }

            if (urlPreview) URL.revokeObjectURL(urlPreview);
            urlPreview = URL.createObjectURL(file);

            avatarImg.src = urlPreview;
            avatarImg.hidden = false;
            avatarHuruf.hidden = true;
            btnSimpanFoto.hidden = false;
        });
    }
});