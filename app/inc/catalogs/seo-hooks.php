<?php
/**
 * SEO plugin integration for catalog overrides.
 */

if (!defined('ABSPATH')) {
    exit;
}

function jawda_catalogs_current_context() {
    static $ctx = null;
    if ($ctx === false) {
        return null;
    }
    if ($ctx instanceof Jawda_Catalog_Context) {
        return $ctx;
    }
    $ctx = Jawda_Catalog_Context::from_request();
    if (!$ctx) {
        $ctx = false;
        return null;
    }
    return $ctx;
}

function jawda_catalogs_filter_title($title) {
    $ctx = jawda_catalogs_current_context();
    if (!$ctx) {
        return $title;
    }
    $service = jawda_catalog_seo_service();
    if (!$service->has_override($ctx)) {
        return $title;
    }
    $new_title = $service->get_meta_title($ctx);
    return $new_title ?: $title;
}
add_filter('wpseo_title', 'jawda_catalogs_filter_title', 50);
add_filter('rank_math/frontend/title', 'jawda_catalogs_filter_title', 50);
add_filter('pre_get_document_title', 'jawda_catalogs_filter_title', 50);

function jawda_catalogs_filter_description($desc) {
    $ctx = jawda_catalogs_current_context();
    if (!$ctx) {
        return $desc;
    }
    $service = jawda_catalog_seo_service();
    if (!$service->has_override($ctx)) {
        return $desc;
    }
    $new_desc = $service->get_meta_description($ctx);
    return $new_desc ?: $desc;
}
add_filter('wpseo_metadesc', 'jawda_catalogs_filter_description', 50);
add_filter('rank_math/frontend/description', 'jawda_catalogs_filter_description', 50);

function jawda_catalogs_output_meta_robots() {
    $ctx = jawda_catalogs_current_context();
    if (!$ctx) {
        return;
    }
    $service = jawda_catalog_seo_service();
    if (!$service->has_override($ctx)) {
        return;
    }
    $robots = $service->get_meta_robots($ctx);
    if ($robots) {
        echo '\n<meta name="robots" content="' . esc_attr($robots) . '" />\n';
    }
}
add_action('wp_head', 'jawda_catalogs_output_meta_robots', 2);
