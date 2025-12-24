<?php
/**
 * Database installer for the Jawda Lookups module.
 * Version 5: Reintroduces slugs/descriptions/icon_class/sort_order/is_active columns
 * and aligns tables with the 4-level hierarchy + relations.
 * This script is idempotent and safe to run on new and existing installations.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Runs the installer on admin_init.
 */
function jawda_lookups_install_v5() {
    $installer_version_flag = 'jawda_lookups_installer_version';
    $current_version = get_option($installer_version_flag, 0);

    if ($current_version >= 5) {
        return; // Already up to date.
    }

    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // 1. Define the schema for all tables
    $sql = "
    CREATE TABLE {$wpdb->prefix}jawda_categories (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        slug VARCHAR(191) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        name_ar VARCHAR(255) NOT NULL,
        description_en TEXT NULL,
        description_ar TEXT NULL,
        icon_class VARCHAR(191) DEFAULT '' NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}jawda_property_types (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        slug VARCHAR(191) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        name_ar VARCHAR(255) NOT NULL,
        description_en TEXT NULL,
        description_ar TEXT NULL,
        icon_class VARCHAR(191) DEFAULT '' NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}jawda_sub_properties (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        property_type_id BIGINT(20) NOT NULL,
        slug VARCHAR(191) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        name_ar VARCHAR(255) NOT NULL,
        description_en TEXT NULL,
        description_ar TEXT NULL,
        icon_class VARCHAR(191) DEFAULT '' NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug),
        KEY property_type_id (property_type_id),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}jawda_usages (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        slug VARCHAR(191) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        name_ar VARCHAR(255) NOT NULL,
        description_en TEXT NULL,
        description_ar TEXT NULL,
        icon_class VARCHAR(191) DEFAULT '' NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}jawda_aliases (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        sub_property_id BIGINT(20) NOT NULL,name_en VARCHAR(255) DEFAULT '' NOT NULL,
        name_ar VARCHAR(255) DEFAULT '' NOT NULL,
        is_primary TINYINT(1) DEFAULT 0 NOT NULL,
        is_deleted TINYINT(1) DEFAULT 0 NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY sub_property_id (sub_property_id),KEY is_deleted (is_deleted)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}jawda_property_type_categories (
        property_type_id BIGINT(20) NOT NULL,
        category_id BIGINT(20) NOT NULL,
        PRIMARY KEY  (property_type_id, category_id),
        KEY category_id (category_id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}jawda_property_type_usages (
        property_type_id BIGINT(20) NOT NULL,
        usage_id BIGINT(20) NOT NULL,
        PRIMARY KEY  (property_type_id, usage_id),
        KEY usage_id (usage_id)
    ) $charset_collate;
    ";

    // 2. Create/update tables using dbDelta()
    dbDelta($sql);

    // 3. Ensure legacy tables have the new columns and constraints
    $tables_to_update = ['categories', 'property_types', 'sub_properties', 'usages'];
    foreach ($tables_to_update as $table_name_suffix) {
        $table_name = $wpdb->prefix . 'jawda_' . $table_name_suffix;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
            $columns = $wpdb->get_col("DESC {$table_name}", 0);
            $required_columns = [
                'slug' => "ALTER TABLE {$table_name} ADD COLUMN slug VARCHAR(191) NOT NULL AFTER id",
                'description_en' => "ALTER TABLE {$table_name} ADD COLUMN description_en TEXT NULL AFTER name_ar",
                'description_ar' => "ALTER TABLE {$table_name} ADD COLUMN description_ar TEXT NULL AFTER description_en",
                'icon_class' => "ALTER TABLE {$table_name} ADD COLUMN icon_class VARCHAR(191) DEFAULT '' NOT NULL AFTER description_ar",
                'sort_order' => "ALTER TABLE {$table_name} ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER icon_class",
                'is_active' => "ALTER TABLE {$table_name} ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order",
                'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME NOT NULL AFTER is_active",
                'updated_at' => "ALTER TABLE {$table_name} ADD COLUMN updated_at DATETIME NOT NULL AFTER created_at",
            ];
            foreach ($required_columns as $column => $query) {
                if (!in_array($column, $columns, true)) {
                    $wpdb->query($query);
                }
            }
            // Add unique slug index if missing
            $has_slug_index = $wpdb->get_row("SHOW INDEX FROM {$table_name} WHERE Column_name = 'slug' AND Non_unique = 0");
            if (!$has_slug_index) {
                $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE KEY slug (slug)");
            }
        }
    }

    // 4. Update the installer version.
    update_option($installer_version_flag, 5);
}
add_action('admin_init', 'jawda_lookups_install_v5');

/* === jawda PROPERTY MODELS LOOKUP (AUTO) === */
// jawda_PROPERTY_MODELS_LOOKUP__INSTALLED
function jawda_lookups_install_property_models_tables($wpdb) {
    $charset_collate = $wpdb->get_charset_collate();
    $t_models = $wpdb->prefix . 'jawda_property_models';
    $t_pm_cats = $wpdb->prefix . 'jawda_property_model_categories';

    $sql1 = "CREATE TABLE IF NOT EXISTS {$t_models} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name_ar VARCHAR(190) NOT NULL DEFAULT '',
        name_en VARCHAR(190) NOT NULL DEFAULT '',
        slug VARCHAR(190) NOT NULL,
        property_type_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        bedrooms TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
        icon VARCHAR(190) NULL DEFAULT NULL,
        is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NULL DEFAULT NULL,
        updated_at DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY property_type_id (property_type_id),
        KEY bedrooms (bedrooms),
        KEY is_active (is_active)
    ) {$charset_collate};";

    $sql2 = "CREATE TABLE IF NOT EXISTS {$t_pm_cats} (
        property_model_id BIGINT(20) UNSIGNED NOT NULL,
        category_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY (property_model_id, category_id),
        KEY category_id (category_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql1);
    dbDelta($sql2);
    // Property Models tables (AUTO)
    if (function_exists('jawda_lookups_install_property_models_tables')) { jawda_lookups_install_property_models_tables($wpdb); }
}
/* === END jawda PROPERTY MODELS LOOKUP (AUTO) === */
