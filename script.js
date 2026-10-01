const body = document.body;
const menuToggle = document.querySelector('.menu-toggle');
const mobileMenu = document.querySelector('.mobile-menu');
const navLinks = [...document.querySelectorAll('[data-nav]')];
const sections = [...document.querySelectorAll('main section[id]')];
const year = document.querySelector('#year');
const formButton = document.querySelector('.button-form');

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

if (formButton?.getAttribute('aria-disabled') === 'true') {
  formButton.addEventListener('click', event => event.preventDefault());
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
