import './bootstrap';
import AOS from 'aos';

import initNavbar from './modules/navbar';
import initCounters from './modules/counters';
import initSmoothScroll from './modules/smooth-scroll';
import initHeroCarousel from './modules/hero-carousel';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

AOS.init({
    duration: 650,
    easing: 'ease-out-cubic',
    once: true,
    offset: 60,
    disable: prefersReducedMotion,
});

initNavbar();
initSmoothScroll();
initCounters({ animate: !prefersReducedMotion });
initHeroCarousel();

// Gambar yang dimuat belakangan bisa menggeser posisi elemen, jadi hitung ulang.
window.addEventListener('load', () => AOS.refresh());
