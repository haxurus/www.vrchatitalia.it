<?php
defined( 'ABSPATH' ) || exit;
$lang = vri_theme_lang();
get_header();
?>
<main class="vri-dashboard-page">
  <?php
  if ( function_exists( 'vri_render_owner_dashboard' ) ) {
      vri_render_owner_dashboard( $lang );
  } else {
      echo '<div class="container"><p>VRChat Italia Core is required.</p></div>';
  }
  ?>
</main>
<?php get_footer(); ?>
