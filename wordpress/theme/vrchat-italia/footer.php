<?php $lang = vri_theme_lang(); ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-shell">
      <div class="footer-brand">
        <span class="footer-brand__title">VRChat Italia</span>
        <p><?php echo esc_html( 'it' === $lang ? 'Un punto di incontro indipendente dedicato alle community italiane presenti nel mondo di VRChat.' : 'An independent meeting point dedicated to Italian communities across VRChat.' ); ?></p>
      </div>
      <div class="footer-links">
        <a href="<?php echo esc_url( vri_theme_url( 'home', $lang ) . '#progetto' ); ?>"><?php echo 'it' === $lang ? 'Progetto' : 'Project'; ?></a>
        <a href="<?php echo esc_url( vri_theme_url( 'home', $lang ) . '#community' ); ?>"><?php echo 'it' === $lang ? 'Community' : 'Communities'; ?></a>
        <a href="<?php echo esc_url( vri_theme_url( 'events', $lang ) ); ?>"><?php echo 'it' === $lang ? 'Eventi' : 'Events'; ?></a>
        <a href="<?php echo esc_url( vri_theme_url( 'home', $lang ) . '#gallery' ); ?>">Gallery</a>
        <a href="https://vrc.group/VRCITA.1559" target="_blank" rel="noopener noreferrer">VRChat Group</a>
        <a href="<?php echo esc_url( vri_theme_url( 'home', $lang ) . '#candidatura' ); ?>"><?php echo 'it' === $lang ? 'Candidati' : 'Apply'; ?></a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>VRChat Italia © <?php echo esc_html( gmdate( 'Y' ) ); ?> · Made with 💚 by <a href="https://haxurus.com" target="_blank" rel="noopener noreferrer">Haxurus</a></span>
      <span>Italian Community Network</span>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body></html>
