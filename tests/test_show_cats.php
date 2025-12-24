<?php
use PHPUnit\Framework\TestCase;

require_once 'app/functions/helper.php';

// Mock WordPress functions
function wp_get_post_categories($post_id) {
    return [1];
}

function get_the_ID() {
    return 1;
}

function get_category($category_id) {
    $cat = new stdClass();
    $cat->term_id = 1;
    $cat->name = 'Test Category';
    return $cat;
}

function get_tag_link($term_id) {
    return 'javascript:alert("XSS")';
}

function esc_url($url) {
    // A simple mock of the WordPress esc_url function for security testing.
    // The real function is much more complex, but this covers the javascript protocol.
    if (strpos(strtolower(trim($url)), 'javascript:') === 0) {
        return '';
    }
    return filter_var($url, FILTER_SANITIZE_URL);
}

function esc_html($text) {
    return htmlspecialchars($text);
}

function add_filter() {
    return;
}

function do_shortcode($v) {
    return $v;
}

function is_rtl() {
    return false;
}

function get_query_var($key, $default) {
    return $default;
}
function add_shortcode() {
    return;
}

class test_show_cats extends TestCase {
    public function test_show_cats_is_secure() {
        ob_start();
        show_cats();
        $output = ob_get_clean();
        $this->assertStringNotContainsString('javascript:alert("XSS")', $output);
    }
}
