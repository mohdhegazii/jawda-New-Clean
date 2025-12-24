<?php
/**
 * Functions for handling the relationship between projects and locations.
 *
 * @package Jawda
 */

// Security Check: Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update the location meta for a project in a consistent way.
 *
 * @param int $post_id
 * @param int|string|null $governorate_id
 * @param int|string|null $city_id
 * @param int|string|null $district_id
 */
function jawda_update_project_location( $post_id, $governorate_id, $city_id, $district_id ) {
    if ( class_exists( 'Jawda_Location_Service' ) ) {
        Jawda_Location_Service::save_location_for_post( $post_id, [
            'governorate_id' => $governorate_id,
            'city_id'        => $city_id,
            'district_id'    => $district_id,
        ] );

        return;
    }

    $post_id        = absint( $post_id );
    $governorate_id = absint( $governorate_id );
    $city_id        = absint( $city_id );
    $district_id    = absint( $district_id );

    if ( $post_id <= 0 ) {
        return;
    }

    if ( $governorate_id > 0 ) {
        update_post_meta( $post_id, 'loc_governorate_id', $governorate_id );
    } else {
        delete_post_meta( $post_id, 'loc_governorate_id' );
    }

    if ( $city_id > 0 ) {
        update_post_meta( $post_id, 'loc_city_id', $city_id );
    } else {
        delete_post_meta( $post_id, 'loc_city_id' );
    }

    if ( $district_id > 0 ) {
        update_post_meta( $post_id, 'loc_district_id', $district_id );
    } else {
        delete_post_meta( $post_id, 'loc_district_id' );
    }
}
