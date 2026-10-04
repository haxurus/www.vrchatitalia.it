<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main class="section"><div class="container"><h1><?php bloginfo( 'name' ); ?></h1><?php if ( have_posts() ) : while ( have_posts() ) : the_post(); the_content(); endwhile; endif; ?></div></main>
<?php get_footer(); ?>
