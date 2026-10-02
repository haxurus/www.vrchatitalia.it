(() => {
  const calendarEl = document.getElementById('events-calendar');
  if (!calendarEl || !window.FullCalendar) return;

  const lang = document.documentElement.lang === 'it' ? 'it' : 'en';
  const locale = lang === 'it' ? 'it' : 'en-gb';

  const text = {
    it: {
      today: 'Oggi',
      month: 'Mese',
      week: 'Settimana',
      day: 'Giorno',
      list: 'Lista',
      date: 'Data e ora',
      timezone: 'Timezone',
      community: 'Community',
      category: 'Tag',
      world: 'Mondo / luogo',
      access: 'Accesso',
      platforms: 'Piattaforme',
      language: 'Lingua',
      age: 'Età',
      capacity: 'Capienza',
      registration: 'Registrazione',
      organizer: 'Organizzatore',
      contact: 'Contatto',
      recurrence: 'Ricorrenza',
      yes: 'Richiesta',
      no: 'Non richiesta',
      notSpecified: 'Non specificato',
      joinGroup: 'Apri gruppo VRChat ↗',
      registrationLink: 'Registrati ↗',
      eventLink: 'Pagina evento ↗',
      demo: 'Evento demo',
      people: 'persone'
    },
    en: {
      today: 'Today',
      month: 'Month',
      week: 'Week',
      day: 'Day',
      list: 'List',
      date: 'Date & time',
      timezone: 'Timezone',
      community: 'Community',
      category: 'Tag',
      world: 'World / location',
      access: 'Access',
      platforms: 'Platforms',
      language: 'Language',
      age: 'Age',
      capacity: 'Capacity',
      registration: 'Registration',
      organizer: 'Organizer',
      contact: 'Contact',
      recurrence: 'Recurrence',
      yes: 'Required',
      no: 'Not required',
      notSpecified: 'Not specified',
      joinGroup: 'Open VRChat group ↗',
      registrationLink: 'Register ↗',
      eventLink: 'Event page ↗',
      demo: 'Demo event',
      people: 'people'
    }
  }[lang];

  const tagLabels = {
    it: {
      gaming: 'Gaming Night',
      drinking: 'Drinking Night',
      'world-exploration': 'Esplorazione Mondi',
      'dj-disco': 'DJ Set / Disco Night',
      hangout: 'Relax / Hangout',
      'age-gated': 'Solo 18+',
      other: 'Altro'
    },
    en: {
      gaming: 'Gaming Night',
      drinking: 'Drinking Night',
      'world-exploration': 'World Exploration',
      'dj-disco': 'DJ Set / Disco Night',
      hangout: 'Relax / Hangout',
      'age-gated': 'Age Gated (18+)',
      other: 'Other'
    }
  }[lang];

  const accessLabels = {
    it: {
      public: 'Public',
      group: 'Group',
      friends: 'Friends+',
      private: 'Privato'
    },
    en: {
      public: 'Public',
      group: 'Group',
      friends: 'Friends+',
      private: 'Private'
    }
  }[lang];

  const events = [
    {
      id: 'demo-social-night',
      title: { it: 'Social Night di apertura', en: 'Opening Social Night' },
      start: '2026-10-03T21:30:00+02:00',
      end: '2026-10-04T00:30:00+02:00',
      community: 'Celestia',
      tags: ['hangout'],
      description: {
        it: 'Serata social dimostrativa per incontrarsi, conoscere nuovi utenti e passare qualche ora insieme in VRChat.',
        en: 'Demo social evening to meet new users, hang out and spend a few hours together in VRChat.'
      },
      world: 'Community Home World',
      access: 'group',
      platforms: ['PC', 'Quest'],
      language: { it: 'Italiano', en: 'Italian' },
      age: '16+',
      capacity: 40,
      registrationRequired: false,
      registrationUrl: '',
      groupUrl: 'https://vrc.group/VRCITA.1559',
      eventUrl: '',
      organizer: 'Staff Celestia',
      contact: { it: 'Discord della community', en: 'Community Discord' },
      recurrence: { it: 'Evento singolo', en: 'One-time event' },

      demo: true
    },
    {
      id: 'demo-world-tour',
      title: { it: 'Italian World Tour', en: 'Italian World Tour' },
      start: '2026-10-05T20:45:00+02:00',
      end: '2026-10-05T23:00:00+02:00',
      community: 'VRChat Italia',
      tags: ['world-exploration'],
      description: {
        it: 'Tour guidato tra alcuni mondi selezionati, con tappe fotografiche e momenti social.',
        en: 'Guided tour through selected worlds, with photo stops and social moments.'
      },
      world: 'Partenza dal gruppo VRChat Italia',
      access: 'group',
      platforms: ['PC', 'Quest'],
      language: { it: 'Italiano', en: 'Italian' },
      age: { it: 'Tutte le età compatibili con VRChat', en: 'All VRChat-compatible ages' },
      capacity: 60,
      registrationRequired: false,
      registrationUrl: '',
      groupUrl: 'https://vrc.group/VRCITA.1559',
      eventUrl: '',
      organizer: 'VRChat Italia',
      contact: { it: 'Referenti del gruppo', en: 'Group representatives' },
      recurrence: { it: 'Evento singolo', en: 'One-time event' },

      demo: true
    },
    {
      id: 'demo-creator-showcase',
      title: { it: 'Avatar & Creator Showcase', en: 'Avatar & Creator Showcase' },
      start: '2026-10-09T21:00:00+02:00',
      end: '2026-10-09T23:30:00+02:00',
      community: 'Creators Hub - Demo',
      tags: ['other'],
      description: {
        it: 'Spazio dimostrativo per creator italiani che vogliono mostrare avatar, asset, shader o nuovi progetti.',
        en: 'Demo showcase for Italian creators presenting avatars, assets, shaders or new projects.'
      },
      world: 'Showcase World',
      access: 'public',
      platforms: ['PC'],
      language: { it: 'Italiano / Inglese', en: 'Italian / English' },
      age: '13+',
      capacity: 32,
      registrationRequired: true,
      registrationUrl: '',
      groupUrl: '',
      eventUrl: '',
      organizer: 'Creators Hub - Demo',
      contact: { it: 'Referente creator', en: 'Creator contact' },
      recurrence: { it: 'Mensile - esempio', en: 'Monthly - example' },

      demo: true
    },
    {
      id: 'demo-roleplay',
      title: { it: 'Serata Roleplay - Sessione Zero', en: 'Roleplay Night - Session Zero' },
      start: '2026-10-12T20:30:00+02:00',
      end: '2026-10-12T23:45:00+02:00',
      community: 'Roleplay Community - Demo',
      tags: ['other', 'age-gated'],
      description: {
        it: 'Sessione introduttiva con presentazione dell’ambientazione, creazione dei gruppi e spiegazione delle regole.',
        en: 'Introductory session presenting the setting, forming groups and explaining the rules.'
      },
      world: 'RP Hub',
      access: 'private',
      platforms: ['PC', 'Quest'],
      language: { it: 'Italiano', en: 'Italian' },
      age: '18+',
      capacity: 24,
      registrationRequired: true,
      registrationUrl: '',
      groupUrl: '',
      eventUrl: '',
      organizer: 'Roleplay Community - Demo',
      contact: { it: 'Game Master / Staff', en: 'Game Master / Staff' },
      recurrence: { it: 'Settimanale - esempio', en: 'Weekly - example' },

      demo: true
    },
    {
      id: 'demo-club-night',
      title: { it: 'Italia Club Night', en: 'Italia Club Night' },
      start: '2026-10-17T22:30:00+02:00',
      end: '2026-10-18T02:30:00+02:00',
      community: 'Night Community - Demo',
      tags: ['dj-disco', 'age-gated'],
      description: {
        it: 'Evento musicale dimostrativo con DJ set, area chill e accesso tramite istanza Group.',
        en: 'Demo music event with DJ sets, chill area and Group instance access.'
      },
      world: 'Club World',
      access: 'group',
      platforms: ['PC', 'Quest'],
      language: { it: 'Italiano / Inglese', en: 'Italian / English' },
      age: '18+',
      capacity: 80,
      registrationRequired: false,
      registrationUrl: '',
      groupUrl: 'https://vrc.group/VRCITA.1559',
      eventUrl: '',
      organizer: 'Night Community - Demo',
      contact: { it: 'Staff evento', en: 'Event staff' },
      recurrence: { it: 'Evento singolo', en: 'One-time event' },

      demo: true
    },
    {
      id: 'demo-national-meetup',
      title: { it: 'Meetup VRChat Italia', en: 'VRChat Italia Meetup' },
      start: '2026-10-24T21:00:00+02:00',
      end: '2026-10-24T23:59:00+02:00',
      community: 'VRChat Italia',
      tags: ['hangout'],
      description: {
        it: 'Esempio di incontro aperto tra utenti provenienti dalle diverse community aderenti al progetto.',
        en: 'Example meetup open to users from different communities participating in the project.'
      },
      world: 'VRChat Italia Meeting Point',
      access: 'group',
      platforms: ['PC', 'Quest', 'Android'],
      language: { it: 'Italiano', en: 'Italian' },
      age: { it: 'Tutte le età compatibili con VRChat', en: 'All VRChat-compatible ages' },
      capacity: 80,
      registrationRequired: false,
      registrationUrl: '',
      groupUrl: 'https://vrc.group/VRCITA.1559',
      eventUrl: '',
      organizer: 'VRChat Italia',
      contact: { it: 'Staff VRChat Italia', en: 'VRChat Italia staff' },
      recurrence: { it: 'Evento singolo', en: 'One-time event' },

      demo: true
    }
  ];

  const communitySelect = document.getElementById('events-community');
  const tagSelect = document.getElementById('events-tag');
  const platformSelect = document.getElementById('events-platform');
  const accessSelect = document.getElementById('events-access');
  const searchInput = document.getElementById('events-search');
  const resetButton = document.getElementById('events-reset');

  [...new Set(events.map(event => event.community))]
    .sort((a, b) => a.localeCompare(b))
    .forEach(community => {
      const option = document.createElement('option');
      option.value = community;
      option.textContent = community;
      communitySelect?.appendChild(option);
    });

  const normalize = value => String(value || '').toLocaleLowerCase(locale);

  const getFilteredEvents = () => {
    const search = normalize(searchInput?.value);
    const community = communitySelect?.value || '';
    const tag = tagSelect?.value || '';
    const platform = platformSelect?.value || '';
    const access = accessSelect?.value || '';

    return events.filter(event => {
      if (community && event.community !== community) return false;
      if (tag && !event.tags.includes(tag)) return false;
      if (platform && !event.platforms.includes(platform)) return false;
      if (access && event.access !== access) return false;

      if (search) {
        const searchable = normalize([
          event.title[lang],
          event.community,
          ...event.tags.map(tag => tagLabels[tag] || tag),
          event.description[lang],
          event.world,
          ...event.tags
        ].join(' '));

        if (!searchable.includes(search)) return false;
      }

      return true;
    });
  };

  const toCalendarEvent = event => ({
    id: event.id,
    title: event.title[lang],
    start: event.start,
    end: event.end,
    classNames: ['vri-calendar-event', 'event-tag-' + (event.tags[0] || 'other')],
    extendedProps: event
  });

  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale,
    timeZone: 'Europe/Rome',
    initialView: 'dayGridMonth',
    firstDay: 1,
    nowIndicator: true,
    navLinks: true,
    dayMaxEvents: true,
    height: 'auto',
    expandRows: true,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    buttonText: {
      today: text.today
    },
    views: {
      dayGridMonth: { buttonText: text.month },
      timeGridWeek: { buttonText: text.week },
      timeGridDay: { buttonText: text.day },
      listMonth: { buttonText: text.list }
    },
    slotMinTime: '16:00:00',
    slotMaxTime: '30:00:00',
    scrollTime: '20:00:00',
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },
    events: getFilteredEvents().map(toCalendarEvent),
    eventClick(info) {
      info.jsEvent.preventDefault();
      openEventDialog(info.event.extendedProps);
    }
  });

  calendar.render();

  const applyFilters = () => {
    calendar.removeAllEvents();
    getFilteredEvents().forEach(event => calendar.addEvent(toCalendarEvent(event)));
  };

  [communitySelect, tagSelect, platformSelect, accessSelect].forEach(element => {
    element?.addEventListener('change', applyFilters);
  });

  searchInput?.addEventListener('input', applyFilters);

  resetButton?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (communitySelect) communitySelect.value = '';
    if (tagSelect) tagSelect.value = '';
    if (platformSelect) platformSelect.value = '';
    if (accessSelect) accessSelect.value = '';
    applyFilters();
  });

  const dialog = document.getElementById('event-dialog');
  const dialogClose = document.getElementById('event-dialog-close');

  dialogClose?.addEventListener('click', () => dialog?.close());
  dialog?.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });

  function localValue(value) {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      return value[lang] || value.it || value.en || text.notSpecified;
    }
    return value || text.notSpecified;
  }

  function formatEventRange(event) {
    const start = new Date(event.start);
    const end = event.end ? new Date(event.end) : null;

    const dateFormatter = new Intl.DateTimeFormat(locale, {
      weekday: 'long',
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      timeZone: 'Europe/Rome'
    });

    const timeFormatter = new Intl.DateTimeFormat(locale, {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: 'Europe/Rome'
    });

    const startDate = dateFormatter.format(start);
    const startTime = timeFormatter.format(start);

    if (!end) return startDate + ', ' + startTime;

    const sameDay =
      new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Rome' }).format(start) ===
      new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Rome' }).format(end);

    if (sameDay) {
      return startDate + ', ' + startTime + ' - ' + timeFormatter.format(end);
    }

    return startDate + ', ' + startTime + ' - ' + dateFormatter.format(end) + ', ' + timeFormatter.format(end);
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function detailItem(label, value) {
    return `<div class="event-detail"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
  }

  function openEventDialog(event) {
    if (!dialog) return;

    const titleEl = document.getElementById('event-dialog-title');
    const descriptionEl = document.getElementById('event-dialog-description');
    const communityEl = document.getElementById('event-dialog-community');
    const demoEl = document.getElementById('event-dialog-demo');
    const detailGrid = document.getElementById('event-detail-grid');
    const tagsEl = document.getElementById('event-dialog-tags');
    const actionsEl = document.getElementById('event-dialog-actions');

    if (titleEl) titleEl.textContent = event.title[lang];
    if (descriptionEl) descriptionEl.textContent = event.description[lang];
    if (communityEl) communityEl.textContent = event.community;
    if (demoEl) {
      demoEl.textContent = text.demo;
      demoEl.hidden = !event.demo;
    }

    if (detailGrid) {
      detailGrid.innerHTML = [
        detailItem(text.date, formatEventRange(event)),
        detailItem(text.timezone, 'Europe/Rome'),
        detailItem(text.community, event.community),
        detailItem(text.category, (event.tags || []).map(tag => tagLabels[tag] || tag).join(', ')),
        detailItem(text.world, event.world),
        detailItem(text.access, accessLabels[event.access] || event.access),
        detailItem(text.platforms, event.platforms.join(', ')),
        detailItem(text.language, localValue(event.language)),
        detailItem(text.age, localValue(event.age)),
        detailItem(text.capacity, event.capacity ? event.capacity + ' ' + text.people : text.notSpecified),
        detailItem(text.registration, event.registrationRequired ? text.yes : text.no),
        detailItem(text.organizer, event.organizer),
        detailItem(text.contact, localValue(event.contact)),
        detailItem(text.recurrence, localValue(event.recurrence))
      ].join('');
    }

    if (tagsEl) {
      tagsEl.innerHTML = event.tags.map(tag => `<span>${escapeHtml(tagLabels[tag] || tag)}</span>`).join('');
    }

    if (actionsEl) {
      const actions = [];
      if (event.groupUrl) actions.push(`<a class="button button-secondary" href="${escapeHtml(event.groupUrl)}" target="_blank" rel="noopener noreferrer">${text.joinGroup}</a>`);
      if (event.registrationRequired && event.registrationUrl) actions.push(`<a class="button button-primary" href="${escapeHtml(event.registrationUrl)}" target="_blank" rel="noopener noreferrer">${text.registrationLink}</a>`);
      if (event.eventUrl) actions.push(`<a class="button button-secondary" href="${escapeHtml(event.eventUrl)}" target="_blank" rel="noopener noreferrer">${text.eventLink}</a>`);
      actionsEl.innerHTML = actions.join('');
    }

    dialog.showModal();
  }
})();
