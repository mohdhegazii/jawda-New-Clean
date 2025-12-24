<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_enqueue_scripts', function($hook){
    if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
        $is_ar = false;

        if (function_exists('jawda_is_arabic_locale')) {
            $is_ar = jawda_is_arabic_locale();
        } else {
            $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
            $is_ar = (bool) preg_match('/^ar/i', (string) $locale);
        }

        wp_enqueue_script(
            'jawda-location-widget',
            get_template_directory_uri() . '/assets/js/location-widget.js',
            ['jquery'],
            '1.0.0',
            true
        );
        $select_gov_first_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'jawda'));
        $select_city_first_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'jawda'));
        $error_loading_cities = $is_ar ? '— حدث خطأ، جرّب إعادة التحميل —' : __('— Error loading cities —', 'jawda');
        $error_loading_districts = $is_ar ? '— حدث خطأ، جرّب إعادة التحميل —' : __('— Error loading districts —', 'jawda');
        $no_cities_found = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('لم يتم العثور على مدن لهذه المحافظة.', __('No cities found for this governorate.', 'jawda'), 'both')
            : ($is_ar ? 'لم يتم العثور على مدن لهذه المحافظة.' : __('No cities found for this governorate.', 'jawda'));
        $no_districts_found = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('لم يتم العثور على مناطق لهذه المدينة.', __('No districts found for this city.', 'jawda'), 'both')
            : ($is_ar ? 'لم يتم العثور على مناطق لهذه المدينة.' : __('No districts found for this city.', 'jawda'));
        $no_options_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— لا توجد بيانات متاحة —', __('— No options available —', 'jawda'), 'both')
            : ($is_ar ? '— لا توجد بيانات متاحة —' : __('— No options available —', 'jawda'));

        wp_localize_script('jawda-location-widget', 'CF_DEP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cf_dep_nonce'),
            'language' => 'both',
            'i18n'     => [
                'loading'              => $is_ar ? '— جاري التحميل… —' : __('— Loading… —', 'jawda'),
                'select_gov_first'     => $select_gov_first_placeholder,
                'select_city_first'    => $select_city_first_placeholder,
                'error_loading_cities' => $error_loading_cities,
                'error_loading_districts' => $error_loading_districts,
                'no_cities_found'      => $no_cities_found,
                'no_districts_found'   => $no_districts_found,
                'no_options'           => $no_options_placeholder,
            ]
        ]);
    }
});

// AJAX handler to get cities based on governorate ID
add_action('wp_ajax_cf_dep_get_cities', 'jawda_cf_dep_get_cities');

function jawda_cf_dep_get_cities(){
    if (!check_ajax_referer('cf_dep_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    if (!jawda_enforce_rate_limit('cf_dep_get_cities_' . $ip, 120, 60)) {
        wp_send_json_error(['message' => 'Too many requests'], 429);
    }

    try {
        $gov_id = isset($_GET['gov_id']) ? absint($_GET['gov_id']) : 0;
        if (!$gov_id) {
            wp_send_json_error(['message' => 'Invalid parameters'], 400);
        }

        $default_lang = 'ar';
        $requested_lang = isset($_GET['lang']) ? wp_unslash($_GET['lang']) : $default_lang;

        if (!in_array($requested_lang, ['ar', 'en'], true)) {
            $requested_lang = 'ar';
        }

        $language = function_exists('jawda_locations_normalize_language')
            ? jawda_locations_normalize_language($requested_lang, $default_lang)
            : $default_lang;

        $results = function_exists('jawda_get_cities_by_governorate') ? jawda_get_cities_by_governorate($gov_id) : [];

        $placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'jawda'), $language)
            : __('— Select City —', 'jawda');

        $opts = ['' => [
            'label' => $placeholder,
            'lat'   => '',
            'lng'   => '',
        ]];
        if ($results) {
            foreach ($results as $row) {
                $label = function_exists('jawda_locations_get_label')
                    ? jawda_locations_get_label(
                        $row[ 'slug_ar' ] ?? '',
                        $row['name_en'] ?? '',
                        $language,
                        sprintf('#%d', (int) $row['id'])
                    )
                    : ($row[ 'slug_ar' ] ?? $row['name_en'] ?? sprintf('#%d', (int) $row['id']));

                $opts[$row['id']] = [
                    'label' => $label,
                    'lat'   => isset($row['latitude']) ? $row['latitude'] : '',
                    'lng'   => isset($row['longitude']) ? $row['longitude'] : '',
                ];
            }
        } else {
            // No cities found, send a specific message to be displayed
            wp_send_json_success(['options' => $opts, 'message' => (defined('CF_DEP_DEBUG') && CF_DEP_DEBUG) ? 'No cities found in DB for this governorate.' : ''], 200);
            return;
        }

        wp_send_json_success(['options' => $opts], 200);
    } catch (Exception $e) {
        if (defined('CF_DEP_DEBUG') && CF_DEP_DEBUG) {
            error_log('[cf_dep_get_cities] Exception: '.$e->getMessage());
        }
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }
}

// AJAX handler to get districts based on city ID
add_action('wp_ajax_cf_dep_get_districts', 'jawda_cf_dep_get_districts');

function jawda_cf_dep_get_districts(){
    if (!check_ajax_referer('cf_dep_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    if (!jawda_enforce_rate_limit('cf_dep_get_districts_' . $ip, 120, 60)) {
        wp_send_json_error(['message' => 'Too many requests'], 429);
    }

    try {
        $city_id = isset($_GET['city_id']) ? absint($_GET['city_id']) : 0;
        if (!$city_id) {
            wp_send_json_error(['message' => 'Invalid parameters'], 400);
        }

        $default_lang = 'ar';
        $requested_lang = isset($_GET['lang']) ? wp_unslash($_GET['lang']) : $default_lang;

        if (!in_array($requested_lang, ['ar', 'en'], true)) {
            $requested_lang = 'ar';
        }

        $language = function_exists('jawda_locations_normalize_language')
            ? jawda_locations_normalize_language($requested_lang, $default_lang)
            : $default_lang;

        $results = function_exists('jawda_get_districts_by_city') ? jawda_get_districts_by_city($city_id) : [];

        $placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المنطقة/الحي —', __('— Select District / Neighborhood —', 'jawda'), $language)
            : __('— Select District / Neighborhood —', 'jawda');

        $opts = ['' => [
            'label' => $placeholder,
            'lat'   => '',
            'lng'   => '',
        ]];
        if ($results) {
            foreach ($results as $row) {
                $label = function_exists('jawda_locations_get_label')
                    ? jawda_locations_get_label(
                        $row[ 'slug_ar' ] ?? '',
                        $row['name_en'] ?? '',
                        $language,
                        sprintf('#%d', (int) $row['id'])
                    )
                    : ($row[ 'slug_ar' ] ?? $row['name_en'] ?? sprintf('#%d', (int) $row['id']));

                $opts[$row['id']] = [
                    'label' => $label,
                    'lat'   => isset($row['latitude']) ? $row['latitude'] : '',
                    'lng'   => isset($row['longitude']) ? $row['longitude'] : '',
                ];
            }
        } else {
            wp_send_json_success(['options' => $opts, 'message' => (defined('CF_DEP_DEBUG') && CF_DEP_DEBUG) ? 'No districts found in DB for this city.' : ''], 200);
            return;
        }

        wp_send_json_success(['options' => $opts], 200);
    } catch (Exception $e) {
        if (defined('CF_DEP_DEBUG') && CF_DEP_DEBUG) {
            error_log('[cf_dep_get_districts] Exception: '.$e->getMessage());
        }
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }
}
