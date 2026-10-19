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

    window.matchMedia('(min-width: 1280px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMenuOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
        }
    });

    initDropdownMenu(navbar, 'support');
    initDropdownMenu(navbar, 'industry');
}

/**
 * Menu dropdown pada layar besar: "Support Us" (data-support-*) dan
 * "Industri" (data-industry-*).
 *
 * Panelnya tertutup lagi begitu pengguna menekan di luar menu atau menekan
 * Escape. Pada layar kecil menu ini memakai <details> bawaan browser sehingga
 * tidak memerlukan JavaScript sama sekali.
 */
function initDropdownMenu(navbar, name) {
    const root = navbar.querySelector(`[data-${name}-menu]`);

    if (!root) {
        return;
    }

    const toggle = root.querySelector(`[data-${name}-toggle]`);
    const panel = root.querySelector(`[data-${name}-panel]`);
    const caret = root.querySelector(`[data-${name}-caret]`);

    if (!toggle || !panel) {
        return;
    }

    const setOpen = (open) => {
        panel.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
        caret?.classList.toggle('rotate-180', open);
    };

    // Klik tidak dihentikan propagasinya supaya dropdown lain yang sedang
    // terbuka ikut tertutup lewat listener dokumen di bawah.
    toggle.addEventListener('click', () => {
        setOpen(panel.classList.contains('hidden'));
    });

    // Tautan anchor di halaman yang sama tidak memuat ulang halaman.
    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
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
