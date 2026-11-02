document.addEventListener('DOMContentLoaded', () => {
    const tombolTabList = document.querySelectorAll('.tab-persetujuan');
    const panelKontenList = document.querySelectorAll('.panel-tab-konten');

    const aktifkanTab = (idTarget) => {
        tombolTabList.forEach((btn) => {
            const cocok = btn.dataset.target === idTarget;
            btn.classList.toggle('aktif', cocok);
            btn.setAttribute('aria-selected', cocok ? 'true' : 'false');
        });

        panelKontenList.forEach((panel) => {
            panel.classList.toggle('tersembunyi', panel.id !== idTarget);
        });
    };

    tombolTabList.forEach((btn) => {
        btn.addEventListener('click', () => {
            aktifkanTab(btn.dataset.target);
        });
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'audit') {
        aktifkanTab('panelAudit');
    }
});
