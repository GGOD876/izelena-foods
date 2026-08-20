<?php
/**
 * Reviewed WooCommerce seed/import contract.
 * Run inside WordPress with:
 * wp --path=/var/www/html --allow-root eval-file /opt/izelena/scripts/woocommerce-seed.php -- --csv=/path/catalogue.csv --report=/path/reconciliation.csv
 */
defined('ABSPATH') || exit;

if (!class_exists('WooCommerce') || !class_exists('WC_Product_Variable')) {
    WP_CLI::error('WooCommerce must be installed and active before seeding.');
}

function izelena_seed_arg($name, $default = '') {
    global $args;
    $argv = isset($args) && is_array($args) ? $args : array();
    foreach ($argv as $arg) if (0 === strpos($arg, '--' . $name . '=')) return substr($arg, strlen($name) + 3);
    return $default;
}

function izelena_seed_baseline($path) {
    $baseline = array();
    if (!$path || !is_readable($path) || !($handle = fopen($path, 'rb'))) return $baseline;
    $headers = fgetcsv($handle); if (!$headers) return $baseline;
    while (($row = fgetcsv($handle)) !== false) {
        $record = array(); foreach ($headers as $key => $header) $record[$header] = trim((string) ($row[$key] ?? ''));
        if (!empty($record['product_slug']) && !empty($record['expected_sku'])) $baseline[$record['product_slug'] . '|' . $record['expected_sku']] = $record;
    }
    fclose($handle); return $baseline;
}

function izelena_seed_image_id($path, $parent_id) {
    $legacy_image_paths = array(
        '17' => '/opt/izelena/product-images/jerk-seasoning.jpg',
        '18' => '/opt/izelena/product-images/jerk-bbq-sauce.jpg',
        '19' => '/opt/izelena/product-images/mango-salsa.jpg',
        '20' => '/opt/izelena/product-images/spicy-mango-salsa.jpg',
        '21' => '/opt/izelena/product-images/sorrel-pepper-sauce.jpg',
        '22' => '/opt/izelena/product-images/crushed-pepper-sauce-coming-soon.png',
    );
    if (isset($legacy_image_paths[(string) $path])) $path = $legacy_image_paths[(string) $path];
    if (ctype_digit((string) $path)) return wp_attachment_is_image(absint($path)) ? absint($path) : 0;
    if (is_readable($path)) {
        $source = wp_normalize_path(realpath($path) ?: $path);
        $existing = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_izelena_seed_source',
            'meta_value' => $source,
        ));
        if ($existing) return absint($existing[0]);
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = wp_tempnam(basename($source));
        if (!$tmp || !copy($source, $tmp)) return 0;
        $attachment_id = media_handle_sideload(array(
            'name' => basename($source),
            'tmp_name' => $tmp,
        ), $parent_id);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return 0;
        }
        update_post_meta($attachment_id, '_izelena_seed_source', $source);
        return absint($attachment_id);
    }
    if (filter_var($path, FILTER_VALIDATE_URL) && function_exists('media_sideload_image')) {
        $sideloaded = media_sideload_image($path, $parent_id, null, 'id');
        return is_wp_error($sideloaded) ? 0 : absint($sideloaded);
    }
    return 0;
}

function izelena_seed_value_matches($expected, $actual, $zero_may_be_blank = false) {
    $expected = (string) $expected;
    $actual = (string) $actual;
    return $expected === $actual || ($zero_may_be_blank && '0' === $expected && '' === $actual);
}

$csv_path = izelena_seed_arg('csv', getenv('IZELENA_SEED_CSV') ?: '');
$report_path = izelena_seed_arg('report', getenv('IZELENA_SEED_REPORT') ?: dirname($csv_path) . '/woocommerce-reconciliation.csv');
$baseline_path = izelena_seed_arg('baseline', getenv('IZELENA_SEED_BASELINE') ?: '');
$baseline = izelena_seed_baseline($baseline_path);
if (!$csv_path || !is_readable($csv_path)) WP_CLI::error('Set --csv to a readable approved product matrix.');

$handle = fopen($csv_path, 'rb');
$headers = fgetcsv($handle);
$required = array('product_slug', 'product_name', 'category', 'attribute_name', 'attribute_value', 'sku', 'regular_price', 'sale_price', 'stock_quantity', 'stock_status', 'heat_level', 'product_image_path', 'variation_image_path', 'tax_class', 'shipping_class', 'weight', 'length', 'width', 'height');
if (!$headers || array_diff($required, $headers)) WP_CLI::error('CSV is missing required catalogue columns.');
$index = array_flip($headers);
$rows = array();
while (($row = fgetcsv($handle)) !== false) {
    if (!array_filter($row, function ($value) { return '' !== trim((string) $value); })) continue;
    $record = array(); foreach ($headers as $key => $header) $record[$header] = trim((string) ($row[$key] ?? ''));
    foreach ($required as $field) if ('' === $record[$field] || false !== strpos($record[$field], 'CLIENT_INPUT')) WP_CLI::error('Unapproved or missing value in ' . $field . ' for ' . $record['product_slug'] . '.');
    if (!in_array(sanitize_key($record['stock_status']), array('instock', 'outofstock', 'onbackorder'), true)) WP_CLI::error('Invalid stock_status for ' . $record['product_slug'] . '.');
    $rows[] = $record;
}
fclose($handle);

$groups = array(); foreach ($rows as $record) $groups[$record['product_slug']][] = $record;
$report = array();
$mismatch_count = 0;
foreach ($groups as $slug => $records) {
    $product_image_paths = array_values(array_unique(array_column($records, 'product_image_path')));
    if (1 !== count($product_image_paths)) WP_CLI::error('Product image must be consistent across all variation rows for ' . $slug . '.');
    // WordPress path lookups can collide with an attachment when an imported
    // image shares the product slug. Query the product post directly and only
    // reuse a real WooCommerce product; otherwise start a fresh variable one.
    global $wpdb;
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'product' AND post_status NOT IN ('trash', 'auto-draft') ORDER BY ID ASC LIMIT 1",
        sanitize_title($slug)
    ));
    $existing = $existing_id ? get_post((int) $existing_id) : false;
    $product = $existing ? wc_get_product($existing->ID) : false;
    if (!$product) $product = new WC_Product_Variable();
    $first = $records[0];
    $product->set_name($first['product_name']);
    $product->set_slug(sanitize_title($slug));
    $product->set_description($first['description'] ?? '');
    $product->set_status('draft');
    $product->set_catalog_visibility('visible');
    $category = term_exists($first['category'], 'product_cat');
    if (!$category) $category = wp_insert_term($first['category'], 'product_cat');
    if (is_wp_error($category)) WP_CLI::error('Could not create product category ' . $first['category'] . '.');
    $category_id = (int) (is_array($category) ? $category['term_id'] : $category);
    $attribute_id = function_exists('wc_attribute_taxonomy_id_by_name') ? wc_attribute_taxonomy_id_by_name($first['attribute_name']) : 0;
    if (!$attribute_id && function_exists('wc_create_attribute')) {
        $attribute_id = wc_create_attribute(array('name' => $first['attribute_name'], 'slug' => sanitize_title($first['attribute_name']), 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false));
        if (is_wp_error($attribute_id)) WP_CLI::error('Could not create global attribute ' . $first['attribute_name'] . '.');
        if (function_exists('wc_register_product_taxonomies')) wc_register_product_taxonomies();
    }
    $attribute_taxonomy = function_exists('wc_attribute_taxonomy_name') ? wc_attribute_taxonomy_name($first['attribute_name']) : '';
    // Newly-created global attributes are persisted in the database during
    // this request, but WooCommerce normally registers their taxonomies on a
    // later request. Register the taxonomy here so a one-shot seed can proceed.
    if ($attribute_taxonomy && !taxonomy_exists($attribute_taxonomy)) {
        register_taxonomy($attribute_taxonomy, array('product'), array(
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => false,
            'public' => false,
            'update_count_callback' => '_update_post_term_count',
            'capabilities' => array(
                'manage_terms' => 'manage_product_terms',
                'edit_terms' => 'edit_product_terms',
                'delete_terms' => 'delete_product_terms',
                'assign_terms' => 'assign_product_terms',
            ),
        ));
    }
    if (!$attribute_id || !$attribute_taxonomy || !taxonomy_exists($attribute_taxonomy)) WP_CLI::error('Global attribute taxonomy could not be registered: ' . $first['attribute_name'] . '.');
    $term_ids = array();
    $attribute_terms = array();
    foreach (array_unique(array_column($records, 'attribute_value')) as $attribute_value) {
        $term = term_exists($attribute_value, $attribute_taxonomy); if (!$term) $term = wp_insert_term($attribute_value, $attribute_taxonomy);
        if (is_wp_error($term)) WP_CLI::error('Could not create attribute term ' . $attribute_value . '.');
        $term_id = (int) (is_array($term) ? $term['term_id'] : $term); $term_ids[] = $term_id; $attribute_terms[$attribute_value] = $term_id;
    }
    $attribute = new WC_Product_Attribute();
    $attribute->set_id((int) $attribute_id);
    $attribute->set_name($attribute_taxonomy);
    $attribute->set_options($term_ids);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $product->set_attributes(array($attribute));
    $product->set_category_ids(array($category_id));
    $product_image_id = izelena_seed_image_id($first['product_image_path'], $product->get_id());
    $product->set_image_id($product_image_id);
    $product_id = $product->save();
    $persisted_product = wc_get_product($product_id);
    $persisted_category_ids = $persisted_product ? $persisted_product->get_category_ids() : array();
    $persisted_attributes = $persisted_product ? $persisted_product->get_attributes() : array();
    foreach ($records as $record) {
        $variation_id = wc_get_product_id_by_sku($record['sku']);
        $variation = $variation_id ? wc_get_product($variation_id) : new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_sku($record['sku']);
        $variation->set_regular_price($record['regular_price']);
        $variation->set_sale_price($record['sale_price']);
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity((int) $record['stock_quantity']);
        $variation->set_stock_status(sanitize_key($record['stock_status']));
        $variation->set_weight($record['weight']);
        $variation->set_length($record['length']);
        $variation->set_width($record['width']);
        $variation->set_height($record['height']);
        $variation_attribute_key = $attribute_taxonomy;
        $variation_attribute_value = sanitize_title($record['attribute_value']);
        $variation->set_attributes(array($variation_attribute_key => $variation_attribute_value));
        $variation_image_id = izelena_seed_image_id($record['variation_image_path'], $product_id);
        if ($variation_image_id) $variation->set_image_id($variation_image_id);
        $tax_class = 'standard' === sanitize_title($record['tax_class']) ? '' : sanitize_title($record['tax_class']);
        $shipping_class_id = function_exists('wc_get_shipping_class_id_by_slug') ? wc_get_shipping_class_id_by_slug(sanitize_title($record['shipping_class'])) : 0;
        $variation->set_tax_class($tax_class);
        if (method_exists($variation, 'set_shipping_class_id')) $variation->set_shipping_class_id($shipping_class_id);
        $product->set_tax_class($tax_class);
        if (method_exists($product, 'set_shipping_class_id')) $product->set_shipping_class_id($shipping_class_id);
        $product->save();
        $variation_id = $variation->save();
        update_post_meta($product_id, '_izelena_heat_level', sanitize_key($record['heat_level']));
        $actual = wc_get_product($variation_id);
        $actual_sku = $actual ? (string) $actual->get_sku() : '';
        $actual_product_id = $actual ? (int) $actual->get_parent_id() : 0;
        $actual_heat = (string) get_post_meta($product_id, '_izelena_heat_level', true);
        $expected_shipping_slug = sanitize_title($record['shipping_class']);
        $expected_tax_slug = 'standard' === sanitize_title($record['tax_class']) ? 'standard' : sanitize_title($record['tax_class']);
        $actual_tax_slug = $actual && $actual->get_tax_class() ? sanitize_title($actual->get_tax_class()) : 'standard';
        $actual_shipping_id = $actual ? (int) $actual->get_shipping_class_id() : 0;
        $actual_shipping_term = $actual_shipping_id ? get_term($actual_shipping_id, 'product_shipping_class') : false;
        $actual_shipping_slug = $actual_shipping_term && !is_wp_error($actual_shipping_term) ? $actual_shipping_term->slug : 'standard';
        $expected_stock_status = sanitize_key($record['stock_status']);
        $actual_stock_status = $actual ? $actual->get_stock_status() : '';
        $baseline_record = isset($baseline[$slug . '|' . $record['sku']] ) ? $baseline[$slug . '|' . $record['sku']] : array();
        if ($baseline_path && (!$baseline_record || !preg_match('/^[1-9][0-9]*$/', (string) ($baseline_record['persisted_product_id'] ?? '')) || !preg_match('/^[1-9][0-9]*$/', (string) ($baseline_record['persisted_variation_id'] ?? '')))) WP_CLI::error('Baseline reconciliation is missing valid positive persisted IDs for ' . $slug . ' / ' . $record['sku'] . '.');
        $expected_product_id = isset($baseline_record['persisted_product_id']) ? absint($baseline_record['persisted_product_id']) : 0;
        $expected_variation_id = isset($baseline_record['persisted_variation_id']) ? absint($baseline_record['persisted_variation_id']) : 0;
        $mismatches = array();
        $persisted_category = get_term($category_id, 'product_cat');
        if (!$persisted_product || !in_array($category_id, $persisted_category_ids, true)) $mismatches[] = 'category';
        if (!$persisted_category || is_wp_error($persisted_category) || $persisted_category->name !== $first['category'] || $persisted_category->slug !== sanitize_title($first['category'])) $mismatches[] = 'category_identity';
        if (!isset($persisted_attributes[$attribute_taxonomy]) || (int) $persisted_attributes[$attribute_taxonomy]->get_id() !== (int) $attribute_id) $mismatches[] = 'attribute_taxonomy';
        if (isset($persisted_attributes[$attribute_taxonomy])) {
            $persisted_term_ids = array_map('absint', (array) $persisted_attributes[$attribute_taxonomy]->get_options()); sort($persisted_term_ids); $expected_term_ids = $term_ids; sort($expected_term_ids);
            if ($persisted_term_ids !== $expected_term_ids) $mismatches[] = 'attribute_terms';
            foreach ($attribute_terms as $term_name => $term_id) { $term = get_term($term_id, $attribute_taxonomy); if (!$term || is_wp_error($term) || $term->name !== $term_name || $term->slug !== sanitize_title($term_name)) $mismatches[] = 'attribute_term_' . sanitize_title($term_name); }
        }
        $persisted_product = wc_get_product($product_id);
        $actual_product_image_id = $persisted_product ? (int) $persisted_product->get_image_id() : 0;
        $actual_product_tax_slug = $persisted_product && $persisted_product->get_tax_class() ? sanitize_title($persisted_product->get_tax_class()) : 'standard';
        $actual_product_shipping_id = $persisted_product ? (int) $persisted_product->get_shipping_class_id() : 0;
        if (!$persisted_product || $actual_product_image_id !== (int) $product_image_id) $mismatches[] = 'product_image';
        if (!$product_image_id) $mismatches[] = 'product_image_unresolved';
        if (!$persisted_product || $actual_product_tax_slug !== $expected_tax_slug) $mismatches[] = 'product_tax';
        if (!$persisted_product || $actual_product_shipping_id !== (int) $shipping_class_id) $mismatches[] = 'product_shipping';
        if (!$actual || $actual_product_id !== (int) $product_id) $mismatches[] = 'product_id';
        if ($expected_product_id && $actual_product_id !== $expected_product_id) $mismatches[] = 'product_id_baseline';
        if (!$actual || (int) $variation_id <= 0) $mismatches[] = 'variation_id';
        if ($expected_variation_id && (int) $variation_id !== $expected_variation_id) $mismatches[] = 'variation_id_baseline';
        if (!$actual || $actual_sku !== (string) $record['sku']) $mismatches[] = 'sku';
        if (!$actual || (string) $actual->get_regular_price() !== (string) $record['regular_price']) $mismatches[] = 'regular_price';
        $actual_sale_price = $actual ? (string) $actual->get_sale_price() : '';
        // The CSV contract requires a value; equal regular/sale prices mean
        // "no sale", which WooCommerce persists as an empty sale field.
        $no_sale = '' === $actual_sale_price && (string) $record['sale_price'] === (string) $record['regular_price'];
        if (!$actual || (!$no_sale && $actual_sale_price !== (string) $record['sale_price'])) $mismatches[] = 'sale_price';
        if (!$actual || (int) $actual->get_stock_quantity() !== (int) $record['stock_quantity']) $mismatches[] = 'stock';
        if (!$actual || $actual_stock_status !== $expected_stock_status) $mismatches[] = 'stock_status';
        if (!$actual || (int) $actual->get_image_id() !== (int) $variation_image_id) $mismatches[] = 'variation_image';
        if (!$variation_image_id) $mismatches[] = 'variation_image_unresolved';
        if (!$actual || !izelena_seed_value_matches($record['weight'], $actual->get_weight(), true)) $mismatches[] = 'weight';
        if (!$actual || !izelena_seed_value_matches($record['length'], $actual->get_length(), true)) $mismatches[] = 'length';
        if (!$actual || !izelena_seed_value_matches($record['width'], $actual->get_width(), true)) $mismatches[] = 'width';
        if (!$actual || !izelena_seed_value_matches($record['height'], $actual->get_height(), true)) $mismatches[] = 'height';
        if (!$actual || $actual_heat !== (string) $record['heat_level']) $mismatches[] = 'heat';
        if (!$actual || $actual_tax_slug !== $expected_tax_slug) $mismatches[] = 'tax';
        if ('standard' !== $expected_shipping_slug && !$shipping_class_id) $mismatches[] = 'shipping_unresolved';
        if (!$actual || $actual_shipping_slug !== $expected_shipping_slug) $mismatches[] = 'shipping';
        $mismatch_count += count($mismatches);
        $report[] = array($slug, $first['category'], $attribute_taxonomy, $record['attribute_value'], $category_id, $product_id, $expected_product_id ?: 'NOT_SUPPLIED', $actual_product_id, $expected_variation_id ?: 'NOT_SUPPLIED', $variation_id, $record['sku'], $actual_sku, $record['regular_price'], $actual ? $actual->get_regular_price() : '', $record['sale_price'], $actual ? $actual->get_sale_price() : '', $record['stock_quantity'], $actual ? $actual->get_stock_quantity() : '', $expected_stock_status, $actual_stock_status, $record['weight'], $actual ? $actual->get_weight() : '', $record['length'], $actual ? $actual->get_length() : '', $record['width'], $actual ? $actual->get_width() : '', $record['height'], $actual ? $actual->get_height() : '', $record['heat_level'], $actual_heat, $record['product_image_path'], $actual_product_image_id, $record['variation_image_path'], $actual ? $actual->get_image_id() : '', $expected_tax_slug, $actual_tax_slug, $expected_shipping_slug, $actual_shipping_slug, $mismatches ? implode('|', $mismatches) : 'PASS');
    }
}

$report_handle = fopen($report_path, 'wb');
fputcsv($report_handle, array('product_slug', 'expected_category', 'attribute_taxonomy', 'attribute_value', 'actual_category_id', 'created_product_id', 'baseline_product_id', 'persisted_product_id', 'baseline_variation_id', 'persisted_variation_id', 'expected_sku', 'actual_sku', 'expected_regular_price', 'actual_regular_price', 'expected_sale_price', 'actual_sale_price', 'expected_stock', 'actual_stock', 'expected_stock_status', 'actual_stock_status', 'expected_weight', 'actual_weight', 'expected_length', 'actual_length', 'expected_width', 'actual_width', 'expected_height', 'actual_height', 'expected_heat', 'actual_heat', 'expected_product_image_path', 'actual_product_image_id', 'expected_variation_image_path', 'actual_variation_image_id', 'expected_tax_class', 'actual_tax_class', 'expected_shipping_class', 'actual_shipping_class', 'status'));
foreach ($report as $line) fputcsv($report_handle, $line);
fclose($report_handle);
if ($mismatch_count) WP_CLI::error(sprintf('Seeded %d variation rows but found %d reconciliation mismatches. Review %s before promotion.', count($report), $mismatch_count, $report_path));
WP_CLI::success(sprintf('Seeded %d variation rows and wrote a passing field-by-field reconciliation report to %s. Products remain draft until client approval.', count($report), $report_path));
