<?php
require_once get_template_directory() . '/app/inc/admin-seo-templates.php';

/**
 * Jawda SEO: Force Redirect spaces to dashes in URLs to prevent duplicate content
 */

/**
 * Jawda SEO: Force Redirect spaces to dashes (Fixed Double Folder Issue)
 */
add_action('template_redirect', function() {
    $requested_uri = $_SERVER['REQUEST_URI'];
    
    if (strpos($requested_uri, '%20') !== false || strpos($requested_uri, ' ') !== false) {
        // 1. استبدال المسافات بداش
        $clean_path = str_replace(['%20', ' '], '-', $requested_uri);
        
        // 2. تنظيف الداش المتكررة
        $clean_path = preg_replace('/-{2,}/', '-', $clean_path);
        
        // 3. الحل العبقري: بما إن URI تبدأ بـ /masharf/ هنشيل الدومين خالص ونستخدم الرابط المباشر
        // أو نستخدم التحويل لبروتوكول الموقع كاملاً
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $full_url = $protocol . $_SERVER['HTTP_HOST'] . $clean_path;
        
        wp_redirect($full_url, 301);
        exit;
    }
});



use Tracy\OutputDebugger;

/* -----------------------------------------------------
# Define Directories
# (Restored to original state)
----------------------------------------------------- */
define('ROOT_DIR', dirname(__FILE__));
define('CLASS_DIR', ROOT_DIR . '/app/classes/');
define('FUNC_DIR', ROOT_DIR . '/app/functions/');
define('TEMP_DIR', ROOT_DIR . '/app/templates/');

/* -----------------------------------------------------
# Define URLs and Paths
----------------------------------------------------- */
define('siteurl', get_site_url());
define('sitename', get_bloginfo('name'));
define('wpath', get_template_directory());
define('wurl', get_template_directory_uri());
define('wcssurl', wurl . '/assets/css/');
define('wfavurl', wurl . '/assets/favicons/');
define('wfonturl', wurl . '/assets/fonts/');
define('wimgurl', wurl . '/assets/images/');
define('wjsurl', wurl . '/assets/js/');

/* -----------------------------------------------------
# Load Security
----------------------------------------------------- */
require_once __DIR__ . '/app/inc/security/secrets.php';

/* -----------------------------------------------------
# Load Composer Autoload
----------------------------------------------------- */
include_once(wpath . '/app/vendor/autoload.php');

/* -----------------------------------------------------
# Load Functions
----------------------------------------------------- */
$functionslist = [
    'basics', 'helper', 'menus', 'minifier', 'settings', 'post_types',
    'payment_plans', 'meta_box', 'styles', 'form_handler', 'tgm', 'schema',
    'pagination', 'shortcodes', 'editor_buttons', 'jawda_leads',
    'jawda_leads_download', 'translate', 'smtp_settings', 'smtp_mailer', 'locations-migrator', 'jawda-locations-admin/jawda-locations-admin', 'auto_catalog'
];
load_my_files($functionslist, FUNC_DIR);

/* -----------------------------------------------------
# Load Modular Features (inc)
----------------------------------------------------- */
if (file_exists(ROOT_DIR . '/app/inc/main.php')) {
    require_once ROOT_DIR . '/app/inc/main.php';
}

/* -----------------------------------------------------
# Load Templates
----------------------------------------------------- */
load_all_files(TEMP_DIR);

// Load the verification script
require_once 'tools/verify_lookups.php';

/* -----------------------------------------------------
# Loader Functions
----------------------------------------------------- */

// Load multiple PHP files
function load_my_files($files, $path) {
    foreach ($files as $filename) {
        $filepath = $path . $filename . '.php';
        if (file_exists($filepath)) {
            include_once($filepath);
        }
    }
}

// Load all PHP files in a directory recursively
function load_all_files($directory) {
    if (is_dir($directory)) {
        $scan = scandir($directory);
        unset($scan[0], $scan[1]);
        foreach ($scan as $file) {
            if (is_dir($directory . '/' . $file)) {
                load_all_files($directory . '/' . $file);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                include_once($directory . '/' . $file);
            }
        }
    }
}

/**
 * HOTFIX: Ensure get_object_term_ids exists (cron-safe)
 */
if (!function_exists('get_object_term_ids')) {
    function get_object_term_ids($object_id, $taxonomy) {
        $ids = wp_get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);
        return is_wp_error($ids) ? [] : $ids;
    }
}

/**
 * Debug mail failures on local dev
 */
add_action('wp_mail_failed', function($wp_error) {
    error_log("WP_MAIL_FAILED: " . print_r($wp_error, true));
});

/**
 * Cleanup Projects UI: Remove Location Meta Box
 */
add_action('add_meta_boxes', function() {
    remove_meta_box('location', 'projects', 'normal');
    remove_meta_box('location', 'projects', 'advanced');
    remove_meta_box('location', 'projects', 'side');
}, 999);

/**
 * Remove Jawda Project Location Meta Box
 */
add_action('add_meta_boxes', function() {
    // حذف المربع الذي يحمل المعرف jawda-project-location من نوع المنشور projects
    remove_meta_box('jawda-project-location', 'projects', 'normal');
    remove_meta_box('jawda-project-location', 'projects', 'advanced');
    remove_meta_box('jawda-project-location', 'projects', 'side');
}, 1000);

// Mute Deprecated Warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Safe guard to prevent database warnings in admin
if (is_admin()) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}



/**
 * تسجيل صفحة SEO Factory بشكل صحيح لضمان صلاحيات الوصول
 */
add_action('admin_menu', function() {
    add_menu_page(
        'SEO Factory', 
        'SEO Factory', 
        'manage_options', 
        'jawda-seo-templates', 
        'jawda_render_seo_templates_page', 
        'dashicons-performance', 
        25
    );
}, 999);
