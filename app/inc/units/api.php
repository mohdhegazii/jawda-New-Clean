<?php
/**
 * API functions for the Unit Lookups system.
 * Provides a cached layer for reading lookup data.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A generic, cached function to retrieve lookups for a specific group.
 *
 * @param string $group_key The group key (e.g., 'unit_status').
 * @param array $args Optional. Arguments for the service layer.
 * @return array The array of lookup objects.
 */
function jawda_get_unit_lookups_by_group( $group_key, $args = [] ) {
    if ( ! Jawda_Unit_Lookups_Service::is_valid_group_key( $group_key ) ) {
        return [];
    }

    $cache_key   = 'jawda_unit_lookups_' . $group_key;
    $cache_group = 'jawda_unit_lookups';

    // Note: We are caching only the default arguments for now.
    // If complex filtering is needed later, the cache key must be made more specific.
    $cached_lookups = wp_cache_get( $cache_key, $cache_group );

    if ( false === $cached_lookups ) {
        $cached_lookups = Jawda_Unit_Lookups_Service::get_lookups_by_group( $group_key, $args );
        wp_cache_set( $cache_key, $cached_lookups, $cache_group, HOUR_IN_SECONDS );
    }

    return $cached_lookups;
}

/**
 * Retrieves all 'Unit Status' lookups.
 * @return array
 */
function jawda_get_unit_statuses() {
    return jawda_get_unit_lookups_by_group( 'unit_status' );
}

/**
 * Retrieves all 'Construction Status' lookups.
 * @return array
 */
function jawda_get_construction_statuses() {
    return jawda_get_unit_lookups_by_group( 'construction_status' );
}

/**
 * Retrieves all 'Finishing Type' lookups.
 * @return array
 */
function jawda_get_finishing_types() {
    return jawda_get_unit_lookups_by_group( 'finishing_type' );
}

/**
 * Retrieves all 'Delivery Timeframe' lookups.
 * @return array
 */
function jawda_get_delivery_timeframes() {
    return jawda_get_unit_lookups_by_group( 'delivery_timeframe' );
}

/**
 * Retrieves all 'View' lookups.
 * @return array
 */
function jawda_get_unit_views() {
    return jawda_get_unit_lookups_by_group( 'view' );
}

/**
 * Retrieves all 'Amenity' lookups.
 * @return array
 */
function jawda_get_unit_amenities() {
    return jawda_get_unit_lookups_by_group( 'amenity' );
}

/**
 * Retrieves all 'Offer Type' lookups.
 * @return array
 */
function jawda_get_offer_types() {
    return jawda_get_unit_lookups_by_group( 'offer_type' );
}

/**
 * Retrieves all 'Market Type' lookups.
 * @return array
 */
function jawda_get_market_types() {
    return jawda_get_unit_lookups_by_group( 'market_type' );
}

/**
 * Get market types formatted for a Carbon Fields select field.
 *
 * @return array
 */
function jawda_get_market_types_for_cf() {
    $market_types = jawda_get_market_types();
    $options = [ '' => __( '— Select Market Type —', 'jawda' ) ];

    foreach ( $market_types as $type ) {
        $options[ $type->id ] = sprintf( '%s / %s', $type->label_en, $type->label_ar );
    }

    return $options;
}
