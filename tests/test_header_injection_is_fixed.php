<?php

// Define ABSPATH to bypass the security check
define('ABSPATH', dirname(__DIR__) . '/');

// --- Global variables for testing ---
global $captured_mail_headers;
$captured_mail_headers = null;

// --- Mock WordPress and theme functions ---

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) { return true; }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '') {
        global $captured_mail_headers;
        $captured_mail_headers = $headers;
        return true; // Simulate successful email sending
    }
}

// Mock other dependencies as simple stubs
if (!function_exists('add_action')) { function add_action($tag, $function_to_add) {} }
if (!function_exists('add_filter')) { function add_filter($tag, $function_to_add) {} }
if (!function_exists('carbon_get_theme_option')) { function carbon_get_theme_option($option) { return 'test@example.com'; } }
if (!function_exists('get_page_link')) { function get_page_link($id) { return 'http://example.com/thank-you'; } }
if (!function_exists('home_url')) { function home_url($path = '') { return 'http://example.com'; } }
if (!function_exists('get_bloginfo')) { function get_bloginfo($show = '') { return 'test@example.com'; } }
if (!function_exists('sanitize_email')) { function sanitize_email($email) { return filter_var($email, FILTER_SANITIZE_EMAIL); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($str) { return $str; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($string, $remove_breaks = false) { return $string; } }
if (!function_exists('esc_html')) { function esc_html($text) { return htmlspecialchars($text); } }
if (!function_exists('wp_die')) { function wp_die($message, $title = '', $args = []) { throw new Exception("wp_die called: $message"); } }
if (!function_exists('wp_redirect')) { function wp_redirect($location, $status = 302) { throw new Exception("wp_redirect called: $location"); } }

global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public $insert_id = 1;
    public function insert($table, $data, $format) { return 1; }
};

// --- Include the actual function to be tested ---
require_once ABSPATH . 'app/functions/form_handler.php';


// --- Test Case ---

function test_email_header_injection_vulnerability() {
    echo "Running test: 'test_email_header_injection_vulnerability'\n";
    echo "---------------------------------------------------------\n";

    // 1. Arrange: Simulate form submission with a malicious name
    $_POST = [
        'name' => "John Doe\r\nBcc: victim@example.com",
        'phone' => '01234567890',
        'email' => 'attacker@example.com',
        'packageid' => 'Test Package',
        'my_contact_form_nonce' => 'a_nonce_value',
        'langu' => 'en'
    ];

    // 2. Act: Call the function
    try {
        prefix_send_email_to_admin();
    } catch (Exception $e) {
        // We expect a "wp_redirect called" exception, which is fine.
    }

    // 3. Assert: Check the captured headers
    global $captured_mail_headers;
    $reply_to_header = '';
    foreach ($captured_mail_headers as $header) {
        if (strpos($header, 'Reply-To:') === 0) {
            $reply_to_header = $header;
            break;
        }
    }

    echo "Generated Reply-To header: '$reply_to_header'\n";

    // Check if any newline characters are present in the final header.
    // The test FAILS if they ARE present, and PASSES if they are NOT.
    if (strpos($reply_to_header, "\r") !== false || strpos($reply_to_header, "\n") !== false) {
        echo "\nResult: TEST FAILED\n";
        echo "Newline characters were found in the Reply-To header, indicating an injection vulnerability.\n";
        exit(1); // Failure
    } else {
        echo "\nResult: TEST PASSED\n";
        echo "The newline characters were successfully stripped, preventing header injection.\n";
        exit(0); // Success
    }
}

// Run the test
test_email_header_injection_vulnerability();
