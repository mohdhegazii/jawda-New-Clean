<?php

// Security Check
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

// Carbon_Fields
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/* -------------------------------------------------------------------------
# Property Meta Boxes
------------------------------------------------------------------------- */

if ( !function_exists('jawda_meta_property') ) {

  add_action( 'carbon_fields_register_fields', 'jawda_meta_property' );
  function jawda_meta_property() {

    // Options
    $meta_package =
    Container::make( 'post_meta', 'Property Data' )
      ->where( 'post_type', '=', 'property' )
      ->add_tab( __( 'Property Details' ), array(

        // Gallery
        Field::make( 'separator', 'jawda_separator_002', __( 'Main Project' ) ),
        Field::make( 'multiselect', 'jawda_project', __( 'Main Project' ) )->add_options( 'get_my_projects_list' ),

        // REMOVED (Legacy Category Details)
        // Field::make( 'separator', 'jawda_separator_cat', __( 'Category Details' ) ),
        // Field::make('html', 'jawda_listing_category_fields_html', '')
        //     ->set_html(function_exists('jawda_render_listing_category_fields') ? jawda_render_listing_category_fields() : ''),

        Field::make('separator', 'jawda_separator_location_tab', __('Location', 'jawda')),
        Field::make('html', 'jawda_property_location_hook', '')
            ->set_html('<div id="jawda-project-location-placeholder" class="jawda-project-location-placeholder"></div>'),
        Field::make('multiselect', 'jawda_project_feature_ids', __('Featured (Amenities / Facilities)', 'jawda'))
            ->set_options('jawda_get_project_feature_options_for_properties')
            ->set_help_text(__('Choose the amenities, facilities, or highlights available for this unit.', 'jawda')),

        // Property details
        Field::make( 'separator', 'jawda_separator_003', __( 'Property details' ) ),
        Field::make( 'select', '_jawda_market_type_id', __( 'Market Type', 'jawda' ) )
            ->set_options( 'jawda_get_market_types_for_cf' ),
        Field::make( 'text', 'jawda_bedrooms', __( 'bedrooms' ) ),
        Field::make( 'text', 'jawda_bathrooms', __( 'bathrooms' ) ),
        Field::make( 'text', 'jawda_garage', __( 'garage' ) ),
        Field::make( 'text', 'jawda_price', __( 'price' ) ),
        Field::make( 'text', 'jawda_size', __( 'size' ) ),
        Field::make( 'text', 'jawda_year', __( 'Receipt date' ) ),
        Field::make( 'text', 'jawda_location', __( 'location' ) ),
        Field::make( 'text', 'jawda_payment_systems', __( 'Payment Systems' ) ),
        Field::make( 'text', 'jawda_finishing', __( 'finishing' ) ),

        Field::make( 'separator', 'jawda_separator_004', __( 'Property Plan' ) ),
        Field::make( 'image', 'jawda_priperty_plan', __( 'Plan' ) ),

    ) ) 
    ->add_tab( __( 'Gallery' ), array(

      // Gallery
      Field::make( 'separator', 'jawda_separator_001', __( 'Property photos' ) ),
      Field::make( 'media_gallery', 'jawda_attachments', __( 'Property Gallery' ) ),


    ) )


    ->add_tab( __( 'Video' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0c1', __( 'Property Video' ) ),
      Field::make( 'text', 'jawda_video_url', __( 'youtube video url' ) ),


    ) )

    ->add_tab( __( 'Map' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0b1', __( 'Property On Map' ) ),
      Field::make('html', 'jawda_property_location_html', '')
          ->set_html(function() {
              global $post;

              return jawda_render_location_tab_html($post);
          }),


    ) )


    ->add_tab( __( 'FAQ' ), array(

      Field::make( 'separator', 'jawda_separator_0d1', __( 'Frequently Asked Questions' ) ),

      Field::make( 'complex', 'jawda_faq', __( 'Questions' ) )
          ->add_fields( array(
              Field::make( 'text', 'jawda_faq_q', __( 'Question' ) ),
              Field::make( 'textarea', 'jawda_faq_a', __( 'Answer' ) ),
          )
        )


    ) );


  }

}


/**
 * Legacy Carbon Fields location fields have been replaced with a custom meta box
 * to ensure dependable storage of governorate, city, and district selections.
 */
if (!function_exists('jawda_is_arabic_locale')) {
    function jawda_is_arabic_locale() {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();

        return (bool) preg_match('/^ar/i', (string) $locale);
    }
}

add_action('add_meta_boxes', 'jawda_register_project_location_meta_box');
function jawda_register_project_location_meta_box() {
    $title = jawda_is_arabic_locale() ? 'الموقع' : __('Location', 'jawda');

    // Removed 'catalogs' from post types array
    add_meta_box(
        'jawda-project-location',
        $title,
        'jawda_render_project_location_meta_box',
        ['projects', 'property'],
        'normal',
        'high'
    );
}

/**
 * Build a unified set of arguments for rendering the location picker widget.
 *
 * @param WP_Post|int $post Current post instance or ID.
 *
 * @return array
 */
function jawda_get_location_picker_args($post) {
    $post = $post instanceof WP_Post ? $post : get_post($post);

    if (!$post || !current_user_can('edit_post', $post->ID)) {
        return [];
    }

    if (!function_exists('jawda_render_location_picker')) {
        require_once get_template_directory() . '/app/templates/locations/location-picker.php';
    }

    $is_property = ($post->post_type === 'property');
    $project_id  = ($is_property && class_exists('Jawda_Listing_Location_Service'))
        ? Jawda_Listing_Location_Service::get_linked_project_id($post->ID)
        : 0;

    $inherit_location = ($is_property && class_exists('Jawda_Listing_Location_Service'))
        ? Jawda_Listing_Location_Service::should_inherit_location($post->ID)
        : false;

    if ($is_property && class_exists('Jawda_Listing_Location_Service')) {
        $saved_location = Jawda_Listing_Location_Service::get_location($post->ID);
    } else {
        $saved_location = class_exists('Jawda_Location_Service')
            ? Jawda_Location_Service::get_location_for_post($post->ID)
            : null;
    }

    $gov_id      = $saved_location ? absint($saved_location['ids']['governorate']) : absint(get_post_meta($post->ID, 'loc_governorate_id', true));
    $city_id     = $saved_location ? absint($saved_location['ids']['city']) : absint(get_post_meta($post->ID, 'loc_city_id', true));
    $district_id = $saved_location ? absint($saved_location['ids']['district']) : absint(get_post_meta($post->ID, 'loc_district_id', true));

    $is_ar = jawda_is_arabic_locale();
    $options_language = function_exists('jawda_locations_normalize_language')
        ? jawda_locations_normalize_language('both', $is_ar ? 'ar' : 'en')
        : 'both';

    $labels = [
        'gov'      => $is_ar ? 'المحافظة' : __('Governorate', 'jawda'),
        'city'     => $is_ar ? 'المدينة' : __('City', 'jawda'),
        'district' => $is_ar ? 'المنطقة/الحي' : __('District / Neighborhood', 'jawda'),
    ];

    $placeholders = [
        'select_gov'  => function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'jawda'), $options_language)
            : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'jawda')),
        'select_city' => function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'jawda'), $options_language)
            : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'jawda')),
        'select_city_first' => function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'jawda'), $options_language)
            : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'jawda')),
        'select_gov_first'  => function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'jawda'), $options_language)
            : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'jawda')),
        'select_district'   => function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المنطقة/الحي —', __('— Select District / Neighborhood —', 'jawda'), $options_language)
            : ($is_ar ? '— اختر المنطقة/الحي —' : __('— Select District / Neighborhood —', 'jawda')),
    ];

    $governorates = function_exists('jawda_get_all_governorates') ? jawda_get_all_governorates() : [];
    $cities = ($gov_id && function_exists('jawda_get_cities_by_governorate')) ? jawda_get_cities_by_governorate($gov_id) : [];
    $districts = ($city_id && function_exists('jawda_get_districts_by_city')) ? jawda_get_districts_by_city($city_id) : [];

    $map_meta = $saved_location['map'] ?? (function_exists('carbon_get_post_meta') ? carbon_get_post_meta($post->ID, 'jawda_map') : []);

    $inherit_data = [
        'enabled'     => $is_property,
        'checked'     => ($inherit_location && $project_id),
        'disabled'    => !$project_id,
        'label'       => $is_ar ? 'استخدام موقع المشروع' : __('Inherit project location', 'jawda'),
        'description' => $project_id
            ? sprintf(
                $is_ar ? 'سيتم استخدام موقع مشروع %s في حالة التفعيل.' : __('Use project %s coordinates when enabled.', 'jawda'),
                get_the_title($project_id) ?: sprintf(__('Project #%d', 'jawda'), $project_id)
            )
            : ($is_ar ? 'أضف ارتباطًا بمشروع لتفعيل الوراثة.' : __('Link this listing to a project to enable inheritance.', 'jawda')),
    ];

    return [
        'context'      => $is_property ? 'listing-meta-box' : 'project-meta-box',
        'selected'     => [
            'governorate' => $gov_id,
            'city'        => $city_id,
            'district'    => $district_id,
        ],
        'governorates' => $governorates,
        'cities'       => $cities,
        'districts'    => $districts,
        'map'          => $map_meta,
        'placeholders' => $placeholders,
        'labels'       => $labels,
        'inherit'      => $inherit_data,
        'language'     => $options_language,
    ];
}

/**
 * Render the Project Location meta box with governorate, city, and district selects.
 *
 * @param WP_Post $post Current post instance.
 */
function jawda_render_project_location_meta_box($post) {
    $args = jawda_get_location_picker_args($post);

    if (!$args) {
        return;
    }

    wp_nonce_field('jawda_save_project_location', 'jawda_project_location_nonce');

    jawda_render_location_picker($args);
}

/**
 * Render the location picker inside the Carbon Fields “Map” tab.
 *
 * @param WP_Post|int|null $post
 *
 * @return string
 */
function jawda_render_location_tab_html($post = null) {
    $args = jawda_get_location_picker_args($post);

    if (!$args) {
        return '';
    }

    $args['context'] = 'map-tab-preview';
    $args['field_names'] = [
        'governorate' => 'loc_governorate_id_preview',
        'city'        => 'loc_city_id_preview',
        'district'    => 'loc_district_id_preview',
        'lat'         => 'jawda_project_latitude_preview',
        'lng'         => 'jawda_project_longitude_preview',
    ];
    $args['readonly'] = true;

    ob_start();
    jawda_render_location_picker($args);

    return ob_get_clean();
}

/**
 * Persist project location selections when the post is saved.
 *
 * @param int $post_id Post identifier.
 */
function jawda_save_project_location_meta_box($post_id) {
    if (!isset($_POST['jawda_project_location_nonce']) ||
        !wp_verify_nonce(wp_unslash($_POST['jawda_project_location_nonce']), 'jawda_save_project_location')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $post_type = get_post_type($post_id);

    $gov_id = isset($_POST['loc_governorate_id']) ? absint($_POST['loc_governorate_id']) : 0;
    $city_id = isset($_POST['loc_city_id']) ? absint($_POST['loc_city_id']) : 0;
    $district_id = isset($_POST['loc_district_id']) ? absint($_POST['loc_district_id']) : 0;
    $inherit_location = ($post_type === 'property' && isset($_POST['jawda_inherit_project_location']));

    $lat_raw = isset($_POST['jawda_project_latitude']) ? wp_unslash($_POST['jawda_project_latitude']) : '';
    $lng_raw = isset($_POST['jawda_project_longitude']) ? wp_unslash($_POST['jawda_project_longitude']) : '';

    if (function_exists('jawda_locations_normalize_coordinate')) {
        $lat = jawda_locations_normalize_coordinate($lat_raw);
        $lng = jawda_locations_normalize_coordinate($lng_raw);
    } else {
        $normalize = static function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $value = str_replace(',', '.', $value);

            return is_numeric($value) ? $value : null;
        };

        $lat = $normalize($lat_raw);
        $lng = $normalize($lng_raw);
    }

    $existing_map = function_exists('carbon_get_post_meta')
        ? carbon_get_post_meta($post_id, 'jawda_map')
        : [];

    $map_zoom = 13;
    $map_address = '';

    if (is_array($existing_map)) {
        if (isset($existing_map['zoom']) && is_numeric($existing_map['zoom'])) {
            $map_zoom = (int) $existing_map['zoom'];
        }

        if (!empty($existing_map['address'])) {
            $map_address = (string) $existing_map['address'];
        }
    }

    $map_payload = [
        'lat'     => $lat !== null ? (string) $lat : '',
        'lng'     => $lng !== null ? (string) $lng : '',
        'zoom'    => $map_zoom,
        'address' => $map_address,
    ];

    if ($post_type === 'property' && class_exists('Jawda_Listing_Location_Service')) {
        Jawda_Listing_Location_Service::save_location($post_id, [
            'governorate_id'          => $gov_id,
            'city_id'                 => $city_id,
            'district_id'             => $district_id,
            'map'                     => $map_payload,
            'overwrite_map'           => true,
            'inherit_project_location'=> $inherit_location,
        ]);

        return;
    }

    if (class_exists('Jawda_Location_Service')) {
        Jawda_Location_Service::save_location_for_post($post_id, [
            'governorate_id' => $gov_id,
            'city_id'        => $city_id,
            'district_id'    => $district_id,
            'map'            => $map_payload,
            'overwrite_map'  => true,
        ]);

        return;
    }

    if (function_exists('jawda_update_project_location')) {
        jawda_update_project_location($post_id, $gov_id, $city_id, $district_id);
    }

    if (function_exists('carbon_set_post_meta')) {
        carbon_set_post_meta($post_id, 'jawda_map', $map_payload);
    } else {
        update_post_meta($post_id, 'jawda_map', $map_payload);
    }
}
add_action('save_post_projects', 'jawda_save_project_location_meta_box', 20);
// Removed save_post_catalogs hook
add_action('save_post_property', 'jawda_save_project_location_meta_box', 20);


/**
 * Displays admin notice if location validation failed during save.
 */
add_action( 'admin_notices', 'jawda_location_validation_notice' );
function jawda_location_validation_notice() {
    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }
    $screen = get_current_screen();
    // Removed 'catalogs' from check
    if ( ! $screen || ! in_array( $screen->post_type, ['projects', 'property'] ) ) {
        return;
    }

    global $post;
    if ( ! $post ) return;

    $error = get_transient( 'jawda_location_error_' . $post->ID );
    if ( $error ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error ); ?></p>
        </div>
        <?php
        delete_transient( 'jawda_location_error_' . $post->ID );
    }
}





/* -----------------------------------------------------------------------------
# Project Categories helper inside Carbon tab
----------------------------------------------------------------------------- */

/**
 * Renders quick-edit style category fields inside the Carbon Project Details tab.
 *
 * @return string
 */
function jawda_render_project_category_fields() {
    $post_id = get_the_ID();

    if (!$post_id && isset($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id = absint($_GET['post']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

    $language = function_exists('jawda_categories_determine_language')
        ? jawda_categories_determine_language('auto')
        : ($is_ar ? 'ar' : 'en');

    $placeholders = class_exists('Jawda_Property_Taxonomy_Helper')
        ? Jawda_Property_Taxonomy_Helper::get_placeholders($language)
        : [
            'select_main_category'       => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر التصنيف الرئيسي —', __('— Select Main Category —', 'jawda'), $language)
                : ($is_ar ? '— اختر التصنيف الرئيسي —' : __('— Select Main Category —', 'jawda')),
            'select_main_category_first' => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر التصنيف الرئيسي أولًا —', __('— Select the main category first —', 'jawda'), $language)
                : ($is_ar ? '— اختر التصنيف الرئيسي أولًا —' : __('— Select the main category first —', 'jawda')),
            'select_property_types'      => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('اختر أنواع الوحدات', __('Select property types', 'jawda'), $language)
                : ($is_ar ? 'اختر أنواع الوحدات' : __('Select property types', 'jawda')),
            'no_types'                   => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('لا توجد أنواع متاحة لهذا التصنيف.', __('No property types available for this category.', 'jawda'), $language)
                : ($is_ar ? 'لا توجد أنواع متاحة لهذا التصنيف.' : __('No property types available for this category.', 'jawda')),
            'no_categories'              => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('لا توجد تصنيفات متاحة.', __('No categories available.', 'jawda'), $language)
                : ($is_ar ? 'لا توجد تصنيفات متاحة.' : __('No categories available.', 'jawda')),
        ];

    $main_categories = function_exists('jawda_get_main_categories_options') ? (array) jawda_get_main_categories_options() : [];

    $placeholder_main = isset($placeholders['select_main_category']) ? (string) $placeholders['select_main_category'] : '';
    if (isset($main_categories[''])) {
        $placeholder_main = (string) $main_categories[''];
        unset($main_categories['']);
    }

    $selected_main           = '';
    $selected_types          = [];
    $selected_main_categories = [];

    if (class_exists('Jawda_Property_Taxonomy_Helper') && $post_id) {
        $selection      = Jawda_Property_Taxonomy_Helper::get_saved_selection($post_id);
        $selected_main  = isset($selection['main_category']) ? (string) $selection['main_category'] : '';
        $selected_types = array_values(array_unique(array_map('strval', (array) ($selection['property_types'] ?? []))));

        $selected_main_categories = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'strval',
                        (array) ($selection['main_categories'] ?? ($selected_main !== '' ? [$selected_main] : []))
                    )
                )
            )
        );
    } elseif ($post_id) {
        $selected_main = get_post_meta($post_id, 'jawda_main_category_id', true);

        $raw_types = get_post_meta($post_id, 'jawda_property_type_ids', true);

        if ($raw_types !== '' && $raw_types !== null) {
            $selected_types = is_array($raw_types) ? $raw_types : [$raw_types];
        }

        if ($selected_main !== '') {
            $selected_main_categories = [$selected_main];
        }
    }

    if (!$selected_main && $selected_main_categories) {
        $selected_main = (string) reset($selected_main_categories);
    }

    $selected_main_categories = array_values(array_unique(array_filter($selected_main_categories)));

    $selected_types = array_values(array_unique(array_filter(array_map('strval', (array) $selected_types))));

    $types_by_category = function_exists('jawda_get_property_types_by_main_category')
            ? jawda_get_property_types_by_main_category(null)
            : [];

    $allowed_types = [];

    if ($selected_main_categories && is_array($types_by_category)) {
        foreach ($selected_main_categories as $selected_main) {
            $key = (string) $selected_main;
            if (!isset($types_by_category[$key]['types'])) {
                continue;
            }

            foreach ((array) $types_by_category[$key]['types'] as $type_id => $type) {
                if (!is_array($type)) {
                    continue;
                }

                $id = isset($type['id'])
                    ? (string) $type['id']
                    : (isset($type['term_id']) ? (string) $type['term_id'] : (string) $type_id);

                if ($id === '') {
                    continue;
                }

                $allowed_types[$id] = $type;
            }
        }
    }

    ob_start();
    ?>
    <div class="jawda-project-category-fields">
        <?php wp_nonce_field('jawda_save_project_categories', 'jawda_project_categories_nonce'); ?>
        <div class="jawda-project-category-row">
            <label for="jawda_main_category_ids"><strong><?php echo esc_html($is_ar ? 'Main Category' : __('Main Category', 'jawda')); ?></strong></label>
            <select name="jawda_main_category_ids[]" id="jawda_main_category_ids" class="jawda-meta-main-category-select" style="width:100%;" multiple="multiple" size="5" data-placeholder="<?php echo esc_attr($placeholder_main); ?>">
                <?php foreach ($main_categories as $cat_id => $label) :
                    $cat_id = (string) $cat_id;
                    ?>
                    <option value="<?php echo esc_attr($cat_id); ?>" <?php selected(in_array($cat_id, $selected_main_categories, true), true); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="jawda-project-category-row" style="margin-top:10px; display:none;">
            <label for="jawda_property_type_ids"><strong><?php echo esc_html($is_ar ? 'Property Types' : __('Property Types', 'jawda')); ?></strong></label>
            <select name="jawda_property_type_ids[]" id="jawda_property_type_ids" class="jawda-meta-property-type-select" multiple size="6" style="width:100%;">

            </select>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders category fields for listings with project inheritance support.
 */
function jawda_render_listing_category_fields() {
    $post_id = get_the_ID();

    if (!$post_id && isset($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id = absint($_GET['post']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

    $language = function_exists('jawda_categories_determine_language')
        ? jawda_categories_determine_language('auto')
        : ($is_ar ? 'ar' : 'en');

    $placeholders = class_exists('Jawda_Property_Taxonomy_Helper')
        ? Jawda_Property_Taxonomy_Helper::get_placeholders($language)
        : [
            'select_main_category'       => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر التصنيف الرئيسي —', __('— Select Main Category —', 'jawda'), $language)
                : ($is_ar ? '— اختر التصنيف الرئيسي —' : __('— Select Main Category —', 'jawda')),
            'select_main_category_first' => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر التصنيف الرئيسي أولًا —', __('— Select the main category first —', 'jawda'), $language)
                : ($is_ar ? '— اختر التصنيف الرئيسي أولًا —' : __('— Select the main category first —', 'jawda')),
            'select_property_types'      => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('اختر أنواع الوحدات', __('Select property types', 'jawda'), $language)
                : ($is_ar ? 'اختر أنواع الوحدات' : __('Select property types', 'jawda')),
            'no_types'                   => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('لا توجد أنواع متاحة لهذا التصنيف.', __('No property types available for this category.', 'jawda'), $language)
                : ($is_ar ? 'لا توجد أنواع متاحة لهذا التصنيف.' : __('No property types available for this category.', 'jawda')),
            'no_categories'              => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('لا توجد تصنيفات متاحة.', __('No categories available.', 'jawda'), $language)
                : ($is_ar ? 'لا توجد تصنيفات متاحة.' : __('No categories available.', 'jawda')),
        ];

    $main_categories = function_exists('jawda_get_main_categories_options') ? (array) jawda_get_main_categories_options() : [];

    $placeholder_main = isset($placeholders['select_main_category']) ? (string) $placeholders['select_main_category'] : '';
    if (isset($main_categories[''])) {
        $placeholder_main = (string) $main_categories[''];
        unset($main_categories['']);
    }

    $selection = [
            'main_categories' => [],
            'property_types'  => [],
            'inherits'        => false,
            'inherited_from'  => 0,
        ];
    // TODO: Implement new lookup service retrieval here

    $inherits   = !empty($selection['inherits']);
    $project_id = isset($selection['inherited_from']) ? absint($selection['inherited_from']) : 0;

    $selected_main_categories = array_values(array_unique(array_filter(array_map('strval', (array) ($selection['main_categories'] ?? [])))));
    $selected_types = array_values(array_unique(array_filter(array_map('strval', (array) ($selection['property_types'] ?? [])))));

    if (!$selected_main_categories && isset($selection['main_category'])) {
        $selected_main_categories = [(string) $selection['main_category']];
    }

    $types_by_category = function_exists('jawda_get_property_types_by_main_category')
            ? jawda_get_property_types_by_main_category(null)
            : [];

    $allowed_types = [];

    if ($selected_main_categories && is_array($types_by_category)) {
        foreach ($selected_main_categories as $selected_main) {
            $key = (string) $selected_main;
            if (!isset($types_by_category[$key]['types'])) {
                continue;
            }

            foreach ((array) $types_by_category[$key]['types'] as $type_id => $type) {
                if (!is_array($type)) {
                    continue;
                }

                $id = isset($type['id'])
                    ? (string) $type['id']
                    : (isset($type['term_id']) ? (string) $type['term_id'] : (string) $type_id);

                if ($id === '') {
                    continue;
                }

                $allowed_types[$id] = $type;
            }
        }
    }

    ob_start();
    ?>
    <div class="jawda-project-category-fields jawda-listing-category-fields">
        <?php wp_nonce_field('jawda_save_property_categories', 'jawda_property_categories_nonce'); ?>
        <?php if ($project_id) : ?>
            <div class="jawda-project-category-row">
                <label>
                    <input type="checkbox" name="jawda_inherit_project_categories" value="1" <?php checked($inherits && $project_id); ?> />
                    <strong><?php echo esc_html($is_ar ? 'استخدام تصنيفات المشروع' : __('Inherit project categories/types', 'jawda')); ?></strong>
                </label>
                <p class="description">
                    <?php
                    $project_label = get_the_title($project_id) ?: sprintf(__('Project #%d', 'jawda'), $project_id);
                    echo esc_html($is_ar
                        ? sprintf('سيتم استخدام تصنيفات مشروع %s في حالة التفعيل.', $project_label)
                        : sprintf(__('Use project %s selections when enabled.', 'jawda'), $project_label));
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="jawda-project-category-row">
            <label for="jawda_main_category_ids"><strong><?php echo esc_html($is_ar ? 'Main Category' : __('Main Category', 'jawda')); ?></strong></label>
            <select name="jawda_main_category_ids[]" id="jawda_main_category_ids" class="jawda-meta-main-category-select" style="width:100%;" multiple="multiple" size="5" data-placeholder="<?php echo esc_attr($placeholder_main); ?>">
                <?php foreach ($main_categories as $cat_id => $label) :
                    $cat_id = (string) $cat_id;
                    ?>
                    <option value="<?php echo esc_attr($cat_id); ?>" <?php selected(in_array($cat_id, $selected_main_categories, true), true); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="jawda-project-category-row" style="margin-top:10px;">
            <label for="jawda_property_type_ids"><strong><?php echo esc_html($is_ar ? 'Property Types' : __('Property Types', 'jawda')); ?></strong></label>
            <select name="jawda_property_type_ids[]" id="jawda_property_type_ids" class="jawda-meta-property-type-select" multiple size="6" style="width:100%;">
                <?php if (empty($selected_main_categories)) : ?>
                    <option value="" disabled><?php echo esc_html($placeholders['select_main_category_first'] ?? ''); ?></option>
                <?php elseif (empty($allowed_types)) : ?>
                    <option value="" disabled><?php echo esc_html($placeholders['no_types'] ?? ''); ?></option>
                <?php else : ?>
                    <option value="" disabled><?php echo esc_html($placeholders['select_property_types'] ?? ''); ?></option>
                    <?php foreach ($allowed_types as $type_id => $type) :
                        if (!is_array($type)) {
                            continue;
                        }
                        $id = isset($type['id']) ? (string) $type['id'] : (string) $type_id;
                        if ($id === '') {
                            continue;
                        }
                        $label = $type['label'] ?? ($type['name'] ?? ($type['name_en'] ?? ($type[ 'slug_ar' ] ?? $id)));
                        ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected(in_array($id, $selected_types, true)); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


/* -----------------------------------------------------------------------------
# Term Meta
----------------------------------------------------------------------------- */


if ( !function_exists('jawda_meta_project') ) {

  add_action( 'carbon_fields_register_fields', 'jawda_meta_project' );
  function jawda_meta_project() {

    // Options
    $meta_package =
    Container::make( 'post_meta', 'Project Details' )
      ->where( 'post_type', '=', 'projects' )
      ->add_tab( __( 'Project Details' ), array(

        // REMOVED (Legacy Category Details)
        // Field::make( 'separator', 'jawda_separator_cat', __( 'Category Details' ) ),
        // Field::make('html', 'jawda_project_category_fields_html', '')
        //     ->set_html(jawda_render_project_category_fields()),

        
        // Services fields removed (Project Features / Amenities / Facilities)


        // Property details
        Field::make( 'separator', 'jawda_separator_003', __( 'Project details' ) ),
        Field::make( 'text', 'jawda_price', __( 'price' ) ),
        Field::make( 'text', 'jawda_installment', __( 'installment' ) ),
        Field::make( 'text', 'jawda_down_payment', __( 'down payment' ) ),
        Field::make( 'text', 'jawda_size', __( 'size' ) ),
        Field::make( 'text', 'jawda_year', __( 'Receipt date' ) ),
        Field::make( 'text', 'jawda_location', __( 'location' ) ),
        Field::make( 'text', 'jawda_unit_types', __( 'Unit types' ) ),

        Field::make( 'text', 'jawda_payment_systems', __( 'Payment Systems' ) ),
        Field::make( 'text', 'jawda_finishing', __( 'finishing' ) ),

        Field::make( 'separator', 'jawda_separator_004', __( 'Property Plan' ) ),
        Field::make( 'image', 'jawda_priperty_plan', __( 'Plan' ) ),
      ) )

      ->add_tab( __( 'Payment Plans', 'jawda' ), function_exists('jawda_get_payment_plan_fields') ? jawda_get_payment_plan_fields() : array() )

      ->add_tab( __( 'Gallery' ), array(

      // Gallery
      Field::make( 'separator', 'jawda_separator_001', __( 'Property photos' ) ),
      Field::make( 'media_gallery', 'jawda_attachments', __( 'Property Gallery' ) ),


      ) )


      ->add_tab( __( 'Video' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0c1', __( 'Property Video' ) ),
      Field::make( 'text', 'jawda_video_url', __( 'youtube video url' ) ),


      ) )

      ->add_tab( __( 'Map' ), array(

      // map
      Field::make( 'separator', 'jawda_separator_0b1', __( 'Property On Map' ) ),
      Field::make('html', 'jawda_project_location_html', '')
          ->set_html(function() {
              global $post;

              return jawda_render_location_tab_html($post);
          }),


      ) )

      ->add_tab( __( 'FAQ' ), array(

      Field::make( 'separator', 'jawda_separator_0d1', __( 'Frequently Asked Questions' ) ),

      Field::make( 'complex', 'jawda_faq', __( 'Questions' ) )
          ->add_fields( array(
              Field::make( 'text', 'jawda_faq_q', __( 'Question' ) ),
              Field::make( 'textarea', 'jawda_faq_a', __( 'Answer' ) ),
          )
        )


      ) );

  }

}

add_action('save_post_projects', 'jawda_save_project_category_fields', 15);
function jawda_save_project_category_fields($post_id) {
    if (!isset($_POST['jawda_project_categories_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jawda_project_categories_nonce'])), 'jawda_save_project_categories')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $main_categories = null;
    if (isset($_POST['jawda_main_category_ids'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $main_categories = (array) $_POST['jawda_main_category_ids']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    } elseif (isset($_POST['jawda_main_category_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $main_categories = [$_POST['jawda_main_category_id']]; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    // Property types are no longer saved from the project screen.
    $property_types = null;

    if (function_exists('jawda_update_project_category_and_types')) {
        jawda_update_project_category_and_types($post_id, $main_categories, $property_types);
    }
}

add_action('save_post_property', 'jawda_save_listing_category_fields', 15);
function jawda_save_listing_category_fields($post_id) {
    if (!isset($_POST['jawda_property_categories_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jawda_property_categories_nonce'])), 'jawda_save_property_categories')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $inherits = isset($_POST['jawda_inherit_project_categories']); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    $main_categories = isset($_POST['jawda_main_category_ids'])
        ? (array) $_POST['jawda_main_category_ids'] // phpcs:ignore WordPress.Security.NonceVerification.Missing
        : null;

    $property_types = isset($_POST['jawda_property_type_ids'])
        ? (array) $_POST['jawda_property_type_ids'] // phpcs:ignore WordPress.Security.NonceVerification.Missing
        : null;

    if (class_exists('Jawda_Listing_Category_Service')) {
        // Jawda_Listing_Category_Service::save_selection($post_id, $main_categories, $property_types, $inherits);
    }
}









add_action( 'carbon_fields_register_fields', 'jawda_terms_meta' );
function jawda_terms_meta() {

  // Options
  $basic_options_container =
  Container::make( 'term_meta', __( 'Photo' ) )
    ->where( 'term_taxonomy', 'IN', ['projects_type','projects_category','projects_tag','projects_area','property_label','property_type','property_feature','property_city','property_area','property_state','property_country','property_status'] )
    ->add_fields( array(
        Field::make( 'image', 'jawda_thumb', __( 'Cover photo' ) ),
    )
  );



}



add_action( 'carbon_fields_register_fields', 'jawda_city_terms_meta' );
function jawda_city_terms_meta() {

  // Options
  $basic_options_container =
  Container::make( 'term_meta', __( 'State' ) )
    ->where( 'term_taxonomy', 'IN', ['property_city'] )
    ->add_fields( array(
      Field::make( 'select', 'jawda_city_state', __( 'Choose State' ) )->set_options( 'get_my_states_list' ),
    )
  );



}




/* ----------------------------------------------------------------------------
# initiative - REMOVED (Legacy Catalog Meta Box)
---------------------------------------------------------------------------- */

// The "catalogs" meta box has been removed.

/* ------  ----------- */

function get_my_projects_types_list(){
  $return = [];
  $terms = get_terms( 'projects_type', array('hide_empty' => false,) );
  $return[] = '';
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}

function get_my_properties_state_list(){
  $return = [];
  $terms = get_terms( 'property_state', array('hide_empty' => false,) );
  $return[] = '';
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}

function get_my_properties_types_list(){
  $return = [];
  $terms = get_terms( 'property_type', array('hide_empty' => false,) );
  $return[] = '';
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}
// carbon_get_post_meta( get_the_ID(), 'jawda_location' );

// Removed jawda_hide_location_map_for_catalogs

function jawda_get_main_categories_options() {
    $bridge_file = get_template_directory() . '/app/inc/lookups/bridge.php';

    if (file_exists($bridge_file)) {
        require_once $bridge_file;
    }

    if (function_exists('jawda_get_main_categories_options_bridge')) {
        return jawda_get_main_categories_options_bridge();
    }

    $placeholder_ar = '— اختر التصنيف الرئيسي —';
    $placeholder_en = __('— Select Main Category —', 'jawda');
    $placeholder    = function_exists('jawda_locations_get_placeholder')
        ? jawda_locations_get_placeholder($placeholder_ar, $placeholder_en, 'both')
        : trim($placeholder_ar . ' / ' . $placeholder_en);

    return ['' => $placeholder];
}

/**
 * Rebuilds the internal linking graph for all projects.
 *
 * This function implements a fair-linking algorithm to ensure each project
 * is recommended a roughly equal number of times across the site.
 * It groups projects by main category and governorate, then creates a
- * circular linking structure within each group.
 */
function jawda_rebuild_internal_project_links() {
    global $wpdb;

    // Fetch all published projects with their necessary metadata
    $projects_query = "
        SELECT p.ID,
               pm_cat.meta_value AS jawda_main_category_id,
               pm_gov.meta_value AS loc_governorate_id,
               pm_city.meta_value AS loc_city_id
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = 'jawda_main_category_id'
        LEFT JOIN {$wpdb->postmeta} pm_gov ON p.ID = pm_gov.post_id AND pm_gov.meta_key = 'loc_governorate_id'
        LEFT JOIN {$wpdb->postmeta} pm_city ON p.ID = pm_city.post_id AND pm_city.meta_key = 'loc_city_id'
        WHERE p.post_type = 'projects' AND p.post_status = 'publish'
    ";
    $all_projects = $wpdb->get_results($projects_query);

    // Prepare projects and create groupings for both city and governorate levels
    $projects_data = [];
    $city_groups = [];
    $gov_groups = [];

    foreach ($all_projects as $project) {
        $project_id = (int)$project->ID;
        $projects_data[$project_id] = [
            'id' => $project_id,
            'category' => !empty($project->jawda_main_category_id) ? $project->jawda_main_category_id : 'uncategorized',
            'city' => !empty($project->loc_city_id) ? $project->loc_city_id : null,
            'gov' => !empty($project->loc_governorate_id) ? $project->loc_governorate_id : null,
            'lang' => function_exists('pll_get_post_language') ? pll_get_post_language($project_id) : 'default',
        ];
    }

    foreach ($projects_data as $project_id => $data) {
        // Group by City
        if ($data['city']) {
            $city_key = $data['category'] . '_' . $data['city'] . '_' . $data['lang'];
            if (!isset($city_groups[$city_key])) $city_groups[$city_key] = [];
            $city_groups[$city_key][] = $project_id;
        }
        // Group by Governorate
        if ($data['gov']) {
            $gov_key = $data['category'] . '_' . $data['gov'] . '_' . $data['lang'];
            if (!isset($gov_groups[$gov_key])) $gov_groups[$gov_key] = [];
            $gov_groups[$gov_key][] = $project_id;
        }
    }

    // Sort all group arrays by ID to ensure a stable, predictable order for the circular algorithm
    foreach($city_groups as &$group) { sort($group); }
    foreach($gov_groups as &$group) { sort($group); }
    unset($group);

    // Helper function to get the next N unique items in a circular array
    $get_circular_items = function($current_item, $array, $count) {
        $total_items = count($array);
        if ($total_items <= 1) return [];

        $current_index = array_search($current_item, $array);
        if ($current_index === false) return [];

        $result = [];
        for ($i = 1; $i < $total_items; $i++) {
            $next_index = ($current_index + $i) % $total_items;
            $result[] = $array[$next_index];
        }
        return array_slice($result, 0, $count);
    };

    // Main loop to calculate related IDs for each project
    foreach ($projects_data as $project_id => $data) {
        $final_related_ids = [];
        $needed = 5;

        // 1. Get from City level
        if ($data['city']) {
            $city_key = $data['category'] . '_' . $data['city'] . '_' . $data['lang'];
            if (isset($city_groups[$city_key])) {
                $city_peers = $city_groups[$city_key];
                $city_related = $get_circular_items($project_id, $city_peers, $needed);
                $final_related_ids = array_merge($final_related_ids, $city_related);
                $needed -= count($city_related);
            }
        }

        // 2. Get from Governorate level if still needed
        if ($needed > 0 && $data['gov']) {
            $gov_key = $data['category'] . '_' . $data['gov'] . '_' . $data['lang'];
            if (isset($gov_groups[$gov_key])) {
                // Exclude the project itself AND any projects already found at the city level
                $exclude_ids = array_merge([$project_id], $final_related_ids);

                // Only proceed if there are potential candidates in the governorate group
                if (count($gov_groups[$gov_key]) > count($exclude_ids)) {
                    // To maintain the "fair" circular order, we must find the project's position in the full list
                    // then get the next items, filtering out the ones we've already used.
                    $full_gov_circle = $get_circular_items($project_id, $gov_groups[$gov_key], 100); // Get a large number of potential candidates
                    $gov_related_candidates = array_diff($full_gov_circle, $exclude_ids);

                    $gov_related = array_slice(array_values($gov_related_candidates), 0, $needed);
                    $final_related_ids = array_merge($final_related_ids, $gov_related);
                }
            }
        }

        // Final cleanup to ensure no duplicates and max of 5
        $final_related_ids = array_unique(array_map('intval', $final_related_ids));

        if (!empty($final_related_ids)) {
            update_post_meta($project_id, '_related_projects_ids', array_slice($final_related_ids, 0, 5));
        } else {
            delete_post_meta($project_id, '_related_projects_ids');
        }
    }
}


/**
 * Trigger for rebuilding the project links.
 *
 * @param int $post_id Post ID.
 */
function jawda_trigger_rebuild_on_save($post_id) {
    if (get_post_type($post_id) === 'projects') {
        jawda_rebuild_internal_project_links();
    }
}
add_action('save_post_projects', 'jawda_trigger_rebuild_on_save');


/**
 * Trigger for rebuilding links when a project is deleted.
 *
 * @param int $post_id Post ID.
 */
function jawda_trigger_rebuild_on_delete($post_id) {
    if (get_post_type($post_id) === 'projects') {
        // Use a shutdown function to ensure the post is deleted before rebuilding.
        register_shutdown_function('jawda_rebuild_internal_project_links');
    }
}
add_action('delete_post', 'jawda_trigger_rebuild_on_delete');


function jawda_collect_property_types_map() {
    if (!function_exists('jawda_get_property_types_by_main_category')) {
        return [];
    }

    $raw_map = jawda_get_property_types_by_main_category(null);
    $map     = [];

    foreach ($raw_map as $category_id => $category) {
        $map[$category_id] = [
            'id'      => isset($category['id']) ? (string) $category['id'] : (string) $category_id,
            'label'   => isset($category['label']) ? (string) $category['label'] : ($category['name'] ?? ''),
             'slug_ar'  => isset($category[ 'slug_ar' ]) ? (string) $category[ 'slug_ar' ] : '',
            'name_en' => isset($category['name_en']) ? (string) $category['name_en'] : '',
            'types'   => [],
        ];

        if (!empty($category['types']) && is_array($category['types'])) {
            $map[$category_id]['types'] = array_values($category['types']);
        }
    }

    return $map;
}

function jawda_get_selected_main_category_for_request() {
    $post_id = 0;

    if (isset($_GET['post'])) {
        $post_id = absint($_GET['post']);
    } elseif (isset($_POST['post_ID'])) {
        $post_id = absint($_POST['post_ID']);
    } else {
        global $post;
        if ($post && isset($post->ID)) {
            $post_id = (int) $post->ID;
        }
    }

    if ($post_id <= 0) {
        return 0;
    }

    $value = '';

    if (function_exists('carbon_get_post_meta')) {
        $value = carbon_get_post_meta($post_id, 'jawda_main_category_id');
    } else {
        $value = get_post_meta($post_id, 'jawda_main_category_id', true);
    }

    return absint($value);
}

function jawda_format_property_type_option_label($type) {
    if (!is_array($type)) {
        return '';
    }

    $label = '';

    if (!empty($type['label'])) {
        $label = (string) $type['label'];
    } elseif (!empty($type['name'])) {
        $label = (string) $type['name'];
    } elseif (!empty($type['name_en'])) {
        $label = (string) $type['name_en'];
    } elseif (!empty($type[ 'slug_ar' ])) {
        $label = (string) $type[ 'slug_ar' ];
    }

    if ($label === '' && isset($type['id'])) {
        $label = (string) $type['id'];
    }

    return $label;
}

function jawda_build_property_type_options($category_id = 0) {
    if (!function_exists('jawda_get_property_types_by_main_category')) {
        return [];
    }

    $category_id = absint($category_id);

    $language = function_exists('jawda_categories_determine_language')
        ? jawda_categories_determine_language('auto')
        : ((function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl()) ? 'ar' : 'en');

    $select_property_types = function_exists('jawda_locations_get_placeholder')
        ? jawda_locations_get_placeholder('اختر أنواع الوحدات', __('Select property types', 'jawda'), $language)
        : ($language === 'ar'
            ? 'اختر أنواع الوحدات'
            : ($language === 'en'
                ? __('Select property types', 'jawda')
                : 'اختر أنواع الوحدات / ' . __('Select property types', 'jawda')));

    $select_category_first = function_exists('jawda_locations_get_placeholder')
        ? jawda_locations_get_placeholder('اختر التصنيف الرئيسي أولًا', __('Select the main category first', 'jawda'), $language)
        : ($language === 'ar'
            ? 'اختر التصنيف الرئيسي أولًا'
            : ($language === 'en'
                ? __('Select the main category first', 'jawda')
                : 'اختر التصنيف الرئيسي أولًا / ' . __('Select the main category first', 'jawda')));

    $no_types_placeholder = function_exists('jawda_locations_get_placeholder')
        ? jawda_locations_get_placeholder('لا توجد أنواع متاحة لهذا التصنيف.', __('No property types available for this category.', 'jawda'), $language)
        : ($language === 'ar'
            ? 'لا توجد أنواع متاحة لهذا التصنيف.'
            : ($language === 'en'
                ? __('No property types available for this category.', 'jawda')
                : 'لا توجد أنواع متاحة لهذا التصنيف. / ' . __('No property types available for this category.', 'jawda')));

    $options = ['' => $select_category_first];

    if ($category_id > 0) {
        $types = jawda_get_property_types_by_main_category($category_id);

        if (!empty($types)) {
            $options[''] = $select_property_types;

            foreach ((array) $types as $type) {
                if (empty($type['id'])) {
                    continue;
                }

                $label = jawda_format_property_type_option_label($type);
                $options[(string) $type['id']] = $label;
            }

            return $options;
        }

        $options = ['' => $no_types_placeholder];
    }

    return $options;
}

function jawda_get_all_property_types_for_cf() {
    $selected_category = jawda_get_selected_main_category_for_request();

    return jawda_build_property_type_options($selected_category);
}

function jawda_prepare_property_type_tree_for_js() {
    if (!function_exists('jawda_get_property_types_by_main_category')) {
        return [];
    }

    $map  = jawda_get_property_types_by_main_category(null);
    $tree = [];

    foreach ($map as $category) {
        $types = [];

        if (!empty($category['types']) && is_array($category['types'])) {
            foreach ($category['types'] as $type) {
                $types[] = [
                    'id'      => isset($type['id']) ? (string) $type['id'] : '',
                    'name'    => isset($type['label']) ? (string) $type['label'] : (isset($type['name']) ? (string) $type['name'] : ''),
                     'slug_ar'  => isset($type[ 'slug_ar' ]) ? (string) $type[ 'slug_ar' ] : '',
                    'name_en' => isset($type['name_en']) ? (string) $type['name_en'] : '',
                    'icon_id' => isset($type['icon_id']) ? (string) $type['icon_id'] : '',
                    'icon_url'=> isset($type['icon_url']) ? (string) $type['icon_url'] : '',
                ];
            }
        }

        $tree[] = [
            'id'    => isset($category['id']) ? (string) $category['id'] : '',
            'name'  => isset($category['label']) ? (string) $category['label'] : (isset($category['name']) ? (string) $category['name'] : ''),
             'slug_ar'  => isset($category[ 'slug_ar' ]) ? (string) $category[ 'slug_ar' ] : '',
            'name_en' => isset($category['name_en']) ? (string) $category['name_en'] : '',
            'types' => $types,
        ];
    }

    return $tree;
}

function jawda_fetch_property_types_by_category($category_id) {
    $category_id = absint($category_id);

    if (!$category_id || !function_exists('jawda_get_property_types_by_main_category')) {
        return [];
    }

    $types = jawda_get_property_types_by_main_category($category_id);

    return array_values($types);
}

add_action('admin_enqueue_scripts', 'jawda_enqueue_category_dependency_scripts');
function jawda_enqueue_category_dependency_scripts($hook) {
    global $post;

    if ($hook !== 'post-new.php' && $hook !== 'post.php') {
        return;
    }

    $post_type = '';

    if ($hook === 'post-new.php') {
        if (isset($_GET['post_type'])) {
            $post_type = sanitize_key($_GET['post_type']);
        } elseif (isset($post->post_type)) {
            $post_type = $post->post_type;
        }
    } else {
        $post_type = isset($post->post_type) ? $post->post_type : '';
    }

    if (!in_array($post_type, ['projects', 'property'], true)) {
        return;
    }

    wp_enqueue_script(
        'jawda-project-meta',
        get_template_directory_uri() . '/assets/js/jawda-project-meta.js',
        ['jquery', 'carbon-fields-boot'],
        '3.0.1',
        true
    );

    $post_id = ($hook === 'post-new.php') ? 0 : (isset($post->ID) ? (int) $post->ID : 0);
    $selected_property_types = [];
    $selected_property_type  = '';
    $selected_main_categories  = [];

    if ($post_id && function_exists('carbon_get_post_meta')) {
        if ($post_type === 'projects') {
            $selected_property_types = (array) carbon_get_post_meta($post_id, 'jawda_property_type_ids');
            $selected_main_categories = (array) carbon_get_post_meta($post_id, 'jawda_main_category_id');
        } elseif ($post_type === 'property') {
            $selected_property_type = (string) carbon_get_post_meta($post_id, 'jawda_property_type_id');
            $selected_main_categories = (array) carbon_get_post_meta($post_id, 'jawda_main_category_id');
        }
    } elseif ($post_id) {
        if ($post_type === 'projects') {
            $selected_property_types = (array) get_post_meta($post_id, 'jawda_property_type_ids', true);
            $selected_main_categories = (array) get_post_meta($post_id, 'jawda_main_category_id', true);
        } elseif ($post_type === 'property') {
            $selected_property_type = (string) get_post_meta($post_id, 'jawda_property_type_id', true);
            $selected_main_categories = (array) get_post_meta($post_id, 'jawda_main_category_id', true);
        }
    }

    $selected_property_types = array_map('strval', $selected_property_types);
    $selected_property_type  = $selected_property_type !== '' ? (string) $selected_property_type : '';
    $selected_main_categories = array_values(array_filter(array_map('strval', $selected_main_categories)));

    $selected_main_category = '';
    if (!empty($selected_main_categories)) {
        $selected_main_category = (string) reset($selected_main_categories);
    }

    $is_arabic = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
    $language  = $is_arabic ? 'ar' : 'en';

    if (function_exists('jawda_locations_normalize_language')) {
        $language = jawda_locations_normalize_language('both', $language);
    }

    $placeholder_map = [];

    $strings  = [
        'no_categories'    => $placeholder_map['no_categories'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('لا توجد تصنيفات متاحة.', __('No categories available.', 'jawda'), $language)
            : ($language === 'ar'
                ? 'لا توجد تصنيفات متاحة.'
                : ($language === 'en'
                    ? __('No categories available.', 'jawda')
                    : 'لا توجد تصنيفات متاحة. / ' . __('No categories available.', 'jawda')))),
        'no_types'         => $placeholder_map['no_types'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('لا توجد أنواع متاحة لهذا التصنيف.', __('No property types available for this category.', 'jawda'), $language)
            : ($language === 'ar'
                ? 'لا توجد أنواع متاحة لهذا التصنيف.'
                : ($language === 'en'
                    ? __('No property types available for this category.', 'jawda')
                    : 'لا توجد أنواع متاحة لهذا التصنيف. / ' . __('No property types available for this category.', 'jawda')))),
        'clear_selection'  => $placeholder_map['clear_selection'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('مسح الاختيار', __('Clear selection', 'jawda'), $language)
            : ($language === 'ar'
                ? 'مسح الاختيار'
                : ($language === 'en'
                    ? __('Clear selection', 'jawda')
                    : 'مسح الاختيار / ' . __('Clear selection', 'jawda')))),
        'fallback_category'=> $placeholder_map['category_fallback'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('تصنيف #%s', __('Category #%s', 'jawda'), $language)
            : ($language === 'ar'
                ? 'تصنيف #%s'
                : ($language === 'en'
                    ? __('Category #%s', 'jawda')
                    : 'تصنيف #%s / ' . __('Category #%s', 'jawda')))),
    ];

    wp_localize_script(
        'jawda-project-meta',
        'JawdaProjectMeta',
        [
            'is_ar' => $is_arabic,
            'types_by_category' => function_exists('jawda_get_property_types_grouped_by_category') ? jawda_get_property_types_grouped_by_category() : [],
            'property_type_tree'       => jawda_prepare_property_type_tree_for_js(),
            'selected_property_types'  => $selected_property_types,
            'selected_property_type_ids' => $selected_property_types,
            'selected_property_type'   => $selected_property_type,
            'selected_main_category_id'=> $selected_main_category,
            'selected_main_category_ids'=> $selected_main_categories,
            'post_type'                => $post_type,
            'strings'                  => $strings,
            'language'                => $language,
        ]
    );
}

add_action('carbon_fields_post_meta_container_saved', 'jawda_sync_project_category_meta_across_languages', 25, 2);
function jawda_sync_project_category_meta_across_languages($post_id, $container) {
    $post_type = get_post_type($post_id);

    if ($post_type !== 'projects') {
        return;
    }

    if (!function_exists('pll_get_post_translations') || !function_exists('pll_get_post_language')) {
        return;
    }

    $translations = pll_get_post_translations($post_id);

    if (empty($translations) || !is_array($translations)) {
        return;
    }

    $current_language = pll_get_post_language($post_id);

    if (!$current_language || !isset($translations[$current_language])) {
        return;
    }

    $category_selection = ['main_categories' => [], 'property_types' => []];

    if (false) {
        // Placeholder
    } else {
        $main_category = '';

        if (function_exists('carbon_get_post_meta')) {
            $main_category = carbon_get_post_meta($post_id, 'jawda_main_category_id');
        } else {
            $main_category = get_post_meta($post_id, 'jawda_main_category_id', true);
        }

        if (is_array($main_category)) {
            $main_category = reset($main_category);
        }

        $main_category = is_scalar($main_category) ? (string) $main_category : '';

        if ($main_category === '0') {
            $main_category = '';
        }

        if (function_exists('carbon_get_post_meta')) {
            $property_types_raw = carbon_get_post_meta($post_id, 'jawda_property_type_ids');
        } else {
            $property_types_raw = get_post_meta($post_id, 'jawda_property_type_ids', true);
        }

        if (!is_array($property_types_raw)) {
            $property_types_raw = $property_types_raw !== '' ? [$property_types_raw] : [];
        }

        $property_types = [];

        foreach ($property_types_raw as $type_id) {
            $type_id = is_scalar($type_id) ? (string) $type_id : '';

            if ($type_id === '' || $type_id === '0') {
                continue;
            }

            $property_types[] = $type_id;
        }

        if ($property_types) {
            $property_types = array_values(array_unique($property_types));
        }

        $category_selection = [
            'main_categories' => $main_category ? [$main_category] : [],
            'property_types'  => $property_types,
        ];
    }

    $service_meta_keys = [
        // Project Services meta keys mapping removed (features/amenities/facilities)
    ];

    $project_services = [];
    $aggregated_features = [];

    foreach ($service_meta_keys as $type => $meta_key) {
        if (function_exists('carbon_get_post_meta')) {
            $raw_values = carbon_get_post_meta($post_id, $meta_key);
        } else {
            $raw_values = get_post_meta($post_id, $meta_key, true);
        }

        if (function_exists('jawda_project_features_normalize_selection')) {
            $normalized = jawda_project_features_normalize_selection($raw_values, [$type]);
        } else {
            $normalized = is_array($raw_values) ? array_filter($raw_values) : ($raw_values !== '' ? [$raw_values] : []);
        }

        $project_services[$meta_key] = $normalized;

        if (!empty($normalized)) {
            $aggregated_features = array_merge($aggregated_features, $normalized);
        }
    }

    if (empty($aggregated_features) && function_exists('jawda_project_features_normalize_selection')) {
        if (function_exists('carbon_get_post_meta')) {
            $legacy_raw = carbon_get_post_meta($post_id, 'jawda_project_feature_ids');
        } else {
            $legacy_raw = get_post_meta($post_id, 'jawda_project_feature_ids', true);
        }

        $legacy_features = jawda_project_features_normalize_selection($legacy_raw);

        if (!empty($legacy_features)) {
            $aggregated_features = $legacy_features;
        }
    }

    if (!empty($aggregated_features)) {
        $aggregated_features = array_map('intval', $aggregated_features);
        $aggregated_features = array_values(array_unique($aggregated_features));
        $aggregated_features = array_map('strval', $aggregated_features);
    } else {
        $aggregated_features = [];
    }

    if (!empty($aggregated_features)) {
        update_post_meta($post_id, 'jawda_project_feature_ids', $aggregated_features);
    } else {
        delete_post_meta($post_id, 'jawda_project_feature_ids');
    }

    $main_categories_for_sync = $category_selection['main_categories'] ?? [];
    $primary_main_category   = $main_categories_for_sync ? reset($main_categories_for_sync) : '';
    $property_types_for_sync = $category_selection['property_types'] ?? [];

    foreach ($translations as $language => $translation_id) {
        $translation_id = (int) $translation_id;

        if ($translation_id <= 0 || $translation_id === (int) $post_id) {
            continue;
        }

        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($translation_id, 'jawda_main_category_id', $primary_main_category);
            carbon_set_post_meta($translation_id, 'jawda_property_type_ids', $property_types_for_sync);
        } else {
            if ($primary_main_category !== '') {
                update_post_meta($translation_id, 'jawda_main_category_id', $primary_main_category);
            } else {
                delete_post_meta($translation_id, 'jawda_main_category_id');
            }

            if (!empty($property_types_for_sync)) {
                update_post_meta($translation_id, 'jawda_property_type_ids', $property_types_for_sync);
            } else {
                delete_post_meta($translation_id, 'jawda_property_type_ids');
            }
        }

        foreach ($service_meta_keys as $type => $meta_key) {
            $values = $project_services[$meta_key] ?? [];
            if (!empty($values)) {
                update_post_meta($translation_id, $meta_key, $values);
            } else {
                delete_post_meta($translation_id, $meta_key);
            }
        }

        if (!empty($aggregated_features)) {
            update_post_meta($translation_id, 'jawda_project_feature_ids', $aggregated_features);
        } else {
            delete_post_meta($translation_id, 'jawda_project_feature_ids');
        }
    }
}

function jawda_get_property_types_for_meta_box() {
    $selected_category = jawda_get_selected_main_category_for_request();

    return jawda_build_property_type_options($selected_category);
}
