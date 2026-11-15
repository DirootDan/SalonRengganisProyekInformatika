/**
 * Sinkronkan atribut aria-selected dan tabindex pada daftar tombol tab.
 *
 * @param {NodeListOf<HTMLElement>|HTMLElement[]} daftarTab
 */
export function sinkronTabAria(daftarTab) {
    const tabs = Array.from(daftarTab || []);
    if (tabs.length === 0) return;

    let indeksAktif = tabs.findIndex((tab) => tab.classList.contains('aktif'));
    if (indeksAktif === -1) indeksAktif = 0;

    tabs.forEach((tab, idx) => {
        const aktif = idx === indeksAktif;
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-selected', aktif ? 'true' : 'false');
        tab.setAttribute('tabindex', aktif ? '0' : '-1');
    });
}

/**
 * Mengikat pola keyboard WAI-ARIA Tablist (ArrowLeft/ArrowRight/Home/End) pada grup tombol tab.
 *
 * @param {NodeListOf<HTMLElement>|HTMLElement[]} daftarTab
 */
export function ikatNavigasiTabAria(daftarTab) {
    const tabs = Array.from(daftarTab || []);
    if (tabs.length === 0) return;

    const wadah = tabs[0].parentElement;
    if (wadah && !wadah.getAttribute('role')) {
        wadah.setAttribute('role', 'tablist');
    }

    sinkronTabAria(tabs);

    tabs.forEach((tab, idx) => {
        tab.addEventListener('click', () => {
            window.requestAnimationFrame(() => sinkronTabAria(tabs));
        });

        tab.addEventListener('keydown', (e) => {
            let targetIdx = null;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                targetIdx = (idx + 1) % tabs.length;
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                targetIdx = (idx - 1 + tabs.length) % tabs.length;
            } else if (e.key === 'Home') {
                targetIdx = 0;
            } else if (e.key === 'End') {
                targetIdx = tabs.length - 1;
            }

            if (targetIdx === null) return;
            e.preventDefault();
            const targetTab = tabs[targetIdx];
            targetTab.focus();
            targetTab.click();
        });
    });
}

/**
 * Sinkronkan atribut role="radio", aria-checked, dan tabindex pada elemen radiogroup.
 *
 * @param {HTMLElement|null} wadahEl
 * @param {string} [selectorRadio='.chip-jam']
 */
export function sinkronRadioAria(wadahEl, selectorRadio = '.chip-jam') {
    if (!wadahEl) return;
    if (!wadahEl.getAttribute('role')) {
        wadahEl.setAttribute('role', 'radiogroup');
    }

    const radios = Array.from(wadahEl.querySelectorAll(selectorRadio));
    if (radios.length === 0) return;

    let indeksFokus = radios.findIndex((r) => r.classList.contains('aktif') && !r.disabled);
    if (indeksFokus === -1) {
        indeksFokus = radios.findIndex((r) => !r.disabled);
    }

    radios.forEach((radio, idx) => {
        const terpilih = radio.classList.contains('aktif');
        radio.setAttribute('role', 'radio');
        radio.setAttribute('aria-checked', terpilih ? 'true' : 'false');
        radio.setAttribute('tabindex', idx === indeksFokus ? '0' : '-1');
    });
}

/**
 * Mengikat navigasi panah keyboard pada elemen radiogroup sesuai pola WAI-ARIA.
 *
 * @param {HTMLElement|null} wadahEl
 * @param {string} [selectorRadio='.chip-jam']
 */
export function ikatNavigasiRadioAria(wadahEl, selectorRadio = '.chip-jam') {
    if (!wadahEl) return;

    sinkronRadioAria(wadahEl, selectorRadio);

    wadahEl.addEventListener('keydown', (e) => {
        const aktif = e.target.closest(selectorRadio);
        if (!aktif) return;

        const radiosAktif = Array.from(wadahEl.querySelectorAll(selectorRadio)).filter((r) => !r.disabled);
        if (radiosAktif.length === 0) return;

        const idx = radiosAktif.indexOf(aktif);
        let target = null;

        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            target = radiosAktif[(idx + 1) % radiosAktif.length];
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            target = radiosAktif[(idx - 1 + radiosAktif.length) % radiosAktif.length];
        } else if (e.key === 'Home') {
            target = radiosAktif[0];
        } else if (e.key === 'End') {
            target = radiosAktif[radiosAktif.length - 1];
        }

        if (!target) return;
        e.preventDefault();
        target.focus();
        target.click();
        sinkronRadioAria(wadahEl, selectorRadio);
    });
}
