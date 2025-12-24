<?php

// Mock WordPress environment
function is_singular() { return false; }
function get_query_var($var) { return 5; }
function absint($val) { return intval($val); }
function get_previous_posts_link() { return '<a href="#">Previous</a>'; }
function get_next_posts_link() { return '<a href="#">Next</a>'; }
function get_pagenum_link($num) { return "/page/$num"; }
function esc_url($url) { return $url; }

// Mock global $wp_query
global $wp_query;
$wp_query = new class {
    public $max_num_pages = 10;
};

// Include the function to be tested
require_once dirname(__DIR__) . '/app/functions/pagination.php';

// --- Test Runner ---
function run_pagination_test($name, $test_function) {
    echo "Running test: $name... ";
    $output = $test_function();

    // Test for the incorrect ellipsis bug
    $expected_string = '<li><a href="/page/1">1</a></li><li>…</li><li><a href="/page/4">4</a></li>';
    if (strpos($output, $expected_string) !== false) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        echo "--- OUTPUT ---\n";
        echo $output;
        echo "\n--------------\n";
    }
    echo "\n";
}

// --- Test Case ---
run_pagination_test("Ellipsis Display", function() {
    ob_start();
    theme_pagination();
    return ob_get_clean();
});
