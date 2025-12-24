<?php
/**
 * Main loader for the Unit Lookups module.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load the database schema creation/update script.
require_once __DIR__ . '/db-update.php';

// Load the service class for managing lookups.
require_once __DIR__ . '/class-jawda-unit-lookups-service.php';

// Load the API functions for reading lookup data.
require_once __DIR__ . '/api.php';

// Load the admin UI for managing lookups if we are in the admin area.
if ( is_admin() ) {
    require_once __DIR__ . '/admin/main-page.php';
    require_once __DIR__ . '/class-jawda-market-type-service.php';
    require_once __DIR__ . '/admin/meta-box.php';

    add_action( 'pre_get_posts', 'jawda_filter_listings_by_market_type' );
}

/**
 * Filter the listings admin query by market type.
 *
 * @param WP_Query $query The main query object.
 */
function jawda_filter_listings_by_market_type( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';
    $market_type_id = isset( $_GET['jawda_market_type_id'] ) ? absint( $_GET['jawda_market_type_id'] ) : 0;

    if ( 'property' === $post_type && $market_type_id > 0 ) {
        $meta_query = $query->get( 'meta_query' ) ?: [];
        $meta_query[] = [
            'key'     => '_jawda_market_type_id',
            'value'   => $market_type_id,
            'compare' => '=',
        ];
        $query->set( 'meta_query', $meta_query );
    }
}
