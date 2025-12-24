<?php
/**
 * Term helpers (safe for cron)
 */
if (!function_exists('get_object_term_ids')) {
    function get_object_term_ids($object_id, $taxonomy) {
        $ids = wp_get_object_terms($object_id, $taxonomy, array('fields' => 'ids'));
        return is_wp_error($ids) ? array() : $ids;
    }
}
