<?php
defined( 'ABSPATH' ) || exit;
$lang = vrcin_theme_lang();
get_header();
?>
<main class="vrcin-dashboard-page">
  <?php
  if ( function_exists( 'vrcin_render_owner_dashboard' ) ) {
      vrcin_render_owner_dashboard( $lang );
  } else {
      echo '<div class="container"><p>VRC Italia Network Core is required.</p></div>';
  }
  ?>
</main>
<?php get_footer(); ?>
