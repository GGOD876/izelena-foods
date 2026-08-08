<?php if (!defined('ABSPATH')) exit; get_header();
$products = izelena_demo_products();
$heat = isset($_GET['heat']) ? sanitize_key(wp_unslash($_GET['heat'])) : '';
$valid_heat = array('mild', 'medium', 'hot');
$invalid_heat = '' !== $heat && !in_array($heat, $valid_heat, true);
if (!$invalid_heat && '' !== $heat) $products = array_values(array_filter($products, function ($product) use ($heat) { return $product['heat'] === $heat; }));
$sort = isset($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'featured';
if ('price' === $sort) usort($products, function ($a, $b) { return $a['price'] <=> $b['price']; });
?>
<main class="page">
  <div class="page-intro"><p class="eyebrow">The flavour collection</p><h1>Shop sauces, seasonings<br><em>and salsas.</em></h1><p>Choose a size, follow the Scotchimeter and bring Jamaican flavour to the table.</p></div>
  <div class="shop-toolbar"><div class="filters" aria-label="Filter by heat">
    <?php foreach (array('' => 'All', 'mild' => 'Mild', 'medium' => 'Medium', 'hot' => 'Hot') as $value => $label) : ?><a class="<?php echo (!$invalid_heat && $heat === $value) || ('' === $value && '' === $heat) ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/shop/' . ('' === $value ? '' : '?heat=' . rawurlencode($value)))); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?>
  </div><label>Sort <select onchange="window.location.href=this.value"><option value="<?php echo esc_url(home_url('/shop/')); ?>" <?php selected($sort, 'featured'); ?>>Featured</option><option value="<?php echo esc_url(home_url('/shop/?sort=price')); ?>" <?php selected($sort, 'price'); ?>>Price: low to high</option><option value="<?php echo esc_url(home_url('/shop/?sort=newest')); ?>" <?php selected($sort, 'newest'); ?>>Newest</option></select></label></div>
  <?php if ($invalid_heat || !$products) : ?><div class="empty callout"><p>No flavours match that heat level yet.</p><a class="text-btn" href="<?php echo esc_url(home_url('/shop/')); ?>">View all flavours <span aria-hidden="true">↗</span></a></div><?php else : ?><div class="product-grid archive"><?php foreach ($products as $product) izelena_product_card($product, true); ?></div><?php endif; ?>
</main>
<?php get_footer(); ?>
