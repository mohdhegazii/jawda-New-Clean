<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# required_plugins
----------------------------------------------------------------------------- */

add_action( 'tgmpa_register', 'my_theme_register_required_plugins' );

function my_theme_register_required_plugins() {

	$plugins = array(
		['name'=> 'Jawda YouTube Embed','slug'=> 'jawda-youtube-embed','required'  => true],
		['name'=> 'LuckyWP Table of Contents','slug'=> 'luckywp-table-of-contents','required'  => true],
		['name'=> 'Yoast SEO','slug'=> 'wordpress-seo','required'  => true],
		['name'=> 'Really Simple SSL','slug'=> 'really-simple-ssl','required'  => true],
		['name'=> 'All In One WP Security & Firewall','slug'=> 'all-in-one-wp-security-and-firewall','required'  => true],
		['name'=> 'UpdraftPlus WordPress Backup Plugin','slug'=> 'updraftplus','required'  => false],
		['name'=> 'WP-Optimize – Cache, Clean, Compress.','slug'=> 'wp-optimize','required'  => false],
	);


	$config = array(
		'id'           => 'tgmpa',
		'default_path' => '',
		'menu'         => 'jawda-install-plugins',
		'parent_slug'  => 'themes.php',
		'capability'   => 'edit_theme_options',
		'has_notices'  => true,
		'dismissable'  => true,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',

	);

	tgmpa( $plugins, $config );

}
