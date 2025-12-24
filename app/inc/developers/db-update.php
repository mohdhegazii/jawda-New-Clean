<?php
/**
 * Database installer for the Jawda Developers Engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create or update the developers table.
 */
function jawda_developers_install_v1() {
    $installer_version_flag = 'jawda_developers_installer_version';
    $current_version = (int) get_option($installer_version_flag, 0);

    if ($current_version >= 1) {
        return;
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'jawda_developers';

    $sql = "
    CREATE TABLE {$table_name} (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        name_en VARCHAR(255) NOT NULL,
        name_ar VARCHAR(255) NOT NULL,
        slug_en VARCHAR(255) NOT NULL,
        slug_ar VARCHAR(255) NOT NULL,
        developer_type_id BIGINT(20) NULL,
        logo_id BIGINT(20) NULL,
        description_en LONGTEXT NULL,
        description_ar LONGTEXT NULL,
        seo_title_en VARCHAR(255) NULL,
        seo_title_ar VARCHAR(255) NULL,
        seo_desc_en TEXT NULL,
        seo_desc_ar TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug_en (slug_en),
        UNIQUE KEY slug_ar (slug_ar),
        KEY developer_type_id (developer_type_id),
        KEY is_active (is_active)
    ) {$charset_collate};
    ";

    dbDelta($sql);

    update_option($installer_version_flag, 1);
}
add_action('admin_init', 'jawda_developers_install_v1');
