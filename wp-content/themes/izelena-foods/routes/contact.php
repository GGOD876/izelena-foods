<?php
if (!defined('ABSPATH')) exit;
get_header();
$contact_status = isset($_GET['contact_status']) ? sanitize_key(wp_unslash($_GET['contact_status'])) : '';
$contact_message = isset($_GET['contact_message']) ? sanitize_text_field(wp_unslash($_GET['contact_message'])) : __('We could not submit your message. Please try again.', 'izelena-foods');
?>
<main class="page contact-page">
  <div class="page-intro"><p class="eyebrow">Say hello</p><h1>Let's make something<br><em>flavourful.</em></h1><p>Retail order, wholesale enquiry or flavour question? Choose a reason so your message reaches the right person.</p></div>
  <div class="contact-grid">
    <div class="contact-details"><p class="eyebrow">Find us</p><h2>Come through.</h2><p>17a WestLake Avenue<br>Kingston 10, Jamaica</p><p><b>Jamaica</b> (658) 210-2059<br><b>USA</b> (835) 245-7446</p><p><a href="mailto:info@izelenafoods.com">info@izelenafoods.com</a></p></div>
    <div>
      <?php if ('success' === $contact_status) : ?>
        <div class="success contact-feedback" role="status"><span aria-hidden="true">✓</span><h2>Message received.</h2><p>Thanks for reaching out. We'll be in touch soon.</p><a class="text-btn" href="<?php echo esc_url(home_url('/contact/')); ?>">Send another <span aria-hidden="true">↗</span></a></div>
      <?php else : ?>
        <?php if ('error' === $contact_status) : ?><div class="contact-feedback contact-feedback-error" role="alert"><?php echo esc_html($contact_message); ?></div><?php endif; ?>
        <form class="contact-form" data-contact-form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="izelena_contact_submit">
          <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/contact/')); ?>">
          <?php wp_nonce_field('izelena_contact_submit', 'izelena_contact_nonce'); ?>
          <label>Name<input name="name" required autocomplete="name" placeholder="Your name"></label>
          <label>Email<input name="email" required type="email" autocomplete="email" placeholder="you@example.com"></label>
          <label>Phone<input name="phone" type="tel" autocomplete="tel" placeholder="+1 (000) 000-0000"></label>
          <label class="contact-honeypot" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off" value=""></label>
          <label>Enquiry type<select name="enquiry" required><option value="general">General question</option><option value="retail">Retail order support</option><option value="wholesale">Wholesale / distributor</option><option value="stockist">Stockist opportunity</option></select></label>
          <label>Message<textarea name="message" required rows="5" placeholder="Tell us what you're thinking..."></textarea></label>
          <label class="check"><input name="consent" value="1" required type="checkbox"> I agree to the privacy notice for this enquiry.</label>
          <div class="contact-feedback" data-contact-feedback role="status" aria-live="polite" hidden></div>
          <button class="btn primary" type="submit">Send message <span aria-hidden="true">↗</span></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>
