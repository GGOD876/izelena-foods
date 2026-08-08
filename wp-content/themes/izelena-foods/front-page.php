<?php get_header(); $products = izelena_demo_products(); ?>
<main>
  <section class="hero">
    <div class="hero-copy">
      <p class="eyebrow">From Izelena's garden to your kitchen</p>
      <h1>Bring home the <em>bold</em> taste of Jamaica.</h1>
      <p class="lede"><?php echo esc_html(get_theme_mod('izelena_tagline', 'Scotch Bonnet-forward sauces, seasonings and salsas inspired by family tradition - made to bring exotic island flavour to every season.')); ?></p>
      <div class="actions"><a class="btn primary" href="<?php echo esc_url(home_url('/shop/')); ?>">Shop the flavours <span aria-hidden="true">↗</span></a><a class="text-btn" href="<?php echo esc_url(home_url('/our-story/')); ?>">Read our story <span aria-hidden="true">→</span></a></div>
      <div class="hero-proof"><span>Small-batch flavour</span><span>Scotch Bonnet roots</span><span>Jamaica, always</span></div>
    </div>
    <section class="hero-art" aria-roledescription="carousel" aria-label="Izelena flavour catalogue">
      <div class="hero-carousel" data-hero-carousel>
        <div class="hero-carousel-viewport">
          <div class="hero-carousel-track">
            <figure class="hero-slide is-active" id="hero-slide-1" role="tabpanel" aria-roledescription="slide" aria-label="Catalogue page 1 of 4">
              <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/catalogue/sales-catalogue-page-03.png'); ?>" alt="Izelena sauces and salsas arranged on a wooden table" fetchpriority="high">
            </figure>
            <figure class="hero-slide" id="hero-slide-2" role="tabpanel" aria-roledescription="slide" aria-label="Catalogue page 2 of 4" aria-hidden="true">
              <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/catalogue/sales-catalogue-page-05.png'); ?>" alt="Izelena Jerk BBQ Sauce and Jerk Seasoning on a wooden table" loading="lazy">
            </figure>
            <figure class="hero-slide" id="hero-slide-3" role="tabpanel" aria-roledescription="slide" aria-label="Catalogue page 3 of 4" aria-hidden="true">
              <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/catalogue/sales-catalogue-page-07.png'); ?>" alt="Izelena Mango Salsa with mango, peppers and fritters" loading="lazy">
            </figure>
            <figure class="hero-slide" id="hero-slide-4" role="tabpanel" aria-roledescription="slide" aria-label="Catalogue page 4 of 4" aria-hidden="true">
              <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/catalogue/sales-catalogue-page-09.png'); ?>" alt="Izelena sauces and seasonings grouped together" loading="lazy">
            </figure>
          </div>
        </div>
        <div class="hero-carousel-controls">
          <button class="hero-carousel-arrow" type="button" data-carousel-prev aria-label="Show previous catalogue page"><span aria-hidden="true">←</span></button>
          <div class="hero-carousel-status" aria-live="polite"><span data-carousel-current>01</span><span aria-hidden="true"> / </span><span>04</span></div>
          <button class="hero-carousel-arrow" type="button" data-carousel-next aria-label="Show next catalogue page"><span aria-hidden="true">→</span></button>
          <div class="hero-carousel-indicators" role="tablist" aria-label="Choose a catalogue page">
            <button type="button" role="tab" aria-selected="true" aria-controls="hero-slide-1" aria-label="Show catalogue page 1" data-carousel-indicator="0"></button>
            <button type="button" role="tab" aria-selected="false" aria-controls="hero-slide-2" aria-label="Show catalogue page 2" data-carousel-indicator="1"></button>
            <button type="button" role="tab" aria-selected="false" aria-controls="hero-slide-3" aria-label="Show catalogue page 3" data-carousel-indicator="2"></button>
            <button type="button" role="tab" aria-selected="false" aria-controls="hero-slide-4" aria-label="Show catalogue page 4" data-carousel-indicator="3"></button>
          </div>
          <button class="hero-carousel-toggle" type="button" data-carousel-toggle aria-pressed="false">Pause</button>
        </div>
      </div>
    </section>
  </section>
  <section class="marquee"><span>Season your story</span><span aria-hidden="true">•</span><span>Share the flavour</span><span aria-hidden="true">•</span><span>Feed your people</span><span aria-hidden="true">•</span></section>
  <section class="section products-section"><div class="section-head"><div><p class="eyebrow">Start here</p><h2>Find your favourite.</h2></div><a class="text-btn" href="<?php echo esc_url(home_url('/shop/')); ?>">See all flavours <span aria-hidden="true">↗</span></a></div><div class="product-grid home-products"><?php foreach (array_slice($products, 0, 4) as $product) { izelena_product_card($product, true, true); } ?></div></section>
  <section class="scotch"><div><p class="eyebrow">The Scotchimeter</p><h2>How much heat<br><em>are you feeling?</em></h2><p>Every pepper has a personality. Find your level and make it yours.</p><a class="btn primary" href="<?php echo esc_url(home_url('/shop/')); ?>">Shop by heat <span aria-hidden="true">↗</span></a></div><div class="heat-levels"><a href="<?php echo esc_url(home_url('/shop/?heat=mild')); ?>"><b>01</b><span class="peppers">🌶</span><h3>Tups</h3><p>Gentle warmth and easy everyday flavour.</p></a><a class="hot-level" href="<?php echo esc_url(home_url('/shop/?heat=medium')); ?>"><b>02</b><span class="peppers">🌶🌶</span><h3>Nuh 2 Much</h3><p>Balanced heat for flavour lovers.</p></a><a href="<?php echo esc_url(home_url('/shop/?heat=hot')); ?>"><b>03</b><span class="peppers">🌶🌶🌶</span><h3>Whole Heap</h3><p>Bold Caribbean fire for serious pepper fans.</p></a></div></section>
  <section class="story-tease"><div class="story-image"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/izelena-face-outline.png'); ?>" alt="Izelena face outline"></div><div><p class="eyebrow">Flavour with roots</p><h2>Long before the brand, there was Izelena.</h2><p>In the farming district of Aboukir, St. Ann, Miss Izelena cultivated Scotch Bonnet pepper and a love of feeding people. Her family now carries that flavour and spirit forward.</p><a class="btn dark" href="<?php echo esc_url(home_url('/our-story/')); ?>">Discover the family story <span aria-hidden="true">↗</span></a></div></section>
  <section class="wholesale"><p class="eyebrow">For the people who feed people</p><h2>Bring Izelena to your shelves.</h2><p>Retailer, restaurant, distributor or just flavour-curious? Let's talk.</p><a class="btn yellow" href="<?php echo esc_url(home_url('/contact/')); ?>">Wholesale enquiries <span aria-hidden="true">↗</span></a></section>
</main>
<?php get_footer(); ?>
