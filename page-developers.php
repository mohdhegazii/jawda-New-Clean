<?php
/*
Template Name: Developers Page
Template Post Type: page
*/

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Category Header
get_my_tax_header();

// Category Posts
get_my_tax_pojects();

// Jawda header
get_my_footer();
