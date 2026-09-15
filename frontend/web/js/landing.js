(() => {
    'use strict';

    const nav = document.querySelector('.landing-nav');
    const toggle = document.querySelector('[data-landing-nav-toggle]');
    const panel = document.querySelector('[data-landing-nav-panel]');

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
    }

    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('.landing-section, .landing-hero').forEach((el, i) => {
            el.classList.add('landing-reveal');
            el.style.setProperty('--reveal-delay', `${i * 0.06}s`);
        });
    }
})();
