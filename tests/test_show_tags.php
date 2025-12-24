<?php
use PHPUnit\Framework\TestCase;

// Mocking WordPress core functions required by show_tags()
if (!function_exists('get_the_tags')) {
    function get_the_tags() {
        global $mock_tags;
        return $mock_tags;
    }
}

if (!function_exists('get_tag_link')) {
    function get_tag_link($term_id) {
        return 'http://example.com/tag/' . $term_id;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        // Mock implementation
    }
}
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        // Mock implementation
    }
}
if (!function_exists('do_shortcode')) {
    function do_shortcode($content) {
        // Mock implementation
        return $content;
    }
}

// Now include the file with the function to test
require_once 'app/functions/helper.php';

class test_show_tags extends TestCase {

    public function test_show_tags_does_not_have_xss_vulnerability() {
        global $mock_tags;
        $xss_string = "<script>alert('XSS');</script>";
        $malicious_tag = (object) [
            'term_id' => 1,
            'name' => 'Test Tag ' . $xss_string
        ];
        $mock_tags = [$malicious_tag];

        // Get return value
        $output = show_tags();

        // This assertion should fail with the current vulnerable code
        $this->assertStringNotContainsString($xss_string, $output, "The XSS string should not be in the output.");
    }
}
