<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// slider
get_my_home_slider();

// Featured Areas
get_my_home_featured_areas();

//احدث مشاريع التجمع الخامس
get_my_home_latest_fifth_settlement_projects();

// Featured properties
get_my_home_featured_properties();

//feature single projects
get_my_home_featured_real_projects();

// Featured Projects categories
get_my_home_featured_projects();

// Why Us
get_my_home_why_us();

// Recent Articles
get_my_home_recent_articles();

// Jawda header
get_my_footer();