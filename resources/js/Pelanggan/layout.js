document.addEventListener('DOMContentLoaded', () => {
    const wadahDropdown = document.getElementById('akunDropdown');
    const tombolTrigger = document.getElementById('akunTrigger');
    const menuDropdown  = document.getElementById('akunMenu');

    if (!wadahDropdown || !tombolTrigger || !menuDropdown) {
        return;
    }

    const ambilItemMenu = () => Array.from(
        menuDropdown.querySelectorAll('[role="menuitem"]')
    );

    const apakahTerbuka = () => tombolTrigger.getAttribute('aria-expanded') === 'true';

    const bukaDropdown = (fokusItemPertama = false) => {
        tombolTrigger.setAttribute('aria-expanded', 'true');
        wadahDropdown.classList.add('terbuka');
        menuDropdown.hidden = false;

        if (fokusItemPertama) {
            const items = ambilItemMenu();
            if (items.length > 0) {
                items[0].focus();
            }
        }
    };

    const tutupDropdown = (kembalikanFokus = false) => {
        if (!apakahTerbuka()) return;

        tombolTrigger.setAttribute('aria-expanded', 'false');
        wadahDropdown.classList.remove('terbuka');
        menuDropdown.hidden = true;

        if (kembalikanFokus) {
            tombolTrigger.focus();
        }
    };

    const toggleDropdown = () => {
        if (apakahTerbuka()) {
            tutupDropdown(false);
        } else {
            bukaDropdown(false);
        }
    };

    // Klik pada tombol "Halo, [nama] ▾"
    tombolTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown();
    });

    // Dukungan keyboard pada tombol trigger (Enter, Space, ArrowDown, ArrowUp, Escape)
    tombolTrigger.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            bukaDropdown(true);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            bukaDropdown(false);
            const items = ambilItemMenu();
            if (items.length > 0) {
                items[items.length - 1].focus();
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            tutupDropdown(true);
        }
    });

    // Navigasi keyboard di dalam menu dropdown (ArrowDown, ArrowUp, Home, End, Escape, Tab)
    menuDropdown.addEventListener('keydown', (e) => {
        const items = ambilItemMenu();
        if (items.length === 0) return;

        const indeksSekarang = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const berikutnya = indeksSekarang < items.length - 1 ? indeksSekarang + 1 : 0;
            items[berikutnya].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const sebelumnya = indeksSekarang > 0 ? indeksSekarang - 1 : items.length - 1;
            items[sebelumnya].focus();
        } else if (e.key === 'Home') {
            e.preventDefault();
            items[0].focus();
        } else if (e.key === 'End') {
            e.preventDefault();
            items[items.length - 1].focus();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            tutupDropdown(true);
        } else if (e.key === 'Tab') {
            tutupDropdown(false);
        }
    });

    // Tutup saat klik di luar area dropdown
    document.addEventListener('click', (e) => {
        if (apakahTerbuka() && !wadahDropdown.contains(e.target)) {
            tutupDropdown(false);
        }
    });

    // Tutup saat menekan tombol Escape di mana saja pada halaman
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && apakahTerbuka()) {
            tutupDropdown(true);
        }
    });
});
