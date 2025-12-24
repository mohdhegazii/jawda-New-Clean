<?php
/**
 * Centralized API functions for handling location data (Governorates, Cities, Districts).
 *
 * @package Jawda
 */

// Security Check: Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure DB update script is loaded
require_once __DIR__ . '/db-update.php';

// Include Routing Logic
require_once __DIR__ . '/routing.php';

/**
 * Get all governorates with caching.
 * Excludes soft-deleted items.
 *
 * @return array[] Each item:
 *  [
 *    'id'        => (int),
 *     'slug_ar'    => (string),
 *    'name_en'   => (string),
 *    'slug'      => (string),
 *    'slug_ar'   => (string),
 *    'latitude'  => (string|null),
 *    'longitude' => (string|null),
 *  ]
 */
function jawda_get_all_governorates() {
	global $wpdb;

    $cache_key = 'jawda_all_governorates';
    $cache = wp_cache_get( $cache_key, 'jawda_locations' );

	if ( $cache !== false ) {
		return $cache;
	}

	$table_name = $wpdb->prefix . 'jawda_governorates';
    // Select * to include new columns, filter out soft deleted
	$results = $wpdb->get_results(
		"SELECT * FROM {$table_name} WHERE is_deleted = 0 ORDER BY name_ar ASC",
		ARRAY_A
	);

	if ( empty( $results ) ) {
		$cache = [];
	} else {
        // Cast IDs to integers for type consistency.
        $cache = array_map( function( $row ) {
            $row['id'] = (int) $row['id'];
            return $row;
        }, $results );
    }

    wp_cache_set( $cache_key, $cache, 'jawda_locations', DAY_IN_SECONDS );

	return $cache;
}

/**
 * Get all cities for a given governorate with caching.
 * Excludes soft-deleted items.
 *
 * @param int $governorate_id
 * @return array[] Each item:
 *  [
 *    'id'        => (int),
 *     'slug_ar'    => (string),
 *    'name_en'   => (string),
 *    'slug'      => (string),
 *    'slug_ar'   => (string),
 *    'governorate_id' => (int),
 *    'latitude'  => (string|null),
 *    'longitude' => (string|null),
 *  ]
 */
function jawda_get_cities_by_governorate( $governorate_id ) {
	global $wpdb;

	$governorate_id = absint( $governorate_id );
	if ( ! $governorate_id ) {
		return [];
	}

    $cache_key = 'jawda_cities_gov_' . $governorate_id;
    $cache = wp_cache_get( $cache_key, 'jawda_locations' );

	if ( $cache !== false ) {
		return $cache;
	}

	$table_name = $wpdb->prefix . 'jawda_cities';
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE governorate_id = %d AND is_deleted = 0 ORDER BY name_ar ASC",
			$governorate_id
		),
		ARRAY_A
	);

	if ( empty( $results ) ) {
		$cache = [];
	} else {
        // Cast IDs to integers.
        $cache = array_map( function( $row ) {
            $row['id'] = (int) $row['id'];
            $row['governorate_id'] = (int) $row['governorate_id'];
            return $row;
        }, $results );
    }

    wp_cache_set( $cache_key, $cache, 'jawda_locations', DAY_IN_SECONDS );

	return $cache;
}

/**
 * Get all districts for a given city with caching.
 * Excludes soft-deleted items.
 *
 * @param int $city_id
 * @return array[] Each item:
 *  [
 *    'id'        => (int),
 *     'slug_ar'    => (string),
 *    'name_en'   => (string),
 *    'slug'      => (string),
 *    'slug_ar'   => (string),
 *    'city_id'   => (int),
 *    'latitude'  => (string|null),
 *    'longitude' => (string|null),
 *  ]
 */
function jawda_get_districts_by_city( $city_id ) {
	global $wpdb;

	$city_id = absint( $city_id );
	if ( ! $city_id ) {
		return [];
	}

    $cache_key = 'jawda_districts_city_' . $city_id;
    $cache = wp_cache_get( $cache_key, 'jawda_locations' );

	if ( $cache !== false ) {
		return $cache;
	}

	$table_name = $wpdb->prefix . 'jawda_districts';
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE city_id = %d AND is_deleted = 0 ORDER BY name_ar ASC",
			$city_id
		),
		ARRAY_A
	);

	if ( empty( $results ) ) {
		$cache = [];
	} else {
        // Cast IDs to integers.
        $cache = array_map( function( $row ) {
            $row['id'] = (int) $row['id'];
            $row['city_id'] = (int) $row['city_id'];
            return $row;
        }, $results );
    }

    wp_cache_set( $cache_key, $cache, 'jawda_locations', DAY_IN_SECONDS );

	return $cache;
}

/**
 * Resolve location names (governorate, city, district) from stored IDs.
 * Uses individual entity caching internally.
 *
 * @param int|string|null $governorate_id
 * @param int|string|null $city_id
 * @param int|string|null $district_id
 * @return array {
 *   @type array|null $governorate {
 *     @type int    $id
 *     @type string $name_ar
 *     @type string $name_en
 *     @type string|null $latitude
 *     @type string|null $longitude
 *   }
 *   @type array|null $city {
 *     @type int    $id
 *     @type string $name_ar
 *     @type string $name_en
 *     @type string|null $latitude
 *     @type string|null $longitude
 *   }
 *   @type array|null $district {
 *     @type int    $id
 *     @type string $name_ar
 *     @type string $name_en
 *     @type string|null $latitude
 *     @type string|null $longitude
 *   }
 * }
 */
function jawda_get_location_names_from_ids( $governorate_id, $city_id, $district_id ) {
	global $wpdb;

	$governorate_id = absint( $governorate_id );
	$city_id        = absint( $city_id );
	$district_id    = absint( $district_id );

	$result = [
		'governorate' => null,
		'city'        => null,
		'district'    => null,
	];

    // Uses jawda_get_* wrappers to benefit from soft delete checks and caching
    if ( $governorate_id > 0 ) {
        $result['governorate'] = jawda_get_governorate($governorate_id);
    }

    if ( $city_id > 0 ) {
        $result['city'] = jawda_get_city($city_id);
    }

    if ( $district_id > 0 ) {
        $result['district'] = jawda_get_district($district_id);
    }

	return $result;
}

/**
 * Retrieves a single city by its ID with caching.
 * Returns null if the city is soft deleted.
 *
 * @param int $city_id The ID of the city.
 * @return array|null The city data as an associative array, or null if not found/deleted.
 */
function jawda_get_city($city_id) {
    global $wpdb;
    $city_id = absint($city_id);
    if (!$city_id) {
        return null;
    }

    $cache_key = 'jawda_city_' . $city_id;
    $cache = wp_cache_get( $cache_key, 'jawda_locations' );

    if ( $cache !== false ) {
        // Check soft delete status from cache if cached object includes it
        if ( isset($cache['is_deleted']) && $cache['is_deleted'] == 1 ) {
            return null;
        }
        return $cache;
    }

    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jawda_cities WHERE id = %d", $city_id), ARRAY_A);

    if ( $row ) {
        $row['id'] = (int) $row['id'];
        $row['governorate_id'] = (int) $row['governorate_id'];
    }

    wp_cache_set( $cache_key, $row, 'jawda_locations', DAY_IN_SECONDS );

    if ( $row && isset($row['is_deleted']) && $row['is_deleted'] == 1 ) {
        return null;
    }

    return $row;
}

/**
 * Retrieves a single district by its ID with caching.
 * Returns null if the district is soft deleted.
 *
 * @param int $district_id The ID of the district.
 * @return array|null The district data as an associative array, or null if not found/deleted.
 */
function jawda_get_district($district_id) {
    global $wpdb;
    $district_id = absint($district_id);
    if (!$district_id) {
        return null;
    }

    $cache_key = 'jawda_district_' . $district_id;
    $cache = wp_cache_get( $cache_key, 'jawda_locations' );

    if ( $cache !== false ) {
         if ( isset($cache['is_deleted']) && $cache['is_deleted'] == 1 ) {
            return null;
        }
        return $cache;
    }

    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jawda_districts WHERE id = %d", $district_id), ARRAY_A);

    if ( $row ) {
        $row['id'] = (int) $row['id'];
        $row['city_id'] = (int) $row['city_id'];
    }

    wp_cache_set( $cache_key, $row, 'jawda_locations', DAY_IN_SECONDS );

    if ( $row && isset($row['is_deleted']) && $row['is_deleted'] == 1 ) {
        return null;
    }

    return $row;
}

/* -------------------------------------------------------------------------
   NEW HELPER FUNCTIONS (Jawda API)
   ------------------------------------------------------------------------- */

/**
 * Retrieves a single governorate by its ID with caching.
 * Returns null if soft deleted.
 *
 * @param int $id
 * @return array|null
 */
function jawda_get_governorate($id) {
    global $wpdb;
    $id = absint($id);
    if (!$id) return null;

    $cache_key = 'jawda_gov_' . $id;
    $cache = wp_cache_get( $cache_key, 'jawda_locations' );

    if ( $cache !== false ) {
         if ( isset($cache['is_deleted']) && $cache['is_deleted'] == 1 ) {
            return null;
        }
        return $cache;
    }

    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jawda_governorates WHERE id = %d", $id), ARRAY_A);

    if ( $row ) {
        $row['id'] = (int) $row['id'];
    }

    wp_cache_set( $cache_key, $row, 'jawda_locations', DAY_IN_SECONDS );

    if ( $row && isset($row['is_deleted']) && $row['is_deleted'] == 1 ) {
        return null;
    }

    return $row;
}





/**
 * Get full location chain names.
 *
 * @param int $governorate_id
 * @param int $city_id
 * @param int $district_id
 * @return array
 */
function jawda_get_location_full_chain($governorate_id, $city_id, $district_id = null) {
    return jawda_get_location_names_from_ids($governorate_id, $city_id, $district_id);
}

/**
 * Build a pretty URL for the new projects routing based on location IDs.
 * e.g. /new-projects/{gov_slug}/{city_slug}/
 *
 * @param int|null $gov_id
 * @param int|null $city_id
 * @param int|null $district_id
 * @return string|null URL or null on failure
 */
function jawda_get_new_projects_url_by_location( $gov_id = null, $city_id = null, $district_id = null, $lang = null ) {
    // Use provided lang or fallback to current locale
    if ( $lang ) {
        $is_ar = ( $lang === 'ar' );
    } else {
        $is_ar = function_exists('jawda_is_arabic_locale') && jawda_is_arabic_locale();
    }

    $base_slug = $is_ar ? 'مشروعات-جديدة' : 'new-projects';
    $country_slug = $is_ar ? 'مصر' : 'egypt';

    $base = "/{$base_slug}/{$country_slug}/";

    // Use pll_home_url if possible to ensure correct prefix
    if ( function_exists( 'pll_home_url' ) ) {
        $url = pll_home_url( $is_ar ? 'ar' : 'en' ) . ltrim( $base, '/' );
        // pll_home_url includes trailing slash, but we want to append base cleanly
        // Example: site.com/en/ + new-projects/egypt/
        // Let's be careful with slashes.
        $home = rtrim( pll_home_url( $is_ar ? 'ar' : 'en' ), '/' );
        $url = $home . $base;
    } else {
        // Fallback
        $url = home_url( $base );
    }

    // Ensure IDs are integers
    $gov_id      = $gov_id ? (int) $gov_id : 0;
    $city_id     = $city_id ? (int) $city_id : 0;
    $district_id = $district_id ? (int) $district_id : 0;

    // Case 1: Country Level (All null or zero)
    if ( ! $gov_id && ! $city_id && ! $district_id ) {
        return $url;
    }

    // Case 2: Resolve Governorate
    // If Gov ID is missing but we have City/Dist, we try to resolve Gov from them.
    if ( ! $gov_id && ( $city_id || $district_id ) ) {
        if ( $city_id ) {
            $city = jawda_get_city( $city_id );
            if ( $city ) {
                $gov_id = (int) $city['governorate_id'];
            }
        } elseif ( $district_id ) {
            $dist = jawda_get_district( $district_id );
            if ( $dist ) {
                $city_id = (int) $dist['city_id'];
                $city    = jawda_get_city( $city_id );
                if ( $city ) {
                    $gov_id = (int) $city['governorate_id'];
                }
            }
        }
    }

    // Validate Governorate
    if ( ! $gov_id ) {
        return null; // Cannot build URL without valid Gov
    }
    $gov = jawda_get_governorate( $gov_id );
    if ( ! $gov ) {
        return null;
    }

    // Resolve Gov Slug (Localized)
    $gov_slug = $gov['slug']; // Default EN
    if ( $is_ar && ! empty( $gov['slug_ar'] ) ) {
        $gov_slug = $gov['slug_ar'];
    } elseif ( empty( $gov_slug ) && ! empty( $gov['slug_ar'] ) ) {
        $gov_slug = $gov['slug_ar']; // Fallback
    }
    $url .= $gov_slug . '/';

    // Case 3: City
    if ( $city_id ) {
        $city = jawda_get_city( $city_id );
        if ( ! $city || (int) $city['governorate_id'] !== $gov_id ) {
            return null; // Hierarchy mismatch or invalid city
        }

        $city_slug = $city['slug'];
        if ( $is_ar && ! empty( $city['slug_ar'] ) ) {
            $city_slug = $city['slug_ar'];
        } elseif ( empty( $city_slug ) && ! empty( $city['slug_ar'] ) ) {
            $city_slug = $city['slug_ar'];
        }
        $url .= $city_slug . '/';
    }

    // Case 4: District
    if ( $district_id && $city_id ) {
        $dist = jawda_get_district( $district_id );
        if ( ! $dist || (int) $dist['city_id'] !== $city_id ) {
            return null;
        }

        $dist_slug = $dist['slug'];
        if ( $is_ar && ! empty( $dist['slug_ar'] ) ) {
            $dist_slug = $dist['slug_ar'];
        } elseif ( empty( $dist_slug ) && ! empty( $dist['slug_ar'] ) ) {
            $dist_slug = $dist['slug_ar'];
        }
        $url .= $dist_slug . '/';
    }

    return $url;
}
