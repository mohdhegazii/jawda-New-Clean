<?php
/**
 * Public helpers for the Developers Engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

function jawda_developers_service() {
    static $service = null;
    if (null === $service) {
        $service = new Jawda_Developers_Service();
    }

    return $service;
}

function jawda_get_developer_by_id($id) {
    return jawda_developers_service()->get_developer_by_id($id);
}

function jawda_get_developer_by_slug_en($slug) {
    return jawda_developers_service()->get_developer_by_slug_en($slug);
}

function jawda_get_developer_by_slug_ar($slug) {
    return jawda_developers_service()->get_developer_by_slug_ar($slug);
}

function jawda_get_developers($args = []) {
    return jawda_developers_service()->get_developers($args);
}

function jawda_get_developer_types() {
    $types = apply_filters('jawda_developer_types', []);
    return is_array($types) ? $types : [];
}
