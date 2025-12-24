<?php

// Define ABSPATH to bypass the security check in form_handler.php
define('ABSPATH', true);

// Global variable to capture the search term passed to WP_Query
$captured_search_term = '';

// Mock WordPress environment
function add_action($tag, $function_to_add) {}
function add_filter($tag, $function_to_add) {}
function wp_send_json_success($data) {
    echo json_encode(['success' => true, 'data' => $data]);
}
function sanitize_text_field($str) {
    return $str; // Pass through for this test
}

function check_ajax_referer($action, $query_arg) {
    // Mocked to always pass for this test
}

class WP_Query {
    public $posts = [];
    public function __construct($args) {
        global $captured_search_term;
        if (isset($args['s'])) {
            $captured_search_term = $args['s'];
        }
    }
}

// Include the function to test from the actual file
include_once(__DIR__ . '/../app/functions/form_handler.php');


// Test Case
function test_stripslashes_is_removed() {
    global $captured_search_term;
    $_POST['search'] = 'A search with \\ slashes'; // A single backslash

    // Run the patched function
    ja_ajax_search_properties();

    $expected_term_after_fix = 'A search with \\ slashes';

    if ($captured_search_term !== $expected_term_after_fix) {
        echo "Test Failed: Input was incorrectly modified.\n";
        echo "Expected: '" . $expected_term_after_fix . "'\n";
        echo "Got: '" . $captured_search_term . "'\n";
        exit(1);
    }

    echo "Test Passed: Input is preserved correctly.\n";
    exit(0);
}

// Run the test
test_stripslashes_is_removed();

?>
