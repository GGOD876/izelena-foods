<?php
$has_heat = isset($_GET['heat']);
$filter = $has_heat ? sanitize_key(wp_unslash($_GET['heat'])) : 'all';
$sort = isset($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'featured';
$items = izelena_demo_products();
if ($has_heat && !in_array($filter, array('mild', 'medium', 'hot'), true)) $items = array();
if (in_array($filter, array('mild', 'medium', 'hot'), true)) $items = array_values(array_filter($items, function ($item) use ($filter) { return $item['heat'] === $filter; }));
if ('price' === $sort) usort($items, function ($a, $b) { return (int) $a['price'] - (int) $b['price']; });
if ('newest' === $sort) $items = array_reverse($items);
get_header(); ?>
<main class="page">
  <div class="page-intro"><p class="eyebrow">The flavour collection</p><h1>Shop sauces, seasonings<br><em>and salsas.</em></h1><p>Choose a size, follow the Scotchimeter and bring Jamaican flavour to the table.</p></div>
  <div class="shop-toolbar"><div class="filters" aria-label="Filter by heat"><a class="<?php echo 'all' === $filter ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/shop/')); ?>">All</a><a class="<?php echo 'mild' === $filter ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/shop/?heat=mild')); ?>">Mild</a><a class="<?php echo 'medium' === $filter ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/shop/?heat=medium')); ?>">Medium</a><a class="<?php echo 'hot' === $filter ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/shop/?heat=hot')); ?>">Hot</a></div><label>Sort <select onchange="if(this.value)location.href=this.value"><option value="<?php echo esc_url(home_url('/shop/')); ?>" <?php selected($sort, 'featured'); ?>>Featured</option><option value="<?php echo esc_url(home_url('/shop/?sort=price')); ?>" <?php selected($sort, 'price'); ?>>Price: low to high</option><option value="<?php echo esc_url(home_url('/shop/?sort=newest')); ?>" <?php selected($sort, 'newest'); ?>>Newest</option></select></label></div>
  <div class="product-grid archive"><?php if ($items) { foreach ($items as $item) izelena_product_card($item, true); } else { ?><div class="callout"><strong>No products match this heat level yet.</strong><br><a href="<?php echo esc_url(home_url('/shop/')); ?>">Reset filters</a></div><?php } ?></div>
</main>
<?php get_footer(); ?>
