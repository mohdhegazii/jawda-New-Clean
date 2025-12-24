<?php
/**
 * Helper script to delete legacy catalog posts and their meta.
 *
 * USAGE:
 * This script is intended to be run manually or via WP-CLI when needed.
 * DO NOT hook this into automatic actions.
 *
 * To run via WP-CLI:
 * wp eval-file tools/delete_catalog_posts.php
 *
 * To run via Browser (if accessible/secured):
 * Visit /tools/delete_catalog_posts.php?confirm=delete_catalogs_now
 */

if ( ! defined( 'ABSPATH' ) ) {
    require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );
}

if ( PHP_SAPI !== 'cli' ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        die( 'Access denied.' );
    }

    if ( ! isset( $_GET['confirm'] ) || $_GET['confirm'] !== 'delete_catalogs_now' ) {
        die( 'To confirm deletion, add ?confirm=delete_catalogs_now to the URL.' );
    }
}

global $wpdb;

echo "Starting cleanup of 'catalogs' post type...\n";

// 1. Get all catalog posts
$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'catalogs'" );

if ( empty( $ids ) ) {
    echo "No catalog posts found.\n";
    exit;
}

echo "Found " . count( $ids ) . " catalog posts. Deleting...\n";

// 2. Delete posts and associated meta
$count = 0;
foreach ( $ids as $id ) {
    // Force delete bypasses trash
    $result = wp_delete_post( $id, true );
    if ( $result ) {
        $count++;
        if ( $count % 50 == 0 ) {
            echo "Deleted $count posts...\n";
        }
    }
}

echo "Done! Deleted $count catalog posts and their meta.\n";
