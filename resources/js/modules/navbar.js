/**
 * Navbar sticky: menambah latar solid saat halaman digulir dan
 * mengatur buka/tutup menu pada tampilan mobile.
 */
export default function initNavbar() {
    const navbar = document.querySelector('[data-navbar]');

    if (!navbar) {
        return;
    }

    const toggle = navbar.querySelector('[data-navbar-toggle]');
    const menu = navbar.querySelector('[data-navbar-menu]');
    const iconOpen = navbar.querySelector('[data-icon-open]');
    const iconClose = navbar.querySelector('[data-icon-close]');

    // Halaman tanpa hero gelap meminta latar solid sejak awal; keadaannya tidak
    // boleh ikut dilepas ketika halaman digulir kembali ke atas.
    const alwaysSolid = navbar.hasAttribute('data-navbar-solid');

    const applyScrollState = () => {
        navbar.classList.toggle('is-scrolled', alwaysSolid || window.scrollY > 24);
    };

    applyScrollState();
    window.addEventListener('scroll', applyScrollState, { passive: true });

    if (!toggle || !menu) {
        return;
    }

    const setMenuOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
        iconOpen?.classList.toggle('hidden', open);
        iconClose?.classList.toggle('hidden', !open);
    };

    toggle.addEventListener('click', () => {
        setMenuOpen(menu.classList.contains('hidden'));
    });

    // Tutup menu setelah memilih tautan atau saat layar melebar ke breakpoint desktop.
    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMenuOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
        }
    });

    initSupportMenu(navbar);
}

/**
 * Menu "Support Us" pada layar besar.
 *
 * Isinya kontak perusahaan; panelnya tertutup lagi begitu pengguna menekan di
 * luar menu atau menekan Escape. Pada layar kecil menu ini memakai <details>
 * bawaan browser sehingga tidak memerlukan JavaScript sama sekali.
 */
function initSupportMenu(navbar) {
    const root = navbar.querySelector('[data-support-menu]');

    if (!root) {
        return;
    }

    const toggle = root.querySelector('[data-support-toggle]');
    const panel = root.querySelector('[data-support-panel]');
    const caret = root.querySelector('[data-support-caret]');

    if (!toggle || !panel) {
        return;
    }

    const setOpen = (open) => {
        panel.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
        caret?.classList.toggle('rotate-180', open);
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(panel.classList.contains('hidden'));
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
}
