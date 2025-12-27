<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }


function set_mail_content_type(){ return "text/html"; }
add_filter( 'wp_mail_content_type','set_mail_content_type' );

// Ensure a valid From header for PHPMailer (especially on localhost)
add_filter('wp_mail_from', function($from){ return 'no-reply@jawda.test'; });
add_filter('wp_mail_from_name', function($name){ return 'Jawda'; });




// Track whether we should force the secondary SMTP configuration.
global $jawda_force_secondary_smtp;
if ( ! isset( $jawda_force_secondary_smtp ) ) {
  $jawda_force_secondary_smtp = false;
}


function prefix_send_email_to_admin() {
$lang      = ( isset( $_POST['langu'] ) && $_POST['langu'] === 'en' ) ? 'en' : 'ar';
  $is_ajax   = ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'WP_TESTS_DOMAIN' );
  $ip        = function_exists( 'getUserIP' ) ? getUserIP() : ( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
  $ip_hash   = hash( 'sha256', $ip . ( defined( 'NONCE_SALT' ) ? NONCE_SALT : ( defined( 'AUTH_KEY' ) ? AUTH_KEY : 'contact' ) ) );
  $log_block = function( $reason ) use ( $ip_hash ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    }
  };

  $send_error = function( $message, $status_code = 400, $code = 'contact_error' ) use ( $is_ajax ) {
    $error = new WP_Error( $code, $message );

    if ( $is_ajax ) {
      wp_send_json_error( [ 'code' => $code, 'message' => $message ], $status_code );
    }

    $redirect = wp_get_referer();
    if ( $redirect ) {
      $redirect = add_query_arg(
        [
          'contact_status' => 'error',
          'contact_code'   => $code,
        ],
        $redirect
      );

      wp_safe_redirect( $redirect );
      exit;
    }

    wp_die( $message, '', [ 'response' => $status_code ] );
  };


  // Track wp_mail() result
  $send_mail = false;
  // Anti-spam: rate limiting (3 submissions / 10 minutes)
  $is_rate_limited = function_exists( 'jawda_rate_limit' )
    ? jawda_rate_limit( 'contact_form', 3, 600 )
    : false;

  if ( $is_rate_limited ) {
      $log_block( 'rate_limit' );
      $error_message = get_text_lang('لقد تجاوزت حد الإرسال المسموح. يرجى المحاولة لاحقاً.','You have exceeded the submission limit. Please try again later.',$lang, false);
      $send_error( $error_message, 429, 'rate_limit' );
      return;
  }

  // Verify nonce
  if ( ! check_ajax_referer( 'my_contact_form_action', 'my_contact_form_nonce', false ) ) {
    $log_block( 'nonce_failed' );
    $error_message = get_text_lang('عذراً، حدث خطأ ما.','Sorry, something went wrong.',$lang, false);
    $send_error( $error_message, 403, 'invalid_nonce' );
    return;
  }

  // Honeypot
  $honeypot = isset( $_POST['website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['website'] ) ) ) : '';
  if ( '' !== $honeypot ) {
    $log_block( 'honeypot_triggered' );
    $error_message = get_text_lang('عذراً، لا يمكن إكمال الإرسال.','Sorry, the submission was rejected.',$lang, false);
    $send_error( $error_message, 400, 'honeypot' );
    return;
  }

  // Timestamp guard: reject too fast (<3s) or too old (>2h)
  $form_ts_raw = isset( $_POST['form_ts'] ) ? intval( $_POST['form_ts'] ) : 0;
  $min_ts_age = ( defined('JAWDA_RELAX_FORM_TS_GUARD') && JAWDA_RELAX_FORM_TS_GUARD ) ? 0 : 3;

  $now         = time();
  if ( $form_ts_raw <= 0 || ($now - $form_ts_raw) < $min_ts_age || ( $now - $form_ts_raw ) > HOUR_IN_SECONDS * 2 ) {
    $log_block( 'timestamp_window' );
    $error_message = get_text_lang('عذراً، انتهت صلاحية النموذج. يرجى المحاولة مرة أخرى.','Sorry, the form submission window expired. Please try again.',$lang, false);
    $send_error( $error_message, 400, 'invalid_timestamp' );
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
    $send_error( $error_message, 500, 'recipient_not_configured' );
    return;
  }

  // Required fields
  $raw_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
  $raw_phone  = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
  $raw_email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
  $raw_msg    = isset( $_POST['special_request'] ) ? wp_unslash( $_POST['special_request'] ) : '';
  $raw_pkg    = isset( $_POST['packageid'] ) ? sanitize_text_field( wp_unslash( $_POST['packageid'] ) ) : '';

  if ( '' === $raw_name || '' === $raw_msg || ( '' === $raw_phone && '' === $raw_email ) ) {
    $error_message = get_text_lang('برجاء التأكد من اضافة جميع الحقول المطلوبة','Please make sure to add all required fields',$lang, false);
    $send_error( $error_message, 400, 'missing_required' );
    return;
  }

  // Normalize whitespace
  $normalize_whitespace = static function ( $value ) {
    $value = str_replace( [ "\r\n", "\r" ], "\n", $value );
    $value = preg_replace( '/[\t ]{2,}/u', ' ', $value );
    return trim( $value );
  };

  // Validate name
  $name = $normalize_whitespace( $raw_name );
  if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 80 ) {
    $error_message = get_text_lang('الاسم يجب أن يكون بين 2 و 80 حرفاً.','Name must be between 2 and 80 characters.',$lang, false);
    $send_error( $error_message, 400, 'invalid_name_length' );
    return;
  }
  if ( preg_match( '/(content-type:|bcc:|cc:)/i', $name ) ) {
    $log_block( 'header_injection_name' );
    $error_message = get_text_lang('تنسيق الاسم غير صالح.','Invalid name format.',$lang, false);
    $send_error( $error_message, 400, 'invalid_name_format' );
    return;
  }

  // Validate phone (optional but max length enforced)
  $phone = '';
  if ( '' !== $raw_phone ) {
    $phone = $normalize_whitespace( $raw_phone );
    if ( mb_strlen( $phone ) > 30 || ! preg_match( '/^[\d\+\-\s\(\)]+$/', $phone ) ) {
      $error_message = get_text_lang('برجاء التأكد من ادخال رقم هاتف صحيح.','Please make sure to enter a valid phone number.',$lang, false);
      $send_error( $error_message, 400, 'invalid_phone' );
      return;
    }
  }

  // Validate email (optional)
  $email = '';
  if ( '' !== $raw_email ) {
    if ( mb_strlen( $raw_email ) > 120 || ! is_email( $raw_email ) ) {
      $error_message = get_text_lang('برجاء التأكد من ادخال بريد الكتروني صحيح.','Please make sure to enter a valid email.',$lang, false);
      $send_error( $error_message, 400, 'invalid_email' );
      return;
    }
    $email = $raw_email;
  }

  // Validate package (context info only)
  $packagename = $normalize_whitespace( $raw_pkg );
  if ( mb_strlen( $packagename ) > 300 ) {
    $error_message = get_text_lang('بيانات غير صحيحة.','Invalid data.',$lang, false);
    $send_error( $error_message, 400, 'invalid_package' );
    return;
  }

  // Validate and sanitize message
  $special_request_raw = $normalize_whitespace( wp_kses( $raw_msg, [] ) );
  if ( '' === $special_request_raw ) {
    $error_message = get_text_lang('الرسالة مطلوبة.','Message is required.',$lang, false);
    $send_error( $error_message, 400, 'missing_message' );
    return;
  }

  if ( mb_strlen( $special_request_raw ) > 2000 ) {
    $error_message = get_text_lang('الرسالة طويلة جداً. الحد الأقصى 2000 حرف.','Message is too long. Max 2000 characters.',$lang, false);
    $send_error( $error_message, 400, 'message_too_long' );
    return;
  }

  if ( preg_match( '/(content-type:|bcc:|cc:)/i', $special_request_raw ) ) {
    $log_block( 'header_injection_message' );
    $error_message = get_text_lang('تنسيق الرسالة غير صالح.','Invalid message format.',$lang, false);
    $send_error( $error_message, 400, 'invalid_message_format' );
    return;
  }

  $massege = $special_request_raw;

  $headers = [];
  $site_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
  $site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
  $from_email = $site_host ? 'no-reply@' . $site_host : 'no-reply@jawda.test';
  $headers[]  = 'From: ' . $site_name . ' <' . $from_email . '>';

  if ( '' !== $email ) {
    $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
  }

  // Title Of Email
  $subject = "رسالة جديدة من العميل : " . $name;

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
          <td>" . esc_html($email ? $email : 'لم يتم اضافته') . "</td>
        </tr>
        <tr>
          <td><strong>تليفون العميل : </strong></td>
          <td>" . esc_html($phone ? $phone : 'لم يتم اضافته') . "</td>
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

    

    // Save lead regardless of mail success (local/prod safe)
    global $wpdb;
    $table = $wpdb->prefix . 'leadstable';
    $data = array(
        'name'        => $name,
        'email'       => $email ? $email : 'لم يتم اضافته',
        'phone'       => $phone ? $phone : 'لم يتم اضافته',
        'massege'     => $massege,
        'packagename' => $packagename
    );
    $format = array('%s','%s','%s','%s','%s');

    $inserted = $wpdb->insert($table, $data, $format);
    if ( $inserted === false && defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('[Lead Insert Failed] table=' . $table . ' err=' . $wpdb->last_error);
    } elseif ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('[Lead Insert OK] table=' . $table . ' id=' . $wpdb->insert_id);
    }
if ( false === $send_mail ) {
        if ( jawda_can_use_secondary_smtp() ) {
            $jawda_force_secondary_smtp = true;
            $send_mail = wp_mail($email_to, $subject, $message, $headers);
            $jawda_force_secondary_smtp = false;

            if ( ! $send_mail && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log('[Mail Error] Secondary SMTP fallback failed. ip_hash=' . $ip_hash);
            }
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
              error_log('[Mail Error] Secondary SMTP settings incomplete. Fallback not attempted. ip_hash=' . $ip_hash);
            }
        }
    }

    if( $send_mail ){
if ( $is_ajax ) {
            wp_send_json_success( array_merge( [
'redirect' => $thankyou
  ], [ 'mail_sent' => (bool) $send_mail ] ) );
            return;
        }

        wp_redirect($thankyou);
        exit;
    } else {
        // Mail failed but lead is already saved; do not block UX on local/prod
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Mail Failed] lead_saved=1 ip_hash=' . $ip_hash);
        }

        if ( $is_ajax ) {
            wp_send_json_success(['redirect' => $thankyou, 'mail_sent' => false]);
            return;
        }

        wp_redirect($thankyou);
        exit;
    }
}

add_action( 'admin_post_nopriv_my_contact_form', 'prefix_send_email_to_admin' );
add_action( 'admin_post_my_contact_form', 'prefix_send_email_to_admin' );
add_action( 'wp_ajax_nopriv_my_contact_form', 'prefix_send_email_to_admin' );
add_action( 'wp_ajax_my_contact_form', 'prefix_send_email_to_admin' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
  WP_CLI::add_command( 'jawda contact-diagnostics', 'jawda_contact_form_diagnostics' );
}

function jawda_contact_form_diagnostics() {
  if ( ! class_exists( 'WP_CLI' ) ) {
    return;
  }

  if ( ! current_user_can( 'manage_options' ) ) {
    WP_CLI::error( 'Insufficient permissions to run diagnostics.' );
  }

  WP_CLI::line( 'Contact form throttling: max 3 submissions / 10 minutes per IP (transient based).' );

  if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
    WP_CLI::warning( 'WP_DEBUG is disabled; log-based diagnostics are unavailable.' );
    return;
  }

  $log_path = WP_CONTENT_DIR . '/debug.log';
  if ( ! file_exists( $log_path ) ) {
    WP_CLI::warning( 'debug.log not found at ' . $log_path );
    return;
  }

  $lines = file( $log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
  $contact_lines = array_filter(
    array_slice( $lines, -200 ),
    static function ( $line ) {
    }
  );

  $contact_lines = array_slice( $contact_lines, -10 );

  if ( empty( $contact_lines ) ) {
    WP_CLI::line( 'No recent contact form security log entries found.' );
    return;
  }

  WP_CLI::line( 'Last contact form block reasons (most recent last):' );
  foreach ( $contact_lines as $entry ) {
    WP_CLI::line( ' - ' . $entry );
  }
}

function jawda_can_use_secondary_smtp() {
  if ( ! function_exists( 'carbon_get_theme_option' ) ) {
    return false;
  }

  $host     = carbon_get_theme_option( 'crb_smtp_host' );
  $port     = carbon_get_theme_option( 'crb_smtp_port' );
  $username = carbon_get_theme_option( 'crb_smtp_username' );
  $password = carbon_get_theme_option( 'crb_smtp_password' );

  return ! empty( $host ) && ! empty( $port ) && ! empty( $username ) && ! empty( $password );
}



function get_text_lang($st1, $st2, $lang, $echo = true){
  $return = ($lang == 'ar') ? $st1 : $st2;
  if ($echo) {
    echo $return;
  } else {
    return $return;
  }
}




function ja_ajax_search_properties() {
    if ( ! check_ajax_referer( 'search_nonce_action', 'security', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid security token' ], 403 );
    }

    // Rate Limit: 60 requests / 60 seconds per IP
    $is_rate_limited = function_exists( 'jawda_rate_limit' ) && jawda_rate_limit( 'search_properties', 60, 60 );
    if ( $is_rate_limited ) {
        wp_send_json_error( [ 'message' => 'Rate limit exceeded' ], 429 );
    }

    $search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $search_term = trim( $search_term );

    // Validation: Length 2-60
    if ( mb_strlen( $search_term ) < 2 || mb_strlen( $search_term ) > 60 ) {
         wp_send_json_error( [ 'message' => 'Invalid search term length' ], 400 );
    }

    // Validation: Allowed characters (letters, numbers, space, -, _)
    if ( ! preg_match( '/^[\p{L}\p{N}\s\-_]+$/u', $search_term ) ) {
         wp_send_json_error( [ 'message' => 'Invalid characters in search term' ], 400 );
    }

	$results = new WP_Query( array(
		'post_type'     => array( 'property' ),
		'post_status'   => 'publish',
    'posts_per_page' => 10,
		's'             => $search_term,
	) );

	$items = array();

	if ( !empty( $results->posts ) ) {
		foreach ( $results->posts as $result ) {
			$items[] = $result->post_title;
		}
	}

	wp_send_json_success( $items );
}
add_action( 'wp_ajax_search_properties',        'ja_ajax_search_properties' );
add_action( 'wp_ajax_nopriv_search_properties', 'ja_ajax_search_properties' );


function ja_ajax_search_projects() {
    if ( ! check_ajax_referer( 'search_nonce_action', 'security', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid security token' ], 403 );
    }

    // Rate Limit: 60 requests / 60 seconds per IP
    $is_rate_limited = function_exists( 'jawda_rate_limit' ) && jawda_rate_limit( 'search_projects', 60, 60 );
    if ( $is_rate_limited ) {
        wp_send_json_error( [ 'message' => 'Rate limit exceeded' ], 429 );
    }

    // Removed auto_catalog.php include (Legacy)

    $search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $search_term = trim( $search_term );

    // Validation: Length 2-60
    if ( mb_strlen( $search_term ) < 2 || mb_strlen( $search_term ) > 60 ) {
         wp_send_json_error( [ 'message' => 'Invalid search term length' ], 400 );
    }

    // Validation: Allowed characters (letters, numbers, space, -, _)
    if ( ! preg_match( '/^[\p{L}\p{N}\s\-_]+$/u', $search_term ) ) {
         wp_send_json_error( [ 'message' => 'Invalid characters in search term' ], 400 );
    }
    $items = [];
    $lang = jawda_is_arabic_locale() ? 'ar' : 'en';
    $name_col = $lang === 'ar' ?  'slug_ar'  : 'name_en';

    // 1. Search Projects
    $project_results = new WP_Query([
        'post_type'      => 'projects',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        's'              => $search_term,
    ]);

    if ( !empty( $project_results->posts ) ) {
        foreach ( $project_results->posts as $post ) {
            $items[] = [
                'value' => get_the_title( $post->ID ),
                'url'   => get_permalink( $post->ID ),
                'type'  => __('Project', 'jawda')
            ];
        }
    }

    // 2. Search Developers
    $developer_results = function_exists('jawda_get_developers')
        ? jawda_get_developers([
            'is_active' => 1,
            'search' => $search_term,
            'number' => 3,
            'offset' => 0,
        ])
        : [];

    if ( ! empty( $developer_results ) ) {
        foreach ( $developer_results as $developer ) {
            $developer_projects = new WP_Query([
                'post_type'      => 'projects',
                'post_status'    => 'publish',
                'posts_per_page' => 3,
                'meta_query'     => [
                    [
                        'key'     => '_selected_developer_id',
                        'value'   => $developer['id'],
                        'compare' => '=',
                    ],
                ],
            ]);

            if ( !empty( $developer_projects->posts ) ) {
                $developer_name = jawda_get_developer_display_name($developer, $lang === 'ar');
                foreach ( $developer_projects->posts as $post ) {
                    $items[] = [
                        'value' => get_the_title( $post->ID ),
                        'url'   => get_permalink( $post->ID ),
                        'type'  => $developer_name
                    ];
                }
            }
        }
    }

    // 3. Search Locations (Cities & Districts)
    global $wpdb;
    $locations = [];

    // Cities
    $cities = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, %s AS type, {$name_col} as name FROM {$wpdb->prefix}jawda_cities WHERE {$name_col} LIKE %s LIMIT 3",
        'city', '%' . $wpdb->esc_like( $search_term ) . '%'
    ) );
    $locations = array_merge($locations, $cities);

    // Districts
    $districts = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, %s AS type, {$name_col} as name FROM {$wpdb->prefix}jawda_districts WHERE {$name_col} LIKE %s LIMIT 3",
        'district', '%' . $wpdb->esc_like( $search_term ) . '%'
    ) );
    $locations = array_merge($locations, $districts);


    // Note: The hero search intentionally only includes cities and districts, not governorates.
    if ( ! empty( $locations ) ) {
        foreach ( $locations as $location ) {
            $url = '';

            // Try new routing first
            if ( function_exists( 'jawda_get_new_projects_url_by_location' ) ) {
                if ( $location->type === 'city' ) {
                    $url = jawda_get_new_projects_url_by_location( null, $location->id, null, $lang );
                } elseif ( $location->type === 'district' ) {
                    $url = jawda_get_new_projects_url_by_location( null, null, $location->id, $lang );
                }
            }

            // Fallback to legacy catalog REMOVED

            if ( ! empty( $url ) ) {
                $type_label = '';
                if ($location->type === 'city') {
                    $type_label = ($lang === 'ar') ? 'مدينة' : 'City';
                } elseif ($location->type === 'district') {
                    $type_label = ($lang === 'ar') ? 'منطقة' : 'District';
                } else {
                    $type_label = ($lang === 'ar') ? 'منطقة' : 'Location';
                }

                $items[] = [
                    'value' => $location->name,
                    'url'   => $url,
                    'type'  => 'location',
                    'subtype' => $location->type,
                    'type_label' => $type_label,
                    'action' => 'store'
                ];
            }
        }
    }

    wp_send_json_success( $items );
}
add_action( 'wp_ajax_search_projects',        'ja_ajax_search_projects' );
add_action( 'wp_ajax_nopriv_search_projects', 'ja_ajax_search_projects' );

add_action( 'template_redirect', 'jawda_handle_hero_search_submit' );

function jawda_handle_hero_search_submit() {
    // Front-end only
    if ( is_admin() ) {
        return;
    }

    // Only run on main request
    if ( ! is_main_query() ) {
        return;
    }

    // Check if this request came from the hero search form
    if ( empty( $_REQUEST['hero_search'] ) ) {
        return;
    }

    // At this point, we know this is a hero search submit
    // 1) Extract the typed query (whatever input name the hero uses)
    $query = '';

    if ( isset( $_REQUEST['s'] ) ) {
        // If hero form still uses 's' as the search field
        $query = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
    }

    // 2) Log the query
    jawda_log_hero_search_query( $query );


    // 3) Redirect to the main projects catalog URL (without ?s= or st)
    if ( function_exists( 'jawda_get_projects_catalog_url' ) ) {
        // This function is deprecated/removed in basics.php, so we shouldn't reach here if it was removed properly.
        // But if it lingers, we use it. If not, fallback.
        $redirect_url = jawda_get_projects_catalog_url();
    } else {
        // New default destination for hero search fallbacks
        $redirect_url = home_url( '/' );
    }

    wp_safe_redirect( $redirect_url );
    exit;
}

function jawda_log_hero_search_query( $query ) {
    $query = trim( $query );
    if ( $query === '' ) {
        return;
    }

    // Store a log of recent hero searches in an option
    $log = get_option( 'jawda_hero_search_log', array() );
    if ( ! is_array( $log ) ) {
        $log = array();
    }

    $log[] = array(
        'query' => $query,
        'time' => current_time( 'mysql' ),
        'ip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
    );

    // Limit the log size, e.g. last 100 entries
    if ( count( $log ) > 100 ) {
        $log = array_slice( $log, -100 );
    }

    update_option( 'jawda_hero_search_log', $log, false );

    // Also store the latest query separately to show as an admin notice
    update_option( 'jawda_hero_last_search', $query, false );
}

add_action( 'admin_notices', 'jawda_show_hero_search_admin_notice' );

function jawda_show_hero_search_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $last_query = get_option( 'jawda_hero_last_search' );
    if ( ! $last_query ) {
        return;
    }

    // Simple notice
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php
            echo esc_html__( 'New hero search query:', 'jawda' ) . ' <strong>' . esc_html( $last_query ) . '</strong>';
        ?></p>
    </div>
    <?php
}
