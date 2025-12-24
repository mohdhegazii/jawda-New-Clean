<?php
/**
 * Bridge helpers mapping legacy jawda_* lookups to the new jawda_* API.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('jawda_lookups_get_language_field')) {
    /**
     * Determine the correct name field for the current language.
     *
     * @return string Either  'slug_ar'  or 'name_en'.
     */
    function jawda_lookups_get_language_field() {
        $language = '';

        if (function_exists('pll_current_language')) {
            $language = (string) pll_current_language();
        }

        if ($language === '') {
            $language = (function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl()) ? 'ar' : 'en';
        }

        return ($language === 'ar') ?  'slug_ar'  : 'name_en';
    }
}

if (!function_exists('jawda_lookups_format_label')) {
    /**
     * Build a localized label with safe fallbacks.
     *
     * @param array  $item       Lookup row.
     * @param string $name_field Preferred name field.
     *
     * @return string
     */
    function jawda_lookups_format_label($item, $name_field) {
        if (!is_array($item)) {
            return '';
        }

        $candidates = [
            $item[$name_field] ?? '',
            $item['label'] ?? '',
            $item['name'] ?? '',
            $item['name_en'] ?? '',
            $item[ 'slug_ar' ] ?? '',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return (string) $candidate;
            }
        }

        return isset($item['id']) ? (string) $item['id'] : '';
    }
}

if (!function_exists('jawda_get_main_categories_options_bridge')) {
    /**
     * Provide dropdown options for main categories using jawda lookups.
     *
     * @return array [id => label]. Includes a placeholder at key ''.
     */
    function jawda_get_main_categories_options_bridge() {
        if (!function_exists('jawda_get_categories')) {
            return [];
        }

        $language_field = jawda_lookups_get_language_field();
        $categories     = jawda_get_categories(['is_active' => 1]);

        $placeholder_ar = '— اختر التصنيف الرئيسي —';
        $placeholder_en = __('— Select Main Category —', 'jawda');
        $placeholder    = function_exists('jawda_locations_get_placeholder')
            ? jawda_locations_get_placeholder($placeholder_ar, $placeholder_en, 'both')
            : trim($placeholder_ar . ' / ' . $placeholder_en);

        $options = ['' => $placeholder];

        if (empty($categories) || !is_array($categories)) {
            return $options;
        }

        foreach ($categories as $category) {
            $id = isset($category['id']) ? (string) $category['id'] : '';

            if ($id === '') {
                continue;
            }

            $options[$id] = jawda_lookups_format_label($category, $language_field);
        }

        return $options;
    }
}

if (!function_exists('jawda_get_property_types_by_main_category')) {
    /**
     * Fetch property types grouped by main category.
     *
     * @param int|string|null $category_id Specific category to filter; null returns full map.
     *
     * @return array
     */
    function jawda_get_property_types_by_main_category($category_id = null) {
        if (!function_exists('jawda_get_categories') || !function_exists('jawda_get_property_types')) {
            return [];
        }

        $language_field = jawda_lookups_get_language_field();
        $categories     = jawda_get_categories(['is_active' => 1]);
        $types          = jawda_get_property_types(['is_active' => 1]);
        $relations      = function_exists('jawda_get_property_type_category_relations')
            ? jawda_get_property_type_category_relations()
            : [];

        if (empty($categories) || !is_array($categories) || empty($types) || !is_array($types)) {
            return [];
        }

        $category_map = [];

        foreach ($categories as $category) {
            $id = isset($category['id']) ? (string) $category['id'] : '';

            if ($id === '') {
                continue;
            }

            $category_map[$id] = [
                'id'      => $id,
                'label'   => jawda_lookups_format_label($category, $language_field),
                 'slug_ar'  => isset($category[ 'slug_ar' ]) ? (string) $category[ 'slug_ar' ] : '',
                'name_en' => isset($category['name_en']) ? (string) $category['name_en'] : '',
                'types'   => [],
            ];
        }

        foreach ($types as $type) {
            $type_id = isset($type['id']) ? (string) $type['id'] : '';

            if ($type_id === '') {
                continue;
            }

            $type_categories = isset($relations[$type_id]) ? (array) $relations[$type_id] : [];

            if (empty($type_categories)) {
                continue;
            }

            $type_data = [
                'id'       => $type_id,
                'label'    => jawda_lookups_format_label($type, $language_field),
                 'slug_ar'   => isset($type[ 'slug_ar' ]) ? (string) $type[ 'slug_ar' ] : '',
                'name_en'  => isset($type['name_en']) ? (string) $type['name_en'] : '',
                'icon_id'  => isset($type['icon_id']) ? (string) $type['icon_id'] : '',
                'icon_url' => isset($type['icon_url']) ? (string) $type['icon_url'] : '',
            ];

            foreach ($type_categories as $cat_id) {
                $cat_key = (string) $cat_id;

                if (!isset($category_map[$cat_key])) {
                    continue;
                }

                $category_map[$cat_key]['types'][$type_id] = $type_data;
            }
        }

        if ($category_id !== null) {
            $key = (string) $category_id;

            return isset($category_map[$key]) ? array_values($category_map[$key]['types']) : [];
        }

        return $category_map;
    }
}

if (!function_exists('jawda_get_property_types_grouped_by_category')) {
    /**
     * Simplified grouped list for JS consumption.
     *
     * @return array [category_id => [type arrays...]]
     */
    function jawda_get_property_types_grouped_by_category() {
        $map      = jawda_get_property_types_by_main_category(null);
        $grouped  = [];

        if (empty($map) || !is_array($map)) {
            return $grouped;
        }

        foreach ($map as $category_id => $category) {
            $grouped[$category_id] = array_values($category['types'] ?? []);
        }

        return $grouped;
    }
}
