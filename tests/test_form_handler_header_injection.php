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
if (!function_exists('get_text_lang')) { function get_text_lang($st1, $st2, $lang, $echo = true) { return $st2; } }
if (!function_exists('jawda_can_use_secondary_smtp')) { function jawda_can_use_secondary_smtp() { return false; } }

global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public function insert($table, $data, $format) { return 1; }
};


// --- Function Under Test (Copied from app/functions/form_handler.php with the fix) ---

function prefix_send_email_to_admin() {
    // Language
    $lang = ( isset($_POST['langu']) && $_POST['langu'] == 'ar' ) ? 'ar' : 'en';
    $is_ajax = ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'WP_TESTS_DOMAIN' );

    $send_error = function( $message ) use ( $is_ajax ) {
        if ( $is_ajax ) {
            wp_send_json_error( [ 'message' => $message ] );
        } else {
            wp_die( $message );
        }
    };

    if ( ! isset( $_POST['my_contact_form_nonce'] ) || ! wp_verify_nonce( $_POST['my_contact_form_nonce'], 'my_contact_form_action' ) ) {
        $error_message = get_text_lang('عذراً، حدث خطأ ما.','Sorry, something went wrong.',$lang, false);
        $send_error( $error_message );
        return;
    }

    $thankyou_page_id = carbon_get_theme_option( 'jawda_page_thankyou_'.$lang );
    $thankyou = $thankyou_page_id ? get_page_link($thankyou_page_id) : home_url('/');

    $email_to = '';
    if ( function_exists( 'carbon_get_theme_option' ) ) {
        $email_to = carbon_get_theme_option( 'jawda_email' );
        if ( empty( $email_to ) ) {
            $email_to = carbon_get_theme_option( '_jawda_email' );
        }
    }
    if ( empty( $email_to ) ) {
        $email_to = get_bloginfo( 'admin_email' );
    }
    $email_to = sanitize_email( $email_to );

    if ( empty( $email_to ) || ! filter_var( $email_to, FILTER_VALIDATE_EMAIL ) ) {
        $error_message = get_text_lang('عذراً، لم يتم ضبط بريد الاستقبال بشكل صحيح. برجاء مراجعة الإعدادات.', 'Sorry, the recipient email address is not configured correctly. Please review the settings.', $lang, false);
        $send_error( $error_message );
        return;
    }

    if( !isset($_POST['name']) || empty($_POST['name']) || !isset($_POST['phone']) || empty($_POST['phone']) || !isset($_POST['packageid']) || empty($_POST['packageid']) ) {
        $error_message = get_text_lang('برجاء التأكد من اضافة جميع الحقول المطلوبة','Please make sure to add all required fields',$lang, false);
        $send_error( $error_message );
        return;
    }

    $packagename = sanitize_text_field($_POST['packageid']);
    // This is the line that was fixed
    $name = str_replace( [ "\r", "\n" ], [ '', '' ], wp_strip_all_tags( trim( $_POST['name'] ) ) );
    $phone = wp_strip_all_tags(trim($_POST['phone']));
    $massege = ( isset($_POST['special_request']) AND $_POST['special_request'] != '' ) ? wp_strip_all_tags(trim($_POST['special_request'])) : 'لم يتم اضافة رسالة';

    $bHasLink = strpos($massege, 'http') !== false || strpos($massege, 'www.') !== false;
    if ( $bHasLink ) {
        $error_message = get_text_lang('غير مسموح بإضافة روابط في الرسالة.','It is not allowed to add links in the message.',$lang, false);
        $send_error( $error_message );
        return;
    }

    $headers = [ 'From: AqarAnd <wordpress@aqarand.com>' ];
    if( isset($_POST['email']) && $_POST['email'] != '' ){
        $email = sanitize_email($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = get_text_lang('برجاء التأكد من ادخال بريد الكتروني صحيح.','Please make sure to enter a valid email.',$lang, false);
            $send_error( $error_message );
            return;
        }
        $headers[]   = 'Reply-To: '.$name.' <'.$email.'>';
    } else {
        $email = "لم يتم اضافته";
    }

    if( strlen($phone) < 11 || strlen($phone) > 17 ){
        $error_message = get_text_lang('برجاء التأكد من ادخال رقم هاتف صحيح.','Please make sure to enter a valid phone number.',$lang, false);
        $send_error( $error_message );
        return;
    }

    $subject = "رسالة جديدة من العميل : ".$name;
    $message = "Email body content...";

    $send_mail = wp_mail($email_to, $subject, $message, $headers);

    if( $send_mail ){
        global $wpdb;
        $table = $wpdb->prefix.'leadstable';
        $data = array('name' => $name,'email' => $email,'phone' => $phone,'massege' => $massege,'packagename' => $packagename);
        $format = array('%s','%s','%s','%s','%s');
        $wpdb->insert($table,$data,$format);
        wp_redirect($thankyou);
        exit;
    } else {
        $error_message = get_text_lang('عذرا، فشل إرسال البريد. يرجى التحقق من إعدادات خادم البريد (SMTP).', 'Sorry, the email could not be sent. Please check the server\'s mail (SMTP) configuration.', $lang, false);
        $send_error( $error_message );
    }
}

// --- Test Case ---

function test_email_header_injection() {
    echo "Running test: 'test_email_header_injection'\n";
    echo "---------------------------------------------------------\n";

    // 1. Arrange: Simulate form submission with a malicious name
    $_POST = [
        'name' => "John Doe\r\nBcc: victim@example.com",
        'phone' => '12345678901',
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

    // Check if any newline characters are present in the final header. They should NOT be.
    if (strpos($reply_to_header, "\r") === false && strpos($reply_to_header, "\n") === false) {
        echo "\nResult: TEST PASSED\n";
        echo "The newline characters were successfully stripped, preventing header injection.\n";
        exit(0); // Success
    } else {
        echo "\nResult: TEST FAILED\n";
        echo "Newline characters were found in the Reply-To header, indicating an injection vulnerability.\n";
        exit(1); // Failure
    }
}

// Run the test
test_email_header_injection();
