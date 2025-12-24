<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Post Loop
while ( have_posts() ) : the_post();

// Page Header
get_my_page_header();

// Page Content
get_my_page_content();

// End Loop
endwhile;

// Reset My Data
wp_reset_postdata();

// Jawda header
get_my_footer();
