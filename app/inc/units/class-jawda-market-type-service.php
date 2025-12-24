<?php
/**
 * Service class for managing Market Type lookups.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Jawda_Market_Type_Service {

    /**
     * The group key for market types in the unit lookups table.
     */
    const GROUP_KEY = 'market_type';

    /**
     * Get the post type for listings.
     *
     * @return string
     */
    private static function get_listing_post_type() {
        return 'property';
    }

    /**
     * Get a single market type by its ID.
     *
     * @param int $id The ID of the market type.
     * @return object|null The market type object or null if not found.
     */
    public static function get_market_type( $id ) {
        return Jawda_Unit_Lookups_Service::get_lookup( $id, self::GROUP_KEY );
    }

    /**
     * Get all market types.
     *
     * @param array $args Optional arguments.
     * @return array An array of market type objects.
     */
    public static function get_all_market_types( $args = [] ) {
        $defaults = [
            'orderby'        => 'label_en',
            'order'          => 'ASC',
            'include_inactive' => false,
        ];
        $args = wp_parse_args( $args, $defaults );

        return Jawda_Unit_Lookups_Service::get_lookups_by_group( self::GROUP_KEY, $args );
    }

    /**
     * Create a new market type.
     *
     * @param array $data The data for the new market type.
     * @return int|WP_Error The new market type ID or a WP_Error on failure.
     */
    public static function create_market_type( $data ) {
        if ( empty( $data['label_en'] ) || empty( $data['label_ar'] ) ) {
            return new WP_Error( 'missing_data', __( 'Both English and Arabic labels are required.', 'jawda' ) );
        }

        $slug = sanitize_title( $data['label_en'] );

        return Jawda_Unit_Lookups_Service::create_lookup( self::GROUP_KEY, [
            'slug'     => $slug,
            'label_en' => sanitize_text_field( $data['label_en'] ),
            'label_ar' => sanitize_text_field( $data['label_ar'] ),
        ] );
    }

    /**
     * Update an existing market type.
     *
     * @param int   $id   The ID of the market type to update.
     * @param array $data The new data for the market type.
     * @return bool|WP_Error True on success, false or a WP_Error on failure.
     */
    public static function update_market_type( $id, $data ) {
        if ( empty( $data['label_en'] ) || empty( $data['label_ar'] ) ) {
            return new WP_Error( 'missing_data', __( 'Both English and Arabic labels are required.', 'jawda' ) );
        }

        return Jawda_Unit_Lookups_Service::update_lookup( $id, [
            'label_en' => sanitize_text_field( $data['label_en'] ),
            'label_ar' => sanitize_text_field( $data['label_ar'] ),
        ], self::GROUP_KEY );
    }

    /**
     * Soft delete a market type by setting its status to inactive.
     *
     * @param int $id The ID of the market type to soft delete.
     * @return bool|WP_Error True on success, false or a WP_Error on failure.
     */
    public static function soft_delete_market_type( $id ) {
        return Jawda_Unit_Lookups_Service::update_lookup( $id, [ 'is_active' => 0 ], self::GROUP_KEY );
    }

    /**
     * Hard delete a market type from the database.
     *
     * @param int $id The ID of the market type to delete.
     * @return bool|WP_Error True on success, false or a WP_Error on failure.
     */
    public static function hard_delete_market_type( $id ) {
        return Jawda_Unit_Lookups_Service::delete_lookup( $id, self::GROUP_KEY );
    }

    /**
     * Get the number of listings linked to a specific market type.
     *
     * @param int $market_type_id The market type ID.
     * @return int The number of linked listings.
     */
    public static function get_linked_listings_count( $market_type_id ) {
        global $wpdb;
        $market_type_id = (int) $market_type_id;

        $sql = $wpdb->prepare(
            "SELECT COUNT(p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status NOT IN ('trash', 'auto-draft')
            AND pm.meta_key = '_jawda_market_type_id'
            AND pm.meta_value = %d",
            self::get_listing_post_type(),
            $market_type_id
        );

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Check if a market type can be hard deleted.
     *
     * @param int $id The ID of the market type.
     * @return bool True if it can be hard deleted, false otherwise.
     */
    public static function can_hard_delete( $id ) {
        return self::get_linked_listings_count( $id ) === 0;
    }

    /**
     * Handle the deletion of a market type, choosing between soft and hard delete.
     *
     * @param int $id The ID of the market type to delete.
     * @return bool|WP_Error True on success, false or a WP_Error on failure.
     */
    public static function delete_market_type( $id ) {
        if ( self::can_hard_delete( $id ) ) {
            return self::hard_delete_market_type( $id );
        } else {
            return self::soft_delete_market_type( $id );
        }
    }
}
