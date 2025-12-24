<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_ajax_jawda_pm_get_sub_properties', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    check_ajax_referer('jawda_pm_ajax', 'nonce');

    $property_type_id = isset($_POST['property_type_id']) ? (int) $_POST['property_type_id'] : 0;


    
    if ($property_type_id <= 0) {
        wp_send_json_success(['items' => []]);
    }

    global $wpdb;
    $t_sub  = $wpdb->prefix . 'jawda_sub_properties';

    $sql = "
      SELECT sp.id, sp.name_en, sp.name_ar, sp.property_type_id
      FROM {$t_sub} sp
      WHERE sp.property_type_id = %d
      ORDER BY sp.name_en ASC
    ";

    $items = $wpdb->get_results($wpdb->prepare($sql, $property_type_id), ARRAY_A);
    wp_send_json_success(['items' => $items ?: []]);
});
