<?php
// Mock WordPress environment
define( 'ABSPATH', dirname( __FILE__ ) . '/' );
define( 'siteurl', 'https://example.com' );
define( 'sitename', 'My Site' );
define( 'wjsurl', get_template_directory_uri().'/assets/js/');

function get_template_directory_uri() {
    return siteurl . '/app';
}

// Mock WP_Query for the latest projects
class WP_Query {
    public $posts;
    public $found_posts;
    private $current_post = -1;

    public function __construct($args) {
        $this->posts = [];
        // Create 15 mock projects
        for ($i = 1; $i <= 15; $i++) {
            $this->posts[] = (object)[
                'ID' => $i,
                'post_title' => 'أحدث المشروعات الطويلة جدًا جدًا جدًا جدًا جدًا جدًا جدًا جدًا جدًا ' . $i,
            ];
        }
        $this->found_posts = count($this->posts);
    }

    public function have_posts() {
        return $this->current_post + 1 < count($this->posts);
    }

    public function the_post() {
        global $post;
        $this->current_post++;
        $post = $this->posts[$this->current_post];
    }

    public function rewind_posts() {
        $this->current_post = -1;
    }
}

// Mock the global post object
global $post;
$post = null;

function get_permalink() {
    global $post;
    return siteurl . '/project/' . $post->ID;
}

function get_the_title() {
    global $post;
    return $post->post_title;
}

function wp_reset_postdata() {
    global $post;
    $post = null; // Reset the global post object
}

function is_rtl() {
    return true;
}

function get_text($text_ar, $text_en) {
    echo $text_ar;
}

function has_nav_menu($location) {
    return true;
}

function wp_nav_menu($args) {
    echo "<ul><li><a href='#'>Menu Item 1</a></li><li><a href='#'>Menu Item 2</a></li></ul>";
}

function carbon_get_theme_option($option) {
    return "mock_value";
}

function get_whatsapp_link($number) {
    return "https://wa.me/" . $number;
}

function sanitize_email($email) {
    return $email;
}

function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_js($text) {
    return $text;
}

function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

function get_my_social() {}
function my_contact_form() {}

function add_action($hook, $function) {}

function wp_create_nonce($action) {
    return 'mock_nonce';
}

function admin_url($path) {
    return siteurl . '/admin/' . $path;
}

function wp_footer() {}

function minifyCss($css) {
    return $css;
}

function get_template_directory() {
    return 'app';
}

function minify_html($html) {
    return $html;
}

// Include the actual functions files
require_once 'app/functions/menus.php';
require_once 'app/functions/styles.php';
require_once 'app/templates/parts/footer.php';
