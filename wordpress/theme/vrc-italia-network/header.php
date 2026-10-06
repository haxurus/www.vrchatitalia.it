<?php
defined( 'ABSPATH' ) || exit;
$lang = vrcin_theme_lang();
$other = 'it' === $lang ? 'en' : 'it';
$view = get_query_var( 'vrcin_view' ) ?: 'home';
$home = vrcin_theme_url( 'home', $lang );
$events = vrcin_theme_url( 'events', $lang );
$dashboard = vrcin_theme_url( 'dashboard', $lang );
?><!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>" data-color-scheme="dark">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#07090e">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script>
(function(){try{var t=localStorage.getItem('vrcin-theme')==='light'?'light':'dark';document.documentElement.setAttribute('data-color-scheme',t);if(t==='light')document.documentElement.setAttribute('data-theme','light');}catch(e){}})();
(function(){try{if(sessionStorage.getItem('vrcin-loaded'))return;var r=document.documentElement;r.classList.add('is-loading');setTimeout(function(){r.classList.remove('is-loading');},8000);}catch(e){}})();
</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?php echo esc_url( $home ); ?>" aria-label="VRC Italia Network - Home">
      <span class="brand-logo" aria-hidden="true">VRC</span>
      <span class="brand-name">VRC Italia Network</span>
    </a>

    <nav class="nav-links" aria-label="<?php echo esc_attr( 'it' === $lang ? 'Navigazione principale' : 'Main navigation' ); ?>">
      <a class="<?php echo 'home' === $view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $home ); ?>">Home</a>
      <a href="<?php echo esc_url( $home . '#progetto' ); ?>"><?php echo 'it' === $lang ? 'Progetto' : 'Project'; ?></a>
      <a href="<?php echo esc_url( $home . '#community' ); ?>"><?php echo 'it' === $lang ? 'Community' : 'Communities'; ?></a>
      <a class="<?php echo 'events' === $view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $events ); ?>"><?php echo 'it' === $lang ? 'Eventi' : 'Events'; ?></a>
      <a href="<?php echo esc_url( $home . '#gallery' ); ?>">Gallery</a>
      <a href="<?php echo esc_url( $home . '#candidatura' ); ?>"><?php echo 'it' === $lang ? 'Candidati' : 'Apply'; ?></a>
    </nav>

    <div class="language-switcher" aria-label="Language selector">
      <a class="<?php echo 'it' === $lang ? 'is-active' : ''; ?>" href="<?php echo esc_url( 'events' === $view ? vrcin_theme_url( 'events', 'it' ) : ( 'dashboard' === $view ? vrcin_theme_url( 'dashboard', 'it' ) : vrcin_theme_url( 'home', 'it' ) ) ); ?>">IT</a>
      <a class="<?php echo 'en' === $lang ? 'is-active' : ''; ?>" href="<?php echo esc_url( 'events' === $view ? vrcin_theme_url( 'events', 'en' ) : ( 'dashboard' === $view ? vrcin_theme_url( 'dashboard', 'en' ) : vrcin_theme_url( 'home', 'en' ) ) ); ?>">EN</a>
    </div>

    <button class="theme-toggle theme-toggle--desktop" type="button" aria-label="Theme">
      <span class="theme-toggle__sun" aria-hidden="true">☀</span><span class="theme-toggle__moon" aria-hidden="true">☾</span>
    </button>

    <?php if ( is_user_logged_in() && current_user_can( 'vrcin_manage_community' ) ) : ?>
      <a class="nav-cta" href="<?php echo esc_url( $dashboard ); ?>"><?php echo 'it' === $lang ? 'Dashboard' : 'Dashboard'; ?></a>
    <?php else : ?>
      <a class="nav-cta" href="<?php echo esc_url( $home . '#candidatura' ); ?>"><?php echo 'it' === $lang ? 'Aderisci al progetto' : 'Join the project'; ?></a>
    <?php endif; ?>

    <button class="menu-toggle" type="button" aria-label="Menu" aria-expanded="false"><span></span></button>
  </div>

  <nav class="mobile-menu" aria-label="Mobile navigation">
    <a href="<?php echo esc_url( $home ); ?>">Home</a>
    <a href="<?php echo esc_url( $home . '#progetto' ); ?>"><?php echo 'it' === $lang ? 'Progetto' : 'Project'; ?></a>
    <a href="<?php echo esc_url( $home . '#community' ); ?>"><?php echo 'it' === $lang ? 'Community' : 'Communities'; ?></a>
    <a href="<?php echo esc_url( $events ); ?>"><?php echo 'it' === $lang ? 'Eventi' : 'Events'; ?></a>
    <a href="<?php echo esc_url( $home . '#gallery' ); ?>">Gallery</a>
    <a href="<?php echo esc_url( $home . '#candidatura' ); ?>"><?php echo 'it' === $lang ? 'Candidati' : 'Apply'; ?></a>
    <div class="mobile-language-switcher">
      <a class="<?php echo 'it' === $lang ? 'is-active' : ''; ?>" href="<?php echo esc_url( 'events' === $view ? vrcin_theme_url( 'events', 'it' ) : ( 'dashboard' === $view ? vrcin_theme_url( 'dashboard', 'it' ) : vrcin_theme_url( 'home', 'it' ) ) ); ?>">Italiano</a>
      <a class="<?php echo 'en' === $lang ? 'is-active' : ''; ?>" href="<?php echo esc_url( 'events' === $view ? vrcin_theme_url( 'events', 'en' ) : ( 'dashboard' === $view ? vrcin_theme_url( 'dashboard', 'en' ) : vrcin_theme_url( 'home', 'en' ) ) ); ?>">English</a>
    </div>
    <button class="theme-toggle theme-toggle--mobile" type="button" aria-label="Theme"><span class="theme-toggle__icons"><span class="theme-toggle__sun">☀</span><span class="theme-toggle__moon">☾</span></span><span class="theme-toggle__label">Theme</span></button>
  </nav>
</header>
