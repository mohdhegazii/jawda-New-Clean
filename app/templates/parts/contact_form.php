<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Contact Form
----------------------------------------------------------------------------- */

function my_contact_form(){
  $langu = is_rtl() ? 'ar' : 'en' ;

  ?>
  <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="siteform">
    <?php wp_nonce_field( 'my_contact_form_action', 'my_contact_form_nonce' ); ?>
    <input type="hidden" name="langu" value="<?php echo $langu; ?>">
    <input type="hidden" name="action" value="my_contact_form">
    <input type="hidden" name="packageid" value="<?php get_my_page_title(); ?> <?php echo get_the_permalink(); ?>">
    <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="hp-field" style="position:absolute;left:-9999px;">
    <input name="name" placeholder="<?php get_text('الاسم','First Name'); ?> *" class="form-bg" aria-label="first-name" required>
    <input name="phone" class="form-bg" placeholder="<?php get_text('رقم الهاتف','Phone Number'); ?> *" aria-label="contact-phone" required>
    <input name="email" type="text" class="form-bg" placeholder="<?php get_text('البريد الإلكتروني','Email'); ?>" aria-label="your-email">
    <textarea required name="special_request" cols="10" rows="1" placeholder="<?php get_text('رسالتك','Your Message'); ?>" aria-label="your-comment" class="comment"></textarea>
    <input type="submit" value="<?php get_text('ارسال','Send'); ?>" class="submit">
  </form>

  <?php
}

function my_home_contact_form(){
  $langu = is_rtl() ? 'ar' : 'en' ;

  ?>
  <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="siteform">
    <?php wp_nonce_field( 'my_contact_form_action', 'my_contact_form_nonce' ); ?>
    <input type="hidden" name="langu" value="<?php echo $langu; ?>">
    <input type="hidden" name="action" value="my_contact_form">
    <input type="hidden" name="packageid" value="<?php get_my_page_title(); ?>">
    <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="hp-field" style="position:absolute;left:-9999px;">
    <span class="inputs-wrap">
      <input name="name" placeholder="<?php get_text('الاسم','First Name'); ?> *" class="form-bg" aria-label="first-name" required>
      <input name="phone" class="form-bg" placeholder="<?php get_text('رقم الهاتف','Phone Number'); ?> *" aria-label="contact-phone" required>
      <input name="email" type="text" class="form-bg" placeholder="<?php get_text('البريد الإلكتروني','Email'); ?>" aria-label="your-email">
    </span>
    <textarea required name="special_request" cols="10" rows="1" placeholder="<?php get_text('رسالتك','Your Message'); ?>" aria-label="your-comment" class="comment"></textarea>
    <input type="submit" value="<?php get_text('ارسال','Send'); ?>" class="submit">
  </form>

  <?php
}


function my_contact_footer_form(){
  $langu = is_rtl() ? 'ar' : 'en' ;
  ?>

<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="siteform" id="myform">
<?php wp_nonce_field( 'my_contact_form_action', 'my_contact_form_nonce' ); ?>
  <input type="hidden" name="langu" value="<?php echo $langu; ?>">
  <input type="hidden" name="action" value="my_contact_form">
  <input type="hidden" name="packageid" value="<?php get_my_page_title(); ?>">
  <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
  <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="hp-field" style="position:absolute;left:-9999px;">
  <input id="fname" name="name" placeholder="<?php get_text('الاسم','First Name'); ?> *" class="form-bg half-r"
    aria-label="your-name" required>
  <input id="fphone" name="phone" class="form-bg half-l" placeholder="<?php get_text('رقم الهاتف','Phone Number'); ?> *"
    aria-label="contact-phone" required>
  <input id="femail" name="email" type="text" class="form-bg"
    placeholder="<?php get_text('البريد الإلكتروني','Email'); ?> *" aria-label="your-email" required>
  <textarea id="fmessage" name="special_request" cols="10" rows="1"
    placeholder="<?php get_text('رسالتك','Your Message'); ?>" aria-label="your-comment" class="comment"></textarea>
  <input type="submit" value="<?php get_text('ارسال','Send'); ?>" class="submit">
</form>

  <?php
}
