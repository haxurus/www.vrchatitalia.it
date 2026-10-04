<?php
defined( 'ABSPATH' ) || exit;
$lang = vrcin_theme_lang();
$it = 'it' === $lang;
$communities = function_exists( 'vrcin_get_communities' ) ? vrcin_get_communities() : array();
$banners = function_exists( 'vrcin_get_home_banners' ) ? vrcin_get_home_banners() : array();
$gallery = function_exists( 'vrcin_get_home_gallery' ) ? vrcin_get_home_gallery() : array();
get_header();

$slides = array_slice( $banners, 0, 4 );
while ( count( $slides ) < 4 ) { $slides[] = null; }
?>
<main>
  <section class="hero" id="home" aria-label="VRC Italia Network">
    <div class="hero-slideshow" aria-hidden="true">
      <?php foreach ( $slides as $i => $slide ) : ?>
        <div class="hero-slide hero-slide--<?php echo esc_attr( $i + 1 ); ?>"<?php echo $slide ? ' style="background-image:linear-gradient(135deg,rgba(7,9,15,.20),rgba(7,9,15,.08)),url(' . esc_url( $slide['url'] ) . ')"' : ''; ?>></div>
      <?php endforeach; ?>
    </div>
    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="container hero-inner">
      <div class="hero-copy">
        <span class="eyebrow">Italian VRChat Community Network</span>
        <h1>VRChat <span>Italia</span></h1>
        <p class="hero-lead"><?php echo esc_html( $it ? 'Le community italiane di VRChat, raccolte in un unico spazio.' : 'Italian VRChat communities, gathered in one place.' ); ?></p>
        <div class="hero-actions">
          <a class="button button-primary" href="#community"><?php echo esc_html( $it ? 'Scopri le community' : 'Explore communities' ); ?></a>
          <a class="button button-secondary" href="#candidatura"><?php echo esc_html( $it ? 'Partecipa' : 'Join' ); ?></a>
        </div>
      </div>
    </div>
    <a class="scroll-indicator" href="#progetto" aria-label="Scroll">
      <span class="scroll-indicator__label"><?php echo esc_html( $it ? 'Scorri' : 'Scroll' ); ?></span>
      <span class="scroll-indicator__mouse" aria-hidden="true"><span></span></span>
      <span class="scroll-indicator__chevron" aria-hidden="true"></span>
    </a>
    <div class="hero-progress" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
  </section>

  <section class="section project-section" id="progetto">
    <div class="container project-layout">
      <div>
        <span class="section-kicker"><?php echo esc_html( $it ? 'Il progetto' : 'The project' ); ?></span>
        <h2><?php echo esc_html( $it ? 'Un punto di incontro per la VR italiana.' : 'A meeting point for the Italian VR community.' ); ?></h2>
      </div>
      <div class="project-copy">
        <p><?php echo esc_html( $it ? 'VRC Italia Network nasce per rendere più semplice scoprire le community italiane attive su VRChat, conoscere nuovi gruppi e dare visibilità alle realtà che contribuiscono ogni giorno alla scena italiana.' : 'VRC Italia Network makes it easier to discover Italian communities active on VRChat, meet new groups and give visibility to the people and projects contributing to the Italian scene every day.' ); ?></p>
        <p class="project-note"><?php echo esc_html( $it ? 'Il portale non sostituisce le singole community: le mette in contatto e le rende più facili da trovare.' : 'The portal does not replace individual communities: it connects them and makes them easier to discover.' ); ?></p>
      </div>

      <div class="national-group">
        <div class="national-group__flag" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="national-group__copy">
          <span class="national-group__label"><?php echo esc_html( $it ? 'Gruppo VRC Italia Network' : 'VRC Italia Network Group' ); ?></span>
          <strong><?php echo esc_html( $it ? 'Porta con orgoglio la community italiana nel mondo.' : 'Represent the Italian community around the world with pride.' ); ?></strong>
          <p><?php echo esc_html( $it ? 'Tutti gli utenti italiani sono invitati a entrare nel gruppo comune per mostrare la propria nazionalità e rappresentare insieme la community italiana su VRChat.' : 'All Italian users are invited to join the shared group, show their nationality and represent the Italian community together across VRChat.' ); ?></p>
        </div>
        <a class="button button-primary national-group__button" href="https://vrc.group/VRCITA.1559" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $it ? 'Entra nel gruppo ↗' : 'Join the group ↗' ); ?></a>
      </div>
    </div>
  </section>

  <section class="section communities-section" id="community">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="section-kicker"><?php echo esc_html( $it ? 'Community aderenti' : 'Participating communities' ); ?></span>
          <h2><?php echo esc_html( $it ? 'Trova il tuo posto.' : 'Find your place.' ); ?></h2>
        </div>
        <p><?php echo esc_html( $it ? 'Scopri le realtà italiane che aderiscono al progetto e i loro collegamenti ufficiali.' : 'Discover the Italian communities participating in the project and their official links.' ); ?></p>
      </div>

      <div class="community-grid">
        <?php if ( $communities ) : foreach ( $communities as $index => $community ) :
          $banner = $community['banner_attachment_id'] ? wp_get_attachment_image_url( $community['banner_attachment_id'], 'large' ) : '';
          $logo = $community['logo_attachment_id'] ? wp_get_attachment_image_url( $community['logo_attachment_id'], 'thumbnail' ) : '';
          $link = $community['vrchat_group_url'] ?: ( $community['discord_url'] ?: $community['website_url'] );
          $description = $it ? $community['description_it'] : $community['description_en'];
          if ( ! $description ) { $description = $it ? $community['description_en'] : $community['description_it']; }
          ?>
          <article class="community-card">
            <div class="community-card__media"<?php echo $banner ? ' style="background-image:url(' . esc_url( $banner ) . ')"' : ''; ?>>
              <span class="community-card__badge">Community</span>
            </div>
            <div class="community-card__body">
              <div class="community-logo"><?php if ( $logo ) : ?><img src="<?php echo esc_url( $logo ); ?>" alt=""><?php else : ?><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?><?php endif; ?></div>
              <div><h3><?php echo esc_html( $community['name'] ); ?></h3><p><?php echo esc_html( $description ?: ( $it ? 'Community italiana aderente a VRC Italia Network.' : 'Italian community participating in VRC Italia Network.' ) ); ?></p></div>
              <?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" class="community-link"><?php echo esc_html( $it ? 'Scopri' : 'Discover' ); ?> <span>↗</span></a><?php endif; ?>
            </div>
          </article>
        <?php endforeach; else : ?>
          <a class="community-card community-card--join" href="#candidatura"><span class="join-plus">+</span><div><span class="section-kicker"><?php echo esc_html( $it ? 'Prime adesioni' : 'First communities' ); ?></span><h3><?php echo esc_html( $it ? 'Il network sta prendendo forma.' : 'The network is taking shape.' ); ?></h3><p><?php echo esc_html( $it ? 'Candidati per essere tra le prime community presenti.' : 'Apply to be among the first participating communities.' ); ?></p></div></a>
        <?php endif; ?>

        <a class="community-card community-card--join" href="#candidatura">
          <span class="join-plus">+</span>
          <div><span class="section-kicker"><?php echo esc_html( $it ? 'La tua community' : 'Your community' ); ?></span><h3><?php echo esc_html( $it ? 'Vuoi comparire qui?' : 'Want to be featured here?' ); ?></h3><p><?php echo esc_html( $it ? 'Consulta i requisiti e invia la candidatura.' : 'Check the requirements and submit your application.' ); ?></p></div>
        </a>
      </div>
    </div>
  </section>

  <section class="section home-events-section" id="eventi-home">
    <div class="container home-events-panel">
      <div class="home-events-copy">
        <span class="section-kicker"><?php echo esc_html( $it ? 'Eventi delle community' : 'Community events' ); ?></span>
        <h2><?php echo esc_html( $it ? 'Scopri cosa succede su VRChat.' : "See what's happening on VRChat." ); ?></h2>
        <p><?php echo esc_html( $it ? 'Consulta il calendario condiviso per trovare gaming night, serate social, esplorazioni di mondi, DJ set e gli altri eventi organizzati dalle community aderenti.' : 'Browse the shared calendar to find gaming nights, social hangouts, world exploration, DJ sets and other events organized by participating communities.' ); ?></p>
        <div class="home-events-actions">
          <a class="button button-primary" href="<?php echo esc_url( vrcin_theme_url( 'events', $lang ) ); ?>"><?php echo esc_html( $it ? 'Apri il calendario' : 'Open the calendar' ); ?></a>
          <span><?php echo esc_html( $it ? 'Viste mese, settimana, giorno e agenda' : 'Month, week, day and agenda views' ); ?></span>
        </div>
      </div>
      <a class="home-events-preview" href="<?php echo esc_url( vrcin_theme_url( 'events', $lang ) ); ?>" aria-label="Calendar">
        <div class="home-events-preview__top"><span><?php echo esc_html( $it ? 'Calendario condiviso' : 'Shared calendar' ); ?></span><strong>VRC Italia Network</strong></div>
        <div class="home-events-preview__week" aria-hidden="true"><?php foreach ( $it ? array('LUN','MAR','MER','GIO','VEN','SAB','DOM') : array('MON','TUE','WED','THU','FRI','SAT','SUN') as $day ) : ?><span><?php echo esc_html( $day ); ?></span><?php endforeach; ?></div>
        <div class="home-events-preview__grid" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span class="has-event event-green"></span><span></span>
          <span></span><span class="has-event event-blue"></span><span></span><span></span><span></span><span></span><span></span>
          <span></span><span></span><span></span><span></span><span class="has-event event-red"></span><span></span><span></span>
        </div>
        <div class="home-events-preview__footer"><span><i class="legend-dot legend-dot--gaming"></i> Gaming Night</span><span><i class="legend-dot legend-dot--dj"></i> DJ Set</span><strong><?php echo esc_html( $it ? 'Vai agli eventi ↗' : 'View events ↗' ); ?></strong></div>
      </a>
    </div>
  </section>

  <section class="section gallery-section" id="gallery">
    <div class="container gallery-heading">
      <span class="section-kicker">Community shots</span>
      <h2><?php echo esc_html( $it ? 'Momenti dal mondo virtuale.' : 'Moments from the virtual world.' ); ?></h2>
      <p><?php echo esc_html( $it ? 'Una selezione casuale di scatti approvati e inviati dagli utenti delle community aderenti.' : 'A random selection of approved screenshots shared by participating communities.' ); ?></p>
    </div>
    <?php
    $gallery_items = $gallery;
    if ( $gallery_items && count( $gallery_items ) < 12 ) {
        while ( count( $gallery_items ) < 12 ) { $gallery_items = array_merge( $gallery_items, $gallery ); }
    }
    $gallery_items = array_slice( $gallery_items, 0, 12 );
    $rows = array( array_slice( $gallery_items, 0, 6 ), array_slice( $gallery_items, 6, 6 ) );
    foreach ( $rows as $row_index => $row ) :
      if ( ! $row ) { continue; }
      ?>
      <div class="photo-marquee<?php echo $row_index ? ' photo-marquee--secondary' : ''; ?>">
        <div class="photo-track <?php echo $row_index ? 'photo-track--right' : 'photo-track--left'; ?>">
          <?php for ( $repeat = 0; $repeat < 2; $repeat++ ) : foreach ( $row as $item ) : ?>
            <div class="shot" style="background-image:url('<?php echo esc_url( $item['url'] ); ?>')"><span>@<?php echo esc_html( $item['community'] ); ?></span></div>
          <?php endforeach; endfor; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="section application-section" id="candidatura">
    <div class="container">
      <div class="application-panel">
        <div class="application-intro">
          <span class="section-kicker"><?php echo esc_html( $it ? 'Aderisci a VRC Italia Network' : 'Join VRC Italia Network' ); ?></span>
          <h2><?php echo esc_html( $it ? 'Porta la tua community nel network.' : 'Bring your community into the network.' ); ?></h2>
          <p><?php echo esc_html( $it ? 'Per mantenere il progetto utile e ordinato, le realtà che desiderano aderire devono rispettare alcuni requisiti di base.' : 'To keep the project useful and organized, communities wishing to join must meet a few basic requirements.' ); ?></p>
        </div>
        <div class="requirements">
          <div class="requirement"><span>01</span><div><strong><?php echo esc_html( $it ? 'Community prevalentemente italiana' : 'Predominantly Italian community' ); ?></strong><p><?php echo esc_html( $it ? "Oltre l'80% degli utenti deve essere italiano e oltre l'80% degli eventi deve essere svolto in lingua italiana." : 'More than 80% of members must be Italian and more than 80% of events must be held in Italian.' ); ?></p></div></div>
          <div class="requirement"><span>02</span><div><strong><?php echo esc_html( $it ? 'Presenza attiva su VRChat' : 'Active presence on VRChat' ); ?></strong><p><?php echo esc_html( $it ? 'La community deve aver aperto almeno 5 lobby su VRChat negli ultimi 7 giorni.' : 'The community must have opened at least 5 VRChat lobbies within the last 7 days.' ); ?></p></div></div>
          <div class="requirement"><span>03</span><div><strong><?php echo esc_html( $it ? 'Contatti verificabili' : 'Verifiable contacts' ); ?></strong><p><?php echo esc_html( $it ? 'Deve essere disponibile almeno un canale ufficiale verificabile, come Discord, Instagram o un sito web.' : 'At least one verifiable official channel must be available, such as Discord, Instagram or a website.' ); ?></p></div></div>
          <div class="requirement"><span>04</span><div><strong><?php echo esc_html( $it ? 'Ambiente compatibile con il progetto' : 'Environment compatible with the project' ); ?></strong><p><?php echo esc_html( $it ? 'La community deve avere un ambiente e una gestione compatibili con le finalità del progetto VRC Italia Network.' : "The community's environment and management must be compatible with the purpose of the VRC Italia Network project." ); ?></p></div></div>
        </div>
        <div class="application-action">
          <div><strong><?php echo esc_html( $it ? 'Pronto a candidare la tua community?' : 'Ready to submit your community?' ); ?></strong><span><?php echo esc_html( $it ? 'Il modulo verrà aperto direttamente sul sito.' : 'The application form opens directly on the site.' ); ?></span></div>
          <button class="button button-primary button-form" type="button" data-vrcin-open-application><?php echo esc_html( $it ? 'Compila il modulo ↗' : 'Open the form ↗' ); ?></button>
        </div>
      </div>
    </div>
  </section>
</main>
<?php if ( function_exists( 'vrcin_render_application_popup' ) ) { vrcin_render_application_popup( $lang ); } ?>
<?php get_footer(); ?>
