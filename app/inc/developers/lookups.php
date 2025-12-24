<?php
/**
 * Developer types lookup provider.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default developer types lookup entries.
 *
 * @return array
 */
function jawda_get_default_developer_types() {
    return [
        ['id' => 1, 'label' => __('National', 'jawda')],
        ['id' => 2, 'label' => __('Regional', 'jawda')],
        ['id' => 3, 'label' => __('International', 'jawda')],
        ['id' => 4, 'label' => __('Government / Semi-government', 'jawda')],
    ];
}

add_filter('jawda_developer_types', function($types) {
    if (empty($types) || !is_array($types)) {
        $types = [];
    }

    // Always ensure the default set exists; allow consumers to override via the filter.
    if (empty($types)) {
        $types = jawda_get_default_developer_types();
    }

    return $types;
});
