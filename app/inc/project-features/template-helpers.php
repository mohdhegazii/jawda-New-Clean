<?php
/**
 * Helper functions for accessing project features lookups.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

function jawda_project_features_build_label($arabic, $english) {
    $arabic  = (string) $arabic;
    $english = (string) $english;

    if (function_exists('jawda_locations_get_label')) {
        return jawda_locations_get_label($arabic, $english, 'both', $english !== '' ? $english : $arabic);
    }

    if ($arabic !== '' && $english !== '') {
        return trim($arabic . ' / ' . $english);
    }

    return $english !== '' ? $english : $arabic;
}

function jawda_project_features_normalize_allowed_types($allowed_types) {
    $known_types = ['feature', 'amenity', 'facility', 'finishing', 'view', 'orientation', 'facade', 'marketing_orientation'];

    if ($allowed_types === null) {
        return ['feature', 'amenity', 'facility'];
    }

    if (!is_array($allowed_types)) {
        $allowed_types = [$allowed_types];
    }

    $allowed_types = array_map('strval', $allowed_types);
    $allowed_types = array_values(array_intersect($known_types, $allowed_types));

    return $allowed_types;
}

function jawda_get_project_service_meta_keys() {
    return [
        'feature'  => 'jawda_project_service_feature_ids',
        'amenity'  => 'jawda_project_service_amenity_ids',
        'facility' => 'jawda_project_service_facility_ids',
    ];
}

function jawda_project_features_get_feature_types() {
    return [
        'feature'  => jawda_project_features_build_label('ميزة', __('Feature', 'jawda')),
        'amenity'  => jawda_project_features_build_label('وسيلة راحة', __('Amenity', 'jawda')),
        'facility' => jawda_project_features_build_label('مرفق', __('Facility', 'jawda')),
        'finishing' => jawda_project_features_build_label('التشطيبات', __('Finishing', 'jawda')),
        'view' => jawda_project_features_build_label('الإطلالات', __('View', 'jawda')),
        'orientation' => jawda_project_features_build_label('الاتجاهات', __('Orientation', 'jawda')),
        'facade' => jawda_project_features_build_label('الواجهات / الوضع', __('Facade / Position', 'jawda')),
        'marketing_orientation' => jawda_project_features_build_label('تسويق الاتجاهات والواجهات', __('Marketing Orientation Label', 'jawda')),
    ];
}

function jawda_project_features_get_context_labels() {
    return [
        'projects'   => jawda_project_features_build_label('المشروعات', __('Projects', 'jawda')),
        'properties' => jawda_project_features_build_label('الوحدات', __('Units', 'jawda')),
        'both'       => jawda_project_features_build_label('المشروعات والوحدات', __('Projects & Units', 'jawda')),
    ];
}

function jawda_project_features_reset_cache() {
    jawda_project_features_fetch_all(true);
}

function jawda_project_features_feature_matches_context($feature, $context) {
    $projects   = !empty($feature['context_projects']);
    $properties = !empty($feature['context_properties']);

    switch ($context) {
        case 'projects':
            return $projects;
        case 'properties':
            return $properties;
        case 'both':
            return $projects && $properties;
        default:
            return $projects || $properties;
    }
}

/**
 * Normalizes a list of feature identifiers.
 *
 * @param mixed $selection Raw selection.
 * @return array
 */
function jawda_project_features_normalize_selection($selection, $allowed_types = null) {
    if (!is_array($selection)) {
        $selection = $selection !== '' ? [$selection] : [];
    }

    if (!$selection) {
        return [];
    }

    $selection = array_map('intval', $selection);
    $selection = array_filter($selection);

    if (!$selection) {
        return [];
    }

    $features = jawda_project_features_fetch_all();

    if (!$features) {
        return [];
    }

    $allowed_types = jawda_project_features_normalize_allowed_types($allowed_types);

    if (!$allowed_types) {
        return [];
    }

    $valid_ids = [];

    foreach ($features as $feature) {
        if (empty($feature['id'])) {
            continue;
        }

        $type = isset($feature['feature_type']) ? (string) $feature['feature_type'] : '';
        if ($type !== '' && !in_array($type, $allowed_types, true)) {
            continue;
        }

        $valid_ids[] = (int) $feature['id'];
    }

    if (!$valid_ids) {
        return [];
    }

    $selection = array_values(array_unique(array_intersect($selection, $valid_ids)));

    return array_map(static function ($value) {
        return (string) (int) $value;
    }, $selection);
}


/**
 * Retrieves all project features.
 *
 * @return array[]
 */
function jawda_project_features_fetch_all($force_refresh = false) {
    static $cache = null;

    if ($force_refresh) {
        $cache = null;
    }

    if ($cache !== null) {
        return $cache;
    }

    global $wpdb;

    $table = jawda_project_features_table();

    $rows = $wpdb->get_results(
        "SELECT id, name_ar, name_en, image_id, feature_type, context_projects, context_properties, orientation_id, facade_id FROM {$table} ORDER BY name_ar ASC, name_en ASC",
        ARRAY_A
    );

    if (!$rows) {
        $cache = [];
        return $cache;
    }

    $cache = array_map(
        static function ($row) {
            return [
                'id'       => isset($row['id']) ? (int) $row['id'] : 0,
                 'slug_ar'   => isset($row[ 'slug_ar' ]) ? (string) $row[ 'slug_ar' ] : '',
                'name_en'  => isset($row['name_en']) ? (string) $row['name_en'] : '',
                'image_id' => isset($row['image_id']) ? (int) $row['image_id'] : 0,
                'feature_type' => isset($row['feature_type']) ? (string) $row['feature_type'] : 'feature',
                'context_projects' => !empty($row['context_projects']),
                'context_properties' => !empty($row['context_properties']),
                'orientation_id' => isset($row['orientation_id']) ? (int) $row['orientation_id'] : 0,
                'facade_id'      => isset($row['facade_id']) ? (int) $row['facade_id'] : 0,
            ];
        },
        $rows
    );

    return $cache;
}

function jawda_project_features_get_feature_by_id($feature_id, $force_refresh = false) {
    $feature_id = (int) $feature_id;

    if ($feature_id <= 0) {
        return null;
    }

    $features = jawda_project_features_fetch_all($force_refresh);

    foreach ($features as $feature) {
        if (!empty($feature['id']) && (int) $feature['id'] === $feature_id) {
            return $feature;
        }
    }

    if ($force_refresh) {
        return null;
    }

    return jawda_project_features_get_feature_by_id($feature_id, true);
}

function jawda_project_features_get_feature_label($feature_id, $language = 'both') {
    $feature = jawda_project_features_get_feature_by_id($feature_id);

    if (!$feature) {
        return '';
    }

    $name_ar = isset($feature[ 'slug_ar' ]) ? (string) $feature[ 'slug_ar' ] : '';
    $name_en = isset($feature['name_en']) ? (string) $feature['name_en'] : '';

    if (function_exists('jawda_locations_get_label')) {
        return jawda_locations_get_label($name_ar, $name_en, $language, $name_en !== '' ? $name_en : $name_ar);
    }

    if ($language === 'ar') {
        return $name_ar !== '' ? $name_ar : $name_en;
    }

    if ($language === 'en') {
        return $name_en !== '' ? $name_en : $name_ar;
    }

    return jawda_project_features_build_label($name_ar, $name_en);
}

function jawda_project_features_filter_by_context(array $features, $context) {
    if (!$features) {
        return [];
    }

    if (!in_array($context, ['projects', 'properties', 'both'], true)) {
        return array_values($features);
    }

    return array_values(array_filter($features, static function ($feature) use ($context) {
        return jawda_project_features_feature_matches_context($feature, $context);
    }));
}

/**
 * Returns formatted select options for Carbon Fields.
 *
 * @param string $context Desired context.
 * @return array
 */
function jawda_get_project_features_options($context = 'projects', $allowed_types = null) {
    $features = jawda_project_features_fetch_all();
    $features = jawda_project_features_filter_by_context($features, $context);

    $allowed_types = jawda_project_features_normalize_allowed_types($allowed_types);
    if (!$allowed_types) {
        $allowed_types = ['feature', 'amenity', 'facility'];
    }

    $features = array_values(array_filter($features, static function ($feature) use ($allowed_types) {
        $type = isset($feature['feature_type']) ? (string) $feature['feature_type'] : '';

        return $type === '' || in_array($type, $allowed_types, true);
    }));

    $is_arabic = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
    $language = $is_arabic ? 'ar' : 'en';

    if (function_exists('jawda_locations_normalize_language')) {
        $language = jawda_locations_normalize_language('both', $language);
    }

    switch ($context) {
        case 'properties':
            $placeholder_ar = 'اختر مميزات/خدمات الوحدة';
            $placeholder_en = __('Select unit amenities / facilities', 'jawda');
            break;
        case 'both':
            $placeholder_ar = 'اختر العناصر المميزة';
            $placeholder_en = __('Select featured items', 'jawda');
            break;
        default:
            $placeholder_ar = 'اختر مميزات المشروع';
            $placeholder_en = __('Select project features / amenities', 'jawda');
            break;
    }

    if (count($allowed_types) === 1) {
        $single_type = $allowed_types[0];
        $type_placeholders = [
            'feature' => [
                'projects'   => ['ar' => 'اختر مميزات المشروع', 'en' => __('Select project features', 'jawda')],
                'properties' => ['ar' => 'اختر مميزات الوحدة', 'en' => __('Select unit features', 'jawda')],
            ],
            'amenity' => [
                'projects'   => ['ar' => 'اختر وسائل الراحة للمشروع', 'en' => __('Select project amenities', 'jawda')],
                'properties' => ['ar' => 'اختر وسائل الراحة للوحدة', 'en' => __('Select unit amenities', 'jawda')],
            ],
            'facility' => [
                'projects'   => ['ar' => 'اختر مرافق المشروع', 'en' => __('Select project facilities', 'jawda')],
                'properties' => ['ar' => 'اختر مرافق الوحدة', 'en' => __('Select unit facilities', 'jawda')],
            ],
        ];

        if (isset($type_placeholders[$single_type])) {
            $context_key = isset($type_placeholders[$single_type][$context]) ? $context : 'projects';
            if (isset($type_placeholders[$single_type][$context_key])) {
                $placeholder_ar = $type_placeholders[$single_type][$context_key]['ar'];
                $placeholder_en = $type_placeholders[$single_type][$context_key]['en'];
            }
        }
    }

    $placeholder = function_exists('jawda_locations_get_placeholder')
        ? jawda_locations_get_placeholder($placeholder_ar, $placeholder_en, $language)
        : ($language === 'ar'
            ? $placeholder_ar
            : ($language === 'en'
                ? $placeholder_en
                : trim($placeholder_ar . ' / ' . $placeholder_en))
        );

    $options = ['' => $placeholder];
    $types   = jawda_project_features_get_feature_types();
    $append_type = count($allowed_types) !== 1;

    foreach ($features as $feature) {
        if (empty($feature['id'])) {
            continue;
        }

        $label = function_exists('jawda_locations_get_label')
            ? jawda_locations_get_label(
                $feature[ 'slug_ar' ] ?? '',
                $feature['name_en'] ?? '',
                $language,
                sprintf('#%d', (int) $feature['id'])
            )
            : ($language === 'en'
                ? ($feature['name_en'] ?? '')
                : ($language === 'ar'
                    ? ($feature[ 'slug_ar' ] ?? '')
                    : trim(($feature[ 'slug_ar' ] ?? '') . ' / ' . ($feature['name_en'] ?? ''))
                )
            );

        if ($label === '') {
            $label = (string) $feature['id'];
        }

        $type_key = isset($feature['feature_type']) ? (string) $feature['feature_type'] : '';
        if ($append_type && $type_key !== '' && isset($types[$type_key])) {
            $label = trim($label . ' — ' . $types[$type_key]);
        }

        $options[(string) $feature['id']] = $label;
    }

    return $options;
}


function jawda_get_project_feature_options_for_projects() {
    return jawda_get_project_features_options('projects');
}

function jawda_get_project_feature_options_for_properties() {
    return jawda_get_project_features_options('properties');
}

function jawda_get_project_feature_options_for_project_features() {
    return jawda_get_project_features_options('projects', ['feature']);
}

function jawda_get_project_feature_options_for_project_amenities() {
    return jawda_get_project_features_options('projects', ['amenity']);
}

function jawda_get_project_feature_options_for_project_facilities() {
    return jawda_get_project_features_options('projects', ['facility']);
}

/**
 * Returns the features selected for a specific post and context.
 *
 * @param int         $post_id  Post identifier.
 * @param string      $context  Context key.
 * @param string      $meta_key Meta key storing selections.
 * @param string|null $language Language indicator.
 *
 * @return array
 */
function jawda_get_feature_selection_for_post($post_id, $context, $meta_key, $language = null, $allowed_types = null) {
    $post_id = (int) $post_id;

    if ($post_id <= 0) {
        return [];
    }

    if ($language === null) {
        $is_arabic = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
        $language = $is_arabic ? 'ar' : 'en';
    }

    if (function_exists('jawda_locations_normalize_language')) {
        $language = jawda_locations_normalize_language($language, $language ?: 'ar');
    }

    if (function_exists('carbon_get_post_meta')) {
        $selected = carbon_get_post_meta($post_id, $meta_key);
    } else {
        $selected = get_post_meta($post_id, $meta_key, true);
    }

    $type_filter = $allowed_types !== null ? jawda_project_features_normalize_allowed_types($allowed_types) : null;
    $selected = jawda_project_features_normalize_selection($selected, $type_filter);

    if (!$selected) {
        return [];
    }

    $features = jawda_project_features_fetch_all();

    if (!$features) {
        return [];
    }

    $indexed = [];

    foreach ($features as $feature) {
        if (empty($feature['id'])) {
            continue;
        }

        $type_key = isset($feature['feature_type']) ? (string) $feature['feature_type'] : '';
        if ($type_filter && $type_key !== '' && !in_array($type_key, $type_filter, true)) {
            continue;
        }

        $indexed[(int) $feature['id']] = $feature;
    }

    $results = [];

    foreach ($selected as $feature_id) {
        $feature_id = (int) $feature_id;

        if (!isset($indexed[$feature_id])) {
            continue;
        }

        $feature = $indexed[$feature_id];

        if (!jawda_project_features_feature_matches_context($feature, $context)) {
            continue;
        }

        $label = function_exists('jawda_locations_get_label')
            ? jawda_locations_get_label(
                $feature[ 'slug_ar' ] ?? '',
                $feature['name_en'] ?? '',
                $language,
                sprintf('#%d', $feature_id)
            )
            : ($language === 'en'
                ? ($feature['name_en'] ?? '')
                : ($language === 'ar'
                    ? ($feature[ 'slug_ar' ] ?? '')
                    : trim(($feature[ 'slug_ar' ] ?? '') . ' / ' . ($feature['name_en'] ?? ''))
                )
            );

        $feature['label'] = $label !== '' ? $label : (string) $feature_id;
        $results[] = $feature;
    }

    return $results;
}


function jawda_get_project_features_for_project($post_id, $language = null) {
    $meta_keys = jawda_get_project_service_meta_keys();
    $combined = [];

    foreach ($meta_keys as $type => $meta_key) {
        $features = jawda_get_feature_selection_for_post($post_id, 'projects', $meta_key, $language, [$type]);
        if ($features) {
            $combined = array_merge($combined, $features);
        }
    }

    if (!$combined) {
        $combined = jawda_get_feature_selection_for_post($post_id, 'projects', 'jawda_project_feature_ids', $language);
    }

    return $combined ?: [];
}


function jawda_get_property_features_for_property($post_id, $language = null) {
    return jawda_get_feature_selection_for_post($post_id, 'properties', 'jawda_project_feature_ids', $language);
}

/**
 * Returns feature data formatted for display.
 *
 * @param int         $post_id  Post identifier.
 * @param string|null $language Language indicator.
 * @return array
 */
function jawda_prepare_project_features_for_display($post_id, $language = null) {
    $features = jawda_get_project_features_for_project($post_id, $language);

    if (!$features) {
        return [];
    }

    $groups = [
        'feature'  => [],
        'amenity'  => [],
        'facility' => [],
    ];

    foreach ($features as $feature) {
        $type_key = isset($feature['feature_type']) ? (string) $feature['feature_type'] : 'feature';
        if ($type_key === '') {
            $type_key = 'feature';
        }
        if (!isset($groups[$type_key])) {
            $groups[$type_key] = [];
        }

        $image_html = '';
        if (!empty($feature['image_id'])) {
            $image_html = wp_get_attachment_image(
                $feature['image_id'],
                'thumbnail',
                false,
                [
                    'class'   => 'project-services__icon-image',
                    'loading' => 'lazy',
                ]
            );
        }

        $groups[$type_key][] = [
            'id'                 => isset($feature['id']) ? (int) $feature['id'] : 0,
             'slug_ar'             => isset($feature[ 'slug_ar' ]) ? (string) $feature[ 'slug_ar' ] : '',
            'name_en'            => isset($feature['name_en']) ? (string) $feature['name_en'] : '',
            'label'              => isset($feature['label']) ? (string) $feature['label'] : '',
            'image_id'           => isset($feature['image_id']) ? (int) $feature['image_id'] : 0,
            'image_html'         => $image_html,
            'feature_type'       => $type_key,
            'context_projects'   => !empty($feature['context_projects']),
            'context_properties' => !empty($feature['context_properties']),
            'orientation_id'     => isset($feature['orientation_id']) ? (int) $feature['orientation_id'] : 0,
            'facade_id'          => isset($feature['facade_id']) ? (int) $feature['facade_id'] : 0,
        ];
    }

    $ordered_types = ['feature', 'amenity', 'facility'];
    $types = jawda_project_features_get_feature_types();
    $results = [];

    foreach ($ordered_types as $type_key) {
        if (empty($groups[$type_key])) {
            continue;
        }

        $results[] = [
            'type'       => $type_key,
            'type_label' => isset($types[$type_key]) ? $types[$type_key] : '',
            'items'      => array_values($groups[$type_key]),
        ];

        unset($groups[$type_key]);
    }

    if ($groups) {
        foreach ($groups as $type_key => $items) {
            if (empty($items)) {
                continue;
            }

            $results[] = [
                'type'       => $type_key,
                'type_label' => isset($types[$type_key]) ? $types[$type_key] : '',
                'items'      => array_values($items),
            ];
        }
    }

    return $results;
}


function jawda_prepare_property_features_for_display($post_id, $language = null) {
    $features = jawda_get_property_features_for_property($post_id, $language);

    if (!$features) {
        return [];
    }

    $types = jawda_project_features_get_feature_types();

    return array_map(
        static function ($feature) use ($types) {
            $image_html = '';
            if (!empty($feature['image_id'])) {
                $image_html = wp_get_attachment_image(
                    $feature['image_id'],
                    'thumbnail',
                    false,
                    [
                        'class'   => 'project-services__icon-image',
                        'loading' => 'lazy',
                    ]
                );
            }

            $type_key   = isset($feature['feature_type']) ? (string) $feature['feature_type'] : '';
            $type_label = ($type_key !== '' && isset($types[$type_key])) ? $types[$type_key] : '';

            return [
                'id'         => isset($feature['id']) ? (int) $feature['id'] : 0,
                 'slug_ar'     => isset($feature[ 'slug_ar' ]) ? (string) $feature[ 'slug_ar' ] : '',
                'name_en'    => isset($feature['name_en']) ? (string) $feature['name_en'] : '',
                'label'      => isset($feature['label']) ? (string) $feature['label'] : '',
                'image_id'   => isset($feature['image_id']) ? (int) $feature['image_id'] : 0,
                'image_html' => $image_html,
                'feature_type' => $type_key,
                'feature_type_label' => $type_label,
                'context_projects' => !empty($feature['context_projects']),
                'context_properties' => !empty($feature['context_properties']),
                'orientation_id' => isset($feature['orientation_id']) ? (int) $feature['orientation_id'] : 0,
                'facade_id'      => isset($feature['facade_id']) ? (int) $feature['facade_id'] : 0,
            ];
        },
        $features
    );
}
