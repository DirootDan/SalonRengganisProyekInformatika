document.addEventListener('DOMContentLoaded', () => {
    const tombolMenu = document.getElementById('btnMenuPublik');
    const menuNav = document.getElementById('pbMenuNav');

    if (!tombolMenu || !menuNav) return;

    const aturMenu = (buka) => {
        menuNav.classList.toggle('buka', buka);
        tombolMenu.setAttribute('aria-expanded', String(buka));
    };

    tombolMenu.addEventListener('click', () => {
        aturMenu(!menuNav.classList.contains('buka'));
    });

    menuNav.querySelectorAll('a').forEach((tautan) => {
        tautan.addEventListener('click', () => {
            aturMenu(false);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menuNav.classList.contains('buka')) {
            aturMenu(false);
            tombolMenu.focus();
        }
    });
});
