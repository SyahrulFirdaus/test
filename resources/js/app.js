import './bootstrap';
import AOS from 'aos';

import initConfirmDialog from './modules/confirm-dialog';
import initNavbar from './modules/navbar';
import initCounters from './modules/counters';
import initSmoothScroll from './modules/smooth-scroll';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

AOS.init({
    duration: 650,
    easing: 'ease-out-cubic',
    once: true,
    offset: 60,
    disable: prefersReducedMotion,
});

initConfirmDialog();
initNavbar();
initSmoothScroll();
initCounters({ animate: !prefersReducedMotion });

// Gambar yang dimuat belakangan bisa menggeser posisi elemen, jadi hitung ulang.
window.addEventListener('load', () => AOS.refresh());
