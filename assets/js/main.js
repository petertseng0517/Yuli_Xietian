// ── Intro overlay ─────────────────────────────────────────────
(function initIntro() {
  const overlay = document.getElementById('intro-overlay');
  if (!overlay) { startSite(); return; }

  const HOLD_MS = 4400;   // ms intro stays before auto-exit
  const FADE_MS = 900;    // ms fade-out duration

  // Prevent page scroll during intro
  document.body.style.overflow = 'hidden';

  function exitIntro() {
    overlay.classList.add('is-hiding');
    document.body.style.overflow = '';
    setTimeout(() => {
      overlay.remove();
      startSite();
    }, FADE_MS);
  }

  // Auto-exit after hold duration
  const autoTimer = setTimeout(exitIntro, HOLD_MS);

  // Allow click / scroll / touch to skip after 1.5s
  function attachSkip() {
    function skip() {
      clearTimeout(autoTimer);
      exitIntro();
      overlay.removeEventListener('click', skip);
      window.removeEventListener('wheel', skip);
      window.removeEventListener('touchstart', skip);
    }
    overlay.addEventListener('click', skip);
    window.addEventListener('wheel',      skip, { passive: true });
    window.addEventListener('touchstart', skip, { passive: true });
  }
  setTimeout(attachSkip, 1500);
})();

// ── Site initialisation (runs after intro is gone) ────────────
function startSite() {
  // AOS scroll entrance animations
  AOS.init({
    duration: 800,
    easing:   'ease-out-cubic',
    once:     true,
    offset:   80,
  });

  // Header: transparent → dark on scroll
  const header = document.getElementById('site-header');
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 80);
  }, { passive: true });

  // Hamburger menu
  const toggle  = document.querySelector('.nav-toggle');
  const overlay = document.querySelector('.nav-overlay');

  toggle.addEventListener('click', () => {
    const isOpen = overlay.classList.toggle('open');
    toggle.classList.toggle('active', isOpen);
    toggle.setAttribute('aria-expanded', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  overlay.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      overlay.classList.remove('open');
      toggle.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });
  });

  // Hero: Ken Burns slideshow
  initHeroSlideshow();
}

// ── Hero: Ken Burns slideshow ──────────────────────────────────
function initHeroSlideshow() {
  const slides = [...document.querySelectorAll('.hero-slide')];
  if (!slides.length) return;

  const SLIDE_DURATION = 7000;
  const FADE_DURATION  = 1500;
  const KB_CLASSES     = ['kb-1', 'kb-2', 'kb-3'];

  slides.forEach(s => {
    const src = s.dataset.src;
    if (src) s.style.backgroundImage = `url('${src}')`;
  });

  let current = 0;

  function activate(index) {
    const slide = slides[index];
    KB_CLASSES.forEach(c => slide.classList.remove(c));
    void slide.offsetWidth; // force reflow to restart animation
    slide.classList.add(KB_CLASSES[index % KB_CLASSES.length]);
    slide.classList.add('is-active');
  }

  function deactivate(index) {
    const slide = slides[index];
    slide.classList.remove('is-active');
    setTimeout(() => KB_CLASSES.forEach(c => slide.classList.remove(c)), FADE_DURATION);
  }

  activate(0);

  setInterval(() => {
    const prev = current;
    current = (current + 1) % slides.length;
    activate(current);
    setTimeout(() => deactivate(prev), FADE_DURATION);
  }, SLIDE_DURATION);
}
