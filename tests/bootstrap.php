<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

if (!defined('WP_TESTS_DOMAIN')) {
    define('WP_TESTS_DOMAIN', 'localhost');
}

if (!class_exists('WP_List_Table')) {
    class WP_List_Table {
        function __construct() {}
        public function current_action() {
            if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete') {
                return 'delete';
            }
            return false;
        }
    }
}

if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public function prepare($query, ...$args) { return vsprintf(str_replace('%s', "'%s'", $query), $args); }
        public function get_row($query, $output = ARRAY_A) { return []; }
        public function query($query) {}
    }
    $wpdb = new wpdb();
}

if (!class_exists('WP_UnitTestCase')) {
    class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {}
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('get_site_option')) {
    function get_site_option($option, $default = false) { return $default; }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {}
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {}
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {}
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '') {}
}

if (!function_exists('get_admin_url')) {
    function get_admin_url($blog_id = null, $path = '', $scheme = 'admin') { return ''; }
}

if (!function_exists('get_current_blog_id')) {
    function get_current_blog_id() { return 1; }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) { return true; }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) { return 'nonce'; }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null) {}
}

if (!function_exists('do_meta_boxes')) {
    function do_meta_boxes($screen, $context, $object) {}
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8'); }
}

if (!function_exists('dbDelta')) {
    function dbDelta($sql) {}
}
