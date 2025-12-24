<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_ajax_jawda_pm_get_property_types', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    check_ajax_referer('jawda_pm_ajax', 'nonce');

    $cat_ids = isset($_POST['category_ids']) ? (array) $_POST['category_ids'] : [];
    $cat_ids = array_values(array_unique(array_filter(array_map('intval', $cat_ids), fn($v)=>$v>0)));

    if (!$cat_ids) {
        wp_send_json_success(['items' => []]);
    }

    global $wpdb;
    $t_pt  = $wpdb->prefix . 'jawda_property_types';
    $t_ptc = $wpdb->prefix . 'jawda_property_type_categories';

    $in = implode(',', array_fill(0, count($cat_ids), '%d'));

    $sql = "
      SELECT pt.id, pt.name_en, pt.name_ar
      FROM {$t_pt} pt
      INNER JOIN {$t_ptc} ptc ON ptc.property_type_id = pt.id
      WHERE ptc.category_id IN ({$in})
      GROUP BY pt.id
      ORDER BY pt.name_en ASC
    ";

    $items = $wpdb->get_results($wpdb->prepare($sql, ...$cat_ids), ARRAY_A);
    wp_send_json_success(['items' => $items ?: []]);
});
