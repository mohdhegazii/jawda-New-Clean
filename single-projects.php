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
get_my_project_header();

// Remove Search Bar from Single Project
// get_projects_top_search(); // Now handled/removed inside header logic if needed, or explicitly removed here if it was called here.
// Wait, get_my_project_header() calls get_projects_top_search() internally in app/templates/project/project_header.php
// I need to remove it from THERE.

// project-main
get_my_project_main();

// related projects
get_my_related_projects();

// End loop
endwhile;

// Jawda header
get_my_footer();
