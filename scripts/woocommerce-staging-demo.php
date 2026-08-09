<?php
/**
 * Apply clearly marked fictional values for local storefront preview only.
 * Run with: IZELENA_STAGING_DEMO=1 wp --path=/var/www/html --allow-root eval-file this-file.php
 */
defined('ABSPATH') || exit;

if ('1' !== getenv('IZELENA_STAGING_DEMO')) {
    WP_CLI::error('Refusing to apply demo inventory without IZELENA_STAGING_DEMO=1.');
}

global $wpdb;

$profiles = array(
    '80-ml-sachet' => array('stock' => 24, 'weight' => '0.1', 'length' => '4', 'width' => '1', 'height' => '6'),
    '5-oz-bottle' => array('stock' => 12, 'weight' => '0.5', 'length' => '2.5', 'width' => '2.5', 'height' => '7'),
    '10-oz-jar' => array('stock' => 10, 'weight' => '0.8', 'length' => '3.5', 'width' => '3.5', 'height' => '4.5'),
    '18-oz-bottle' => array('stock' => 8, 'weight' => '1.3', 'length' => '3', 'width' => '3', 'height' => '9'),
    '1-gallon-bulk' => array('stock' => 4, 'weight' => '9', 'length' => '8', 'width' => '8', 'height' => '12'),
);

$slugs = array('jerk-seasoning', 'jerk-bbq-sauce', 'mango-salsa', 'spicy-mango-salsa', 'sorrel-pepper-sauce');
$demo_note = 'LOCAL STAGING DEMO ONLY - fictional stock, weight, and dimensions. Replace before production.';

function izelena_staging_product_id($slug) {
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'product' AND post_status NOT IN ('trash', 'auto-draft') ORDER BY ID ASC LIMIT 1",
        sanitize_title($slug)
    ));
}

foreach ($slugs as $slug) {
    $product_id = izelena_staging_product_id($slug);
    $product = $product_id ? wc_get_product($product_id) : false;
    if (!$product || !($product instanceof WC_Product_Variable)) WP_CLI::error('Variable product not found: ' . $slug . '.');

    $product->set_status('publish');
    update_post_meta($product_id, '_izelena_staging_demo', '1');
    update_post_meta($product_id, '_izelena_staging_demo_note', $demo_note);

    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) continue;
        $attribute_values = $variation->get_attributes();
        $variation_key = $attribute_values ? sanitize_title((string) reset($attribute_values)) : '';
        if (!isset($profiles[$variation_key])) WP_CLI::error('No demo profile for ' . $slug . ' / ' . $variation_key . '.');
        $profile = $profiles[$variation_key];
        $variation->set_status('publish');
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity($profile['stock']);
        $variation->set_stock_status('instock');
        $variation->set_weight($profile['weight']);
        $variation->set_length($profile['length']);
        $variation->set_width($profile['width']);
        $variation->set_height($profile['height']);
        $variation->save();
        update_post_meta($variation_id, '_izelena_staging_demo', '1');
        update_post_meta($variation_id, '_izelena_staging_demo_note', $demo_note);
    }

    $product->save();
    wc_delete_product_transients($product_id);
    WP_CLI::log(sprintf('Published staging product %s (%d) with %d variations.', $slug, $product_id, count($product->get_children())));
}

$coming_soon_id = izelena_staging_product_id('crushed-pepper-sauce');
$coming_soon = $coming_soon_id ? wc_get_product($coming_soon_id) : false;
if ($coming_soon) {
    $coming_soon->set_status('draft');
    $coming_soon->save();
    update_post_meta($coming_soon_id, '_izelena_staging_demo', '1');
    update_post_meta($coming_soon_id, '_izelena_staging_demo_note', 'LOCAL STAGING DEMO ONLY - kept draft because the catalogue marks this product coming soon and supplies no product photo.');
}

WP_CLI::success('Applied local staging demo values. Do not promote these inventory or shipping values to production.');
