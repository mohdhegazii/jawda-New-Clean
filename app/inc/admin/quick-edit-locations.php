<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('jawda_render_location_picker')) {
    require_once get_template_directory() . '/app/templates/locations/location-picker.php';
}

/**
 * Adds custom location fields to the project quick edit screen.
 */
function jawda_add_location_to_quick_edit($column_name, $post_type) {
    if (!in_array($post_type, ['projects', 'property'], true)) {
        return;
    }

    $is_project_column = ($post_type === 'projects' && $column_name === 'taxonomy-projects_developer');
    $is_property_column = ($post_type === 'property' && $column_name === 'title');

    if ($is_project_column || $is_property_column) { // A good column to hook after
        $main_category_options = function_exists('jawda_get_main_categories_options')
            ? (array) jawda_get_main_categories_options()
            : [];

        if (isset($main_category_options[''])) {
            unset($main_category_options['']);
        }

        $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
        $language = function_exists('jawda_locations_normalize_language')
            ? jawda_locations_normalize_language('both', $is_ar ? 'ar' : 'en')
            : ($is_ar ? 'ar' : 'en');

        $placeholders = [
            'select_gov'        => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'jawda'), $language)
                : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'jawda')),
            'select_city'       => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'jawda'), $language)
                : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'jawda')),
            'select_city_first' => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'jawda'), $language)
                : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'jawda')),
            'select_gov_first'  => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'jawda'), $language)
                : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'jawda')),
            'select_district'   => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المنطقة —', __('— Select District —', 'jawda'), $language)
                : ($is_ar ? '— اختر المنطقة —' : __('— Select District —', 'jawda')),
        ];

        $labels = [
            'gov'      => $is_ar ? 'المحافظة' : __('Governorate', 'jawda'),
            'city'     => $is_ar ? 'المدينة' : __('City', 'jawda'),
            'district' => $is_ar ? 'المنطقة/الحي' : __('District / Neighborhood', 'jawda'),
        ];

        $governorates = function_exists('jawda_get_all_governorates') ? jawda_get_all_governorates() : [];

        wp_nonce_field('jawda_location_quick_edit_nonce', 'location_quick_edit_nonce');
        wp_nonce_field('jawda_project_category_quick_edit', 'jawda_project_category_quick_edit_nonce');
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <span class="title inline-edit-group-title"><?php _e('Location', 'jawda'); ?></span>
                <?php
                if (function_exists('jawda_render_location_picker')) {
                    jawda_render_location_picker([
                        'context'      => 'quick-edit',
                        'governorates' => $governorates,
                        'placeholders' => $placeholders,
                        'labels'       => $labels,
                        'include_map'  => false,
                        'compact'      => true,
                        'language'     => $language,
                    ]);
                }
                ?>
            </div>
        </fieldset>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <span class="title inline-edit-group-title"><?php _e('Categories', 'jawda'); ?></span>
                <label class="alignleft">
                    <span class="title"><?php _e('Main Category', 'jawda'); ?></span>
                    <select name="jawda_qe_main_category_ids[]" class="jawda-qe-main-category-select" multiple="multiple" size="5" data-placeholder="<?php _e('— Select Main Category —', 'jawda'); ?>">
                        <?php foreach ($main_category_options as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="alignleft jawda-qe-property-types-wrapper" style="display:none;">
                    <span class="title"><?php _e('Property Types', 'jawda'); ?></span>
                    <select name="jawda_qe_property_type_ids[]" class="jawda-qe-property-type-select" multiple="multiple" size="6">
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }
}
add_action('quick_edit_custom_box', 'jawda_add_location_to_quick_edit', 10, 2);

/**
 * Adds custom location fields to the project bulk edit screen.
 */
function jawda_add_location_to_bulk_edit($column_name, $post_type) {
    if (!in_array($post_type, ['projects', 'property'], true)) {
        return;
    }

    $is_project_column = ($post_type === 'projects' && $column_name === 'taxonomy-projects_developer');
    $is_property_column = ($post_type === 'property' && $column_name === 'title');

    if ($is_project_column || $is_property_column) { // A good column to hook after
        $main_category_options = function_exists('jawda_get_main_categories_options')
            ? (array) jawda_get_main_categories_options()
            : [];

        if (isset($main_category_options[''])) {
            unset($main_category_options['']);
        }

        $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
        $language = function_exists('jawda_locations_normalize_language')
            ? jawda_locations_normalize_language('both', $is_ar ? 'ar' : 'en')
            : ($is_ar ? 'ar' : 'en');

        $placeholders = [
            'select_gov'        => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'jawda'), $language)
                : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'jawda')),
            'select_city'       => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'jawda'), $language)
                : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'jawda')),
            'select_city_first' => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'jawda'), $language)
                : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'jawda')),
            'select_gov_first'  => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'jawda'), $language)
                : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'jawda')),
            'select_district'   => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المنطقة —', __('— Select District —', 'jawda'), $language)
                : ($is_ar ? '— اختر المنطقة —' : __('— Select District —', 'jawda')),
            'no_change'         => __('— No Change —', 'jawda'),
        ];

        $labels = [
            'gov'      => $is_ar ? 'المحافظة' : __('Governorate', 'jawda'),
            'city'     => $is_ar ? 'المدينة' : __('City', 'jawda'),
            'district' => $is_ar ? 'المنطقة/الحي' : __('District / Neighborhood', 'jawda'),
        ];

        $governorates = function_exists('jawda_get_all_governorates') ? jawda_get_all_governorates() : [];

        wp_nonce_field('jawda_location_bulk_edit_nonce', 'location_bulk_edit_nonce');
        wp_nonce_field('jawda_project_category_bulk_edit', 'jawda_project_category_bulk_edit_nonce');
        ?>
        <div class="inline-edit-group">
            <?php
            if (function_exists('jawda_render_location_picker')) {
                jawda_render_location_picker([
                    'context'      => 'bulk-edit',
                    'selected'     => [
                        'governorate' => -1,
                        'city'        => -1,
                        'district'    => -1,
                    ],
                    'governorates' => $governorates,
                    'placeholders' => $placeholders,
                    'labels'       => $labels,
                    'include_map'  => false,
                    'compact'      => true,
                    'allow_no_change' => true,
                    'language'     => $language,
                ]);
            }
            ?>
        </div>
        <div class="inline-edit-group">
            <label class="alignleft">
                <span class="title"><?php _e('Main Category', 'jawda'); ?></span>
                <select name="jawda_be_main_category_ids[]" class="jawda-be-main-category-select" multiple="multiple" size="5" data-placeholder="<?php _e('— Select Main Category —', 'jawda'); ?>">
                    <option value="0"><?php _e('— Clear Main Category —', 'jawda'); ?></option>
                    <?php foreach ($main_category_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="alignleft" style="display:none;">
                <span class="title"><?php _e('Property Types', 'jawda'); ?></span>
                <select name="jawda_be_property_type_ids[]" class="jawda-be-property-type-select" multiple="multiple" size="6">
                </select>
            </label>
        </div>
        <?php
    }
}
add_action('bulk_edit_custom_box', 'jawda_add_location_to_bulk_edit', 10, 2);

/**
 * Enqueues the javascript for the quick edit locations and localizes data.
 */
function jawda_enqueue_quick_edit_locations_js($hook) {
    $screen = get_current_screen();

    if (!$screen || !in_array($screen->post_type, ['projects', 'property'], true)) {
        return;
    }

    if (!in_array($hook, ['edit.php', 'post.php', 'post-new.php'], true)) {
        return;
    }

    if (in_array($hook, ['edit.php', 'post.php', 'post-new.php'], true)) {
        wp_enqueue_script('jawda-location-widget', get_template_directory_uri() . '/assets/js/location-widget.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('jawda-quick-edit-locations', get_template_directory_uri() . '/app/inc/admin/js/quick-edit-locations.js', ['jquery', 'inline-edit-post', 'jawda-location-widget'], '1.2', true);
        wp_enqueue_script('jawda-project-quick-edit-categories', get_template_directory_uri() . '/app/inc/admin/js/project-quick-edit-categories.js', ['jquery', 'inline-edit-post'], '1.0.0', true);

        $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : false;

        $language = function_exists('jawda_categories_determine_language')
            ? jawda_categories_determine_language($is_ar ? 'ar' : 'en')
            : ($is_ar ? 'ar' : 'en');

        $select_gov_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'jawda'));
        $select_city_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'jawda'));
        $select_city_first_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'jawda'));
        $select_gov_first_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'jawda'));
        $select_district_placeholder = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر المنطقة —', __('— Select District —', 'jawda'), 'both')
            : ($is_ar ? '— اختر المنطقة —' : __('— Select District —', 'jawda'));

        wp_localize_script('jawda-location-widget', 'CF_DEP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cf_dep_nonce'),
            'language' => 'both',
            'i18n'     => [
                'loading'              => $is_ar ? '— جاري التحميل… —' : __('— Loading… —', 'jawda'),
                'select_gov'           => $select_gov_placeholder,
                'select_gov_first'     => $select_gov_first_placeholder,
                'select_city'          => $select_city_placeholder,
                'select_city_first'    => $select_city_first_placeholder,
                'select_district'      => $select_district_placeholder,
            ]
        ]);

        $types_by_category = function_exists('jawda_get_property_types_grouped_by_category')
            ? jawda_get_property_types_grouped_by_category()
            : [];

        $placeholder_map = class_exists('Jawda_Property_Taxonomy_Helper')
            ? Jawda_Property_Taxonomy_Helper::get_placeholders($language)
            : [];

        $select_main_category_placeholder = $placeholder_map['select_main_category'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر التصنيف الرئيسي —', __('— Select Main Category —', 'jawda'), $language)
            : ($is_ar ? '— اختر التصنيف الرئيسي —' : __('— Select Main Category —', 'jawda')));

        $select_main_category_first_placeholder = $placeholder_map['select_main_category_first'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('— اختر التصنيف الرئيسي أولًا —', __('— Select the main category first —', 'jawda'), $language)
            : ($is_ar ? '— اختر التصنيف الرئيسي أولًا —' : __('— Select the main category first —', 'jawda')));

        $select_property_types_placeholder = $placeholder_map['select_property_types'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('اختر أنواع الوحدات', __('Select property types', 'jawda'), $language)
            : ($is_ar ? 'اختر أنواع الوحدات' : __('Select property types', 'jawda')));

        $no_types_placeholder = $placeholder_map['no_types'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('لا توجد أنواع متاحة لهذا التصنيف.', __('No property types available for this category.', 'jawda'), $language)
            : ($is_ar ? 'لا توجد أنواع متاحة لهذا التصنيف.' : __('No property types available for this category.', 'jawda')));

        $no_categories_placeholder = $placeholder_map['no_categories'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('لا توجد تصنيفات متاحة.', __('No categories available.', 'jawda'), $language)
            : ($is_ar ? 'لا توجد تصنيفات متاحة.' : __('No categories available.', 'jawda')));

        $type_fallback = $placeholder_map['type_fallback'] ?? (function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder('نوع #%s', __('Type #%s', 'jawda'), $language)
            : ($is_ar ? 'نوع #%s' : __('Type #%s', 'jawda')));

        wp_localize_script('jawda-project-quick-edit-categories', 'JawdaProjectQuickEdit', [
            'types_by_category' => $types_by_category,
            'strings' => [
                'select_main_category'       => $select_main_category_placeholder,
                'select_main_category_first' => $select_main_category_first_placeholder,
                'select_property_types'      => $select_property_types_placeholder,
                'no_types'                   => $no_types_placeholder,
                'no_categories'              => $no_categories_placeholder,
                'type_fallback'              => $type_fallback,
            ],
        ]);
    }
}
add_action('admin_enqueue_scripts', 'jawda_enqueue_quick_edit_locations_js');

/**
 * Adds the project's location data to a hidden div in a column for easy access in javascript.
 */
function jawda_add_location_data_to_project_column($column, $post_id) {
    $post_type = get_post_type($post_id);
    $is_project_column  = ($post_type === 'projects' && $column === 'taxonomy-projects_developer');
    $is_property_column = ($post_type === 'property' && $column === 'title');

    if ($is_project_column || $is_property_column) {
        $main_categories = [];
        $property_types  = [];

        if (false) {
            // Placeholder for new unified lookup service
        } else {
            $raw_main = get_post_meta($post_id, 'jawda_main_category_id', true);
            if ($raw_main !== '' && $raw_main !== null) {
                $main_categories = is_array($raw_main) ? array_map('strval', $raw_main) : [(string) $raw_main];
            }

            $property_types_raw = get_post_meta($post_id, 'jawda_property_type_ids', true);
            if (is_array($property_types_raw)) {
                foreach ($property_types_raw as $type_id) {
                    $type_id = absint($type_id);

                    if ($type_id > 0) {
                        $property_types[] = (string) $type_id;
                    }
                }
            } elseif (!empty($property_types_raw)) {
                $type_id = absint($property_types_raw);

                if ($type_id > 0) {
                    $property_types[] = (string) $type_id;
                }
            }
        }

        if ($main_categories) {
            $main_categories = array_values(array_unique(array_filter($main_categories)));
        }

        if ($property_types) {
            $property_types = array_values(array_unique(array_filter($property_types)));
        }

        $encoded_main  = wp_json_encode($main_categories ?: []);
        $encoded_types = wp_json_encode($property_types ?: []);

        $location = null;

        if ($post_type === 'property' && class_exists('Jawda_Listing_Location_Service')) {
            $location = Jawda_Listing_Location_Service::get_location($post_id, false);
        } elseif (class_exists('Jawda_Location_Service')) {
            $location = Jawda_Location_Service::get_location_for_post($post_id, false);
        }

        $gov_id      = $location['ids']['governorate'] ?? get_post_meta($post_id, 'loc_governorate_id', true);
        $city_id     = $location['ids']['city'] ?? get_post_meta($post_id, 'loc_city_id', true);
        $district_id = $location['ids']['district'] ?? get_post_meta($post_id, 'loc_district_id', true);

        printf(
            '<div class="jawda-location-data" style="display:none;" data-gov-id="%s" data-city-id="%s" data-district-id="%s"></div>',
            esc_attr($gov_id),
            esc_attr($city_id),
            esc_attr($district_id)
        );

        printf(
            '<div class="jawda-project-category-data" style="display:none;" data-main-category-ids="%s" data-property-type-ids="%s"></div>',
            esc_attr($encoded_main ? $encoded_main : '[]'),
            esc_attr($encoded_types ? $encoded_types : '[]')
        );
    }
}
add_action('manage_projects_posts_custom_column', 'jawda_add_location_data_to_project_column', 10, 2);
add_action('manage_property_posts_custom_column', 'jawda_add_location_data_to_project_column', 10, 2);

/**
 * Saves location data from the bulk edit screen.
 *
 * This function is hooked to 'load-edit.php', which runs before the admin
 * list table is displayed. It checks for the bulk edit action and processes the data.
 */
function jawda_save_bulk_edit_locations() {
    if (!isset($_GET['post_type']) || !in_array($_GET['post_type'], ['projects', 'property'], true)) {
        return;
    }

    // Check if the bulk edit form has been submitted.
    // WordPress uses 'action' for the top dropdown and 'action2' for the bottom one.
    if (!isset($_GET['action']) || ('edit' !== $_GET['action'] && 'edit' !== $_GET['action2'])) {
        return;
    }

    // Security check
    if (!isset($_GET['jawda_project_category_bulk_edit_nonce']) || !wp_verify_nonce(wp_unslash($_GET['jawda_project_category_bulk_edit_nonce']), 'jawda_project_category_bulk_edit')) {
        return;
    }

    if (!isset($_GET['jawda_project_category_bulk_edit_nonce']) || !wp_verify_nonce(wp_unslash($_GET['jawda_project_category_bulk_edit_nonce']), 'jawda_project_category_bulk_edit')) {
        return;
    }

    check_admin_referer('bulk-posts');

    // Get the post IDs and location data from the form submission
    $post_ids = isset($_GET['post']) ? array_map('absint', $_GET['post']) : [];
    $gov_id = isset($_GET['loc_governorate_id']) ? absint($_GET['loc_governorate_id']) : -1;
    $city_id = isset($_GET['loc_city_id']) ? absint($_GET['loc_city_id']) : -1;
    $district_id = isset($_GET['loc_district_id']) ? absint($_GET['loc_district_id']) : -1;

    // If "No Change" was selected for all, there's nothing to do.
    if (-1 === $gov_id && -1 === $city_id && -1 === $district_id) {
        return;
    }

    // Loop through the post IDs and update the location
    if (!empty($post_ids)) {
        foreach ($post_ids as $post_id) {
            // Get the current location meta for the project.
            $current_gov_id = (int) get_post_meta($post_id, 'loc_governorate_id', true);
            $current_city_id = (int) get_post_meta($post_id, 'loc_city_id', true);
            $current_district_id = (int) get_post_meta($post_id, 'loc_district_id', true);

            // Determine the final values. If a dropdown is set to "No Change" (-1), keep the current value.
            $final_gov_id = ($gov_id !== -1) ? $gov_id : $current_gov_id;
            $final_city_id = ($city_id !== -1) ? $city_id : $current_city_id;
            $final_district_id = ($district_id !== -1) ? $district_id : $current_district_id;

            // Apply hierarchical logic. If a parent is changed, children must be reset
            // unless a new child value was also explicitly provided in the same bulk action.
            if ($gov_id !== -1 && $gov_id !== $current_gov_id) {
                // Governorate was changed. Reset city and district unless they were also changed.
                $final_city_id = ($city_id !== -1) ? $city_id : 0;
                $final_district_id = ($district_id !== -1) ? $district_id : 0;
            } elseif ($city_id !== -1 && $city_id !== $current_city_id) {
                // City was changed (but gov wasn't). Reset district unless it was also changed.
                $final_district_id = ($district_id !== -1) ? $district_id : 0;
            }

            // Call the centralized function to update the post's location.
            if ($_GET['post_type'] === 'property' && class_exists('Jawda_Listing_Location_Service')) {
                Jawda_Listing_Location_Service::save_location($post_id, [
                    'governorate_id'           => $final_gov_id,
                    'city_id'                  => $final_city_id,
                    'district_id'              => $final_district_id,
                    'sync_map_from_location'   => true,
                    'overwrite_map'            => true,
                    'inherit_project_location' => false,
                ]);
            } elseif (class_exists('Jawda_Location_Service')) {
                Jawda_Location_Service::save_location_for_post($post_id, [
                    'governorate_id'          => $final_gov_id,
                    'city_id'                 => $final_city_id,
                    'district_id'             => $final_district_id,
                    'sync_map_from_location'  => true,
                    'overwrite_map'           => true,
                ]);
            } else {
                jawda_update_project_location(
                    $post_id,
                    $final_gov_id,
                    $final_city_id,
                    $final_district_id
                );

                // Also trigger the coordinate update.
                jawda_update_project_coordinates_from_location($post_id);
            }
        }
    }
}
add_action('load-edit.php', 'jawda_save_bulk_edit_locations');

/**
 * Saves category + property type selections from the bulk edit panel.
 */
function jawda_save_bulk_edit_project_categories() {
    if (!isset($_GET['post_type']) || !in_array($_GET['post_type'], ['projects', 'property'], true)) {
        return;
    }

    $top_action    = isset($_GET['action']) ? wp_unslash($_GET['action']) : '';
    $bottom_action = isset($_GET['action2']) ? wp_unslash($_GET['action2']) : '';

    if ('edit' !== $top_action && 'edit' !== $bottom_action) {
        return;
    }

    check_admin_referer('bulk-posts');

    if (!isset($_GET['post'])) {
        return;
    }

    $post_ids = array_filter(array_map('absint', (array) $_GET['post']));

    if (!$post_ids) {
        return;
    }

    $main_categories_to_apply = null;
    if (isset($_GET['jawda_be_main_category_ids'])) {
        $main_categories_to_apply = [];
        $raw_mains = (array) $_GET['jawda_be_main_category_ids'];

        foreach ($raw_mains as $cat_id) {
            $cat_id = (int) wp_unslash($cat_id);
            if ($cat_id === 0) {
                // Explicit clear.
                $main_categories_to_apply = [];
                break;
            }
            if ($cat_id > 0) {
                $main_categories_to_apply[] = $cat_id;
            }
        }

        if ($main_categories_to_apply !== null) {
            $main_categories_to_apply = array_values(array_unique($main_categories_to_apply));
        }
    }

    // Property types are no longer saved from the project screen.
    $types_to_apply = null;

    if ($main_categories_to_apply === null && $types_to_apply === null) {
        return;
    }

    foreach ($post_ids as $post_id) {
        if (function_exists('jawda_update_project_category_and_types')) {
            jawda_update_project_category_and_types(
                $post_id,
                $main_categories_to_apply,
                $types_to_apply
            );
        }
    }
}
add_action('load-edit.php', 'jawda_save_bulk_edit_project_categories');


/**
 * AJAX handler to get all governorates, respecting the current language.
 */
add_action('wp_ajax_cf_dep_get_governorates', 'jawda_cf_dep_get_governorates');

function jawda_cf_dep_get_governorates() {
    if (!check_ajax_referer('cf_dep_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    if (!jawda_enforce_rate_limit('cf_dep_get_governorates_' . $ip, 120, 60)) {
        wp_send_json_error(['message' => 'Too many requests'], 429);
    }

    $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : false;
    $default_lang = 'ar';
    $requested_lang = isset($_GET['lang']) ? wp_unslash($_GET['lang']) : $default_lang;

    if (!in_array($requested_lang, ['ar', 'en'], true)) {
        $requested_lang = 'ar';
    }

    $language = function_exists('jawda_locations_normalize_language')
        ? jawda_locations_normalize_language($requested_lang, $default_lang)
        : $default_lang;

    $governorates = function_exists('jawda_get_all_governorates') ? jawda_get_all_governorates() : [];

    $placeholder = function_exists('jawda_locations_get_placeholder')
        ? jawda_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'jawda'), $language)
        : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'jawda'));
    $options = ['' => $placeholder];

    if ($governorates) {
        foreach ($governorates as $row) {
            $label = function_exists('jawda_locations_get_label')
                ? jawda_locations_get_label(
                    $row[ 'slug_ar' ] ?? '',
                    $row['name_en'] ?? '',
                    $language,
                    sprintf('#%d', (int) $row['id'])
                )
                : ($row[ 'slug_ar' ] ?? $row['name_en'] ?? sprintf('#%d', (int) $row['id']));

            $options[$row['id']] = $label;
        }
    }
    wp_send_json_success(['options' => $options]);
}

/**
 * Saves the location data from the quick edit screen.
 */
function jawda_save_quick_edit_location_data($post_id) {
    if (!isset($_POST['location_quick_edit_nonce']) || !wp_verify_nonce($_POST['location_quick_edit_nonce'], 'jawda_location_quick_edit_nonce')) {
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

    if ($post_type === 'property' && class_exists('Jawda_Listing_Location_Service')) {
        Jawda_Listing_Location_Service::save_location($post_id, [
            'governorate_id'           => $gov_id,
            'city_id'                  => $city_id,
            'district_id'              => $district_id,
            'sync_map_from_location'   => true,
            'overwrite_map'            => true,
            'inherit_project_location' => false,
        ]);
        return;
    }

    if (class_exists('Jawda_Location_Service')) {
        $map_payload = null;
        Jawda_Location_Service::save_location_for_post($post_id, [
            'governorate_id'         => $gov_id,
            'city_id'                => $city_id,
            'district_id'            => $district_id,
            'map'                    => $map_payload,
            'sync_map_from_location' => $map_payload ? false : true,
            'overwrite_map'          => true,
        ]);

        return;
    }

    if (function_exists('jawda_update_project_location')) {
        jawda_update_project_location($post_id, $gov_id, $city_id, $district_id);
    }

    jawda_update_project_coordinates_from_location($post_id);
}
add_action('save_post_projects', 'jawda_save_quick_edit_location_data');
add_action('save_post_property', 'jawda_save_quick_edit_location_data');

/**
 * Saves the main category + property types from the quick edit form.
 */
function jawda_save_quick_edit_project_categories($post_id) {
    if (!isset($_POST['jawda_project_category_quick_edit_nonce']) || !wp_verify_nonce($_POST['jawda_project_category_quick_edit_nonce'], 'jawda_project_category_quick_edit')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $main_categories = null;
    if (isset($_POST['jawda_qe_main_category_ids'])) {
        $main_categories = [];
        foreach ((array) $_POST['jawda_qe_main_category_ids'] as $cat_id) {
            $cat_id = (int) wp_unslash($cat_id);
            if ($cat_id > 0) {
                $main_categories[] = $cat_id;
            }
        }
    } elseif (isset($_POST['jawda_qe_main_category_id'])) {
        $raw_main = (int) wp_unslash($_POST['jawda_qe_main_category_id']);
        $main_categories = $raw_main > 0 ? [$raw_main] : [];
    }

    // Property types are no longer saved from the project screen.
    $property_type_ids = null;

    if (function_exists('jawda_update_project_category_and_types')) {
        jawda_update_project_category_and_types($post_id, $main_categories, $property_type_ids);
    }
}
add_action('save_post_projects', 'jawda_save_quick_edit_project_categories', 30);
add_action('save_post_property', 'jawda_save_quick_edit_project_categories', 30);

/**
 * Helper function to update project coordinates based on its location.
 */
function jawda_update_project_coordinates_from_location($post_id) {
    if (class_exists('Jawda_Location_Service')) {
        $location = Jawda_Location_Service::get_location_for_post($post_id, false);

        Jawda_Location_Service::save_location_for_post($post_id, [
            'governorate_id'         => $location['ids']['governorate'],
            'city_id'                => $location['ids']['city'],
            'district_id'            => $location['ids']['district'],
            'sync_map_from_location' => true,
            'overwrite_map'          => true,
            'map_zoom'               => 15,
        ]);

        return;
    }

    global $wpdb;
    $district_id = get_post_meta($post_id, 'loc_district_id', true);
    $city_id = get_post_meta($post_id, 'loc_city_id', true);
    $gov_id = get_post_meta($post_id, 'loc_governorate_id', true);

    $coords = null;

    if ($district_id) {
        $coords = $wpdb->get_row($wpdb->prepare("SELECT latitude, longitude FROM {$wpdb->prefix}jawda_districts WHERE id = %d", $district_id));
    } elseif ($city_id) {
        $coords = $wpdb->get_row($wpdb->prepare("SELECT latitude, longitude FROM {$wpdb->prefix}jawda_cities WHERE id = %d", $city_id));
    } elseif ($gov_id) {
        $coords = $wpdb->get_row($wpdb->prepare("SELECT latitude, longitude FROM {$wpdb->prefix}jawda_governorates WHERE id = %d", $gov_id));
    }

    if ($coords && !empty($coords->latitude) && !empty($coords->longitude)) {
        carbon_set_post_meta($post_id, 'jawda_map', [
            'lat'  => $coords->latitude,
            'lng'  => $coords->longitude,
            'zoom' => 15, // A reasonable default zoom level
        ]);
    }
}

