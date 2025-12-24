<?php

// Mock WordPress environment
define('ABSPATH', dirname(__DIR__) . '/');

// Mock wpdb class
$wpdb = new class {
    public function query($query) {
        // In a real test, we might log the query
        // For this test, we'll just check if the function is called
        echo "wpdb->query called with: " . $query . "\n";
    }
    public $prefix = 'wp_';
};

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return;
    }
}
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        return;
    }
}
// Include the file containing the vulnerable function
require_once ABSPATH . 'app/functions/helper.php';

echo "Starting SQL injection test...\n";

// 1. Set up the test parameters
$test_parameters = [
    'st'   => 1,
    'city' => 1,
    'type' => 1,
    's'    => 'test',
    'postcount' => 'all'
];

// 2. Capture the output and call the function
ob_start();
search_helper($test_parameters);
$output = ob_get_clean();

// 3. Assert the results
if (strpos($output, "wpdb->query called with") === false) {
    echo "Test Passed: The vulnerable function was not called.\n";
} else {
    echo "Test Failed: The vulnerable function was called.\n";
    echo "Output was:\n" . $output;
}
