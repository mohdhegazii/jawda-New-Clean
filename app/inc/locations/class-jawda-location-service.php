<?php
/**
 * Central location service to unify saving/loading governorate, city, district, and map coordinates.
 * Refactored to enforce stricter validation and hierarchy rules.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Alias for backward compatibility if needed, though we'll use Jawda_Location_Service primarily.


class Jawda_Location_Service {
    const META_GOVERNORATE = 'loc_governorate_id';
    const META_CITY        = 'loc_city_id';
    const META_DISTRICT    = 'loc_district_id';
    const META_MAP         = 'jawda_map';

    /**
     * Return the canonical meta keys for location fields.
     *
     * @return array
     */
    public static function get_meta_keys() {
        return [
            'governorate' => self::META_GOVERNORATE,
            'city'        => self::META_CITY,
            'district'    => self::META_DISTRICT,
            'map'         => self::META_MAP,
        ];
    }

    /**
     * Normalize a coordinate string/number into a string or null.
     *
     * @param mixed $value Raw lat/lng value.
     * @return string|null
     */
    protected static function normalize_coordinate( $value ) {
        if ( function_exists( 'jawda_locations_normalize_coordinate' ) ) {
            return jawda_locations_normalize_coordinate( $value );
        }

        if ( ! isset( $value ) ) {
            return null;
        }

        $value = trim( (string) $value );

        if ( $value === '' ) {
            return null;
        }

        $value = str_replace( ',', '.', $value );

        return is_numeric( $value ) ? $value : null;
    }

    /**
     * Retrieve the stored map meta in a normalized structure.
     *
     * @param int $post_id
     * @return array
     */
    public static function get_map_meta( $post_id ) {
        $map = function_exists( 'carbon_get_post_meta' )
            ? carbon_get_post_meta( $post_id, self::META_MAP )
            : get_post_meta( $post_id, self::META_MAP, true );

        $map = is_array( $map ) ? $map : [];

        $lat = isset( $map['lat'] ) ? self::normalize_coordinate( $map['lat'] ) : null;
        $lng = isset( $map['lng'] ) ? self::normalize_coordinate( $map['lng'] ) : null;

        return [
            'lat'     => $lat !== null ? (string) $lat : '',
            'lng'     => $lng !== null ? (string) $lng : '',
            'zoom'    => isset( $map['zoom'] ) && is_numeric( $map['zoom'] ) ? (int) $map['zoom'] : 13,
            'address' => isset( $map['address'] ) ? (string) $map['address'] : '',
        ];
    }

    /**
     * Persist map meta if allowed.
     *
     * @param int   $post_id
     * @param array $map_data
     * @param array $options
     */
    protected static function save_map_meta( $post_id, array $map_data, array $options = [] ) {
        $defaults = [
            'overwrite' => true,
        ];
        $options  = wp_parse_args( $options, $defaults );

        $existing = self::get_map_meta( $post_id );

        if ( ! $options['overwrite'] && $existing['lat'] !== '' && $existing['lng'] !== '' ) {
            return;
        }

        $lat = isset( $map_data['lat'] ) ? self::normalize_coordinate( $map_data['lat'] ) : null;
        $lng = isset( $map_data['lng'] ) ? self::normalize_coordinate( $map_data['lng'] ) : null;

        $payload = [
            'lat'     => $lat !== null ? (string) $lat : '',
            'lng'     => $lng !== null ? (string) $lng : '',
            'zoom'    => isset( $map_data['zoom'] ) && is_numeric( $map_data['zoom'] ) ? (int) $map_data['zoom'] : $existing['zoom'],
            'address' => isset( $map_data['address'] ) ? (string) $map_data['address'] : $existing['address'],
        ];

        if ( function_exists( 'carbon_set_post_meta' ) ) {
            carbon_set_post_meta( $post_id, self::META_MAP, $payload );
        } else {
            update_post_meta( $post_id, self::META_MAP, $payload );
        }
    }

    /**
     * Resolve the best available coordinates for a given hierarchy.
     *
     * @param int $governorate_id
     * @param int $city_id
     * @param int $district_id
     * @return array|null
     */
    public static function resolve_coordinates_from_hierarchy( $governorate_id, $city_id, $district_id ) {
        $candidates = [];

        if ( $district_id && function_exists( 'jawda_get_district' ) ) {
            $candidates[] = jawda_get_district( $district_id );
        }

        if ( $city_id && function_exists( 'jawda_get_city' ) ) {
            $candidates[] = jawda_get_city( $city_id );
        }

        if ( $governorate_id && function_exists( 'jawda_get_all_governorates' ) ) {
            foreach ( jawda_get_all_governorates() as $row ) {
                if ( isset( $row['id'] ) && (int) $row['id'] === (int) $governorate_id ) {
                    $candidates[] = $row;
                    break;
                }
            }
        }

        foreach ( $candidates as $candidate ) {
            if ( ! is_array( $candidate ) ) {
                continue;
            }

            $lat = isset( $candidate['latitude'] ) ? self::normalize_coordinate( $candidate['latitude'] ) : null;
            $lng = isset( $candidate['longitude'] ) ? self::normalize_coordinate( $candidate['longitude'] ) : null;

            if ( $lat !== null && $lng !== null ) {
                return [ 'lat' => $lat, 'lng' => $lng ];
            }
        }

        return null;
    }

    /**
     * Return stored IDs, resolved labels, and map payload for a post.
     *
     * @param int  $post_id
     * @param bool $include_names Whether to resolve labels.
     * @return array
     */
    public static function get_location_for_post( $post_id, $include_names = true ) {
        $keys = self::get_meta_keys();

        $governorate_id = absint( get_post_meta( $post_id, $keys['governorate'], true ) );
        $city_id        = absint( get_post_meta( $post_id, $keys['city'], true ) );
        $district_id    = absint( get_post_meta( $post_id, $keys['district'], true ) );

        $map = self::get_map_meta( $post_id );

        $names = [
            'governorate' => null,
            'city'        => null,
            'district'    => null,
        ];

        if ( $include_names && function_exists( 'jawda_get_location_names_from_ids' ) ) {
            $resolved = jawda_get_location_names_from_ids( $governorate_id, $city_id, $district_id );
            $names    = wp_parse_args( $resolved, $names );
        }

        return [
            'ids'   => [
                'governorate' => $governorate_id,
                'city'        => $city_id,
                'district'    => $district_id,
            ],
            'names' => $names,
            'map'   => $map,
        ];
    }

    /**
     * Save location IDs and optional map payload for a post.
     * Includes validation logic.
     *
     * @param int   $post_id
     * @param array $data {
     *   @type int    $governorate_id
     *   @type int    $city_id
     *   @type int    $district_id
     *   @type array  $map Optional map payload.
     *   @type bool   $overwrite_map Whether to overwrite existing coordinates.
     *   @type bool   $sync_map_from_location Autofill coordinates using hierarchy.
     * }
     */
    public static function save_location_for_post( $post_id, array $data ) {
        $post_id        = absint( $post_id );
        $governorate_id = absint( $data['governorate_id'] ?? 0 );
        $city_id        = absint( $data['city_id'] ?? $data['loc_city_id'] ?? 0 );
        $district_id    = absint( $data['district_id'] ?? $data['loc_district_id'] ?? 0 );

        // HIERARCHY AUTO-CORRECTION
        // If a District is selected, it overrides City and Governorate.
        if ( $district_id > 0 && function_exists( 'jawda_get_district' ) ) {
            $district = jawda_get_district( $district_id );
            if ( $district ) {
                $city_id = absint( $district['city_id'] );
            }
        }

        // If a City is selected (or resolved), it overrides Governorate.
        if ( $city_id > 0 && function_exists( 'jawda_get_city' ) ) {
            $city = jawda_get_city( $city_id );
            if ( $city ) {
                $governorate_id = absint( $city['governorate_id'] );
            }
        }

        // VALIDATION
        // Project must have Governorate + City.
        // Properties (if not inheriting) also must have Governorate + City.
        // Note: If this is a Property and it inherits, Jawda_Listing_Location_Service handles that BEFORE calling this.
        // So here we just assume we need valid location data.

        $is_valid = true;
        if ( $governorate_id <= 0 || $city_id <= 0 ) {
            $is_valid = false;
        }

        // If invalid, we should NOT save empty/broken data if it means breaking data integrity.
        // However, if it's a new post, not saving means it stays empty.
        // If it's an update, not saving means keeping old data (good) OR leaving it in invalid state.
        // The requirement says: "Projects/properties without governorate/city start failing with a clear admin error message (validation)."

        // We'll implement a transient error message if validation fails.
        if ( ! $is_valid ) {
             // Check if it's a relevant post type
             $pt = get_post_type( $post_id );
             if ( in_array( $pt, ['projects', 'catalogs', 'property'] ) ) {
                 // We set a transient to display an admin notice.
                 set_transient( 'jawda_location_error_' . $post_id, __( 'Location not saved: Governorate and City are required.', 'jawda' ), 45 );

                 // Do NOT proceed with saving partial data.
                 // But we might want to save the MAP data if it was provided?
                 // Usually location and map go together. Let's abort everything to be safe.
                 return;
             }
        }

        if ( $post_id <= 0 ) {
            return;
        }

        // Update meta keys consistently.
        if ( $governorate_id > 0 ) {
            update_post_meta( $post_id, self::META_GOVERNORATE, $governorate_id );
        } else {
            delete_post_meta( $post_id, self::META_GOVERNORATE );
        }

        if ( $city_id > 0 ) {
            update_post_meta( $post_id, self::META_CITY, $city_id );
        } else {
            delete_post_meta( $post_id, self::META_CITY );
        }

        if ( $district_id > 0 ) {
            update_post_meta( $post_id, self::META_DISTRICT, $district_id );
        } else {
            delete_post_meta( $post_id, self::META_DISTRICT );
        }

        $map_overwrite           = isset( $data['overwrite_map'] ) ? (bool) $data['overwrite_map'] : true;
        $sync_map_from_location  = ! empty( $data['sync_map_from_location'] );
        $map_payload             = isset( $data['map'] ) && is_array( $data['map'] ) ? $data['map'] : null;

        if ( $map_payload !== null ) {
            self::save_map_meta( $post_id, $map_payload, [ 'overwrite' => $map_overwrite ] );
            return;
        }

        if ( $sync_map_from_location ) {
            $coords = self::resolve_coordinates_from_hierarchy( $governorate_id, $city_id, $district_id );
            if ( $coords ) {
                self::save_map_meta( $post_id, [
                    'lat'  => $coords['lat'],
                    'lng'  => $coords['lng'],
                    'zoom' => isset( $data['map_zoom'] ) && is_numeric( $data['map_zoom'] ) ? (int) $data['map_zoom'] : 15,
                ], [ 'overwrite' => $map_overwrite ] );
            }
        }
    }

    /**
     * Clear the cache for a specific location and its list.
     *
     * @param string $type
     * @param int $id
     * @param int $parent_id Optional parent ID (gov_id for city, city_id for district).
     */
    public static function clear_location_cache( $type, $id, $parent_id = 0 ) {
        if ( ! function_exists('wp_cache_delete') ) {
            return;
        }

        if ( $type === 'governorate' ) {
            wp_cache_delete( 'jawda_all_governorates', 'jawda_locations' );
            wp_cache_delete( 'jawda_gov_' . $id, 'jawda_locations' );
        } elseif ( $type === 'city' ) {
            wp_cache_delete( 'jawda_city_' . $id, 'jawda_locations' );
            if ( $parent_id > 0 ) {
                wp_cache_delete( 'jawda_cities_gov_' . $parent_id, 'jawda_locations' );
            }
        } elseif ( $type === 'district' ) {
            wp_cache_delete( 'jawda_district_' . $id, 'jawda_locations' );
            if ( $parent_id > 0 ) {
                wp_cache_delete( 'jawda_districts_city_' . $parent_id, 'jawda_locations' );
            }
        }
    }

    /**
     * Soft delete a location record if it has no dependencies.
     *
     * @param string $type 'governorate', 'city', or 'district'
     * @param int $id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function soft_delete_location( $type, $id ) {
        global $wpdb;
        $id = absint( $id );

        if ( ! $id ) {
            return new WP_Error( 'invalid_id', __( 'Invalid location ID.', 'jawda' ) );
        }

        // 1. Check Dependencies (Orphaned Data Protection)
        $dependencies = self::check_location_dependencies( $type, $id );
        if ( is_wp_error( $dependencies ) ) {
            return $dependencies; // Return the error describing dependencies
        }

        // 2. Fetch Parent ID for Cache Invalidation BEFORE Deletion
        $parent_id = 0;
        if ( $type === 'city' ) {
            // Fetch city to get governorate_id
            if ( function_exists('jawda_get_city') ) {
                $city = jawda_get_city( $id );
                if ( $city ) {
                    $parent_id = isset($city['governorate_id']) ? (int) $city['governorate_id'] : 0;
                }
            }
        } elseif ( $type === 'district' ) {
            // Fetch district to get city_id
            if ( function_exists('jawda_get_district') ) {
                $district = jawda_get_district( $id );
                if ( $district ) {
                    $parent_id = isset($district['city_id']) ? (int) $district['city_id'] : 0;
                }
            }
        }

        // 3. Perform Soft Delete
        $table_map = [
            'governorate' => $wpdb->prefix . 'jawda_governorates',
            'city'        => $wpdb->prefix . 'jawda_cities',
            'district'    => $wpdb->prefix . 'jawda_districts',
        ];

        if ( ! isset( $table_map[ $type ] ) ) {
            return new WP_Error( 'invalid_type', __( 'Invalid location type.', 'jawda' ) );
        }

        $table = $table_map[ $type ];
        $now = current_time( 'mysql' );

        $updated = $wpdb->update(
            $table,
            [ 'is_deleted' => 1, 'deleted_at' => $now ],
            [ 'id' => $id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );

        if ( $updated === false ) {
             return new WP_Error( 'db_error', __( 'Database error while deleting location.', 'jawda' ) );
        }

        // 4. Clear Cache
        self::clear_location_cache( $type, $id, $parent_id );

        return true;
    }

    /**
     * Check if a location has dependent child locations or posts.
     *
     * @param string $type
     * @param int $id
     * @return bool|WP_Error True if safe to delete (no dependencies), WP_Error otherwise.
     */
    protected static function check_location_dependencies( $type, $id ) {
        global $wpdb;

        // Check for child locations
        if ( $type === 'governorate' ) {
            $child_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}jawda_cities WHERE governorate_id = %d AND is_deleted = 0",
                $id
            ) );
            if ( $child_count > 0 ) {
                return new WP_Error( 'has_children', sprintf( __( 'Cannot delete: This governorate has %d active cities.', 'jawda' ), $child_count ) );
            }
        } elseif ( $type === 'city' ) {
            $child_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}jawda_districts WHERE city_id = %d AND is_deleted = 0",
                $id
            ) );
            if ( $child_count > 0 ) {
                return new WP_Error( 'has_children', sprintf( __( 'Cannot delete: This city has %d active districts.', 'jawda' ), $child_count ) );
            }
        }

        // Check for linked Projects or Properties
        $meta_key = '';
        if ( $type === 'governorate' ) $meta_key = self::META_GOVERNORATE;
        if ( $type === 'city' )        $meta_key = self::META_CITY;
        if ( $type === 'district' )    $meta_key = self::META_DISTRICT;

        // Count posts that use this location
        // We check 'projects' and 'property' post types.
        // Since meta is in postmeta, we can just check postmeta, but better to join with posts to ensure they are not trash/deleted (unless we count trash too).
        // Let's count all non-trashed posts.

        $post_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND pm.meta_value = %d
             AND p.post_status NOT IN ('trash', 'auto-draft')
             AND p.post_type IN ('projects', 'property')",
            $meta_key, $id
        ) );

        if ( $post_count > 0 ) {
            return new WP_Error( 'has_posts', sprintf( __( 'Cannot delete: There are %d projects/listings linked to this location.', 'jawda' ), $post_count ) );
        }

        return true;
    }
}
