<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Category Header
get_my_cat_header();

// Category Posts
get_my_cat_posts();

// Jawda header
get_my_footer();
