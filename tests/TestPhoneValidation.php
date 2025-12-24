<?php

// This test verifies the phone number validation logic in form_handler.php

define('ABSPATH', dirname(__DIR__) . '/');

// --- Global state for capturing test output ---
global $last_wp_die_message;
$last_wp_die_message = '';

// --- Mock WordPress environment ---
if (!function_exists('wp_verify_nonce')) { function wp_verify_nonce($nonce, $action) { return true; } }
if (!function_exists('wp_mail')) { function wp_mail($to, $subject, $message, $headers = '') { return true; } }
if (!function_exists('carbon_get_theme_option')) { function carbon_get_theme_option($option) { return 'test@example.com'; } }
if (!function_exists('get_page_link')) { function get_page_link($id) { return 'http://example.com/thank-you'; } }
if (!function_exists('home_url')) { function home_url($path = '') { return 'http://example.com'; } }
if (!function_exists('get_bloginfo')) { function get_bloginfo($show = '') { return 'test@example.com'; } }
if (!function_exists('sanitize_email')) { function sanitize_email($email) { return filter_var($email, FILTER_SANITIZE_EMAIL); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($str) { return $str; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($string, $remove_breaks = false) { return $string; } }
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        global $last_wp_die_message;
        $last_wp_die_message = $message;
    }
}
if (!function_exists('wp_redirect')) { function wp_redirect($location, $status = 302) { throw new Exception("wp_redirect called"); } }
if (!function_exists('esc_html')) { function esc_html($text) { return htmlspecialchars($text); } }
if (!function_exists('wp_send_json_error')) { function wp_send_json_error($error) { /* Do nothing */ } }
if (!function_exists('wp_send_json_success')) { function wp_send_json_success($data) { /* Do nothing */ } }
if (!function_exists('add_filter')) { function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) { /* Do nothing */ } }
if (!function_exists('add_action')) { function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) { /* Do nothing */ } }



global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public $insert_id = 1;
    public function insert($table, $data, $format) { return 1; }
};

// Include the actual function to be tested.
// This is better than duplicating the function's code.
require_once dirname(__DIR__) . '/app/functions/form_handler.php';


// --- Test Runner ---
function run_test($description, $phone_number, $should_be_valid) {
    echo "Running Test: '$description'\n";

    $_POST = [
        'my_contact_form_nonce' => 'test_nonce',
        'name'      => 'Test User',
        'phone'     => $phone_number,
        'packageid' => 'Test Package'
    ];

    global $last_wp_die_message;
    $last_wp_die_message = ''; // Reset

    try {
        prefix_send_email_to_admin();
    } catch (Exception $e) {
        // Ignore the "wp_redirect called" exception
    }

    $is_valid = ($last_wp_die_message === '');

    if ($is_valid === $should_be_valid) {
        echo "  [SUCCESS] Test Passed.\n";
        return true;
    } else {
        echo "  [FAILURE] Test Failed.\n";
        if ($should_be_valid) {
            echo "    - Expected phone number to be VALID, but it was REJECTED.\n";
            echo "    - Error message: '$last_wp_die_message'\n";
        } else {
            echo "    - Expected phone number to be REJECTED, but it was ACCEPTED.\n";
        }
        return false;
    }
}

// --- Test Cases ---
$all_tests_passed = true;

// Invalid cases
if (!run_test("Rejects phone with letters", "12345abcde1", false)) $all_tests_passed = false;
if (!run_test("Rejects short number", "12345", false)) $all_tests_passed = false;
if (!run_test("Rejects overly long number", "12345678901234567890123", false)) $all_tests_passed = false;

// Valid cases
if (!run_test("Accepts valid Egyptian number", "01012345678", true)) $all_tests_passed = false;
if (!run_test("Accepts valid international number with +", "+201012345678", true)) $all_tests_passed = false;
if (!run_test("Accepts valid Egyptian landline", "02123456789", true)) $all_tests_passed = false;
if (!run_test("Rejects invalid Egyptian-style number", "01312345678", false)) $all_tests_passed = false;


// --- Final Report ---
echo "\n--------------------------------------------\n";
if ($all_tests_passed) {
    echo "SUCCESS: All phone validation tests passed.\n";
    exit(0);
} else {
    echo "FAILURE: Some phone validation tests failed.\n";
    exit(1);
}
