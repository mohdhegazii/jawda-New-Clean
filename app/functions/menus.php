<?php

/* -----------------------------------------------------------------------------
-- Setup Menus
----------------------------------------------------------------------------- */

if (!function_exists('my_menus')) {
  function my_menus() {
  	register_nav_menus( array(
  		'header_menu' => 'Header Menu',
  		'footer_menu_1' => 'Footer Menu 1',
      'footer_menu_2' => 'Footer Menu 2',
      'important_links_1' => 'أحدث المشروعات',
      'important_links_2' => 'أشهر المناطق',
      'important_links_3' => 'أفضل المطورين',
      'important_links_4' => 'أفضل المشروعات',
  	) );
  }
  add_action( 'after_setup_theme', 'my_menus' );
}
