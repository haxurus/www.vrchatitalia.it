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

const revealTargets = document.querySelectorAll(
  '.project-layout, .section-head, .community-card, .gallery-heading, .application-panel'
);

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
