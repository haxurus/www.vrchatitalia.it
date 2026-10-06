(() => {
  const calendarEl = document.getElementById('events-calendar');
  if (!calendarEl || !window.FullCalendar || !window.VRCIN_EVENTS) return;

  const lang = VRCIN_EVENTS.lang === 'it' ? 'it' : 'en';
  const locale = lang === 'it' ? 'it' : 'en-gb';
  const endpoint = VRCIN_EVENTS.endpoint;

  const text = lang === 'it' ? {
    today:'Oggi', month:'Mese', week:'Settimana', day:'Giorno', list:'Agenda',
    date:'Data e ora', community:'Community', world:'Mondo / luogo', access:'Accesso',
    platforms:'Piattaforme', capacity:'Capienza', people:'persone',
    group:'Apri gruppo VRChat ↗', register:'Registrati ↗', page:'Pagina evento ↗',
    noCapacity:'Non specificata'
  } : {
    today:'Today', month:'Month', week:'Week', day:'Day', list:'Agenda',
    date:'Date & time', community:'Community', world:'World / location', access:'Access',
    platforms:'Platforms', capacity:'Capacity', people:'people',
    group:'Open VRChat group ↗', register:'Register ↗', page:'Event page ↗',
    noCapacity:'Not specified'
  };

  const tagLabels = lang === 'it' ? {
    gaming:'Gaming Night', drinking:'Drinking Night', 'world-exploration':'Esplorazione Mondi',
    'dj-disco':'DJ Set / Disco Night', hangout:'Relax / Hangout', 'age-gated':'Solo 18+', other:'Altro'
  } : {
    gaming:'Gaming Night', drinking:'Drinking Night', 'world-exploration':'World Exploration',
    'dj-disco':'DJ Set / Disco Night', hangout:'Relax / Hangout', 'age-gated':'Age Gated (18+)', other:'Other'
  };

  const searchInput = document.getElementById('events-search');
  const communitySelect = document.getElementById('events-community');
  const tagSelect = document.getElementById('events-tag');
  const platformSelect = document.getElementById('events-platform');
  const accessSelect = document.getElementById('events-access');
  const resetButton = document.getElementById('events-reset');

  let lastRawEvents = [];

  function normalize(v) {
    return String(v || '').toLocaleLowerCase(locale);
  }

  function updateCommunityOptions(events) {
    if (!communitySelect) return;
    const current = communitySelect.value;
    const first = communitySelect.options[0]?.textContent || '';
    const names = [...new Set(events.map(e => e.extendedProps?.community).filter(Boolean))].sort((a,b) => a.localeCompare(b));
    communitySelect.innerHTML = '';
    const all = document.createElement('option');
    all.value = '';
    all.textContent = first;
    communitySelect.appendChild(all);
    names.forEach(name => {
      const option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      communitySelect.appendChild(option);
    });
    communitySelect.value = names.includes(current) ? current : '';
  }

  function applyFilters(events) {
    const search = normalize(searchInput?.value);
    const community = communitySelect?.value || '';
    const tag = tagSelect?.value || '';
    const platform = platformSelect?.value || '';
    const access = accessSelect?.value || '';

    return events.filter(event => {
      const props = event.extendedProps || {};
      if (community && props.community !== community) return false;
      if (tag && !(props.tags || []).includes(tag)) return false;
      if (platform && !(props.platforms || []).includes(platform)) return false;
      if (access && props.access !== access) return false;
      if (search) {
        const haystack = normalize([
          event.title,
          props.community,
          props.description,
          props.world,
          ...(props.tags || []).map(t => tagLabels[t] || t)
        ].join(' '));
        if (!haystack.includes(search)) return false;
      }
      return true;
    });
  }

  async function loadEvents(info, success, failure) {
    try {
      const url = new URL(endpoint, window.location.origin);
      url.searchParams.set('start', info.startStr);
      url.searchParams.set('end', info.endStr);
      url.searchParams.set('lang', lang);
      const response = await fetch(url.toString(), { credentials:'same-origin' });
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const events = await response.json();
      lastRawEvents = Array.isArray(events) ? events : [];
      updateCommunityOptions(lastRawEvents);
      // FullCalendar v7 reads `className`; the REST API still sends v6-style `classNames`.
      success(applyFilters(lastRawEvents).map(event => ({
        ...event,
        className: event.className || (event.classNames || []).join(' ')
      })));
    } catch (error) {
      failure(error);
    }
  }

  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale,
    colorScheme: document.documentElement.getAttribute('data-color-scheme') || 'dark',
    timeZone:'Europe/Rome',
    initialView:'dayGridMonth',
    firstDay:1,
    nowIndicator:true,
    navLinks:true,
    dayMaxEvents:true,
    height:'auto',
    expandRows:true,
    headerToolbar:{ left:'prev,next today', center:'title', right:'dayGridMonth,timeGridWeek,timeGridDay,listMonth' },
    buttonText:{ today:text.today },
    views:{
      dayGridMonth:{ buttonText:text.month },
      timeGridWeek:{ buttonText:text.week },
      timeGridDay:{ buttonText:text.day },
      listMonth:{ buttonText:text.list }
    },
    slotMinTime:'16:00:00',
    slotMaxTime:'30:00:00',
    scrollTime:'20:00:00',
    eventTimeFormat:{ hour:'2-digit', minute:'2-digit', hour12:false },
    events:loadEvents,
    eventClick(info) {
      info.jsEvent.preventDefault();
      openDialog(info.event);
    }
  });

  calendar.render();

  document.addEventListener('vrcin-theme-change', event => {
    if (event.detail?.theme) calendar.setOption('colorScheme', event.detail.theme);
  });

  [communitySelect, tagSelect, platformSelect, accessSelect].forEach(el => el?.addEventListener('change', () => calendar.refetchEvents()));
  searchInput?.addEventListener('input', () => calendar.refetchEvents());
  resetButton?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (communitySelect) communitySelect.value = '';
    if (tagSelect) tagSelect.value = '';
    if (platformSelect) platformSelect.value = '';
    if (accessSelect) accessSelect.value = '';
    calendar.refetchEvents();
  });

  const dialog = document.getElementById('event-dialog');
  document.getElementById('event-dialog-close')?.addEventListener('click', () => dialog?.close());
  dialog?.addEventListener('click', e => { if (e.target === dialog) dialog.close(); });

  function escapeHtml(value) {
    return String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
  }

  function detail(label, value) {
    return '<div class="event-detail"><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value || '-') + '</strong></div>';
  }

  function formatRange(event) {
    const formatter = new Intl.DateTimeFormat(locale, {
      weekday:'long', day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit',
      hour12:false, timeZone:'Europe/Rome'
    });
    const start = event.start ? formatter.format(event.start) : '';
    const end = event.end ? formatter.format(event.end) : '';
    return end ? start + ' - ' + end : start;
  }

  function openDialog(event) {
    if (!dialog) return;
    const props = event.extendedProps || {};
    document.getElementById('event-dialog-title').textContent = event.title || '';
    document.getElementById('event-dialog-community').textContent = props.community || '';
    document.getElementById('event-dialog-description').textContent = props.description || '';

    const grid = document.getElementById('event-detail-grid');
    if (grid) {
      grid.innerHTML = [
        detail(text.date, formatRange(event)),
        detail(text.community, props.community),
        detail(text.world, props.world),
        detail(text.access, props.access),
        detail(text.platforms, (props.platforms || []).join(', ')),
        detail(text.capacity, props.capacity ? props.capacity + ' ' + text.people : text.noCapacity)
      ].join('');
    }

    const tags = document.getElementById('event-dialog-tags');
    if (tags) tags.innerHTML = (props.tags || []).map(tag => '<span>' + escapeHtml(tagLabels[tag] || tag) + '</span>').join('');

    const actions = [];
    if (props.groupUrl) actions.push('<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(props.groupUrl) + '">' + text.group + '</a>');
    if (props.registrationUrl) actions.push('<a class="button button-primary" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(props.registrationUrl) + '">' + text.register + '</a>');
    if (props.eventUrl) actions.push('<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(props.eventUrl) + '">' + text.page + '</a>');
    const actionEl = document.getElementById('event-dialog-actions');
    if (actionEl) actionEl.innerHTML = actions.join('');

    dialog.showModal();
  }
})();