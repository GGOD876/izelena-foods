<?php
$sent = false; $error = '';
if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (!isset($_POST['izelena_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['izelena_contact_nonce'])), 'izelena_contact')) $error = 'Please refresh and try again.';
    elseif (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message']) || empty($_POST['privacy'])) $error = 'Please complete the required fields and privacy consent.';
    else $sent = true;
}
get_header(); ?>
<main class="page contact-page">
  <div class="page-intro"><p class="eyebrow">Say hello</p><h1>Let's make something<br><em>flavourful.</em></h1><p>Retail order, wholesale enquiry or flavour question? Choose a reason so your message reaches the right person.</p></div>
  <div class="contact-grid"><div class="contact-details"><p class="eyebrow">Find us</p><h2>Come through.</h2><p>17a WestLake Avenue<br>Kingston 10, Jamaica</p><p><b>Jamaica</b> <?php echo esc_html(get_theme_mod('izelena_phone', '(658) 210-2059')); ?><br><b>USA</b> (835) 245-7446</p><p><a href="mailto:<?php echo esc_attr(get_theme_mod('izelena_email', 'info@izelenafoods.com')); ?>"><?php echo esc_html(get_theme_mod('izelena_email', 'info@izelenafoods.com')); ?></a></p></div><form method="post" action=""><?php if ($error) echo '<div class="callout">' . esc_html($error) . '</div>'; ?><?php if ($sent): ?><div class="success"><span>✓</span><h2>Message received.</h2><p>Thanks for reaching out. We'll be in touch soon.</p><a class="text-btn" href="<?php echo esc_url(home_url('/contact/')); ?>">Send another <span aria-hidden="true">↗</span></a></div><?php else: ?><?php wp_nonce_field('izelena_contact', 'izelena_contact_nonce'); ?><label>Name<input required name="name" placeholder="Your name"></label><label>Email<input required name="email" type="email" placeholder="you@example.com"></label><label>Phone<input name="phone" type="tel" placeholder="+1 (000) 000-0000"></label><label>Enquiry type<select name="type"><option>General question</option><option>Retail order support</option><option>Wholesale / distributor</option><option>Stockist opportunity</option></select></label><label>Message<textarea required name="message" rows="5" placeholder="Tell us what you're thinking..."></textarea></label><label class="check"><input required name="privacy" type="checkbox"> I agree to the privacy notice for this enquiry.</label><button class="btn primary" type="submit">Send message <span aria-hidden="true">↗</span></button><?php endif; ?></form></div>
</main>
<?php get_footer(); ?>
