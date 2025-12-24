<?php

// Mock WordPress environment
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES);
    }
}
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!class_exists('WP_List_Table')) {
    class WP_List_Table {}
}
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add) {}
}


// Include the file containing the function to be tested
require_once ABSPATH . 'app/functions/jawda_leads.php';

// --- Test Runner ---
function run_test($name, $test_function) {
    echo "Running test: '$name'... ";
    if ($test_function()) {
        echo "PASS\n";
    } else {
        echo "FAIL\n";
    }
}

// --- Test Cases ---
run_test("Output is correctly escaped in the admin form", function() {
    $item = [
        'name' => '<script>alert("XSS");</script>',
        'email' => '" onmouseover="alert(\'XSS\')',
        'phone' => '1234567890',
        'massege' => 'Test message',
        'packagename' => 'Test Package'
    ];

    ob_start();
    jawda_leads_leads_form_meta_box_handler($item);
    $output = ob_get_clean();

    // Check if the name field is correctly escaped
    $expected_name = 'value="&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;"';
    if (strpos($output, $expected_name) === false) {
        echo "Name field was not correctly escaped. ";
        return false;
    }

    // Check if the email field is correctly escaped
    $expected_email = 'value="&quot; onmouseover=&quot;alert(&#039;XSS&#039;)"';
    if (strpos($output, $expected_email) === false) {
        echo "Email field was not correctly escaped. ";
        return false;
    }

    return true;
});
