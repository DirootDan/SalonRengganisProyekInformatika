export const buatPengelolaJadwalAjax = ({
    formBooking,
    inputTanggal,
    inputJam,
    checkboxes,
    chipTanggalList,
    chipJamList,
    btnMenitCepatList,
    statusTersediaEl,
    onJadwalBerubah,
}) => {
    const sinkronChipTanggal = () => {
        const tglAktif = inputTanggal?.value || '';
        chipTanggalList.forEach((chip) => {
            chip.classList.toggle('aktif', chip.dataset.tgl === tglAktif);
        });
    };

    const sinkronUIWaktu = () => {
        const jamNilai = (inputJam?.value || '10:00').slice(0, 5);
        const mntBagian = jamNilai.split(':')[1] || '00';

        chipJamList.forEach((chip) => {
            chip.classList.toggle('aktif', chip.dataset.jam === jamNilai);
        });
        btnMenitCepatList.forEach((btn) => {
            btn.classList.toggle('aktif', btn.dataset.menit === mntBagian);
        });
    };

    const muatPetugasTersedia = async () => {
        const urlDasar = formBooking.dataset.urlPetugas;
        const tgl = inputTanggal?.value;
        const jam = (inputJam?.value || '').slice(0, 5);

        if (!urlDasar || !tgl || !/^\d{2}:\d{2}$/.test(jam)) return;

        const parameter = new URLSearchParams({ tgl_booking: tgl, jam_booking: jam });
        checkboxes.forEach((cb) => {
            if (cb.checked) parameter.append('layanan[]', cb.value);
        });

        if (statusTersediaEl) statusTersediaEl.textContent = `Memeriksa slot pukul ${jam} WIB...`;

        try {
            const res = await fetch(`${urlDasar}?${parameter.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) return;

            const data = await res.json();
            const grupPetugas = data.petugas || {};
            const petugasPerLayanan = data.petugas_per_layanan || {};
            const statusJam = data.status_jam || {};
            const lewatTutupServer = Boolean(data.melewati_jam_tutup);

            chipJamList.forEach((chip) => {
                const jamOpt = chip.dataset.jam || '';
                const tersedia = statusJam[jamOpt] !== false;
                const statusEl = chip.querySelector('.chip-jam-status');

                chip.disabled = !tersedia;
                chip.setAttribute('aria-disabled', tersedia ? 'false' : 'true');
                chip.classList.toggle('penuh', !tersedia);
                chip.classList.toggle('aktif', jamOpt === jam);
                if (statusEl) statusEl.textContent = tersedia ? 'Tersedia' : 'Penuh / Tutup';
            });

            document.querySelectorAll('.select-petugas').forEach((select) => {
                const jabatan = select.dataset.jabatan || 'Stylist';
                const idLayanan = select.closest('.opsi-blok')?.dataset.idLayanan || '';
                const daftar = (idLayanan && Array.isArray(petugasPerLayanan[idLayanan]))
                    ? petugasPerLayanan[idLayanan]
                    : (grupPetugas[jabatan] || []);
                const nilaiSebelum = select.value || select.dataset.pilihanLama || '';

                select.dataset.tersedia = String(daftar.length);
                select.innerHTML = '';

                if (daftar.length === 0) {
                    const optKosong = document.createElement('option');
                    optKosong.value = '';
                    optKosong.textContent = lewatTutupServer
                        ? '-- Jadwal melewati jam tutup salon (21:00 WIB) --'
                        : `-- Semua ${jabatan} penuh pada pukul ${jam} --`;
                    select.appendChild(optKosong);
                    return;
                }

                const optOtomatis = document.createElement('option');
                optOtomatis.value = '';
                optOtomatis.textContent = `-- Pilihkan ${jabatan} Otomatis (${daftar.length} tersedia) --`;
                select.appendChild(optOtomatis);

                daftar.forEach((staf) => {
                    const opt = document.createElement('option');
                    opt.value = staf.id_karyawan;
                    opt.textContent = staf.nama_staff;
                    if (String(staf.id_karyawan) === String(nilaiSebelum)) opt.selected = true;
                    select.appendChild(opt);
                });
            });

            if (statusTersediaEl) {
                if (lewatTutupServer && data.pesan_jam_tutup) {
                    statusTersediaEl.textContent = data.pesan_jam_tutup;
                } else {
                    const jmlStylist = (grupPetugas.Stylist || []).length;
                    const jmlTherapist = (grupPetugas.Therapist || []).length;
                    statusTersediaEl.textContent = `Pukul ${jam} WIB: ${jmlStylist} Stylist & ${jmlTherapist} Therapist tersedia`;
                }
            }

            onJadwalBerubah();
        } catch {
            if (statusTersediaEl) statusTersediaEl.textContent = 'Jadwal siap dipilih';
        }
    };

    chipTanggalList.forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!inputTanggal) return;
            inputTanggal.value = chip.dataset.tgl || '';
            sinkronChipTanggal();
            muatPetugasTersedia();
            onJadwalBerubah();
        });
    });

    chipJamList.forEach((chip) => {
        chip.addEventListener('click', () => {
            if (chip.classList.contains('penuh') || !inputJam) return;
            inputJam.value = chip.dataset.jam || '10:00';
            sinkronUIWaktu();
            muatPetugasTersedia();
            onJadwalBerubah();
        });
    });

    btnMenitCepatList.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!inputJam) return;
            const jamSekarang = (inputJam.value || '10:00').split(':')[0] || '10';
            let gabungan = `${jamSekarang.padStart(2, '0')}:${btn.dataset.menit || '00'}`;
            if (gabungan > '20:30') gabungan = '20:30';
            if (gabungan < '09:00') gabungan = '09:00';

            inputJam.value = gabungan;
            sinkronUIWaktu();
            muatPetugasTersedia();
            onJadwalBerubah();
        });
    });

    inputTanggal?.addEventListener('change', () => {
        sinkronChipTanggal();
        muatPetugasTersedia();
    });
    inputJam?.addEventListener('change', () => {
        sinkronUIWaktu();
        muatPetugasTersedia();
        onJadwalBerubah();
    });
    inputJam?.addEventListener('input', () => {
        sinkronUIWaktu();
        onJadwalBerubah();
    });

    return { sinkronChipTanggal, sinkronUIWaktu, muatPetugasTersedia };
};
