<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Region Loop
while ( have_posts() ) : the_post();

// Head
get_my_property_header();

// project-main
get_my_property_main();

// related projects
get_my_related_properties();

// End loop
endwhile;

// Jawda header
get_my_footer();
