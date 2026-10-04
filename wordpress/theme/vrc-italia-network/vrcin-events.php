<?php
defined( 'ABSPATH' ) || exit;
$lang = vrcin_theme_lang();
$it = 'it' === $lang;
get_header();
?>
<main>
  <section class="events-hero">
    <div class="container events-hero__inner">
      <div>
        <span class="section-kicker"><?php echo esc_html( $it ? 'Calendario condiviso' : 'Shared calendar' ); ?></span>
        <h1><?php echo esc_html( $it ? 'Eventi' : 'Events' ); ?></h1>
        <p><?php echo esc_html( $it ? 'Tutti gli appuntamenti delle community aderenti, raccolti in un unico calendario. Cambia visualizzazione, filtra gli eventi e apri una voce per vedere tutti i dettagli.' : 'Events from participating communities, collected in one calendar. Switch views, filter the schedule and open an event to see all available details.' ); ?></p>
      </div>
      <div class="events-hero__meta"><span>Timezone</span><strong>Europe/Rome</strong><small><?php echo esc_html( $it ? "Gli orari vengono mostrati nell'ora italiana." : 'Times are displayed in Italian local time.' ); ?></small></div>
    </div>
  </section>

  <section class="events-calendar-section">
    <div class="container">
      <div class="events-toolbar-panel">
        <div class="event-filter"><label for="events-search"><?php echo esc_html( $it ? 'Cerca' : 'Search' ); ?></label><input id="events-search" type="search" placeholder="<?php echo esc_attr( $it ? 'Titolo, community, tag...' : 'Title, community, tag...' ); ?>" autocomplete="off"></div>
        <div class="event-filter"><label for="events-community">Community</label><select id="events-community"><option value=""><?php echo esc_html( $it ? 'Tutte le community' : 'All communities' ); ?></option></select></div>
        <div class="event-filter"><label for="events-tag">Tag</label><select id="events-tag">
          <option value=""><?php echo esc_html( $it ? 'Tutti i tag' : 'All tags' ); ?></option>
          <option value="gaming">Gaming Night</option><option value="drinking">Drinking Night</option>
          <option value="world-exploration"><?php echo esc_html( $it ? 'Esplorazione Mondi' : 'World Exploration' ); ?></option>
          <option value="dj-disco">DJ Set / Disco Night</option><option value="hangout">Relax / Hangout</option>
          <option value="age-gated"><?php echo esc_html( $it ? 'Solo 18+' : 'Age Gated (18+)' ); ?></option>
          <option value="other"><?php echo esc_html( $it ? 'Altro' : 'Other' ); ?></option>
        </select></div>
        <div class="event-filter"><label for="events-platform"><?php echo esc_html( $it ? 'Piattaforma' : 'Platform' ); ?></label><select id="events-platform"><option value=""><?php echo esc_html( $it ? 'Tutte' : 'All' ); ?></option><option>PC</option><option>Quest</option><option>Android</option></select></div>
        <div class="event-filter"><label for="events-access"><?php echo esc_html( $it ? 'Accesso' : 'Access' ); ?></label><select id="events-access"><option value=""><?php echo esc_html( $it ? 'Qualsiasi' : 'Any' ); ?></option><option value="public">Public</option><option value="group">Group</option><option value="friends">Friends+</option><option value="private"><?php echo esc_html( $it ? 'Privato' : 'Private' ); ?></option></select></div>
        <button class="events-reset" id="events-reset" type="button"><?php echo esc_html( $it ? 'Azzera filtri' : 'Reset filters' ); ?></button>
      </div>

      <div class="events-legend">
        <span><i class="legend-dot legend-dot--gaming"></i> Gaming Night</span>
        <span><i class="legend-dot legend-dot--drinking"></i> Drinking Night</span>
        <span><i class="legend-dot legend-dot--world"></i> <?php echo esc_html( $it ? 'Esplorazione Mondi' : 'World Exploration' ); ?></span>
        <span><i class="legend-dot legend-dot--dj"></i> DJ Set / Disco Night</span>
        <span><i class="legend-dot legend-dot--hangout"></i> Relax / Hangout</span>
        <span><i class="legend-dot legend-dot--age"></i> <?php echo esc_html( $it ? 'Solo 18+' : 'Age Gated (18+)' ); ?></span>
        <span><i class="legend-dot legend-dot--other"></i> <?php echo esc_html( $it ? 'Altro' : 'Other' ); ?></span>
      </div>

      <div class="calendar-shell"><div id="events-calendar" aria-label="VRC Italia Network events calendar"></div></div>
    </div>
  </section>
</main>

<dialog class="event-dialog" id="event-dialog" aria-labelledby="event-dialog-title">
  <button class="event-dialog__close" id="event-dialog-close" type="button" aria-label="Close">×</button>
  <div class="event-dialog__header"><div><span class="event-dialog__community" id="event-dialog-community"></span><h2 id="event-dialog-title"></h2><p id="event-dialog-description"></p></div></div>
  <div class="event-detail-grid" id="event-detail-grid"></div>
  <div class="event-dialog__tags" id="event-dialog-tags"></div>
  <div class="event-dialog__actions" id="event-dialog-actions"></div>
</dialog>
<?php get_footer(); ?>
