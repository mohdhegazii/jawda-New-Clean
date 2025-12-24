<?php
/**
 * API functions for the refactored 4-level Jawda Lookups system.
 */

if (!defined('ABSPATH')) exit;

function jawda_get_lookups_by_entity($entity, $args = []) {
    $cache_key = 'jawda_' . $entity . '_' . md5(serialize($args));
    $cached = wp_cache_get($cache_key, 'jawda_lookups');
    if (false !== $cached) return $cached;

    $results = call_user_func(['Jawda_Lookups_Service', 'get_all_' . $entity], $args);
    wp_cache_set($cache_key, $results, 'jawda_lookups', HOUR_IN_SECONDS);
    return $results;
}

function jawda_get_categories($args = []) { return jawda_get_lookups_by_entity('categories', $args); }
function jawda_get_usages($args = []) { return jawda_get_lookups_by_entity('usages', $args); }
function jawda_get_property_types($args = []) { return jawda_get_lookups_by_entity('property_types', $args); }
function jawda_get_sub_properties($args = []) { return jawda_get_lookups_by_entity('sub_properties', $args); }

/**
 * Cached map of property type → category relations.
 *
 * @param array $args Reserved for future filters.
 *
 * @return array [property_type_id => [category_ids...]]
 */
function jawda_get_property_type_category_relations($args = []) {
    if (!class_exists('Jawda_Lookups_Service')) {
        return [];
    }

    global $wpdb;

    $cache_key = 'jawda_property_type_categories_' . md5(serialize($args));
    $cached    = wp_cache_get($cache_key, 'jawda_lookups');

    if (false !== $cached) {
        return $cached;
    }

    $table   = $wpdb->prefix . 'jawda_property_type_categories';
    $results = $wpdb->get_results("SELECT property_type_id, category_id FROM {$table}");

    $map = [];

    if (!empty($results)) {
        foreach ($results as $row) {
            $type_id = isset($row->property_type_id) ? (string) $row->property_type_id : '';
            $cat_id  = isset($row->category_id) ? (string) $row->category_id : '';

            if ($type_id === '' || $cat_id === '') {
                continue;
            }

            if (!isset($map[$type_id])) {
                $map[$type_id] = [];
            }

            $map[$type_id][] = $cat_id;
        }
    }

    wp_cache_set($cache_key, $map, 'jawda_lookups', HOUR_IN_SECONDS);

    return $map;
}

function jawda_get_aliases($args = []) {
    // This remains unchanged as per the requirement.
    return class_exists('Jawda_Lookups_Service') ? Jawda_Lookups_Service::get_all_aliases($args) : [];
}

function jawda_lookups_flush_cache() {
    wp_cache_flush();
}

/* === jawda PROPERTY MODELS LOOKUP (AUTO) === */
add_action('wp_ajax_jawda_pm_types_for_categories', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    global $wpdb;
    $cat_ids = isset($_POST['category_ids']) ? (array) $_POST['category_ids'] : [];
    $cat_ids = array_values(array_unique(array_filter(array_map('intval', $cat_ids), fn($v)=>$v>0)));

    if (empty($cat_ids)) {
        wp_send_json_success(['items' => []]);
    }

    $cache_group = 'jawda_lookups';
    $cache_key = 'pm_types_for_cats_' . md5(implode(',', $cat_ids));
    $cached = wp_cache_get($cache_key, $cache_group);
    if ($cached !== false) {
        wp_send_json_success(['items' => $cached]);
    }

    $t_types = $wpdb->prefix . 'jawda_property_types';
    $t_ptc = $wpdb->prefix . 'jawda_property_type_categories';

    $in = implode(',', array_fill(0, count($cat_ids), '%d'));
    $sql = "
        SELECT DISTINCT t.id, t.name_ar, t.name_en, t.slug
        FROM {$t_types} t
        INNER JOIN {$t_ptc} ptc ON ptc.property_type_id = t.id
        WHERE ptc.category_id IN ({$in})
        ORDER BY t.id DESC
    ";
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$cat_ids), ARRAY_A);
    $rows = $rows ?: [];

    wp_cache_set($cache_key, $rows, $cache_group, 3600);
    wp_send_json_success(['items' => $rows]);
});
/* === END jawda PROPERTY MODELS LOOKUP (AUTO) === */
