<?php
/**
 * Dynamic Routing for Location System (Governorates, Cities, Districts).
 * Handles URL rewriting and query variable registration.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom query variables for location routing.
 */
function jawda_locations_register_query_vars( $vars ) {
    $vars[] = 'jawda_loc_gov';
    $vars[] = 'jawda_loc_city';
    $vars[] = 'jawda_loc_dist';
    $vars[] = 'jawda_new_projects_root';
    $vars[] = 'jawda_loc_level';
    return $vars;
}
add_filter( 'query_vars', 'jawda_locations_register_query_vars' );

/**
 * Add rewrite rules for hierarchical location URLs.
 * Structure: /new-projects/{gov}/{city}/{district}
 */
function jawda_locations_add_rewrite_rules() {
    // Define supported bases and countries
    // Explicitly set the language query var for Arabic routes to ensure WP_Query fetches the correct translations.

    $configs = [
        ['base' => 'new-projects', 'country' => 'egypt', 'lang' => 'en'],
        ['base' => 'مشروعات-جديدة', 'country' => 'مصر', 'lang' => 'ar'],
        // Fallback for encoded variants just in case (updated to match 'مشروعات')
        ['base' => '%d9%85%d8%b4%d8%b1%d9%88%d8%b9%d8%a7%d8%aa-%d8%ac%d8%af%d9%8a%d8%af%d8%a9', 'country' => '%d9%85%d8%b5%d8%b1', 'lang' => 'ar']
    ];

    foreach ($configs as $conf) {
        $b = $conf['base'];
        $c = $conf['country'];
        $l = $conf['lang'];

        $query_suffix = "&lang={$l}";

        // --- Level 0: Country Root ---

        // /new-projects/egypt/page/2/
        add_rewrite_rule(
            "^{$b}/{$c}/page/([0-9]{1,})/?$",
            'index.php?post_type=projects&jawda_new_projects_root=1&paged=$matches[1]' . $query_suffix,
            'top'
        );

        // /new-projects/egypt/
        add_rewrite_rule(
            "^{$b}/{$c}/?$",
            'index.php?post_type=projects&jawda_new_projects_root=1' . $query_suffix,
            'top'
        );

        // --- Level 3: Gov/City/Dist ---

        // /new-projects/egypt/gov/city/dist/page/2/
        add_rewrite_rule(
            "^{$b}/{$c}/([^/]+)/([^/]+)/([^/]+)/page/([0-9]{1,})/?$",
            'index.php?post_type=projects&jawda_loc_gov=$matches[1]&jawda_loc_city=$matches[2]&jawda_loc_dist=$matches[3]&paged=$matches[4]' . $query_suffix,
            'top'
        );

        // /new-projects/egypt/gov/city/dist/
        add_rewrite_rule(
            "^{$b}/{$c}/([^/]+)/([^/]+)/([^/]+)/?$",
            'index.php?post_type=projects&jawda_loc_gov=$matches[1]&jawda_loc_city=$matches[2]&jawda_loc_dist=$matches[3]' . $query_suffix,
            'top'
        );

        // --- Level 2: Gov/City ---

        // /new-projects/egypt/gov/city/page/2/
        add_rewrite_rule(
            "^{$b}/{$c}/([^/]+)/([^/]+)/page/([0-9]{1,})/?$",
            'index.php?post_type=projects&jawda_loc_gov=$matches[1]&jawda_loc_city=$matches[2]&paged=$matches[3]' . $query_suffix,
            'top'
        );

        // /new-projects/egypt/gov/city/
        add_rewrite_rule(
            "^{$b}/{$c}/([^/]+)/([^/]+)/?$",
            'index.php?post_type=projects&jawda_loc_gov=$matches[1]&jawda_loc_city=$matches[2]' . $query_suffix,
            'top'
        );

        // --- Level 1: Gov ---

        // /new-projects/egypt/gov/page/2/
        add_rewrite_rule(
            "^{$b}/{$c}/([^/]+)/page/([0-9]{1,})/?$",
            'index.php?post_type=projects&jawda_loc_gov=$matches[1]&paged=$matches[2]' . $query_suffix,
            'top'
        );

        // /new-projects/egypt/gov/
        add_rewrite_rule(
            "^{$b}/{$c}/([^/]+)/?$",
            'index.php?post_type=projects&jawda_loc_gov=$matches[1]' . $query_suffix,
            'top'
        );
    }

    // --- Fallback for Root without Country (Optional, but good for UX) ---
    // /new-projects/ -> Redirect logic or alias to /new-projects/egypt/
    // For now we map it to root context same as before
    add_rewrite_rule(
        "^new-projects/?$",
        'index.php?post_type=projects&jawda_new_projects_root=1',
        'top'
    );
}
add_action( 'init', 'jawda_locations_add_rewrite_rules' );

/**
 * Resolve location slugs to IDs when the query vars are present.
 * Hooks into 'parse_request' to set a global context or modify query vars.
 */
function jawda_locations_resolve_slugs( $wp ) {
    global $wpdb;

    // 1. Check for Root Case (Country Level)
    if ( ! empty( $wp->query_vars['jawda_new_projects_root'] ) ) {
        // Redirect Short Root to Full Country URL
        // e.g. /new-projects/ -> /new-projects/egypt/
        // e.g. /ar/mashro3at/ -> /ar/mashro3at/masr/
        if ( function_exists( 'jawda_get_new_projects_url_by_location' ) ) {
            $full_url = jawda_get_new_projects_url_by_location( null, null, null );

            // Check if we are already there?
            // Actually rewrite rules map both short and long to the same query var.
            // But we can check the REQUEST_URI or just force redirect if we are here via the SHORT rule.
            // The short rule pattern is "^base/?$".

            // Simple check: If the request URI does NOT contain the country slug, redirect.
            $is_ar = function_exists('jawda_is_arabic_locale') && jawda_is_arabic_locale();
            $country_slug = $is_ar ? 'مصر' : 'egypt'; // from api.php logic
            // Note: URL might be encoded.

            // Safer: Reconstruct current URL and compare?
            // Or assume if we hit this block, we MIGHT be on short url.
            // Let's just redirect. WP handles if it matches.
            // Wait, infinite loop risk if the long URL also maps here?
            // The Long URL (/new-projects/egypt/) maps to: jawda_new_projects_root=1.
            // The Short URL (/new-projects/) also maps to: jawda_new_projects_root=1.

            // Difference? The Long URL matches rule "^base/country/?$".
            // The Short URL matches "^base/?$".

            // We can check $_SERVER['REQUEST_URI'].
            $req = urldecode( $_SERVER['REQUEST_URI'] );
            // Check if country slug is missing
            // We need to be careful about the language prefix /ar/ etc.

            if ( strpos( $req, '/' . $country_slug ) === false ) {
                 wp_safe_redirect( $full_url, 301 );
                 exit;
            }
        }

        $wp->query_vars['jawda_loc_level'] = 'country';
        $GLOBALS['jawda_current_location_context'] = [
            'level'   => 'country',
            'name_en' => 'Egypt', // Hardcoded for now as per instructions
             'slug_ar'  => 'مصر',
        ];
        // Map legacy global for compatibility if needed, though we should migrate to context
        $GLOBALS['jawda_current_location'] = $GLOBALS['jawda_current_location_context'];
        return;
    }

    // 2. Check for Hierarchical Slugs
    // Decode URL slugs to handle Arabic characters correctly
    $gov_slug  = isset( $wp->query_vars['jawda_loc_gov'] ) ? urldecode( $wp->query_vars['jawda_loc_gov'] ) : null;
    $city_slug = isset( $wp->query_vars['jawda_loc_city'] ) ? urldecode( $wp->query_vars['jawda_loc_city'] ) : null;
    $dist_slug = isset( $wp->query_vars['jawda_loc_dist'] ) ? urldecode( $wp->query_vars['jawda_loc_dist'] ) : null;

    if ( ! $gov_slug ) {
        return;
    }

    // --- Language Detection ---
    $lang = isset( $wp->query_vars['lang'] ) ? $wp->query_vars['lang'] : '';
    if ( empty( $lang ) && function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language( 'slug' );
    }
    if ( empty( $lang ) ) {
        $lang = 'ar'; // Default to Arabic as per project context
    }
    $is_ar = ( $lang === 'ar' );

    // --- Resolve Governorate ---
    $gov_row = null;
    if ( $is_ar ) {
        // AR: Try slug_ar, then name_ar, then slug (Robust fallback)
        $gov_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, name_ar, name_en, slug, slug_ar FROM {$wpdb->prefix}jawda_governorates WHERE (slug_ar = %s OR name_ar = %s OR slug = %s) AND is_deleted = 0",
            $gov_slug, $gov_slug, $gov_slug
        ) );
    } else {
        // EN: Try slug only (Strict)
        $gov_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, name_ar, name_en, slug, slug_ar FROM {$wpdb->prefix}jawda_governorates WHERE slug = %s AND is_deleted = 0",
            $gov_slug
        ) );
    }

    if ( ! $gov_row ) {
        $wp->query_vars['error'] = '404';
        return;
    }

    // Set resolved ID
    $wp->query_vars['jawda_loc_gov_id'] = (int) $gov_row->id;
    $wp->query_vars['jawda_loc_level']  = 'governorate';

    // Build Context
    $context = [
        'level'       => 'governorate',
        'governorate' => $gov_row,
        'data'        => $gov_row, // Legacy support
    ];

    // --- Resolve City ---
    $city_row = null;
    if ( $city_slug ) {
        if ( $is_ar ) {
             // Fuzzy match for Arabic Names (replace hyphens with spaces)
             $city_name_fuzzy = str_replace( '-', ' ', $city_slug );
             $city_row = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, name_ar, name_en, slug, slug_ar, governorate_id FROM {$wpdb->prefix}jawda_cities WHERE (slug_ar = %s OR name_ar = %s OR slug = %s) AND governorate_id = %d AND is_deleted = 0",
                $city_slug, $city_name_fuzzy, $city_slug, $gov_row->id
            ) );
        } else {
            $city_row = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, name_ar, name_en, slug, slug_ar, governorate_id FROM {$wpdb->prefix}jawda_cities WHERE slug = %s AND governorate_id = %d AND is_deleted = 0",
                $city_slug, $gov_row->id
            ) );
        }

        if ( ! $city_row ) {
            $wp->query_vars['error'] = '404';
            return;
        }

        $wp->query_vars['jawda_loc_city_id'] = (int) $city_row->id;
        $wp->query_vars['jawda_loc_level']   = 'city';

        $context['level']  = 'city';
        $context['city']   = $city_row;
        $context['data']   = $city_row; // Legacy support
        $context['parent'] = $gov_row; // Legacy support
    }

    // --- Resolve District ---
    if ( $dist_slug && $city_row ) {
        $dist_row = null;
        if ( $is_ar ) {
             // Fuzzy match for Arabic Names (replace hyphens with spaces)
             $dist_name_fuzzy = str_replace( '-', ' ', $dist_slug );
             $dist_row = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, name_ar, name_en, slug, slug_ar, city_id FROM {$wpdb->prefix}jawda_districts WHERE (slug_ar = %s OR name_ar = %s OR slug = %s) AND city_id = %d AND is_deleted = 0",
                $dist_slug, $dist_name_fuzzy, $dist_slug, $city_row->id
            ) );
        } else {
            $dist_row = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, name_ar, name_en, slug, slug_ar, city_id FROM {$wpdb->prefix}jawda_districts WHERE slug = %s AND city_id = %d AND is_deleted = 0",
                $dist_slug, $city_row->id
            ) );
        }

        if ( ! $dist_row ) {
            $wp->query_vars['error'] = '404';
            return;
        }

        $wp->query_vars['jawda_loc_dist_id'] = (int) $dist_row->id;
        $wp->query_vars['jawda_loc_level']   = 'district';

        $context['level']       = 'district';
        $context['district']    = $dist_row;
        $context['data']        = $dist_row; // Legacy support
        $context['parent']      = $city_row; // Legacy support
        $context['grandparent'] = $gov_row; // Legacy support
    }

    // Store Global Context
    $GLOBALS['jawda_current_location_context'] = $context;
    $GLOBALS['jawda_current_location']         = $context; // Maintain compatibility
}
add_action( 'parse_request', 'jawda_locations_resolve_slugs' );

/**
 * Filter the main query to show projects for the resolved location.
 */
function jawda_locations_filter_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // 1. Handle Root Case (Country Level)
    if ( $query->get( 'jawda_new_projects_root' ) ) {
        // Explicitly do NOT filter by location IDs.
        // The post_type=projects is already set by the rewrite rule.
        // Set posts_per_page to match legacy catalogs (9 items)
        $query->set( 'posts_per_page', 9 );
        return;
    }

    // 2. Handle Hierarchical Location Filtering
    $gov_id  = $query->get( 'jawda_loc_gov_id' );
    $city_id = $query->get( 'jawda_loc_city_id' );
    $dist_id = $query->get( 'jawda_loc_dist_id' );

    if ( ! $gov_id ) {
        return;
    }

    // We are filtering by location!

    // Set posts_per_page to match legacy catalogs (9 items)
    $query->set( 'posts_per_page', 9 );

    // Construct Meta Query
    $meta_query = $query->get( 'meta_query' );
    if ( ! is_array( $meta_query ) ) {
        $meta_query = [];
    }

    if ( $dist_id ) {
        $meta_query[] = [
            'key'   => 'loc_district_id',
            'value' => $dist_id,
        ];
    } elseif ( $city_id ) {
        $meta_query[] = [
            'key'   => 'loc_city_id',
            'value' => $city_id,
        ];
    } elseif ( $gov_id ) {
        $meta_query[] = [
            'key'   => 'loc_governorate_id',
            'value' => $gov_id,
        ];
    }

    $query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'jawda_locations_filter_query' );

/**
 * Generate dynamic SEO titles for location pages.
 */
function jawda_locations_seo_title( $title ) {
    global $jawda_current_location; // Alias for context

    if ( ! isset( $jawda_current_location ) ) {
        return $title;
    }

    $is_ar = function_exists( 'jawda_is_arabic_locale' ) ? jawda_is_arabic_locale() : is_rtl();
    $name  = '';

    // Handle Root Context (Array with name keys)
    if ( isset( $jawda_current_location['level'] ) && $jawda_current_location['level'] === 'country' ) {
        $name = $is_ar ? ($jawda_current_location[ 'slug_ar' ] ?? 'مصر') : ($jawda_current_location['name_en'] ?? 'Egypt');
    }
    // Handle DB Object (Gov/City/Dist)
    elseif ( isset( $jawda_current_location['data'] ) && is_object( $jawda_current_location['data'] ) ) {
        $data = $jawda_current_location['data'];
        $name = $is_ar ? $data->name_ar : $data->name_en;
    }

    if ( empty( $name ) ) {
        return $title;
    }

    // e.g. "New Projects in Cairo - 2024"
    if ( $is_ar ) {
        return sprintf( 'مشاريع جديدة في %s - %s', $name, date('Y') );
    } else {
        return sprintf( 'New Projects in %s - %s', $name, date('Y') );
    }
}
add_filter( 'wpseo_title', 'jawda_locations_seo_title' );
add_filter( 'rank_math/frontend/title', 'jawda_locations_seo_title' );
add_filter( 'pre_get_document_title', 'jawda_locations_seo_title', 20 );

/**
 * Force the correct template for dynamic location pages.
 */
function jawda_locations_template_include( $template ) {
    global $jawda_current_location;

    if ( isset( $jawda_current_location ) ) {
        // Use our custom virtual catalog template to control Breadcrumbs & Pagination HTML
        // We look for it in the theme first (allow override), then fall back to plugin path

        // 1. Check Theme Override: app/templates/locations/virtual-catalog.php
        $theme_override = locate_template( [ 'app/templates/locations/virtual-catalog.php' ] );
        if ( $theme_override ) {
            return $theme_override;
        }

        // 2. Use our plugin-provided template
        $plugin_template = __DIR__ . '/templates/virtual-catalog.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
}
add_filter( 'template_include', 'jawda_locations_template_include' );

/**
 * Filter Polylang translation URL for virtual location pages.
 * This ensures the language switcher points to the correct translated URL (e.g., /en/new-projects/egypt/ vs /ar/mashro3at/masr/).
 *
 * @param string $url The default translation URL generated by Polylang.
 * @param string $lang The target language code (e.g. 'en', 'ar').
 * @return string
 */
function jawda_locations_pll_translation_url( $url, $lang ) {
    global $jawda_current_location_context;

    // Only act if we are on a dynamic location page
    if ( ! isset( $jawda_current_location_context ) || empty( $jawda_current_location_context ) ) {
        return $url;
    }

    // Ensure API function is available
    if ( ! function_exists( 'jawda_get_new_projects_url_by_location' ) ) {
        return $url;
    }

    $ctx = $jawda_current_location_context;
    $level = $ctx['level'] ?? '';

    $gov_id = 0;
    $city_id = 0;
    $dist_id = 0;

    // Extract IDs based on context
    if ( $level === 'country' ) {
        // All IDs 0 for country root
    } elseif ( $level === 'governorate' && isset( $ctx['governorate']->id ) ) {
        $gov_id = (int) $ctx['governorate']->id;
    } elseif ( $level === 'city' && isset( $ctx['city']->id ) ) {
        $city_id = (int) $ctx['city']->id;
        // Parent Gov? Not needed for helper if we have city, but cleaner to pass if we have it.
        // Helper resolves it anyway.
    } elseif ( $level === 'district' && isset( $ctx['district']->id ) ) {
        $dist_id = (int) $ctx['district']->id;
    }

    // Generate URL for target language
    $new_url = jawda_get_new_projects_url_by_location( $gov_id, $city_id, $dist_id, $lang );

    if ( $new_url ) {
        return $new_url;
    }

    return $url;
}
add_filter( 'pll_translation_url', 'jawda_locations_pll_translation_url', 10, 2 );
