<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Search
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
page_advanced_search_body($_GET);
}

// Jawda header
get_my_footer();
