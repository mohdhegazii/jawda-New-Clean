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

    global $wpdb;
    $table_name = $wpdb->prefix . 'jawda_developers';
    $developer = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, name_ar, name_en, description_ar, description_en, logo FROM {$table_name} WHERE slug = %s OR slug_ar = %s LIMIT 1",
            $slug,
            $slug
        ),
        ARRAY_A
    );

    if (!$developer) {
        return $template;
    }

    $GLOBALS['jawda_current_developer'] = [
        'id' => $developer['id'],
        'name_ar' => $developer['name_ar'],
        'name_en' => $developer['name_en'],
        'description_ar' => $developer['description_ar'],
        'description_en' => $developer['description_en'],
        'logo_id' => $developer['logo'],
    ];
    $GLOBALS['jawda_is_rendering_developer_template'] = true;

    return get_theme_file_path('app/templates/developers/single-developer.php');
});

add_action('after_switch_theme', function() {
    if (get_option('jawda_dev_routes_flushed')) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('jawda_dev_routes_flushed', 1);
});
