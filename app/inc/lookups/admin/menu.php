<?php
/**
 * Admin Menu setup for Jawda Lookups Types & Categories.
 */

if (!defined('ABSPATH')) {
    exit;
}

function jawda_lookups_admin_menu() {
    add_menu_page(
        __('Jawda Lookups', 'jawda'),
        __('Jawda Lookups', 'jawda'),
        'manage_options',
        'jawda-lookups',
        'jawda_lookups_dashboard_page',
        'dashicons-category',
        25
    );

    add_submenu_page(
        'jawda-lookups',
        __('Types & Categories', 'jawda'),
        __('Types & Categories', 'jawda'),
        'manage_options',
        'jawda-lookups-types',
        'jawda_lookups_types_categories_page'
    );
}
add_action('admin_menu', 'jawda_lookups_admin_menu');

function jawda_lookups_dashboard_page() {
    echo '<div class="wrap"><h1>' . __('Jawda Lookups Dashboard', 'jawda') . '</h1><p>' . __('Select a subsection from the menu.', 'jawda') . '</p></div>';
}

require_once __DIR__ . '/types-categories-page.php';
