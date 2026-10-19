/**
 * Tombol bahasa "ID / EN" pada navbar.
 *
 * Situs ditulis dalam bahasa Indonesia; versi Inggrisnya diterjemahkan
 * otomatis oleh Google Translate. Pilihan bahasa disimpan pada cookie
 * `googtrans` (format milik Google: "/id/en") lalu halaman dimuat ulang.
 *
 * Script Google hanya diunduh ketika bahasa Inggris aktif, sehingga pengunjung
 * berbahasa Indonesia tidak memuat apa pun dari Google Translate.
 */

const SOURCE = 'id';
const COOKIE = 'googtrans';

export function currentLanguage() {
    const match = document.cookie.match(/(?:^|;\s*)googtrans=\/[a-z-]+\/([a-z-]+)/i);

    return match && match[1] !== SOURCE ? match[1] : SOURCE;
}

/**
 * Google kadang menyimpan cookie-nya pada domain induk (.contoh.com), jadi
 * cookie ditulis/dihapus pada setiap varian domain agar tidak ada sisa.
 */
function cookieDomains() {
    const host = window.location.hostname;
    const parts = host.split('.');
    const domains = [''];

    if (parts.length > 1 && !/^\d+(\.\d+){3}$/.test(host)) {
        domains.push(`; domain=${host}`, `; domain=.${parts.slice(-2).join('.')}`);
    }

    return domains;
}

function setLanguage(language) {
    const expired = 'Thu, 01 Jan 1970 00:00:00 GMT';

    cookieDomains().forEach((domain) => {
        document.cookie = `${COOKIE}=; expires=${expired}; path=/${domain}`;
    });

    if (language !== SOURCE) {
        // Satu tahun; tanpa atribut domain agar berlaku untuk host saat ini.
        document.cookie = `${COOKIE}=/${SOURCE}/${language}; max-age=31536000; path=/; SameSite=Lax`;
    }

    window.location.reload();
}

function loadGoogleTranslate() {
    if (document.getElementById('google-translate-script')) {
        return;
    }

    const holder = document.createElement('div');
    holder.id = 'google_translate_element';
    holder.hidden = true;
    document.body.appendChild(holder);

    window.googleTranslateElementInit = () => {
        new window.google.translate.TranslateElement(
            { pageLanguage: SOURCE, includedLanguages: 'en', autoDisplay: false },
            'google_translate_element',
        );
    };

    const script = document.createElement('script');
    script.id = 'google-translate-script';
    script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    script.async = true;
    document.body.appendChild(script);
}

export default function initLanguageSwitcher() {
    const switches = document.querySelectorAll('[data-lang-switch]');
    const active = currentLanguage();

    switches.forEach((root) => {
        root.querySelectorAll('[data-lang]').forEach((button) => {
            const isActive = button.dataset.lang === active;
            button.setAttribute('aria-pressed', String(isActive));

            button.addEventListener('click', () => {
                if (button.dataset.lang !== currentLanguage()) {
                    setLanguage(button.dataset.lang);
                }
            });
        });
    });

    if (active !== SOURCE) {
        loadGoogleTranslate();
    }
}
