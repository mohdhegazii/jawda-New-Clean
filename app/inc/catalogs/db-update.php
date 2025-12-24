<?php
/**
 * Catalog SEO DB schema updates.
 */

if (!defined('ABSPATH')) {
    exit;
}

function jawda_catalogs_update_db_schema() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'jawda_catalog_seo_overrides';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        catalog_key VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        location_level VARCHAR(50) NULL,
        location_id BIGINT(20) NULL,
        is_custom_catalog TINYINT(1) DEFAULT 0,
        meta_title_ar VARCHAR(255) NULL,
        meta_title_en VARCHAR(255) NULL,
        meta_desc_ar TEXT NULL,
        meta_desc_en TEXT NULL,
        intro_html_ar LONGTEXT NULL,
        intro_html_en LONGTEXT NULL,
        content_ar LONGTEXT NULL,
        content_en LONGTEXT NULL,
        featured_image_id BIGINT(20) NULL,
        meta_robots VARCHAR(100) NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        UNIQUE KEY catalog_key (catalog_key),
        UNIQUE KEY catalog_context (type, location_level, location_id, is_custom_catalog),
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    dbDelta($sql);
}

add_action('admin_init', function() {
    $version = get_option('jawda_catalogs_db_version');
    if ($version !== '1.1') {
        jawda_catalogs_update_db_schema();
        update_option('jawda_catalogs_db_version', '1.1');
    }
});
