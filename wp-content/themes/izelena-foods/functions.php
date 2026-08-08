<?php
if (!defined('ABSPATH')) exit;

function izelena_setup() {
    load_theme_textdomain('izelena-foods', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('woocommerce');
    register_nav_menus(array('primary' => __('Primary navigation', 'izelena-foods'), 'footer' => __('Footer navigation', 'izelena-foods')));
}
add_action('after_setup_theme', 'izelena_setup');

function izelena_assets() {
    wp_enqueue_style('izelena-style', get_stylesheet_uri(), array(), '4.0.6');
    wp_enqueue_script('izelena-interactions', get_template_directory_uri() . '/assets/theme.js', array(), '4.0.2', true);
}
add_action('wp_enqueue_scripts', 'izelena_assets');

function izelena_sanitize_text($value) { return sanitize_text_field($value); }
function izelena_sanitize_textarea($value) { return sanitize_textarea_field($value); }
function izelena_sanitize_email($value) { return sanitize_email($value); }

function izelena_customizer($wp_customize) {
    $wp_customize->add_section('izelena_brand', array('title' => __('Izelena Brand', 'izelena-foods'), 'priority' => 30));
    $fields = array(
        'announcement' => array('Announcement', 'text', 'Authentically Jamaican'),
        'tagline' => array('Hero supporting copy', 'textarea', 'Scotch Bonnet-forward sauces, seasonings and salsas inspired by family tradition - made to bring exotic island flavour to every season.'),
        'phone' => array('Contact phone', 'text', ''),
        'email' => array('Contact email', 'email', 'info@izelenafoods.com'),
    );
    foreach ($fields as $id => $field) {
        $sanitize = 'izelena_sanitize_text';
        if ('textarea' === $field[1]) $sanitize = 'izelena_sanitize_textarea';
        if ('email' === $field[1]) $sanitize = 'izelena_sanitize_email';
        $wp_customize->add_setting('izelena_' . $id, array('default' => $field[2], 'sanitize_callback' => $sanitize));
        $wp_customize->add_control('izelena_' . $id, array('label' => __($field[0], 'izelena-foods'), 'section' => 'izelena_brand', 'type' => $field[1]));
    }
}
add_action('customize_register', 'izelena_customizer');

function izelena_demo_products() {
    return array(
        array('id' => 'jerk-seasoning', 'name' => 'Jerk Seasoning', 'tag' => 'The heartbeat of Jamaican cooking', 'desc' => 'An authentic jerk marinade bursting with pimento, thyme, scallion, and Scotch Bonnet heat.', 'price' => 900, 'heat' => 'medium', 'tone' => 'gold', 'note' => 'Sweet heat. Smoky finish.'),
        array('id' => 'jerk-bbq', 'name' => 'Jerk BBQ Sauce', 'tag' => 'Sweet heat. Smoky finish.', 'desc' => 'A bold fusion of classic BBQ sweetness and jerk spice for meats, wings and anything worth glazing.', 'price' => 150, 'heat' => 'medium', 'tone' => 'red', 'note' => 'Rich, smoky glaze.'),
        array('id' => 'mango', 'name' => 'Mango Salsa', 'tag' => 'Sweet island sunshine', 'desc' => 'A smooth tropical mango sauce with a mild finish - perfect for dipping, glazing and pairing.', 'price' => 140, 'heat' => 'mild', 'tone' => 'yellow', 'note' => 'Soft mango. Gentle warmth.'),
        array('id' => 'spicy-mango', 'name' => 'Spicy Mango Salsa', 'tag' => 'Sweet meets fire', 'desc' => 'Ripe mango sweetness and pepper heat, balanced for a perfectly bright sweet-spicy kick.', 'price' => 150, 'heat' => 'hot', 'tone' => 'green', 'note' => 'A bright, balanced kick.'),
        array('id' => 'sorrel', 'name' => 'Sorrel Pepper Sauce', 'tag' => 'Tangy, spicy, unforgettable', 'desc' => 'Traditional Jamaican sorrel meets fiery peppers in a vibrant balance of tangy sweetness and heat.', 'price' => 150, 'heat' => 'hot', 'tone' => 'burgundy', 'note' => 'Bold Caribbean heat.'),
        array('id' => 'crushed', 'name' => 'Crushed Pepper Sauce', 'tag' => 'Bring the heat. Keep the flavour.', 'desc' => 'A vibrant fiery pepper sauce designed to enhance - not overpower - every meal.', 'price' => 150, 'heat' => 'hot', 'tone' => 'black', 'note' => 'For serious pepper fans.', 'soon' => true),
    );
}

function izelena_product_heat($product_id) {
    $heat = get_post_meta($product_id, '_izelena_heat_level', true);
    return in_array($heat, array('mild', 'medium', 'hot'), true) ? $heat : 'medium';
}

function izelena_product_card($product, $fallback = false) {
    $is_wc = !$fallback && is_object($product) && is_a($product, 'WC_Product');
    if ($is_wc) {
        $id = (string) $product->get_id();
        $name = $product->get_name();
        $tag = 'Izelena flavour collection';
        $desc = $product->get_short_description() ? wp_strip_all_tags($product->get_short_description()) : 'Jamaican flavour for every season.';
        $heat = izelena_product_heat($product->get_id());
        $tone = 'red';
        $url = $product->get_permalink();
        $image = $product->get_image('woocommerce_thumbnail', array('class' => 'product-image'));
        $price = function_exists('wc_price') && '' !== $product->get_price() ? wc_price(wc_get_price_to_display($product), array('currency' => 'JMD')) : '';
        $action = '<a class="add-btn" href="' . esc_url($url) . '">View <span>↗</span></a>';
        if ($product->is_purchasable() && $product->is_in_stock() && $product->is_type('simple')) $action = '<a class="add-btn add_to_cart_button ajax_add_to_cart" href="' . esc_url($product->add_to_cart_url()) . '" data-product_id="' . esc_attr($product->get_id()) . '">Add <span>+</span></a>';
    } else {
        $id = $product['id'] ?? sanitize_title($product['name']);
        $name = $product['name']; $tag = $product['tag']; $desc = $product['desc']; $heat = $product['heat']; $tone = $product['tone']; $image = '';
        $url = home_url('/product/' . sanitize_title($name) . '/');
        $price = 'From J$' . number_format_i18n((float) $product['price']);
        $action = !empty($product['soon']) ? '<button class="add-btn" type="button" disabled>Soon <span>+</span></button>' : '<button class="add-btn" type="button" data-demo-add="' . esc_attr($id) . '">Add <span>+</span></button>';
    }
    $initials = '';
    foreach (preg_split('/\s+/', $name) as $word) $initials .= substr($word, 0, 1);
    $numeric_price = $is_wc ? (float) $product->get_price() : (float) $product['price'];
    echo '<article class="product-card ' . esc_attr($tone) . '" data-product-id="' . esc_attr($id) . '" data-product-name="' . esc_attr($name) . '" data-product-price="' . esc_attr($numeric_price) . '" data-product-tone="' . esc_attr($tone) . '"><div class="product-visual">' . $image . '<span class="product-mark">' . esc_html(strtoupper($initials)) . '</span><span class="heat-pill">' . esc_html($heat) . '</span>' . (!empty($product['soon']) ? '<span class="soon">Coming soon</span>' : '') . '</div><div class="product-info"><p class="eyebrow">' . esc_html($tag) . '</p><h3><a href="' . esc_url($url) . '">' . esc_html($name) . '</a></h3><p>' . esc_html($desc) . '</p><div class="product-row"><strong>' . wp_kses_post($price) . '</strong>' . $action . '</div></div></article>';
}

function izelena_product_filter_query($query) {
    if (is_admin() || !$query->is_main_query()) return;
    $heat = isset($_GET['heat']) ? sanitize_key(wp_unslash($_GET['heat'])) : '';
    if (!in_array($heat, array('mild', 'medium', 'hot'), true)) return;
    if ($query->is_post_type_archive('product')) {
        $meta = (array) $query->get('meta_query');
        $meta[] = array('key' => '_izelena_heat_level', 'value' => $heat, 'compare' => '=');
        $query->set('meta_query', $meta);
    }
}
add_action('pre_get_posts', 'izelena_product_filter_query');

function izelena_woo_product_meta_query($meta_query) {
    $heat = isset($_GET['heat']) ? sanitize_key(wp_unslash($_GET['heat'])) : '';
    if (in_array($heat, array('mild', 'medium', 'hot'), true)) $meta_query[] = array('key' => '_izelena_heat_level', 'value' => $heat, 'compare' => '=');
    return $meta_query;
}
add_filter('woocommerce_product_query_meta_query', 'izelena_woo_product_meta_query');

function izelena_heat_meta_box() { add_meta_box('izelena_heat', __('Scotchimeter heat', 'izelena-foods'), 'izelena_heat_meta_box_html', 'product', 'side'); }
add_action('add_meta_boxes', 'izelena_heat_meta_box');
function izelena_heat_meta_box_html($post) { wp_nonce_field('izelena_heat_save', 'izelena_heat_nonce'); $current = izelena_product_heat($post->ID); echo '<label for="izelena_heat_level">' . esc_html__('Heat level', 'izelena-foods') . '</label><select name="izelena_heat_level" id="izelena_heat_level" style="width:100%"><option value="mild" ' . selected($current, 'mild', false) . '>Mild / Tups</option><option value="medium" ' . selected($current, 'medium', false) . '>Medium / Nuh 2 Much</option><option value="hot" ' . selected($current, 'hot', false) . '>Hot / Whole Heap</option></select>'; }
function izelena_save_heat($post_id) { if (!isset($_POST['izelena_heat_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['izelena_heat_nonce'])), 'izelena_heat_save') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) return; $heat = isset($_POST['izelena_heat_level']) ? sanitize_key(wp_unslash($_POST['izelena_heat_level'])) : 'medium'; if (in_array($heat, array('mild', 'medium', 'hot'), true)) update_post_meta($post_id, '_izelena_heat_level', $heat); }
add_action('save_post_product', 'izelena_save_heat');

function izelena_register_routes() { add_rewrite_rule('^(our-story|contact|shop|wholesale)/?$', 'index.php?izelena_route=$matches[1]', 'top'); add_rewrite_rule('^product/([^/]+)/?$', 'index.php?izelena_product_route=$matches[1]', 'top'); }
add_action('init', 'izelena_register_routes');
function izelena_flush_routes_on_activation() { izelena_register_routes(); flush_rewrite_rules(); }
add_action('after_switch_theme', 'izelena_flush_routes_on_activation');
function izelena_route_query_var($vars) { $vars[] = 'izelena_route'; return $vars; }
add_filter('query_vars', 'izelena_route_query_var');
function izelena_product_query_var($vars) { $vars[] = 'izelena_product_route'; return $vars; }
add_filter('query_vars', 'izelena_product_query_var');
function izelena_route_template($template) {
    $route = get_query_var('izelena_route');
    if (in_array($route, array('our-story', 'contact', 'shop', 'wholesale'), true)) { $candidate = get_template_directory() . '/routes/' . $route . '.php'; if (file_exists($candidate)) return $candidate; }
    if (get_query_var('izelena_product_route')) return get_template_directory() . '/single-product.php';
    return $template;
}
add_filter('template_include', 'izelena_route_template');

/* Keep blueprint routes working immediately in the local container even when
 * rewrite rules have not been flushed since the theme refactor. */
function izelena_virtual_route_fallback() {
    if (is_admin() || empty($_SERVER['REQUEST_URI'])) return;
    $path = trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/');
    $routes = array('our-story' => 'our-story', 'contact' => 'contact', 'shop' => 'shop', 'wholesale' => 'wholesale');
    if (isset($routes[$path])) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
        include get_template_directory() . '/routes/' . $routes[$path] . '.php';
        exit;
    }
    if (preg_match('#^product/([^/]+)/?$#', $path, $match)) {
        global $wp_query;
        set_query_var('izelena_product_route', sanitize_title($match[1]));
        $wp_query->is_404 = false;
        status_header(200);
        include get_template_directory() . '/single-product.php';
        exit;
    }
}
add_action('template_redirect', 'izelena_virtual_route_fallback', 0);
