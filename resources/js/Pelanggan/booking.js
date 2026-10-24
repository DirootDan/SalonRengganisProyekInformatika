document.addEventListener('DOMContentLoaded', () => {
    const formBooking       = document.getElementById('formBooking');
    const inputTanggal      = document.getElementById('tgl_booking');
    const selectJam         = document.getElementById('jam_booking');
    const statusTersediaEl  = document.getElementById('statusKetersediaan');
    const searchInput       = document.getElementById('searchInput');
    const blokLayanan       = document.querySelectorAll('.opsi-blok');
    const checkboxes        = document.querySelectorAll('input[name="layanan[]"]');
    const submitBtn         = document.getElementById('submitBtn');
    const totalHargaEl      = document.getElementById('totalHarga');
    const jumlahPilihanEl   = document.getElementById('jumlahPilihan');
    const infoValidasiEl    = document.getElementById('infoValidasi');
    const tidakDitemukan    = document.getElementById('tidakDitemukan');

    if (!formBooking) return;

    // 1. Pencarian langsung layanan
    searchInput?.addEventListener('input', (e) => {
        const keyword = e.target.value.toLowerCase().trim();
        let jumlahTampil = 0;

        blokLayanan.forEach(blok => {
            const namaLayanan = (blok.dataset.nama || '').toLowerCase();
            const cocok = namaLayanan.includes(keyword);
            blok.style.display = cocok ? '' : 'none';
            if (cocok) jumlahTampil++;
        });

        tidakDitemukan?.classList.toggle('tampil', jumlahTampil === 0);
    });

    // 2. Tampilkan / sembunyikan dropdown petugas saat layanan dicentang
    const aturPilihanPetugas = (checkbox) => {
        const blok   = checkbox.closest('.opsi-blok');
        const select = blok?.querySelector('.select-petugas');

        blok?.classList.toggle('terpilih', checkbox.checked);

        if (select) {
            select.disabled = !checkbox.checked;
            select.required = checkbox.checked;
            if (!checkbox.checked) select.value = '';
        }
    };

    // 3. Kalkulasi harga & validasi tombol kirim
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
                infoValidasiEl.textContent = 'Pilih minimal satu layanan perawatan untuk melanjutkan.';
            } else if (petugasBelumDipilih > 0) {
                infoValidasiEl.textContent = `Masih ada ${petugasBelumDipilih} layanan yang belum dipilih petugasnya.`;
            } else {
                infoValidasiEl.textContent = '';
            }
        }

        if (submitBtn) {
            submitBtn.disabled = !siap;
            submitBtn.style.opacity = siap ? '1' : '0.5';
        }
    };

    // 4. Muat daftar petugas tersedia secara dinamis (AJAX / fetch) saat tanggal atau jam berubah
    const muatPetugasTersedia = async () => {
        const urlDasar = formBooking.dataset.urlPetugas;
        const tgl      = inputTanggal?.value;
        const jam      = selectJam?.value;

        if (!urlDasar || !tgl || !jam) return;

        if (statusTersediaEl) {
            statusTersediaEl.textContent = '⏳ Memeriksa ketersediaan Stylist dan Therapist...';
        }

        try {
            const res = await fetch(`${urlDasar}?tgl_booking=${encodeURIComponent(tgl)}&jam_booking=${encodeURIComponent(jam)}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;

            const data = await res.json();
            const grupPetugas = data.petugas || {};

            document.querySelectorAll('.select-petugas').forEach(select => {
                const jabatan      = select.dataset.jabatan || 'Stylist';
                const daftar       = grupPetugas[jabatan] || [];
                const nilaiSebelum = select.value || select.dataset.pilihanLama || '';

                select.innerHTML = '';

                if (daftar.length === 0) {
                    const optKosong = document.createElement('option');
                    optKosong.value = '';
                    optKosong.textContent = `-- Semua ${jabatan} penuh pada jam ${jam} --`;
                    select.appendChild(optKosong);
                } else {
                    const optAwal = document.createElement('option');
                    optAwal.value = '';
                    optAwal.textContent = `-- Pilih ${jabatan} (${daftar.length} tersedia) --`;
                    select.appendChild(optAwal);

                    daftar.forEach(staf => {
                        const opt = document.createElement('option');
                        opt.value = staf.id_karyawan;
                        opt.textContent = staf.nama_staff;
                        if (String(staf.id_karyawan) === String(nilaiSebelum)) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    });
                }
            });

            if (statusTersediaEl) {
                const jmlStylist   = (grupPetugas.Stylist || []).length;
                const jmlTherapist = (grupPetugas.Therapist || []).length;
                statusTersediaEl.textContent = `✓ Jadwal ${tgl} pukul ${jam} WIB: ${jmlStylist} Stylist & ${jmlTherapist} Therapist tersedia.`;
            }

            updateRingkasan();
        } catch (err) {
            if (statusTersediaEl) {
                statusTersediaEl.textContent = 'Menampilkan daftar petugas salon.';
            }
        }
    };

    inputTanggal?.addEventListener('change', muatPetugasTersedia);
    selectJam?.addEventListener('change', muatPetugasTersedia);

    checkboxes.forEach(checkbox => {
        aturPilihanPetugas(checkbox);

        checkbox.addEventListener('change', () => {
            aturPilihanPetugas(checkbox);
            updateRingkasan();

            if (checkbox.checked) {
                const select = checkbox.closest('.opsi-blok')?.querySelector('.select-petugas');
                select?.focus();
            }
        });
    });

    document.querySelectorAll('.select-petugas').forEach(select => {
        select.addEventListener('change', () => {
            select.closest('.opsi-blok')?.classList.remove('galat');
            updateRingkasan();
        });
    });

    muatPetugasTersedia();
    updateRingkasan();
});
