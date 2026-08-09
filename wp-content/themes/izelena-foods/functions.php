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
    $theme_dir = get_template_directory();
    $style_path = $theme_dir . '/style.css';
    $fixes_path = $theme_dir . '/assets/title-fixes.css';
    $script_path = $theme_dir . '/assets/theme.js';
    $version = static function ($path) {
        return file_exists($path) ? (string) filemtime($path) : '4.4.0';
    };
    wp_enqueue_style('izelena-style', get_stylesheet_uri(), array(), $version($style_path));
    wp_enqueue_style('izelena-title-fixes', get_template_directory_uri() . '/assets/title-fixes.css', array('izelena-style'), $version($fixes_path));
    wp_enqueue_script('izelena-interactions', get_template_directory_uri() . '/assets/theme.js', array(), $version($script_path), true);
    wp_localize_script('izelena-interactions', 'izelenaConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'contactNonce' => wp_create_nonce('izelena_contact_submit'),
        'cartNonce' => wp_create_nonce('izelena_cart'),
        'woocommerce' => function_exists('WC') && class_exists('WooCommerce'),
        'checkoutEnabled' => function_exists('izelena_checkout_enabled') && izelena_checkout_enabled(),
        'demoMode' => function_exists('izelena_demo_mode') && izelena_demo_mode(),
        'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
        'shopUrl' => home_url('/shop/'),
        'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
    ));
}
add_action('wp_enqueue_scripts', 'izelena_assets');

function izelena_favicon() {
    echo '<link rel="icon" type="image/png" href="' . esc_url(get_template_directory_uri() . '/assets/izelena-flower.png') . '">';
    echo '<link rel="apple-touch-icon" href="' . esc_url(get_template_directory_uri() . '/assets/izelena-flower.png') . '">';
}
add_action('wp_head', 'izelena_favicon', 5);

function izelena_register_contact_submission() {
    register_post_type('izelena_submission', array(
        'labels' => array(
            'name' => __('Contact submissions', 'izelena-foods'),
            'singular_name' => __('Contact submission', 'izelena-foods'),
            'menu_name' => __('Contact submissions', 'izelena-foods'),
            'view_item' => __('View submission', 'izelena-foods'),
            'search_items' => __('Search submissions', 'izelena-foods'),
            'not_found' => __('No submissions found.', 'izelena-foods'),
        ),
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'has_archive' => false,
        'rewrite' => false,
        'query_var' => false,
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-email-alt',
    ));
}
add_action('init', 'izelena_register_contact_submission');

function izelena_contact_submission_columns($columns) {
    return array(
        'cb' => isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox" />',
        'title' => __('Submission', 'izelena-foods'),
        'izelena_email' => __('Email', 'izelena-foods'),
        'izelena_enquiry' => __('Enquiry type', 'izelena-foods'),
        'date' => isset($columns['date']) ? $columns['date'] : __('Date', 'izelena-foods'),
    );
}
add_filter('manage_izelena_submission_posts_columns', 'izelena_contact_submission_columns');

function izelena_contact_submission_column($column, $post_id) {
    if ('izelena_email' === $column) {
        echo esc_html(get_post_meta($post_id, '_izelena_contact_email', true));
    }
    if ('izelena_enquiry' === $column) {
        echo esc_html(get_post_meta($post_id, '_izelena_contact_enquiry', true));
    }
}
add_action('manage_izelena_submission_posts_custom_column', 'izelena_contact_submission_column', 10, 2);

function izelena_contact_submission_meta_box() {
    add_meta_box(
        'izelena_contact_details',
        __('Contact details', 'izelena-foods'),
        'izelena_contact_submission_meta_box_html',
        'izelena_submission',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_izelena_submission', 'izelena_contact_submission_meta_box');

function izelena_contact_submission_meta_box_html($post) {
    $fields = array(
        'Name' => '_izelena_contact_name',
        'Email' => '_izelena_contact_email',
        'Phone' => '_izelena_contact_phone',
        'Enquiry type' => '_izelena_contact_enquiry',
        'Consent' => '_izelena_contact_consent',
        'Mail attempt' => '_izelena_contact_mail_status',
    );
    echo '<dl class="izelena-contact-details">';
    foreach ($fields as $label => $meta_key) {
        echo '<dt>' . esc_html($label) . '</dt><dd>' . esc_html(get_post_meta($post->ID, $meta_key, true)) . '</dd>';
    }
    echo '</dl><p>' . esc_html__('The message is stored in the main editor above. Submissions are private and are not publicly queryable.', 'izelena-foods') . '</p>';
}

function izelena_contact_error($code, $message) {
    return new WP_Error($code, $message);
}

function izelena_contact_submission_data() {
    if (!isset($_POST['izelena_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['izelena_contact_nonce'])), 'izelena_contact_submit')) {
        return izelena_contact_error('invalid_nonce', __('Security check failed. Please refresh and try again.', 'izelena-foods'));
    }

    $honeypot = isset($_POST['website']) ? trim((string) wp_unslash($_POST['website'])) : '';
    if ('' !== $honeypot) {
        return izelena_contact_error('spam', __('We could not submit your message. Please try again.', 'izelena-foods'));
    }

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $enquiry = isset($_POST['enquiry']) ? sanitize_key(wp_unslash($_POST['enquiry'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $consent = isset($_POST['consent']) && '1' === (string) wp_unslash($_POST['consent']);
    $allowed_enquiries = array('general', 'retail', 'wholesale', 'stockist');

    if ('' === $name || '' === $message || !is_email($email) || !$consent || !in_array($enquiry, $allowed_enquiries, true)) {
        return izelena_contact_error('invalid_fields', __('Please complete the required fields and consent before sending your message.', 'izelena-foods'));
    }

    return array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'enquiry' => $enquiry,
        'message' => $message,
        'consent' => '1',
    );
}

function izelena_store_contact_submission($data) {
    $post_id = wp_insert_post(array(
        'post_type' => 'izelena_submission',
        'post_status' => 'private',
        'post_title' => sprintf(/* translators: %1$s is the sender name, %2$s is the submission time. */ __('%1$s — %2$s', 'izelena-foods'), $data['name'], current_time('mysql')),
        'post_content' => $data['message'],
        'post_author' => get_current_user_id(),
    ), true);
    if (is_wp_error($post_id)) return $post_id;

    foreach (array('name', 'email', 'phone', 'enquiry', 'consent') as $field) {
        update_post_meta($post_id, '_izelena_contact_' . $field, $data[$field]);
    }

    $recipient = get_theme_mod('izelena_email', get_option('admin_email'));
    $recipient = is_email($recipient) ? $recipient : get_option('admin_email');
    $subject = sprintf(__('New Izelena contact enquiry: %s', 'izelena-foods'), $data['name']);
    $body = "Name: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\nEnquiry type: {$data['enquiry']}\n\nMessage:\n{$data['message']}";
    $headers = array('Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $data['email']);
    $mail_sent = wp_mail($recipient, $subject, $body, $headers);
    update_post_meta($post_id, '_izelena_contact_mail_status', $mail_sent ? 'attempted' : 'failed');
    update_post_meta($post_id, '_izelena_contact_mail_attempted_at', current_time('mysql'));

    return $post_id;
}

function izelena_contact_redirect_response($success, $message) {
    $requested_redirect = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : wp_get_referer();
    $redirect = wp_validate_redirect($requested_redirect, home_url('/contact/'));
    $redirect = add_query_arg('contact_status', $success ? 'success' : 'error', $redirect);
    if (!$success) $redirect = add_query_arg('contact_message', $message, $redirect);
    wp_safe_redirect($redirect);
    exit;
}

function izelena_contact_submit_admin_post() {
    $data = izelena_contact_submission_data();
    if (is_wp_error($data)) izelena_contact_redirect_response(false, $data->get_error_message());
    $stored = izelena_store_contact_submission($data);
    if (is_wp_error($stored)) izelena_contact_redirect_response(false, __('We could not save your message. Please try again.', 'izelena-foods'));
    izelena_contact_redirect_response(true, __('Message received.', 'izelena-foods'));
}
add_action('admin_post_izelena_contact_submit', 'izelena_contact_submit_admin_post');
add_action('admin_post_nopriv_izelena_contact_submit', 'izelena_contact_submit_admin_post');

function izelena_contact_submit_ajax() {
    $data = izelena_contact_submission_data();
    if (is_wp_error($data)) wp_send_json_error(array('message' => $data->get_error_message()), 400);
    $stored = izelena_store_contact_submission($data);
    if (is_wp_error($stored)) wp_send_json_error(array('message' => __('We could not save your message. Please try again.', 'izelena-foods')), 500);
    wp_send_json_success(array('message' => __('Message received. Thanks for reaching out. We will be in touch soon.', 'izelena-foods')));
}
add_action('wp_ajax_izelena_contact_submit', 'izelena_contact_submit_ajax');
add_action('wp_ajax_nopriv_izelena_contact_submit', 'izelena_contact_submit_ajax');

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

/**
 * WooCommerce is the authoritative catalogue and cart when it is available.
 * The demo data below is retained only so the theme can still be previewed
 * before WooCommerce is installed; it is never used as a production fallback.
 */
function izelena_woocommerce_active() {
    return function_exists('WC') && class_exists('WooCommerce') && function_exists('wc_get_products');
}

function izelena_demo_mode() {
    if (!defined('IZELENA_DEMO_MODE') || !IZELENA_DEMO_MODE) return false;
    if (function_exists('wp_get_environment_type')) {
        return in_array(wp_get_environment_type(), array('local', 'development', 'staging'), true);
    }
    return true;
}

function izelena_checkout_enabled() {
    if (!izelena_woocommerce_active() || !function_exists('wc_get_checkout_url')) return false;
    $gates = array('IZELENA_CHECKOUT_RELEASED', 'IZELENA_PAYMENT_READY', 'IZELENA_SHIPPING_READY', 'IZELENA_TAX_READY', 'IZELENA_EMAIL_READY', 'IZELENA_HOSTING_READY');
    foreach ($gates as $gate) if (!defined($gate) || true !== constant($gate)) return false;
    return true;
}

function izelena_checkout_gate_redirect() {
    if (!is_admin() && function_exists('is_checkout') && is_checkout() && !izelena_checkout_enabled()) {
        wp_safe_redirect(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/shop/'));
        exit;
    }
}
add_action('template_redirect', 'izelena_checkout_gate_redirect', 5);

function izelena_catalogue_products($args = array()) {
    if (!izelena_woocommerce_active()) return izelena_demo_mode() ? izelena_demo_products() : array();
    $defaults = array(
        'status' => 'publish',
        'limit' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'return' => 'objects',
    );
    $products = wc_get_products(wp_parse_args($args, $defaults));
    return is_array($products) ? $products : array();
}

function izelena_normalize_quantity($value, $allow_zero = false) {
    $raw = is_scalar($value) ? trim((string) $value) : '';
    if ('' === $raw || !preg_match('/^\d+$/', $raw)) return $allow_zero ? 0 : 1;
    $quantity = min(999999, (int) $raw);
    if (!$allow_zero) $quantity = max(1, $quantity);
    return $quantity;
}

function izelena_product_description($product) {
    if (!is_object($product) || !is_a($product, 'WC_Product')) return '';
    $short = trim(wp_strip_all_tags((string) $product->get_short_description()));
    if ('' !== $short) return $short;
    $full = trim(wp_strip_all_tags((string) $product->get_description()));
    return '' !== $full ? $full : __('Jamaican flavour for every season.', 'izelena-foods');
}

/**
 * Return product price markup without assuming a particular WooCommerce
 * helper exists in the installed runtime.
 */
function izelena_product_price_html($product) {
    if (!is_object($product) || !is_a($product, 'WC_Product')) return '';
    if (is_callable(array($product, 'get_price_html'))) return (string) $product->get_price_html();
    if (function_exists('wc_get_price_html')) return (string) wc_get_price_html($product);
    if (function_exists('wc_price')) {
        $price = function_exists('wc_get_price_to_display')
            ? wc_get_price_to_display($product)
            : (float) $product->get_price();
        return (string) wc_price($price);
    }
    $price = (float) $product->get_price();
    return '' !== $product->get_price()
        ? esc_html('J$' . number_format_i18n($price, 2))
        : '';
}

/**
 * WooCommerce owns the product photography. The local theme mapping remains
 * only as a safe fallback for demo products or incomplete staging records.
 */
function izelena_approved_product_image($product, $size = 'large') {
    if (is_object($product) && is_a($product, 'WC_Product')) {
        $image_id = (int) $product->get_image_id();
        if ($image_id) {
            $woo_image = wp_get_attachment_image_url($image_id, $size);
            if ($woo_image) return $woo_image;
        }
    }
    $slug = is_object($product) && is_a($product, 'WC_Product')
        ? sanitize_title($product->get_slug())
        : sanitize_title((string) $product);
    $mapping = array(
        'jerk-seasoning' => 'heartbeat-of-jamaican-cooking-jerk-marinade.jpg',
        'jerk-marinade' => 'heartbeat-of-jamaican-cooking-jerk-marinade.jpg',
        'jerk-bbq' => 'sweet-heat-smoky-finish-bbq-jerk-sauce.jpg',
        'jerk-bbq-sauce' => 'sweet-heat-smoky-finish-bbq-jerk-sauce.jpg',
        'mango' => 'sweet-island-sunshine-mango-sauce.jpg',
        'mango-salsa' => 'sweet-island-sunshine-mango-sauce.jpg',
        'spicy-mango' => 'sweet-meets-fire-spicy-mango-sauce.jpg',
        'spicy-mango-salsa' => 'sweet-meets-fire-spicy-mango-sauce.jpg',
        'sorrel' => 'tangy-spicy-unforgettable-sorrel-pepper-sauce.jpg',
        'sorrel-pepper-sauce' => 'tangy-spicy-unforgettable-sorrel-pepper-sauce.jpg',
    );
    if (!isset($mapping[$slug])) return '';
    $file = get_template_directory() . '/assets/' . $mapping[$slug];
    return file_exists($file) ? get_template_directory_uri() . '/assets/' . $mapping[$slug] : '';
}

function izelena_heat_icons($heat) {
    $count = array('mild' => 1, 'medium' => 2, 'hot' => 3);
    $level = isset($count[$heat]) ? $count[$heat] : 0;
    if (!$level) return '<span class="heat-flames heat-flames-pending" aria-hidden="true">&mdash;</span>';
    $icon = get_template_directory_uri() . '/assets/heat-flame.png';
    $html = '<span class="heat-flames heat-flames-' . esc_attr($heat) . '" aria-hidden="true">';
    for ($i = 0; $i < $level; $i++) $html .= '<img src="' . esc_url($icon) . '" width="44" height="55" alt="">';
    return $html . '</span>';
}

function izelena_variation_attribute_label($attribute, $value = '') {
    $taxonomy = str_replace('attribute_', '', (string) $attribute);
    $label = function_exists('wc_attribute_label') ? wc_attribute_label($taxonomy) : ucwords(str_replace(array('pa_', '-', '_'), array('', ' ', ' '), $taxonomy));
    if ($value && taxonomy_exists($taxonomy)) {
        $term = get_term_by('slug', $value, $taxonomy);
        if ($term && !is_wp_error($term)) $value = $term->name;
    }
    return array('label' => $label, 'value' => (string) $value);
}

function izelena_product_variation_data($product) {
    if (!is_object($product) || !is_a($product, 'WC_Product') || !$product->is_type('variable')) return array();
    $variations = array();
    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation || !$variation->exists()) continue;
        $attributes = array();
        $attribute_labels = array();
        foreach ($variation->get_attributes() as $key => $value) {
            $attribute_key = 0 === strpos((string) $key, 'attribute_') ? (string) $key : 'attribute_' . sanitize_title($key);
            $formatted = izelena_variation_attribute_label($attribute_key, $value);
            $attributes[$attribute_key] = (string) $value;
            $attribute_labels[$attribute_key] = $formatted['value'];
        }
        $approved_image = $variation->get_image_id()
            ? wp_get_attachment_image_url($variation->get_image_id(), 'large')
            : izelena_approved_product_image($product);
        $variations[] = array(
            'id' => (int) $variation->get_id(),
            'attributes' => $attributes,
            'attribute_labels' => $attribute_labels,
            'price' => (float) $variation->get_price(),
            'price_html' => izelena_product_price_html($variation),
            'in_stock' => $variation->is_in_stock(),
            'purchasable' => $variation->is_purchasable(),
            'variation_description' => wp_strip_all_tags($variation->get_description()),
            'image' => $approved_image,
        );
    }
    return $variations;
}

function izelena_demo_products() {
    return array(
        array('id' => 'jerk-seasoning', 'name' => 'Jerk Seasoning', 'tag' => 'The heartbeat of Jamaican cooking', 'desc' => 'An authentic jerk marinade bursting with pimento, thyme, scallion, and Scotch Bonnet heat.', 'price' => 900, 'heat' => 'medium', 'tone' => 'gold', 'note' => 'Sweet heat. Smoky finish.', 'image' => 'heartbeat-of-jamaican-cooking-jerk-marinade.jpg'),
        array('id' => 'jerk-bbq', 'name' => 'Jerk BBQ Sauce', 'tag' => 'Sweet heat. Smoky finish.', 'desc' => 'A bold fusion of classic BBQ sweetness and jerk spice for meats, wings and anything worth glazing.', 'price' => 150, 'heat' => 'medium', 'tone' => 'red', 'note' => 'Rich, smoky glaze.', 'image' => 'sweet-heat-smoky-finish-bbq-jerk-sauce.jpg'),
        array('id' => 'mango', 'name' => 'Mango Salsa', 'tag' => 'Sweet island sunshine', 'desc' => 'A smooth tropical mango sauce with a mild finish - perfect for dipping, glazing and pairing.', 'price' => 140, 'heat' => 'mild', 'tone' => 'yellow', 'note' => 'Soft mango. Gentle warmth.', 'image' => 'sweet-island-sunshine-mango-sauce.jpg'),
        array('id' => 'spicy-mango', 'name' => 'Spicy Mango Salsa', 'tag' => 'Sweet meets fire', 'desc' => 'Ripe mango sweetness and pepper heat, balanced for a perfectly bright sweet-spicy kick.', 'price' => 150, 'heat' => 'hot', 'tone' => 'green', 'note' => 'A bright, balanced kick.', 'image' => 'sweet-meets-fire-spicy-mango-sauce.jpg'),
        array('id' => 'sorrel', 'name' => 'Sorrel Pepper Sauce', 'tag' => 'Tangy, spicy, unforgettable', 'desc' => 'Traditional Jamaican sorrel meets fiery peppers in a vibrant balance of tangy sweetness and heat.', 'price' => 150, 'heat' => 'hot', 'tone' => 'burgundy', 'note' => 'Bold Caribbean heat.', 'image' => 'tangy-spicy-unforgettable-sorrel-pepper-sauce.jpg'),
        array('id' => 'crushed', 'name' => 'Crushed Pepper Sauce', 'tag' => 'Bring the heat. Keep the flavour.', 'desc' => 'A vibrant fiery pepper sauce designed to enhance - not overpower - every meal.', 'price' => 150, 'heat' => 'hot', 'tone' => 'black', 'note' => 'For serious pepper fans.', 'soon' => true),
    );
}

function izelena_product_heat($product_id) {
    $heat = get_post_meta($product_id, '_izelena_heat_level', true);
    return in_array($heat, array('mild', 'medium', 'hot'), true) ? $heat : '';
}

function izelena_heat_tone($heat) {
    return array('mild' => 'green', 'medium' => 'yellow', 'hot' => 'red')[$heat] ?? 'red';
}

function izelena_legacy_product_card($product, $fallback = false, $modal_trigger = false) {
    $is_wc = !$fallback && is_object($product) && is_a($product, 'WC_Product');
    $soon = false;
    $image_url = '';
    if ($is_wc) {
        $id = (string) $product->get_id();
        $name = $product->get_name();
        $tag = 'Izelena flavour collection';
        $desc = izelena_product_description($product);
        $heat = izelena_product_heat($product->get_id());
        $tone = izelena_heat_tone($heat);
        $url = $product->get_permalink();
        $image_url = izelena_approved_product_image($product);
        $image = $image_url ? '<img class="product-card-image" src="' . esc_url($image_url) . '" alt="' . esc_attr($name . ' product') . '">' : '';
        $price = function_exists('wc_price') && '' !== $product->get_price() ? wc_price(wc_get_price_to_display($product), array('currency' => 'JMD')) : '';
        $action = '<a class="add-btn" href="' . esc_url($url) . '">View <span>↗</span></a>';
        if ($product->is_purchasable() && $product->is_in_stock() && $product->is_type('simple')) $action = '<a class="add-btn add_to_cart_button ajax_add_to_cart" href="' . esc_url($product->add_to_cart_url()) . '" data-product_id="' . esc_attr($product->get_id()) . '">Add <span>+</span></a>';
    } else {
        $id = $product['id'] ?? sanitize_title($product['name']);
        $name = $product['name']; $tag = $product['tag']; $desc = $product['desc']; $heat = $product['heat']; $tone = izelena_heat_tone($heat);
        $image_file = $product['image'] ?? '';
        $image_url = $image_file ? get_template_directory_uri() . '/assets/' . $image_file : '';
        $image = $image_url ? '<img class="product-card-image" src="' . esc_url($image_url) . '" alt="' . esc_attr($name . ' product') . '">' : '';
        $soon = !empty($product['soon']);
        $url = home_url('/product/' . sanitize_title($name) . '/');
        $price = 'From J$' . number_format_i18n((float) $product['price']);
        $action = $soon ? '<button class="add-btn" type="button" disabled>Soon <span>+</span></button>' : '<button class="add-btn" type="button" data-demo-add="' . esc_attr($id) . '">Add <span>+</span></button>';
    }
    $initials = '';
    foreach (preg_split('/\s+/', $name) as $word) $initials .= substr($word, 0, 1);
    $numeric_price = $is_wc ? (float) $product->get_price() : (float) $product['price'];
    $metadata = ' data-product-id="' . esc_attr($id) . '"'
        . ' data-product-name="' . esc_attr($name) . '"'
        . ' data-product-tag="' . esc_attr($tag) . '"'
        . ' data-product-description="' . esc_attr($desc) . '"'
        . ' data-product-price="' . esc_attr($numeric_price) . '"'
        . ' data-product-heat="' . esc_attr($heat) . '"'
        . ' data-product-tone="' . esc_attr($tone) . '"'
        . ' data-product-initials="' . esc_attr(strtoupper($initials)) . '"'
        . ' data-product-image="' . esc_attr($image_url) . '"'
        . ' data-product-url="' . esc_url($url) . '"'
        . ' data-product-soon="' . ($soon ? '1' : '0') . '"';
    $visual_mark = $image ? $image : '<span class="product-mark">' . esc_html(strtoupper($initials)) . '</span>';
    echo '<article class="product-card ' . esc_attr($tone) . '"' . $metadata . '><div class="product-visual">' . $visual_mark . '<span class="heat-pill">' . esc_html($heat) . '</span>' . ($soon ? '<span class="soon">Coming soon</span>' : '') . '</div><div class="product-info"><p class="eyebrow">' . esc_html($tag) . '</p><h3><a href="' . esc_url($url) . '">' . esc_html($name) . '</a></h3><p>' . esc_html($desc) . '</p><div class="product-row"><strong>' . wp_kses_post($price) . '</strong>' . $action . '</div></div></article>';
}

function izelena_product_card($product, $fallback = false, $modal_trigger = false) {
    $is_wc = !$fallback && is_object($product) && is_a($product, 'WC_Product');
    $soon = false;
    $image_url = '';
    $variation_data = array();
    if ($is_wc) {
        $id = (string) $product->get_id();
        $name = $product->get_name();
        $tag = 'Izelena flavour collection';
        $desc = izelena_product_description($product);
        $heat = izelena_product_heat($product->get_id());
        $tone = 'red';
        $url = $product->get_permalink();
        $image_url = izelena_approved_product_image($product);
        $image = $image_url ? '<img class="product-card-image" src="' . esc_url($image_url) . '" alt="' . esc_attr($name . ' product') . '">' : '';
        $price = izelena_product_price_html($product);
        $variation_data = izelena_product_variation_data($product);
        if ('' === trim(wp_strip_all_tags($price)) && $variation_data) {
            $variation_prices = array_map('floatval', wp_list_pluck($variation_data, 'price'));
            $variation_prices = array_filter($variation_prices, function ($value) { return $value > 0; });
            if ($variation_prices) {
                $minimum = min($variation_prices);
                $minimum_html = function_exists('wc_price')
                    ? wc_price($minimum)
                    : esc_html('J$' . number_format_i18n($minimum, 2));
                $price = sprintf(__('From %s', 'izelena-foods'), $minimum_html);
            }
        }
        $action = '<a class="add-btn" href="' . esc_url($url) . '">View <span aria-hidden="true">&rarr;</span></a>';
        if ($product->is_purchasable() && $product->is_in_stock() && $product->is_type('simple')) {
            $action = '<button class="add-btn" type="button" data-wc-add data-product-id="' . esc_attr($product->get_id()) . '">Add <span aria-hidden="true">+</span></button>';
        }
        if (!$product->is_in_stock()) $action = '<button class="add-btn" type="button" disabled>Sold out</button>';
    } else {
        $id = $product['id'] ?? sanitize_title($product['name']);
        $name = $product['name']; $tag = $product['tag']; $desc = $product['desc']; $heat = $product['heat']; $tone = $product['tone'];
        $image_file = $product['image'] ?? '';
        $image_url = $image_file ? get_template_directory_uri() . '/assets/' . $image_file : '';
        $image = $image_url ? '<img class="product-card-image" src="' . esc_url($image_url) . '" alt="' . esc_attr($name . ' product') . '">' : '';
        $soon = !empty($product['soon']);
        $url = home_url('/product/' . sanitize_title($name) . '/');
        $price = 'From J$' . number_format_i18n((float) $product['price']);
        $action = $soon ? '<button class="add-btn" type="button" disabled>Soon <span aria-hidden="true">+</span></button>' : '<button class="add-btn" type="button" data-demo-add="' . esc_attr($id) . '">Add <span aria-hidden="true">+</span></button>';
    }
    $heat_label = $heat ? ucfirst($heat) : __('Heat pending approval', 'izelena-foods');
    $initials = '';
    foreach (preg_split('/\s+/', $name) as $word) $initials .= substr($word, 0, 1);
    $numeric_price = $is_wc && '' !== $product->get_price() ? (float) $product->get_price() : ($is_wc ? 0 : (float) $product['price']);
    $metadata = ' data-product-id="' . esc_attr($id) . '"'
        . ' data-product-name="' . esc_attr($name) . '"'
        . ' data-product-tag="' . esc_attr($tag) . '"'
        . ' data-product-description="' . esc_attr($desc) . '"'
        . ' data-product-price="' . esc_attr($numeric_price) . '"'
        . ' data-product-heat="' . esc_attr($heat) . '"'
        . ' data-product-tone="' . esc_attr($tone) . '"'
        . ' data-product-initials="' . esc_attr(strtoupper($initials)) . '"'
        . ' data-product-image="' . esc_attr($image_url) . '"'
        . ' data-product-url="' . esc_url($url) . '"'
        . ' data-product-type="' . esc_attr($is_wc ? $product->get_type() : 'demo') . '"'
        . ' data-product-variations="' . esc_attr(wp_json_encode($variation_data)) . '"'
        . ' data-product-soon="' . ($soon ? '1' : '0') . '"';
    $visual_mark = $image ? $image : '<span class="product-mark">' . esc_html(strtoupper($initials)) . '</span>';
    echo '<article class="product-card ' . esc_attr($tone) . '"' . $metadata . '><div class="product-visual">' . $visual_mark . '<span class="heat-pill heat-pill-' . esc_attr($heat ?: 'pending') . '">' . esc_html($heat_label) . '</span>' . ($soon ? '<span class="soon">Coming soon</span>' : '') . '</div><div class="product-info"><p class="eyebrow">' . esc_html($tag) . '</p><h3><a href="' . esc_url($url) . '">' . esc_html($name) . '</a></h3><p>' . esc_html($desc) . '</p><div class="product-row"><strong>' . wp_kses_post($price) . '</strong>' . $action . '</div></div></article>';
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
function izelena_heat_meta_box_html($post) { wp_nonce_field('izelena_heat_save', 'izelena_heat_nonce'); $current = izelena_product_heat($post->ID); echo '<label for="izelena_heat_level">' . esc_html__('Heat level', 'izelena-foods') . '</label><select name="izelena_heat_level" id="izelena_heat_level" style="width:100%"><option value="" ' . selected($current, '', false) . '>Uncategorized - client input required</option><option value="mild" ' . selected($current, 'mild', false) . '>Mild / Tups</option><option value="medium" ' . selected($current, 'medium', false) . '>Medium / Nuh 2 Much</option><option value="hot" ' . selected($current, 'hot', false) . '>Hot / Whole Heap</option></select>'; }
function izelena_save_heat($post_id) { if (!isset($_POST['izelena_heat_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['izelena_heat_nonce'])), 'izelena_heat_save') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) return; $heat = isset($_POST['izelena_heat_level']) ? sanitize_key(wp_unslash($_POST['izelena_heat_level'])) : ''; if (in_array($heat, array('mild', 'medium', 'hot'), true)) update_post_meta($post_id, '_izelena_heat_level', $heat); else delete_post_meta($post_id, '_izelena_heat_level'); }
add_action('save_post_product', 'izelena_save_heat');

function izelena_load_cart() {
    if (!izelena_woocommerce_active()) return false;
    if (function_exists('wc_load_cart')) wc_load_cart();
    return function_exists('WC') && WC()->cart;
}

function izelena_cart_payload() {
    if (!izelena_load_cart()) return array('enabled' => false, 'items' => array(), 'count' => 0, 'total_html' => '');
    WC()->cart->calculate_totals();
    $items = array();
    foreach (WC()->cart->get_cart() as $cart_key => $cart_item) {
        $product = isset($cart_item['data']) ? $cart_item['data'] : false;
        if (!$product || !$product->exists()) continue;
        $variation_text = array();
        $formatted_data = wc_get_formatted_cart_item_data($cart_item, false);
        if (is_array($formatted_data)) foreach ($formatted_data as $attribute) {
            if (isset($attribute['key'], $attribute['value'])) $variation_text[] = wp_strip_all_tags($attribute['key'] . ': ' . $attribute['value']);
        }
        $product_id = isset($cart_item['product_id']) ? absint($cart_item['product_id']) : (int) $product->get_id();
        $variation_id = isset($cart_item['variation_id']) ? absint($cart_item['variation_id']) : 0;
        $items[] = array(
            'key' => $cart_key,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'name' => $product->get_name(),
            'quantity' => (int) $cart_item['quantity'],
            'variation' => implode(' | ', $variation_text),
            'price_html' => wc_price(wc_get_price_to_display($product)),
            'subtotal_html' => wc_price((float) $cart_item['line_total'] + (float) $cart_item['line_tax']),
            'image' => $product->get_image_id() ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : '',
        );
    }
    return array(
        'enabled' => true,
        'items' => $items,
        'count' => (int) WC()->cart->get_cart_contents_count(),
        'total_html' => WC()->cart->get_total(),
        'cart_url' => wc_get_cart_url(),
        'checkout_url' => wc_get_checkout_url(),
        'checkout_enabled' => izelena_checkout_enabled(),
    );
}

function izelena_cart_ajax() {
    if (!check_ajax_referer('izelena_cart', 'nonce', false)) wp_send_json_error(array('code' => 'invalid_nonce', 'message' => __('Security check failed. Please refresh and try again.', 'izelena-foods')), 403);
    if (!izelena_load_cart()) wp_send_json_error(array('code' => 'woocommerce_unavailable', 'message' => __('WooCommerce is not active. Cart actions are unavailable.', 'izelena-foods')), 503);
    $action = isset($_POST['cart_action']) ? sanitize_key(wp_unslash($_POST['cart_action'])) : 'get';
    if ('add' === $action) {
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $quantity = isset($_POST['quantity']) ? izelena_normalize_quantity(wp_unslash($_POST['quantity'])) : 1;
        if (!$product_id) wp_send_json_error(array('code' => 'invalid_product', 'message' => __('That product is not available.', 'izelena-foods')), 400);
        $variation = array();
        if (isset($_POST['variation']) && is_array($_POST['variation'])) {
            foreach (wp_unslash($_POST['variation']) as $key => $value) {
                $clean_key = sanitize_key($key);
                if (0 !== strpos($clean_key, 'attribute_')) $clean_key = 'attribute_' . $clean_key;
                if (!is_scalar($value)) continue;
                $variation[$clean_key] = sanitize_title((string) $value);
            }
        }
        $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
        if (!$added) wp_send_json_error(array('code' => 'add_failed', 'message' => __('That size is unavailable. Please choose another variation.', 'izelena-foods')), 409);
    } elseif ('update' === $action) {
        $cart_key = isset($_POST['cart_key']) ? wc_clean(wp_unslash($_POST['cart_key'])) : '';
        $quantity = isset($_POST['quantity']) ? izelena_normalize_quantity(wp_unslash($_POST['quantity'])) : 1;
        $cart = WC()->cart->get_cart();
        if (!$cart_key || !isset($cart[$cart_key])) wp_send_json_error(array('code' => 'missing_line', 'message' => __('That cart line is no longer available.', 'izelena-foods')), 404);
        if (0 === $quantity) {
            if (!WC()->cart->remove_cart_item($cart_key)) wp_send_json_error(array('code' => 'remove_failed', 'message' => __('That cart line could not be removed.', 'izelena-foods')), 409);
        } elseif (false === WC()->cart->set_quantity($cart_key, $quantity, true)) {
            wp_send_json_error(array('code' => 'quantity_unavailable', 'message' => __('That quantity is unavailable. Please review stock and try again.', 'izelena-foods')), 409);
        }
    } elseif ('remove' === $action) {
        $cart_key = isset($_POST['cart_key']) ? wc_clean(wp_unslash($_POST['cart_key'])) : '';
        if (!$cart_key) wp_send_json_error(array('code' => 'missing_line', 'message' => __('That cart line is no longer available.', 'izelena-foods')), 400);
        if (!WC()->cart->remove_cart_item($cart_key)) wp_send_json_error(array('code' => 'remove_failed', 'message' => __('That cart line could not be removed.', 'izelena-foods')), 409);
    } elseif ('get' !== $action) {
        wp_send_json_error(array('code' => 'invalid_action', 'message' => __('That cart action is not supported.', 'izelena-foods')), 400);
    }
    wp_send_json_success(izelena_cart_payload());
}
add_action('wp_ajax_izelena_cart', 'izelena_cart_ajax');
add_action('wp_ajax_nopriv_izelena_cart', 'izelena_cart_ajax');

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
