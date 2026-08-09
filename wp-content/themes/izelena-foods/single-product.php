<?php
defined('ABSPATH') || exit;

$slug = get_query_var('izelena_product_route');
if (!$slug && isset($_SERVER['REQUEST_URI'])) {
    $request_path = trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/');
    if (preg_match('#(?:^|/)product/([^/]+)/?$#', $request_path, $route_match)) $slug = $route_match[1];
}
$slug = sanitize_title((string) ($slug ?: get_query_var('name')));
$product = false;
if (izelena_woocommerce_active()) {
    if ($slug && is_numeric($slug)) $product = wc_get_product(absint($slug));
    if (!$product && $slug) {
        $product_post = get_page_by_path($slug, OBJECT, 'product');
        if ($product_post && 'publish' === $product_post->post_status) $product = wc_get_product($product_post->ID);
    }
    if ($product && 'publish' !== $product->get_status()) $product = false;
}

get_header();
if (!$product || !is_a($product, 'WC_Product')) :
    global $wp_query;
    if ($wp_query) $wp_query->is_404 = true;
    status_header(404);
    ?>
    <main class="page"><div class="empty callout"><h1>Flavour not found.</h1><p>This product is not published in WooCommerce yet.</p><a class="btn primary" href="<?php echo esc_url(home_url('/shop/')); ?>">Return to the shop</a></div></main>
    <?php get_footer(); return; endif;

$variations = izelena_product_variation_data($product);
$attribute_choices = array();
foreach ($variations as $variation) {
    foreach ($variation['attributes'] as $attribute => $value) {
        if (!isset($attribute_choices[$attribute])) $attribute_choices[$attribute] = array();
        if (!isset($attribute_choices[$attribute][$value])) $attribute_choices[$attribute][$value] = isset($variation['attribute_labels'][$attribute]) && $variation['attribute_labels'][$attribute] ? $variation['attribute_labels'][$attribute] : $value;
    }
}
$approved_image = izelena_approved_product_image($product);
$image = $approved_image ? '<img class="product-detail-image" src="' . esc_url($approved_image) . '" alt="' . esc_attr($product->get_name() . ' product') . '">' : '';
$heat = izelena_product_heat($product->get_id());
$tone = izelena_heat_tone($heat);
$heat_label = $heat ? ucfirst($heat) : __('Heat pending approval', 'izelena-foods');
$available = $product->is_purchasable() && $product->is_in_stock();
$description = izelena_product_description($product);
$initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->get_name()), 0, 2));
?>
<main class="page product-page">
  <div class="page-intro"><p class="eyebrow">The flavour collection</p><p>Choose an approved size, review the live WooCommerce price and add the selected variation to your cart.</p></div>
  <div class="product-detail" data-wc-product data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-product-name="<?php echo esc_attr($product->get_name()); ?>" data-product-tag="Izelena flavour collection" data-product-description="<?php echo esc_attr($description); ?>" data-product-price="<?php echo esc_attr((float) $product->get_price()); ?>" data-product-heat="<?php echo esc_attr($heat); ?>" data-product-initials="<?php echo esc_attr($initials); ?>" data-product-tone="<?php echo esc_attr($tone); ?>" data-product-image="<?php echo esc_attr($approved_image); ?>" data-product-variations="<?php echo esc_attr(wp_json_encode($variations)); ?>">
    <div class="product-visual <?php echo esc_attr($tone); ?>"><?php echo $image ? $image : '<span class="product-mark">' . esc_html(strtoupper(substr($product->get_name(), 0, 1))) . '</span>'; ?><span class="heat-pill heat-pill-<?php echo esc_attr($heat ?: 'pending'); ?>"><?php echo esc_html($heat_label); ?></span></div>
    <div>
      <p class="eyebrow">Izelena flavour collection</p>
      <h1><?php echo esc_html($product->get_name()); ?></h1>
      <div class="product-description"><?php echo wp_kses_post($product->get_description() ?: $product->get_short_description()); ?></div>
      <strong class="product-meta" data-detail-price><?php echo wp_kses_post(izelena_product_price_html($product)); ?></strong>
      <?php foreach ($attribute_choices as $attribute => $choices) : ?>
        <label><?php echo esc_html(izelena_variation_attribute_label($attribute)['label']); ?><select data-wc-attribute="<?php echo esc_attr($attribute); ?>"><?php foreach ($choices as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
      <?php endforeach; ?>
      <div class="modal-quantity"><span>Quantity</span><div class="quantity-controls"><button type="button" data-modal-quantity-change="-1" aria-label="Decrease quantity">-</button><input type="number" min="1" max="999999" value="1" data-modal-quantity aria-label="Quantity"><button type="button" data-modal-quantity-change="1" aria-label="Increase quantity">+</button></div></div>
      <button class="btn primary full" type="button" data-detail-add data-product-id="<?php echo esc_attr($product->get_id()); ?>" <?php disabled(!$available); ?>><?php echo $available ? 'Add to cart' : 'Unavailable'; ?> <span>+</span></button>
      <p data-detail-stock><?php echo $available ? esc_html__('Available', 'izelena-foods') : esc_html__('Out of stock or unavailable', 'izelena-foods'); ?></p>
      <h3>How to use</h3><div><?php echo wp_kses_post($product->get_short_description() ?: 'Product usage, ingredients, allergens, nutrition and storage guidance will be published from the approved product record.'); ?></div>
      <a class="text-btn" href="<?php echo esc_url(home_url('/shop/')); ?>">Explore the full flavour collection <span aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
</main>
<?php get_footer(); ?>
