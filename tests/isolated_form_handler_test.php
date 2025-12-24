<?php

// Define ABSPATH to bypass the security check in form_handler.php
define('ABSPATH', dirname(__DIR__) . '/');

// Mock global $wpdb object
global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public $insert_data = null;
    public $insert_id = 1;

    public function insert($table, $data, $format) {
        $this->insert_data = $data;
        return 1;
    }
};

// Mock WordPress functions
if (!function_exists('add_action')) { function add_action($tag, $function_to_add) {} }
if (!function_exists('add_filter')) { function add_filter($tag, $function_to_add) {} }
if (!function_exists('wp_verify_nonce')) { function wp_verify_nonce($nonce, $action) { return true; } }
if (!function_exists('wp_mail')) { function wp_mail($to, $subject, $message, $headers) { return true; } }
if (!function_exists('carbon_get_theme_option')) { function carbon_get_theme_option($option) { return 'test@example.com'; } }
if (!function_exists('get_page_link')) { function get_page_link($id) { return 'http://example.com/thank-you'; } }
if (!function_exists('wp_send_json_success')) { function wp_send_json_success($data) { echo json_encode(['success' => true, 'data' => $data]); } }
if (!function_exists('wp_redirect')) { function wp_redirect($location) { throw new Exception('wp_redirect_called'); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($str) { return stripslashes($str); } }
if (!function_exists('wp_unslash')) { function wp_unslash($str) { return $str; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($str) { return $str; } }
if (!function_exists('sanitize_email')) { function sanitize_email($email) { return $email; } }
if (!function_exists('esc_html')) { function esc_html($text) { return $text; } }
if (!function_exists('wp_doing_ajax')) { function wp_doing_ajax() { return false; } }
if (!function_exists('wp_die')) { function wp_die($message) { throw new Exception("wp_die called: $message"); } }
if (!function_exists('get_text_lang')) { function get_text_lang($st1, $st2, $lang, $echo = true) { return $st2; } }
if (!function_exists('jawda_can_use_secondary_smtp')) { function jawda_can_use_secondary_smtp() { return false; } }

// --- Function to test ---
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

  // Verify nonce
  if ( ! isset( $_POST['my_contact_form_nonce'] ) || ! wp_verify_nonce( $_POST['my_contact_form_nonce'], 'my_contact_form_action' ) ) {
    $error_message = get_text_lang('عذراً، حدث خطأ ما.','Sorry, something went wrong.',$lang, false);
    $send_error( $error_message );
    return;
  }

  // Thank you page
  $thankyou_page_id = carbon_get_theme_option( 'jawda_page_thankyou_'.$lang );
  $thankyou = $thankyou_page_id ? get_page_link($thankyou_page_id) : home_url('/');


  // Site Email
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
    $error_message = get_text_lang(
      'عذراً، لم يتم ضبط بريد الاستقبال بشكل صحيح. برجاء مراجعة الإعدادات.',
      'Sorry, the recipient email address is not configured correctly. Please review the settings.',
      $lang,
      false
    );
    $send_error( $error_message );
    return;
  }

  // Check Required Data
  if(
    !isset($_POST['name']) || empty($_POST['name'])
    || !isset($_POST['phone']) || empty($_POST['phone'])
    || !isset($_POST['packageid']) || empty($_POST['packageid'])
  ){
    $error_message = get_text_lang('برجاء التأكد من اضافة جميع الحقول المطلوبة','Please make sure to add all required fields',$lang, false);
    $send_error( $error_message );
    return;
  }

    // package id
    $packagename = sanitize_text_field($_POST['packageid']);

    $name = wp_strip_all_tags(trim($_POST['name']));
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

    // Check Phone
    if( strlen($phone) < 11 || strlen($phone) > 17 ){
        $error_message = get_text_lang('برجاء التأكد من ادخال رقم هاتف صحيح.','Please make sure to enter a valid phone number.',$lang, false);
        $send_error( $error_message );
        return;
    }

    // Title Of Email
    $subject = "رسالة جديدة من العميل : ".$name;

    $message = "
    <html>
    <head>
    <title>" . esc_html($subject) . "</title>
    </head>
    <body>
      <h2>" . esc_html($subject) . "</h2>
      <table>
        <tr>
          <td><strong>اسم العميل : </strong></td>
          <td>" . esc_html($name) . "</td>
        </tr>
        <tr>
          <td><strong>ايميل العميل : </strong></td>
          <td>" . esc_html($email) . "</td>
        </tr>
        <tr>
          <td><strong>تليفون العميل : </strong></td>
          <td>" . esc_html($phone) . "</td>
        </tr>
        <tr>
          <td><strong>رسالة العميل : </strong></td>
          <td>" . esc_html($massege) . "</td>
        </tr>
        <tr>
          <td><strong>اسم المشروع / الإهتمام : </strong></td>
          <td>" . esc_html($packagename) . "</td>
        </tr>
      </table>
    </body>
    </html>
    ";

    global $jawda_force_secondary_smtp;

    $jawda_force_secondary_smtp = false;
    $send_mail = wp_mail($email_to, $subject, $message, $headers);

    if ( false === $send_mail ) {
        if ( jawda_can_use_secondary_smtp() ) {
            $jawda_force_secondary_smtp = true;
            $send_mail = wp_mail($email_to, $subject, $message, $headers);
            $jawda_force_secondary_smtp = false;

            if ( ! $send_mail ) {
                error_log('[Mail Error] Secondary SMTP fallback failed.');
            }
        } else {
            error_log('[Mail Error] Secondary SMTP settings incomplete. Fallback not attempted.');
        }
    }

    if( $send_mail ){
        global $wpdb;
        $table = $wpdb->prefix.'leadstable';
        $data = array('name' => $name,'email' => $email,'phone' => $phone,'massege' => $massege,'packagename' => $packagename);
        $format = array('%s','%s','%s','%s','%s');
        $wpdb->insert($table,$data,$format);
        $my_id = $wpdb->insert_id;

        if ( $is_ajax ) {
            wp_send_json_success(['redirect' => $thankyou]);
            return;
        }

        wp_redirect($thankyou);
        exit;
    } else {
        $error_message = get_text_lang(
            'عذرا، فشل إرسال البريد. يرجى التحقق من إعدادات خادم البريد (SMTP).',
            'Sorry, the email could not be sent. Please check the server\'s mail (SMTP) configuration.',
            $lang,
            false
        );
        $send_error( $error_message );
    }
}


// --- Test Case ---
function test_form_handler_preserves_backslashes_isolated() {
    global $wpdb;

    echo "Running test: 'test_form_handler_preserves_backslashes_isolated'\n";
    echo "---------------------------------------------------------\n";

    // 1. Arrange: Simulate form submission
    $_POST = [
        'name' => 'Al\Jazeera',
        'phone' => '12345678901',
        'packageid' => 'Test Package',
        'my_contact_form_nonce' => 'a_nonce_value',
        'langu' => 'en'
    ];
    $expected_name = 'Al\Jazeera';

    // 2. Act: Call the function
    try {
        prefix_send_email_to_admin();
    } catch (Exception $e) {
        // Do nothing, this is expected.
    }

    // 3. Assert: Check the data that was prepared for the database
    $submitted_name = $wpdb->insert_data['name'] ?? null;

    echo "Input name: '$expected_name'\n";
    echo "Name captured for DB insertion: '$submitted_name'\n";

    if ($submitted_name === $expected_name) {
        echo "\nResult: TEST PASSED\n";
        echo "The backslash was correctly preserved.\n";
        exit(0); // Success
    } else {
        echo "\nResult: TEST FAILED\n";
        echo "The backslash was not preserved as expected.\n";
        echo "Expected: '$expected_name', Got: '$submitted_name'\n";
        exit(1); // Failure
    }
}

// Run the test
test_form_handler_preserves_backslashes_isolated();
