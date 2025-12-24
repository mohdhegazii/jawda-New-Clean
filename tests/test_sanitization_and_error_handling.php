<?php

// Mock WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!defined('WP_TESTS_DOMAIN')) {
    define('WP_TESTS_DOMAIN', 'example.org');
}

// --- Test-specific mocks for AJAX ---
$last_json_response = null;
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data) {
        global $last_json_response;
        $last_json_response = ['success' => false, 'data' => $data];
    }
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add) {}
}
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $function_to_add) {}
}
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add) {}
}
if (!function_exists('carbon_get_theme_option')) {
    function carbon_get_theme_option($option_name) { return 'admin@example.com'; }
}
if (!function_exists('sanitize_email')) {
    function sanitize_email($email) { return $email; }
}
if (!function_exists('get_page_link')) {
    function get_page_link($page_id) { return 'http://example.com/thank-you'; }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = 'name', $filter = 'raw') {
        return 'Test Blog';
    }
}

// Include the function files to be tested
require_once ABSPATH . 'app/functions/helper.php';
require_once ABSPATH . 'app/functions/form_handler.php';

// --- Test Runner ---
function run_test($name, $test_function) {
    global $last_json_response;
    // Reset state before each test
    $last_json_response = null;

    echo "Running test: $name... ";
    $test_function();
    echo "\n";
}

// --- Test Cases ---

// Test 1: Verify test_input doesn't corrupt data
run_test("test_input data integrity", function() {
    $input = "Test with 'single quotes' and \"double quotes\"";
    $expected = "Test with 'single quotes' and \"double quotes\"";
    $actual = test_input($input);

    if ($actual === $expected) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        echo "Expected: $expected\n";
        echo "Actual:   $actual\n";
    }
});

// Test 2: Verify wp_die is replaced with wp_send_json_error for nonce failure
run_test("Nonce Failure Returns JSON Error", function() {
    global $last_json_response;

    // Mock nonce failure
    if (!function_exists('wp_verify_nonce')) {
        function wp_verify_nonce($nonce, $action) {
            return false;
        }
    }

    $_POST = [
        'my_contact_form_nonce' => 'invalid-nonce'
    ];

    prefix_send_email_to_admin();

    if ($last_json_response && $last_json_response['success'] === false && isset($last_json_response['data']['message'])) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        print_r($last_json_response);
    }
});
