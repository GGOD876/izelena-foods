</main>
<footer class="site-footer">
  <div class="footer-top">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/izelena-logo.png'); ?>" alt="Izelena Foods">
    <p>Exotic Island Flavours<br>for Every Season.</p>
    <a class="btn yellow" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">Shop the flavours <span aria-hidden="true">↗</span></a>
  </div>
  <div class="footer-bottom">
    <span>© <?php echo esc_html(date('Y')); ?> Izelena Foods</span>
    <div><a href="<?php echo esc_url(home_url('/shop/')); ?>">Shop</a><a href="<?php echo esc_url(home_url('/our-story/')); ?>">Our Story</a><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></div>
    <span>Made with Jamaican spirit.</span>
  </div>
</footer>
<?php wp_footer(); ?></body></html>
