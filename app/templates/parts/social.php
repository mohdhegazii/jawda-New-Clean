<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

// Social
function get_my_social(){
  echo '<ul>';
  if ( carbon_get_theme_option( 'jawda_facebook_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_facebook_link' ).'"><i class="icon-facebook" title="facebook"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_twitter_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_twitter_link' ).'"><i class="icon-twitter" title="twitter"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_linkedin_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_linkedin_link' ).'"><i class="icon-linkedin" title="linkedin"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_instagram_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_instagram_link' ).'"><i class="icon-instagram" title="instagram"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_pinterest_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_pinterest_link' ).'"><i class="icon-pinterest" title="pinterest"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_youtube_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_youtube_link' ).'"><i class="icon-youtube" title="youtube"></i></a>';
  }
  echo '</ul>';
}

function get_my_social_pc(){
  echo '<ul class="pc-social">';
  if ( carbon_get_theme_option( 'jawda_facebook_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_facebook_link' ).'"><i class="icon-facebook" title="facebook"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_twitter_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_twitter_link' ).'"><i class="icon-twitter" title="twitter"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_linkedin_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_linkedin_link' ).'"><i class="icon-linkedin" title="linkedin"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_instagram_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_instagram_link' ).'"><i class="icon-instagram" title="instagram"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_pinterest_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_pinterest_link' ).'"><i class="icon-pinterest" title="pinterest"></i></a>';
  }
  if ( carbon_get_theme_option( 'jawda_youtube_link' )) {
    echo '<li><a target="_blank" href="'.carbon_get_theme_option( 'jawda_youtube_link' ).'"><i class="icon-youtube" title="youtube"></i></a>';
  }
  echo '</ul>';
}
