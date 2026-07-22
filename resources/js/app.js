import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('.menu-toggle');
    const navigationMenu = document.querySelector('.nav-menu');

    if (!menuButton || !navigationMenu) {
        return;
    }

    menuButton.addEventListener('click', () => {
        const isOpen = navigationMenu.classList.toggle('open');

        menuButton.setAttribute('aria-expanded', String(isOpen));
        menuButton.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
    });

    navigationMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            navigationMenu.classList.remove('open');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'Open navigation menu');
        });
    });
});
