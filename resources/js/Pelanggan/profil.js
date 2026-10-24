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
    const avatarInisial = document.getElementById('avatarInisial');

    if (inputNama && previewNama) {
        inputNama.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            previewNama.textContent = val || 'Nama Pelanggan';
            if (avatarInisial) {
                avatarInisial.textContent = val ? val.charAt(0).toUpperCase() : '?';
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
            pwMatchInfo.textContent = '✓ Konfirmasi password cocok';
            pwMatchInfo.className = 'pw-match-info cocok';
        } else {
            pwMatchInfo.textContent = '✕ Konfirmasi password belum cocok';
            pwMatchInfo.className = 'pw-match-info tidak-cocok';
        }
    };

    if (pwBaru && pwKonfirmasi) {
        pwBaru.addEventListener('input', cekKecocokanPassword);
        pwKonfirmasi.addEventListener('input', cekKecocokanPassword);
    }
});
