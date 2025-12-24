<?php

// Mock WordPress environment
define('ABSPATH', dirname(__DIR__) . '/');
define('WP_TESTS_DOMAIN', 'example.org');

// Mock global $wpdb object
global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public $insert_calls = [];
    public $insert_id = 0;

    public function insert($table, $data, $format) {
        $this->insert_calls[] = ['table' => $table, 'data' => $data, 'format' => $format];
        $this->insert_id = 1;
        return 1;
    }
};

// Mock WordPress functions
if (!function_exists('add_action')) { function add_action($tag, $function_to_add) {} }
if (!function_exists('add_filter')) { function add_filter($tag, $function_to_add) {} }
if (!function_exists('get_page_link')) { function get_page_link($page_id) { return 'http://example.com/thank-you'; } }
if (!function_exists('carbon_get_theme_option')) {
    function carbon_get_theme_option($option_name) {
        if (in_array($option_name, ['jawda_email', '_jawda_email'], true)) {
            return 'options@example.com';
        }

        if (strpos($option_name, 'jawda_page_thankyou_') === 0) {
            return 123;
        }

        $smtp_defaults = [
            'crb_smtp_host'     => 'smtp.example.com',
            'crb_smtp_port'     => '587',
            'crb_smtp_username' => 'smtp-user@example.com',
            'crb_smtp_password' => 'app-password',
        ];

        if (array_key_exists($option_name, $smtp_defaults)) {
            return $smtp_defaults[$option_name];
        }

        return 'admin@example.com';
    }
}
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($str) { return $str; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($str) { return $str; } }
if (!function_exists('sanitize_email')) { function sanitize_email($email) { return $email; } }
global $last_mail_args, $last_mail_args_history, $wp_mail_return_values;
$last_mail_args = null;
$last_mail_args_history = [];
$wp_mail_return_values = [];
if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers) {
        global $last_mail_args, $last_mail_args_history, $wp_mail_return_values;
        $args = compact('to', 'subject', 'message', 'headers');
        $last_mail_args = $args;
        $last_mail_args_history[] = $args;

        if (!empty($wp_mail_return_values)) {
            return array_shift($wp_mail_return_values);
        }

        return true;
    }
}
if (!function_exists('check_referrer')) { function check_referrer() {} }
if (!function_exists('test_input')) { function test_input($data) { return $data; } }
if (!function_exists('esc_html')) { function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); } }

// --- Test-specific mocks for AJAX ---
$last_json_response = null;
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data) {
        global $last_json_response;
        $last_json_response = ['success' => true, 'data' => $data];
        // In a real WP environment, this would die. We don't die here to allow the test to continue.
    }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data) {
        global $last_json_response;
        $last_json_response = ['success' => false, 'data' => $data];
    }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true;
    }
}
if (!function_exists('wp_die')) {
    function wp_die($message) {
        // Don't die, just echo the message for testing purposes
        global $last_json_response;
        if (!$last_json_response || !$last_json_response['success']) {
            echo "wp_die called with message: $message";
        }
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = 'name', $filter = 'raw') {
        return 'Test Blog';
    }
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($location, $status = 302) {
        // Don't redirect, just echo the location for testing purposes
        echo "wp_redirect called with location: $location";
    }
}


// Include the function file to be tested
require_once ABSPATH . 'app/functions/form_handler.php';

// --- Test Runner ---

function run_test($name, $test_function) {
    global $last_json_response, $wpdb, $last_mail_args, $last_mail_args_history, $wp_mail_return_values, $jawda_force_secondary_smtp;
    // Reset state before each test
    $last_json_response = null;
    $wpdb->insert_calls = [];
    $last_mail_args = null;
    $last_mail_args_history = [];
    $wp_mail_return_values = [];
    $jawda_force_secondary_smtp = false;

    echo "Running test: $name... ";
    $test_function();
    echo "\n";
}

// --- Test Cases ---

// Test 1: Successful form submission
run_test("Successful Submission", function() {
    global $last_json_response;
    $_POST = [
        'name' => 'Test User',
        'phone' => '12345678901',
        'packageid' => 'Test Package',
        'email' => 'test@example.com',
        'special_request' => 'This is a test message.',
        'langu' => 'en',
        'my_contact_form_nonce' => 'nonce'
    ];

    prefix_send_email_to_admin();

    if ($last_json_response && $last_json_response['success'] === true && !empty($last_json_response['data']['redirect'])) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        print_r($last_json_response);
    }
});

// Test 2: Missing required field (phone)
run_test("Missing Required Field", function() {
    global $last_json_response;
    $_POST = [
        'name' => 'Test User',
        'phone' => '', // Missing phone
        'packageid' => 'Test Package',
        'langu' => 'ar',
        'my_contact_form_nonce' => 'nonce'
    ];

    prefix_send_email_to_admin();

    if ($last_json_response && $last_json_response['success'] === false && strpos($last_json_response['data']['message'], 'الحقول المطلوبة') !== false) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        print_r($last_json_response);
    }
});

// Test 3: Invalid email format
run_test("Invalid Email Format", function() {
    global $last_json_response;
    $_POST = [
        'name' => 'Test User',
        'phone' => '12345678901',
        'packageid' => 'Test Package',
        'email' => 'invalid-email',
        'langu' => 'en',
        'my_contact_form_nonce' => 'nonce'
    ];

    prefix_send_email_to_admin();

    if ($last_json_response && $last_json_response['success'] === false && strpos($last_json_response['data']['message'], 'valid email') !== false) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        print_r($last_json_response);
    }
});

// Test 4: Email is sent to Carbon Fields option with correct headers
run_test("Uses Carbon email and headers", function() {
    global $last_json_response, $last_mail_args;

    $_POST = [
        'name' => 'Header Test',
        'phone' => '12345678901',
        'packageid' => 'Header Package',
        'email' => 'sender@example.com',
        'langu' => 'en',
        'my_contact_form_nonce' => 'nonce'
    ];

    $last_mail_args = null;
    prefix_send_email_to_admin();

    $has_reply_to = false;
    if (isset($last_mail_args['headers']) && is_array($last_mail_args['headers'])) {
        foreach ($last_mail_args['headers'] as $header) {
            if (stripos($header, 'Reply-To:') === 0 && strpos($header, 'sender@example.com') !== false) {
                $has_reply_to = true;
                break;
            }
        }
    }

    if (
        $last_mail_args &&
        $last_mail_args['to'] === 'options@example.com' &&
        in_array('From: AqarAnd <wordpress@aqarand.com>', $last_mail_args['headers'], true) &&
        $has_reply_to &&
        $last_json_response && $last_json_response['success'] === true
    ) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        var_dump($last_mail_args);
        print_r($last_json_response);
    }
});

// Test 5: Fallback to secondary SMTP when the first attempt fails
run_test("Fallback to secondary SMTP", function() {
    global $last_json_response, $last_mail_args_history, $wp_mail_return_values;

    $_POST = [
        'name' => 'Fallback Test',
        'phone' => '12345678901',
        'packageid' => 'Fallback Package',
        'email' => 'fallback@example.com',
        'langu' => 'en',
        'my_contact_form_nonce' => 'nonce'
    ];

    $wp_mail_return_values = [false, true];

    prefix_send_email_to_admin();

    $two_attempts = count($last_mail_args_history) === 2;
    $success_redirect = $last_json_response && $last_json_response['success'] === true;

    if ($two_attempts && $success_redirect) {
        echo "PASS";
    } else {
        echo "FAIL\n";
        var_dump($last_mail_args_history);
        print_r($last_json_response);
    }
});
