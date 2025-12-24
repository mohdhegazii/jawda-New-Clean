<?php
/*
Template Name: Thank You Page
Template Post Type: page
*/

if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }

function jawda_render_thankyou_fallback() {
  $is_ar = false;
  if (function_exists('pll_current_language')) {
    $is_ar = (pll_current_language('slug') === 'ar');
  } else {
    $is_ar = function_exists('is_rtl') ? is_rtl() : (strpos(get_locale(), 'ar') === 0);
  }

  $sent  = isset($_GET['sent']) && $_GET['sent'] === '1';
  $error = isset($_GET['error']) && $_GET['error'] === '1';

  $title = $is_ar ? 'شكراً لك' : 'Thank you';
  $msg_ok = $is_ar ? 'تم استلام بياناتك بنجاح. هنتواصل معاك قريباً.' : 'We received your message successfully. We will contact you soon.';
  $msg_er = $is_ar ? 'حصلت مشكلة أثناء الإرسال. من فضلك جرّب تاني.' : 'Something went wrong while sending. Please try again.';
  $msg = $error ? $msg_er : $msg_ok;

  $home = home_url('/');
  $projects = home_url('/projects/');
  $properties = home_url('/properties/');

  echo '<main class="jawda-thankyou" style="max-width:900px;margin:40px auto;padding:0 16px;">';
  echo '<h1>'.esc_html($title).'</h1>';
  echo '<p>'.esc_html($msg).'</p>';
  echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;">';
  echo '<a class="button" href="'.esc_url($home).'">'.esc_html($is_ar?'الصفحة الرئيسية':'Home').'</a>';
  echo '<a class="button" href="'.esc_url($projects).'">'.esc_html($is_ar?'المشروعات':'Projects').'</a>';
  echo '<a class="button" href="'.esc_url($properties).'">'.esc_html($is_ar?'العقارات':'Properties').'</a>';
  echo '</div>';
  echo '</main>';
}

ob_start();

// header
if (function_exists('get_my_header')) {
  get_my_header();
} else {
  get_header();
}

// loop
while ( have_posts() ) : the_post();
  if (function_exists('get_my_page_thank_you')) {
    get_my_page_thank_you();
  } else {
    jawda_render_thankyou_fallback();
  }
endwhile;

wp_reset_postdata();

// footer
if (function_exists('get_my_footer')) {
  get_my_footer();
} else {
  get_footer();
}

$html = ob_get_clean();

// check if empty output (strip tags + whitespace + &nbsp;)
$plain = trim(str_replace("\xC2\xA0", ' ', wp_strip_all_tags($html)));
$plain = trim(str_replace('&nbsp;', ' ', $plain));

if ($plain === '') {
  // render guaranteed fallback
  get_header();
  jawda_render_thankyou_fallback();
  get_footer();
} else {
  echo $html;
}
