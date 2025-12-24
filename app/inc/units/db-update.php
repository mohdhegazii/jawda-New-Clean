<?php
/**
 * Database schema for the Unit Lookups system.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Creates or updates the unit lookups table.
 * This function is designed to be safe to run multiple times (idempotent).
 */
function jawda_unit_lookups_create_db_schema() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'jawda_unit_lookups';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_key VARCHAR(50) NOT NULL,
        slug VARCHAR(150) NOT NULL,
        label_en VARCHAR(255) NOT NULL DEFAULT '',
        label_ar VARCHAR(255) NOT NULL DEFAULT '',
        extra_data LONGTEXT NULL,
        sort_order INT(11) DEFAULT 0 NOT NULL,
        is_active TINYINT(1) DEFAULT 1 NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY group_slug (group_key, slug),
        KEY is_active (is_active)
    ) {$charset_collate};";

    // dbDelta requires this file.
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Hook the DB creation function to admin_init to ensure it runs.
 * We use a versioned option flag to prevent it from running on every page load.
 */
add_action( 'admin_init', function() {
    $current_version = '1.1';
    $installed_version = get_option( 'jawda_unit_lookups_db_version' );

    if ( $installed_version !== $current_version ) {
        jawda_unit_lookups_create_db_schema();
        update_option( 'jawda_unit_lookups_db_version', $current_version );
    }
} );
