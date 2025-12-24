<?php
// Test runner for AJAX security fix.
// This file can be run from the command line: php tests/ajax-security-test.php

// --- Test Setup & Mocking ---

// Define ABSPATH to allow direct execution.
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Global variable to hold test output
$mock_output = '';
$mock_die_called = false;

// Mock necessary WordPress functions
function wp_send_json_success($data) {
    global $mock_output;
    $mock_output = json_encode(['success' => true, 'data' => $data]);
}

function wp_send_json_error($data) {
    global $mock_output;
    $mock_output = json_encode(['success' => false, 'data' => $data]);
}

function wp_create_nonce($action = -1) {
    return md5('nonce' . $action);
}

function wp_verify_nonce($nonce, $action = -1) {
    return md5('nonce' . $action) === $nonce;
}

// Mock check_ajax_referer for testing purposes
function mock_check_ajax_referer($action = -1, $query_arg = false, $die = true) {
    global $mock_die_called;
    if (!isset($_POST[$query_arg]) || !wp_verify_nonce($_POST[$query_arg], $action)) {
        if ($die) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
            $mock_die_called = true;
        }
        return false;
    }
    return true;
}


function sanitize_text_field($str) {
    return $str;
}

class WP_Query {
    public $posts = [];
    public function __construct($args = []) {
        if (isset($args['s']) && $args['s'] === 'test') {
            $post = new stdClass();
            $post->post_title = 'Test Property';
            $this->posts[] = $post;
        }
    }
}

// --- Function to be tested (copied from form_handler.php) ---
// We copy it here to ensure it calls our mocked functions.

function ja_ajax_search_properties_testable() {
    global $mock_die_called;
    mock_check_ajax_referer( 'search_nonce_action', 'security' );
    if ($mock_die_called) return; // Stop execution if nonce check failed

	$results = new WP_Query( array(
		'post_type'     => array( 'property' ),
		'post_status'   => 'publish',
        'posts_per_page' => 10,
		's'             => sanitize_text_field( $_POST['search'] ),
	) );

	$items = array();

	if ( !empty( $results->posts ) ) {
		foreach ( $results->posts as $result ) {
			$items[] = $result->post_title;
		}
	}

	wp_send_json_success( $items );
}


// --- Test Runner ---
function assert_true($condition, $message) {
    if (!$condition) {
        throw new Exception($message);
    }
}

function test_case($name, $callback) {
    global $mock_output, $mock_die_called;
    $mock_output = '';
    $mock_die_called = false;
    $_POST = [];
    $_GET = [];

    echo "Test: $name ... ";
    try {
        $callback();
        echo "PASS\n";
        return true;
    } catch (Exception $e) {
        echo "FAIL\n";
        echo "  Message: " . $e->getMessage() . "\n";
        return false;
    }
}

$failures = 0;

// Test 1
$failures += !test_case('AJAX search without nonce should fail', function() {
    global $mock_output;
    $_POST = [
        'action' => 'search_properties',
        'search' => 'test'
    ];

    ja_ajax_search_properties_testable();

    $response = json_decode($mock_output, true);
    assert_true($response !== null, 'Response was not valid JSON.');
    assert_true(isset($response['success']) && $response['success'] === false, 'Expected success=false.');
    assert_true(strpos($mock_output, 'Nonce verification failed') !== false, 'Expected nonce failure message.');
});

// Test 2
$failures += !test_case('AJAX search with correct nonce should succeed', function() {
    global $mock_output;
    $_POST = [
        'action' => 'search_properties',
        'search' => 'test',
        'security' => wp_create_nonce('search_nonce_action')
    ];

    ja_ajax_search_properties_testable();

    $response = json_decode($mock_output, true);
    assert_true($response !== null, 'Response was not valid JSON. Output: ' . $mock_output);
    assert_true(isset($response['success']) && $response['success'] === true, 'Expected success=true.');
    assert_true(!empty($response['data']) && $response['data'][0] === 'Test Property', 'Expected data not found in response.');
});

// --- Final Report ---
echo "\nTests complete.\n";
if ($failures > 0) {
    echo "Result: $failures test(s) failed.\n";
    exit(1);
} else {
    echo "Result: All tests passed.\n";
    exit(0);
}
?>
