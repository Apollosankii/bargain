(() => {
    'use strict';

    const nav = document.querySelector('[data-portal-nav]');
    const toggle = document.querySelector('[data-portal-nav-toggle]');
    const panel = document.querySelector('[data-portal-nav-panel]');

    if (toggle && panel) {
        toggle.addEventListener('click', () => {
            const open = panel.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        panel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                panel.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('click', (event) => {
            if (!nav?.contains(event.target)) {
                panel.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();
