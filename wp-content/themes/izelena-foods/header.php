<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<div class="announcement"><?php echo esc_html(get_theme_mod('izelena_announcement', 'Authentically Jamaican')); ?> <span aria-hidden="true">•</span> Exotic island flavours <span aria-hidden="true">•</span> JMD pricing</div>
<header class="nav site-header">
  <a class="brand site-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Izelena Foods home"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/izelena-logo.png'); ?>" alt="Izelena Foods"></a>
  <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-menu">Menu</button>
  <nav class="site-nav" id="primary-menu" aria-label="Primary navigation">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
    <a href="<?php echo esc_url(home_url('/shop/')); ?>">Shop</a>
    <a href="<?php echo esc_url(home_url('/our-story/')); ?>">Our Story</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
    <a class="cart" data-cart-trigger href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>">Cart <span><?php echo esc_html(function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0); ?></span></a>
  </nav>
</header>
