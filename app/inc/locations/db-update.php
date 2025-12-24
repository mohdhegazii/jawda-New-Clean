<?php
/**
 * Database schema updates for the Location system.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Update the location tables with new columns and indexes.
 */
function jawda_locations_update_db_schema() {
    global $wpdb;

    $tables = [
        'jawda_governorates',
        'jawda_cities',
        'jawda_districts'
    ];

    $columns = [
        'name_ar'      => "VARCHAR(255) NOT NULL",
        'name_en'      => "VARCHAR(255) NOT NULL",
        'slug'         => "VARCHAR(255) NULL",
        'slug_ar'      => "VARCHAR(255) NULL",
        'latitude'     => "DECIMAL(10, 8) NULL",
        'longitude'    => "DECIMAL(11, 8) NULL",
        'country_code' => "VARCHAR(5) DEFAULT 'EG'",
        'is_active'    => "TINYINT(1) DEFAULT 1",
        'is_deleted'   => "TINYINT(1) DEFAULT 0",
        'deleted_at'   => "DATETIME NULL",
        'created_at'   => "DATETIME NULL",
        'updated_at'   => "DATETIME NULL"
    ];

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    foreach ( $tables as $table ) {
        $table_name = $wpdb->prefix . $table;

        // Basic check if table exists (it should)
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            continue;
        }

        // Add columns if they don't exist
        foreach ( $columns as $col_name => $col_def ) {
            $existing = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE '{$col_name}'" );
            if ( empty( $existing ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$col_name} {$col_def}" );
            }
        }

        // Add Indexes
        $indexes = [
            'slug'    => 'slug',
            'slug_ar' => 'slug_ar'
        ];

        // Add parent_id index based on table type
        if ( $table === 'jawda_cities' ) {
            $indexes['governorate_id'] = 'governorate_id';
        } elseif ( $table === 'jawda_districts' ) {
            $indexes['city_id'] = 'city_id';
        }

        foreach ( $indexes as $index_name => $col_name ) {
            // Check if index exists
            $index_exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW INDEX FROM {$table_name} WHERE Key_name = %s",
                $index_name
            ) );

            if ( ! $index_exists ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD INDEX {$index_name} ({$col_name})" );
            }
        }
    }

    // Populate slugs for existing entries if they are empty
    jawda_populate_location_slugs();
}

/**
 * Populate empty slugs with a sanitized version of the English name.
 */
function jawda_populate_location_slugs() {
    global $wpdb;
    $tables = [
        'jawda_governorates',
        'jawda_cities',
        'jawda_districts'
    ];

    foreach ( $tables as $table ) {
        $table_name = $wpdb->prefix . $table;
        // Select rows where slug is NULL or empty
        $rows = $wpdb->get_results( "SELECT id, name_en FROM {$table_name} WHERE slug IS NULL OR slug = ''" );

        foreach ( $rows as $row ) {
            if ( ! empty( $row->name_en ) ) {
                $slug = sanitize_title( $row->name_en );
                // Fallback if name_en is weird, use ID
                if ( empty( $slug ) ) {
                    $slug = 'loc-' . $row->id;
                }
                $wpdb->update( $table_name, [ 'slug' => $slug ], [ 'id' => $row->id ] );
            }
        }

        // Populate slug_ar if empty
        $rows_ar = $wpdb->get_results( "SELECT id, name_ar FROM {$table_name} WHERE slug_ar IS NULL OR slug_ar = ''" );
        foreach ( $rows_ar as $row ) {
            if ( ! empty( $row->name_ar ) ) {
                // Simple initialization for Arabic slugs.
                $slug_ar = sanitize_title( $row->name_ar );
                if ( empty( $slug_ar ) ) {
                    // If sanitize_title strips everything (common for Arabic without plugins), fallback to ID.
                    $slug_ar = 'ar-' . $row->id;
                }
                $wpdb->update( $table_name, [ 'slug_ar' => $slug_ar ], [ 'id' => $row->id ] );
            }
        }
    }
}

// Run once via admin_init
add_action( 'admin_init', function() {
    // Version incremented to 1.2 to trigger new columns and indexes
    if ( get_option( 'jawda_locations_db_version' ) !== '1.2' ) {
        jawda_locations_update_db_schema();
        update_option( 'jawda_locations_db_version', '1.2' );
    }
} );
