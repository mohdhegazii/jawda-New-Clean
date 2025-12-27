<?php
/**
 * Custom rewrite rules for the developer system.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function() {
    add_rewrite_rule('^مشروعات-جديدة/([^/]+)/?$', 'index.php?jawda_dev_slug=$matches[1]', 'top');
    add_rewrite_rule('^en/new-projects/([^/]+)/?$', 'index.php?jawda_dev_slug=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'jawda_dev_slug';
    return $vars;
});

add_filter('template_include', function($template) {
    $slug = get_query_var('jawda_dev_slug');
    if (!$slug) {
        return $template;
    }

    $developer = function_exists('jawda_get_developer_by_slug_ar')
        ? jawda_get_developer_by_slug_ar($slug)
        : null;
    if (!$developer && function_exists('jawda_get_developer_by_slug_en')) {
        $developer = jawda_get_developer_by_slug_en($slug);
    }

    if (!$developer) {
        return $template;
    }

    $GLOBALS['jawda_current_developer'] = $developer;
    $GLOBALS['jawda_is_rendering_developer_template'] = true;

    return get_theme_file_path('app/templates/developers/single-developer.php');
});

/**
 * Ensure Polylang language switcher links to the translated developer URL.
 *
 * @param string $url  Default translation URL.
 * @param string $lang Target language code.
 * @return string
 */
function jawda_developers_pll_translation_url( $url, $lang ) {
    $developer = $GLOBALS['jawda_current_developer'] ?? null;
    if ( ! $developer ) {
        return $url;
    }

    $slug_ar = $developer['slug_ar'] ?? ($developer['name_ar'] ?? '');
    $slug_en = $developer['slug_en'] ?? ($developer['name_en'] ?? '');
    $slug_ar = jawda_developers_slugify( $slug_ar, 'ar' );
    $slug_en = jawda_developers_slugify( $slug_en, 'en' );
    if ( $lang === 'ar' && $slug_ar ) {
        return home_url( '/مشروعات-جديدة/' . rawurlencode( $slug_ar ) . '/' );
    }

    if ( $lang === 'en' && $slug_en ) {
        return home_url( '/en/new-projects/' . rawurlencode( $slug_en ) . '/' );
    }

    return $url;
}
add_filter( 'pll_translation_url', 'jawda_developers_pll_translation_url', 10, 2 );

if ( ! function_exists( 'jawda_developers_slugify' ) ) {
    function jawda_developers_slugify( $value, $lang = 'en' ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }

        if ( 'ar' === $lang ) {
            $slug = str_replace( ' ', '-', $value );
            $slug = preg_replace( '/[^\x{0600}-\x{06FF}a-zA-Z0-9\-]/u', '', $slug );
            $slug = preg_replace( '/-+/', '-', $slug );
            return mb_strtolower( $slug, 'UTF-8' );
        }

        return sanitize_title( $value );
    }
}

add_action('after_switch_theme', function() {
    if (get_option('jawda_dev_routes_flushed')) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('jawda_dev_routes_flushed', 1);
});
