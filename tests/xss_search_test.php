<?php

// Define ABSPATH to prevent the script from dying.
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Mock WordPress functions that are not available in the test environment.
if (!function_exists('is_rtl')) {
    function is_rtl() {
        return false;
    }
}
if (!function_exists('get_term')) {
    function get_term($term_id) {
        return null;
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}
if (!function_exists('get_search_box')) {
    function get_search_box() {
        // Mock implementation
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {
        // Mock implementation
    }
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        // Mock implementation
    }
}
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        // Mock implementation
    }
}


// Mock the WP_Query class
if (!class_exists('WP_Query')) {
    class WP_Query {
        public function __construct($args) {}
        public function have_posts() { return false; }
    }
}

// Include helper functions
require_once 'app/functions/helper.php';

// Include the file to be tested
require_once 'app/templates/pages/search.php';

// Simulate a search request with an XSS payload.
$malicious_search_term = '<script>alert("XSS");</script>';
$_GET['s'] = $malicious_search_term;
$_GET['st'] = 1;

// Capture the output of the function.
ob_start();
page_advanced_search_body($_GET);
$output = ob_get_clean();

// Check if the output contains the unescaped script tag.
if (strpos($output, $malicious_search_term) !== false) {
    echo "Test failed: XSS payload found in the output.\n";
    exit(1);
}

echo "Test passed!\n";
exit(0);
