<?php
use PHPUnit\Framework\TestCase;

if (!class_exists('WP_List_Table')) {
    class WP_List_Table {
        public function __construct($args = []) {}
        public function prepare_items() {}
        public function display() {}
        public function current_action() { return false; }
        protected function row_actions($actions, $always_visible = false) {
            return '';
        }
    }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('add_action')) {
    function add_action($hook, $callback) {
        // Mock implementation
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
require_once(dirname(__DIR__) . '/app/functions/jawda_leads.php');

class xss_test extends TestCase
{
    public function testXssInColumnName()
    {
        $_REQUEST['page'] = 'leads';
        $jawdaLeadsListTable = new Jawda_leads_List_Table();
        $item = [
            'id' => 1,
            'name' => '<script>alert("xss");</script>',
        ];
        $output = $jawdaLeadsListTable->column_name($item);
        $this->assertStringNotContainsString('<script>alert("xss");</script>', $output);
    }
}
