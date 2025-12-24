<?php
/**
 * Service class for managing Unit Detail Lookups.
 * Handles CRUD operations, validation, and business logic for the unified lookups table.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Jawda_Unit_Lookups_Service {

    /**
     * The list of valid group keys for unit lookups.
     * @var string[]
     */
    const ALLOWED_GROUP_KEYS = [
        'unit_status',
        'construction_status',
        'finishing_type',
        'delivery_timeframe',
        'view',
        'amenity',
        'offer_type',
        'market_type',
    ];

    /**
     * Get the database table name for unit lookups.
     * @return string
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'jawda_unit_lookups';
    }

    /**
     * Retrieve a single lookup by its ID.
     *
     * @param int $id The ID of the lookup.
     * @return object|null The lookup object or null if not found.
     */
    public static function get_lookup( $id ) {
        global $wpdb;
        $id = absint( $id );
        if ( ! $id ) {
            return null;
        }
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::get_table_name() . " WHERE id = %d", $id ) );
    }

    /**
     * Retrieve a single lookup by its group and slug.
     *
     * @param string $group_key The group key.
     * @param string $slug The slug.
     * @return object|null The lookup object or null if not found.
     */
    public static function get_lookup_by_slug( $group_key, $slug ) {
        global $wpdb;
        if ( ! self::is_valid_group_key( $group_key ) || empty( $slug ) ) {
            return null;
        }
        $table = self::get_table_name();
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE group_key = %s AND slug = %s",
            $group_key,
            $slug
        ) );
    }

    /**
     * Retrieve all lookups for a given group.
     *
     * @param string $group_key The group key to filter by.
     * @param array $args Optional arguments for filtering.
     * @return array An array of lookup objects.
     */
    public static function get_lookups_by_group( $group_key, $args = [] ) {
        global $wpdb;

        if ( ! self::is_valid_group_key( $group_key ) ) {
            return [];
        }

        $defaults = [
            'is_active' => 1,
            'orderby'   => 'sort_order',
            'order'     => 'ASC',
        ];
        $args = wp_parse_args( $args, $defaults );

        $table = self::get_table_name();
        $where_clauses = [ $wpdb->prepare( 'group_key = %s', $group_key ) ];

        if ( isset( $args['is_active'] ) ) {
            $where_clauses[] = $wpdb->prepare( 'is_active = %d', (int) $args['is_active'] );
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        $orderby = in_array( strtolower( $args['orderby'] ), ['id', 'slug', 'label_en', 'label_ar', 'sort_order'] ) ? $args['orderby'] : 'sort_order';
        $order = in_array( strtoupper( $args['order'] ), ['ASC', 'DESC'] ) ? $args['order'] : 'ASC';

        return $wpdb->get_results( "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order}" );
    }

    /**
     * Create a new lookup item.
     *
     * @param string $group_key The group key.
     * @param array $data The data for the new lookup.
     * @return int|WP_Error The new lookup ID on success, or WP_Error on failure.
     */
    public static function create_lookup( $group_key, $data ) {
        global $wpdb;

        $validation = self::validate_lookup_data( $group_key, $data );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $slug = sanitize_title( $data['slug'] );
        if ( self::get_lookup_by_slug( $group_key, $slug ) ) {
            return new WP_Error( 'slug_exists', __( 'A lookup with this slug already exists in this group.', 'jawda' ) );
        }

        $now = current_time( 'mysql' );
        $insert_data = [
            'group_key'  => $group_key,
            'slug'       => $slug,
            'label_en'   => sanitize_text_field( $data['label_en'] ),
            'label_ar'   => sanitize_text_field( $data['label_ar'] ),
            'extra_data' => self::encode_extra_data( $group_key, $data ),
            'sort_order' => isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0,
            'is_active'  => isset( $data['is_active'] ) ? intval( $data['is_active'] ) : 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $result = $wpdb->insert( self::get_table_name(), $insert_data );

        if ( ! $result ) {
            return new WP_Error( 'db_insert_error', __( 'Could not create the lookup item in the database.', 'jawda' ) );
        }

        self::clear_cache_for_group( $group_key );
        return $wpdb->insert_id;
    }

    /**
     * Update an existing lookup item.
     *
     * @param int $id The ID of the lookup to update.
     * @param array $data The new data.
     * @return bool|WP_Error True on success, or WP_Error on failure.
     */
    public static function update_lookup( $id, $data ) {
        global $wpdb;

        $existing = self::get_lookup( $id );
        if ( ! $existing ) {
            return new WP_Error( 'not_found', __( 'Lookup item not found.', 'jawda' ) );
        }

        // We use the existing group key, it cannot be changed.
        $group_key = $existing->group_key;

        $validation = self::validate_lookup_data( $group_key, $data, $id );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $slug = sanitize_title( $data['slug'] );
        $duplicate = self::get_lookup_by_slug( $group_key, $slug );
        if ( $duplicate && (int) $duplicate->id !== (int) $id ) {
            return new WP_Error( 'slug_exists', __( 'A lookup with this slug already exists in this group.', 'jawda' ) );
        }

        $update_data = [
            'slug'       => $slug,
            'label_en'   => sanitize_text_field( $data['label_en'] ),
            'label_ar'   => sanitize_text_field( $data['label_ar'] ),
            'extra_data' => self::encode_extra_data( $group_key, $data ),
            'sort_order' => isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : $existing->sort_order,
            'is_active'  => isset( $data['is_active'] ) ? intval( $data['is_active'] ) : $existing->is_active,
            'updated_at' => current_time( 'mysql' ),
        ];

        $result = $wpdb->update( self::get_table_name(), $update_data, [ 'id' => $id ] );

        if ( $result === false ) {
            return new WP_Error( 'db_update_error', __( 'Could not update the lookup item in the database.', 'jawda' ) );
        }

        self::clear_cache_for_group( $group_key );
        return true;
    }

    /**
     * Soft delete a lookup item (sets is_active to 0).
     *
     * @param int $id The ID of the lookup to delete.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_lookup( $id ) {
        global $wpdb;

        $existing = self::get_lookup( $id );
        if ( ! $existing ) {
            return new WP_Error( 'not_found', __( 'Lookup item not found.', 'jawda' ) );
        }

        $result = $wpdb->update(
            self::get_table_name(),
            [ 'is_active' => 0, 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $id ]
        );

        if ( $result === false ) {
            return new WP_Error( 'db_delete_error', __( 'Could not delete the lookup item.', 'jawda' ) );
        }

        self::clear_cache_for_group( $existing->group_key );
        return true;
    }

    /**
     * Validates lookup data before create/update.
     *
     * @param string $group_key The group key.
     * @param array $data The data to validate.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private static function validate_lookup_data( $group_key, $data ) {
        if ( ! self::is_valid_group_key( $group_key ) ) {
            return new WP_Error( 'invalid_group_key', __( 'Invalid group key provided.', 'jawda' ) );
        }
        if ( empty( $data['slug'] ) ) {
            return new WP_Error( 'missing_slug', __( 'Slug is a required field.', 'jawda' ) );
        }
        if ( empty( $data['label_en'] ) && empty( $data['label_ar'] ) ) {
            return new WP_Error( 'missing_label', __( 'At least one label (English or Arabic) is required.', 'jawda' ) );
        }
        return true;
    }

    /**
     * Check if a group key is valid.
     *
     * @param string $group_key The key to check.
     * @return bool
     */
    public static function is_valid_group_key( $group_key ) {
        return in_array( $group_key, self::ALLOWED_GROUP_KEYS, true );
    }

    /**
     * Encode extra data based on group type, currently only for delivery_timeframe.
     *
     * @param string $group_key The group key.
     * @param array $data The raw data array.
     * @return string|null JSON string or null.
     */
    public static function encode_extra_data( $group_key, $data ) {
        if ( $group_key !== 'delivery_timeframe' ) {
            return null;
        }

        $extra = [
            'year'    => ! empty( $data['year'] ) ? intval( $data['year'] ) : null,
            'quarter' => ! empty( $data['quarter'] ) ? sanitize_text_field( $data['quarter'] ) : null,
            'profile' => ! empty( $data['profile'] ) ? sanitize_title( $data['profile'] ) : null,
        ];

        // Only encode if there's actual data.
        if ( count( array_filter( $extra ) ) > 0 ) {
            return wp_json_encode( $extra );
        }

        return null;
    }

    /**
     * Decode the extra_data JSON string.
     *
     * @param string|null $json The JSON string from the database.
     * @return array The decoded data as an associative array.
     */
    public static function decode_extra_data( $json ) {
        if ( empty( $json ) ) {
            return [];
        }
        $data = json_decode( $json, true );
        return is_array( $data ) ? $data : [];
    }

    /**
     * Clear the cache for a specific lookup group.
     *
     * @param string $group_key The group key.
     */
    public static function clear_cache_for_group( $group_key ) {
        if ( self::is_valid_group_key( $group_key ) ) {
            wp_cache_delete( 'jawda_unit_lookups_' . $group_key, 'jawda_unit_lookups' );
        }
    }
}
