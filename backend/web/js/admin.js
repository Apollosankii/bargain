(() => {
    'use strict';

    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');
    const menuBtn = document.getElementById('admin-menu-btn');

    const closeSidebar = () => {
        sidebar?.classList.remove('is-open');
        overlay?.classList.remove('is-visible');
    };

    menuBtn?.addEventListener('click', () => {
        sidebar?.classList.toggle('is-open');
        overlay?.classList.toggle('is-visible');
    });

    overlay?.addEventListener('click', closeSidebar);

    sidebar?.querySelectorAll('.admin-sidebar__link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });
})();
