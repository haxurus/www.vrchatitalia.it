const body = document.body;
const menuToggle = document.querySelector('.menu-toggle');
const mobileMenu = document.querySelector('.mobile-menu');
const navLinks = [...document.querySelectorAll('[data-nav]')];
const sections = [...document.querySelectorAll('main section[id]')];
const year = document.querySelector('#year');
const formButton = document.querySelector('.button-form');
const scrollIndicator = document.querySelector('.scroll-indicator');

if (year) {
  year.textContent = new Date().getFullYear();
}

if (menuToggle) {
  menuToggle.addEventListener('click', () => {
    const isOpen = body.classList.toggle('menu-open');
    menuToggle.setAttribute('aria-expanded', String(isOpen));
  });
}

if (mobileMenu) {
  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      body.classList.remove('menu-open');
      menuToggle?.setAttribute('aria-expanded', 'false');
    });
  });
}

document.addEventListener('keydown', event => {
  if (event.key !== 'Escape' || !body.classList.contains('menu-open')) return;
  body.classList.remove('menu-open');
  menuToggle?.setAttribute('aria-expanded', 'false');
});

if (formButton?.getAttribute('aria-disabled') === 'true') {
  formButton.addEventListener('click', event => event.preventDefault());
}

// Application popup rendered by VRC Italia Network Core (WordPress only).
const applicationModal = document.getElementById('vrcin-application-modal');
let applicationOpener = null;

function closeApplicationModal() {
  if (!applicationModal || applicationModal.hidden) return;
  applicationModal.hidden = true;
  body.style.overflow = '';
  applicationOpener?.focus();
}

if (applicationModal) {
  document.querySelectorAll('[data-vrcin-open-application]').forEach(button => {
    button.addEventListener('click', () => {
      applicationOpener = button;
      applicationModal.hidden = false;
      body.style.overflow = 'hidden';
      applicationModal.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
    });
  });

  applicationModal.querySelectorAll('[data-vrcin-close]').forEach(button => {
    button.addEventListener('click', closeApplicationModal);
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeApplicationModal();
  });
}

// Result of the application form / email verification redirect.
const applicationStatus = new URLSearchParams(window.location.search).get('vrcin_application_status');

if (applicationStatus) {
  const isItalianPage = document.documentElement.lang === 'it';
  const messages = {
    'check-email': [
      'Candidatura ricevuta. Controlla la tua email per confermare l\'indirizzo.',
      'Application received. Check your inbox to confirm your email address.'
    ],
    verified: [
      'Email confermata. La candidatura è ora in votazione tra le community.',
      'Email confirmed. Your application is now being voted on by the communities.'
    ],
    missing: [
      'Compila tutti i campi obbligatori e riprova.',
      'Please fill in all required fields and try again.'
    ],
    'verify-error': [
      'Il link di verifica non è valido o è scaduto.',
      'The verification link is invalid or has expired.'
    ]
  };
  const isSuccess = applicationStatus === 'check-email' || applicationStatus === 'verified';
  const message = messages[applicationStatus] || [
    'Non è stato possibile completare la richiesta (' + applicationStatus + ').',
    'The request could not be completed (' + applicationStatus + ').'
  ];

  const toast = document.createElement('div');
  toast.className = 'vrcin-toast' + (isSuccess ? '' : ' is-error');
  toast.setAttribute('role', 'status');
  const toastText = document.createElement('p');
  toastText.textContent = message[isItalianPage ? 0 : 1];
  const toastClose = document.createElement('button');
  toastClose.type = 'button';
  toastClose.setAttribute('aria-label', isItalianPage ? 'Chiudi' : 'Close');
  toastClose.textContent = '×';
  toastClose.addEventListener('click', () => toast.remove());
  toast.append(toastText, toastClose);
  body.appendChild(toast);

  const cleanUrl = new URL(window.location.href);
  cleanUrl.searchParams.delete('vrcin_application_status');
  window.history.replaceState(null, '', cleanUrl.toString());
}

let scrollCueReady = false;

if (scrollIndicator) {
  window.setTimeout(() => {
    scrollCueReady = true;
    if (window.scrollY < 32) {
      scrollIndicator.classList.add('is-visible');
    }
  }, 4500);

  const updateScrollCue = () => {
    scrollIndicator.classList.toggle(
      'is-visible',
      scrollCueReady && window.scrollY < 32
    );
  };

  window.addEventListener('scroll', updateScrollCue, { passive: true });
}

const revealTargets = document.querySelectorAll([
  '.project-layout > div:first-child', '.project-copy', '.national-group',
  '.section-head', '.community-card', '.home-events-copy', '.home-events-preview',
  '.gallery-heading', '.photo-marquee', '.application-intro', '.requirement',
  '.application-action', '.footer-shell', '.events-toolbar-panel', '.events-legend',
  '.calendar-shell', '.vrcin-dashboard-card'
].join(', '));

if ('IntersectionObserver' in window) {
  revealTargets.forEach(element => element.classList.add('reveal'));

  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      revealObserver.unobserve(entry.target);
    });
  }, { threshold: 0.12 });

  revealTargets.forEach(element => revealObserver.observe(element));

  const navObserver = new IntersectionObserver(entries => {
    const visible = entries
      .filter(entry => entry.isIntersecting)
      .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

    if (!visible) return;

    navLinks.forEach(link => {
      link.classList.toggle(
        'is-active',
        link.getAttribute('href') === '#' + visible.target.id
      );
    });
  }, {
    rootMargin: '-28% 0px -58% 0px',
    threshold: [0.01, 0.2, 0.5]
  });

  sections.forEach(section => navObserver.observe(section));
}


const themeToggles = [...document.querySelectorAll('.theme-toggle')];
const themeColorMeta = document.querySelector('meta[name="theme-color"]');

function getStoredTheme() {
  try {
    return localStorage.getItem('vrcin-theme') === 'light' ? 'light' : 'dark';
  } catch (error) {
    return 'dark';
  }
}

function applyTheme(theme, persist = false) {
  const nextTheme = theme === 'light' ? 'light' : 'dark';
  document.documentElement.toggleAttribute('data-theme', nextTheme === 'light');

  document.documentElement.setAttribute('data-color-scheme', nextTheme);

  if (nextTheme === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }

  if (persist) {
    try {
      localStorage.setItem('vrcin-theme', nextTheme);
    } catch (error) {}
  }

  const isItalian = document.documentElement.lang === 'it';
  const isLight = nextTheme === 'light';
  const label = isItalian
    ? (isLight ? 'Passa al tema scuro' : 'Passa al tema chiaro')
    : (isLight ? 'Switch to dark theme' : 'Switch to light theme');

  themeToggles.forEach(toggle => {
    toggle.setAttribute('aria-label', label);
    toggle.setAttribute('title', label);
    toggle.setAttribute('aria-pressed', String(isLight));
  });

  if (themeColorMeta) {
    themeColorMeta.setAttribute('content', isLight ? '#f6f3ec' : '#07090e');
  }

  document.dispatchEvent(new CustomEvent('vrcin-theme-change', {
    detail: { theme: nextTheme }
  }));
}

applyTheme(getStoredTheme());

themeToggles.forEach(toggle => {
  toggle.addEventListener('click', () => {
    const currentTheme = document.documentElement.hasAttribute('data-theme') ? 'light' : 'dark';
    applyTheme(currentTheme === 'light' ? 'dark' : 'light', true);
  });
});


// ---------- Motion ----------

const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

// Tricolore scroll progress bar
const scrollProgress = document.createElement('div');
scrollProgress.className = 'scroll-progress';
scrollProgress.setAttribute('aria-hidden', 'true');
body.appendChild(scrollProgress);

let progressFrame = 0;
const updateProgress = () => {
  progressFrame = 0;
  const max = document.documentElement.scrollHeight - window.innerHeight;
  scrollProgress.style.setProperty('--progress', max > 0 ? String(window.scrollY / max) : '0');
};
window.addEventListener('scroll', () => {
  if (!progressFrame) progressFrame = requestAnimationFrame(updateProgress);
}, { passive: true });
updateProgress();

// Pointer spotlight on cards
if (finePointer) {
  document.querySelectorAll('.community-card, .requirement, .national-group, .vrcin-dashboard-card').forEach(card => {
    card.addEventListener('pointermove', event => {
      const rect = card.getBoundingClientRect();
      card.style.setProperty('--mx', (event.clientX - rect.left) + 'px');
      card.style.setProperty('--my', (event.clientY - rect.top) + 'px');
    });
  });
}

// Calendar teaser tilts toward the pointer
const eventsPreview = document.querySelector('.home-events-preview');

if (eventsPreview && finePointer) {
  const wideScreen = window.matchMedia('(min-width: 961px)');
  const restTransform = () => wideScreen.matches ? 'perspective(1400px) rotateY(-6deg) rotateX(2deg)' : '';

  eventsPreview.style.transform = restTransform();
  wideScreen.addEventListener('change', () => { eventsPreview.style.transform = restTransform(); });

  eventsPreview.addEventListener('pointermove', event => {
    if (!wideScreen.matches) return;
    const rect = eventsPreview.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - 0.5;
    const y = (event.clientY - rect.top) / rect.height - 0.5;
    eventsPreview.style.transform =
      `perspective(1400px) rotateY(${(x * 10).toFixed(2)}deg) rotateX(${(-y * 8).toFixed(2)}deg) translateY(-4px)`;
  });
  eventsPreview.addEventListener('pointerleave', () => { eventsPreview.style.transform = restTransform(); });
}

// Hero: drifting tricolore particles
const hero = document.querySelector('.hero');
const particleCanvas = hero && document.createElement('canvas');
const particleContext = particleCanvas?.getContext('2d');

if (hero && particleContext) {
  particleCanvas.className = 'hero-particles';
  particleCanvas.setAttribute('aria-hidden', 'true');
  hero.appendChild(particleCanvas);

  const colors = ['47, 212, 135', '246, 241, 228', '255, 95, 92'];
  const pointer = { x: 0, y: 0 };
  let particles = [];
  let width = 0;
  let height = 0;
  let running = false;
  let frame = 0;

  const resize = () => {
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    width = hero.clientWidth;
    height = hero.clientHeight;
    particleCanvas.width = width * ratio;
    particleCanvas.height = height * ratio;
    particleContext.setTransform(ratio, 0, 0, ratio, 0, 0);

    const count = Math.round(Math.min(90, Math.max(30, width * height / 16000)));
    particles = Array.from({ length: count }, () => ({
      x: Math.random() * width,
      y: Math.random() * height,
      radius: Math.random() * 1.8 + 0.6,
      speed: Math.random() * 0.35 + 0.12,
      sway: Math.random() * Math.PI * 2,
      depth: Math.random() * 0.8 + 0.2,
      color: colors[Math.floor(Math.random() * colors.length)],
      alpha: Math.random() * 0.5 + 0.25
    }));
  };

  const draw = time => {
    if (!running) return;
    particleContext.clearRect(0, 0, width, height);

    particles.forEach(p => {
      p.y -= p.speed;
      p.sway += 0.01;
      if (p.y < -10) {
        p.y = height + 10;
        p.x = Math.random() * width;
      }

      const x = p.x + Math.sin(p.sway) * 14 + pointer.x * 24 * p.depth;
      const y = p.y + pointer.y * 16 * p.depth;
      const twinkle = 0.6 + Math.sin(time / 700 + p.sway * 3) * 0.4;

      particleContext.beginPath();
      particleContext.arc(x, y, p.radius * (0.6 + p.depth), 0, Math.PI * 2);
      particleContext.fillStyle = `rgba(${p.color}, ${(p.alpha * twinkle).toFixed(3)})`;
      particleContext.shadowColor = `rgba(${p.color}, .8)`;
      particleContext.shadowBlur = 8 * p.depth;
      particleContext.fill();
    });

    frame = requestAnimationFrame(draw);
  };

  const start = () => {
    if (running) return;
    running = true;
    frame = requestAnimationFrame(draw);
  };

  const stop = () => {
    running = false;
    cancelAnimationFrame(frame);
  };

  resize();
  window.addEventListener('resize', resize);

  hero.addEventListener('pointermove', event => {
    const rect = hero.getBoundingClientRect();
    pointer.x = (event.clientX - rect.left) / rect.width - 0.5;
    pointer.y = (event.clientY - rect.top) / rect.height - 0.5;
  });

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([entry]) => (entry.isIntersecting ? start() : stop())).observe(hero);
  } else {
    start();
  }
}
