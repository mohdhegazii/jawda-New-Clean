<?php
// Test for the bug fix in show_cats() function.
// This file can be run from the command line: php tests/test_category_link_fix.php

// --- Test Setup & Mocking ---

// Define ABSPATH to allow direct execution.
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Mock necessary WordPress functions
if (!function_exists('get_the_ID')) {
    function get_the_ID() { return 1; }
}
if (!function_exists('wp_get_post_categories')) {
    function wp_get_post_categories($post_id) { return [10]; }
}
if (!function_exists('get_category')) {
    function get_category($category_id) {
        $category = new stdClass();
        $category->term_id = 10;
        $category->name = 'Test Category';
        return $category;
    }
}
if (!function_exists('get_category_link')) {
    function get_category_link($term_id) {
        return 'https://example.com/category/test-category';
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return $text; }
}
// Mock dependencies from helper.php that are not directly related to the function under test.
if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) { return true; }
}
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) { return true; }
}
if (!function_exists('get_query_var')) {
    function get_query_var($var, $default = '') { return 1; }
}
if (!function_exists('is_rtl')) {
    function is_rtl() { return false; }
}
if (!function_exists('do_shortcode')) {
    function do_shortcode($content) { return $content; }
}


// Include the file with the function to be tested
require_once 'app/functions/helper.php';

// --- Test Runner ---
function assert_true($condition, $message) {
    if (!$condition) {
        throw new Exception($message);
    }
}

function test_case($name, $callback) {
    echo "Running test: '$name'\n";
    try {
        ob_start();
        $callback();
        ob_end_clean();
        echo "Result: PASS\n";
        return true;
    } catch (Exception $e) {
        ob_end_clean();
        echo "Result: FAIL\n";
        echo "  - Message: " . $e->getMessage() . "\n";
        return false;
    }
}

$total_tests = 1;
$failures = 0;

// Test 1: Verify the fix. This should now pass with the actual file.
$test_passed = test_case('show_cats generates correct category link after fix', function() {
    ob_start();
    show_cats();
    $output = ob_get_clean();
    $expected = '<a href="https://example.com/category/test-category">Test Category</a>';
    assert_true($output === $expected, "Expected output to be '$expected', but got '$output'");
});

if (!$test_passed) {
    $failures++;
}

// --- Final Report ---
echo "\n--- Test Report ---\n";
if ($failures > 0) {
    echo "FAILURE: $failures out of $total_tests test(s) failed.\n";
    exit(1);
} else {
    echo "SUCCESS: All $total_tests test(s) passed.\n";
    exit(0);
}
?>
