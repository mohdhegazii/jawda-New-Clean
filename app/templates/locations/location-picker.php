<?php
/**
 * Shared location picker (governorate → city → district + map) for admin and frontend forms.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('jawda_render_location_picker')) {
    /**
     * Render a unified location picker widget.
     *
     * @param array $args {
     *   @type string $context   Context key: meta-box|quick-edit|bulk-edit|frontend.
     *   @type array  $selected  Selected IDs [governorate, city, district].
     *   @type array  $governorates Governorate options arrays.
     *   @type array  $cities    City options arrays.
     *   @type array  $districts District options arrays.
     *   @type array  $map       Map payload with lat/lng/zoom/address.
     *   @type array  $placeholders Placeholder strings keyed by select.
     *   @type array  $labels    Label strings keyed by select.
     *   @type array  $inherit   Inheritance toggle data.
     *   @type array  $field_names Custom field names for selects.
     *   @type bool   $include_map Whether to render the map widget.
     *   @type bool   $compact  Compact layout flag (smaller map & spacing).
     *   @type bool   $allow_no_change Allow "no change" option (-1) for bulk edit.
     * }
     */
    function jawda_render_location_picker(array $args = []) {
        $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

        $defaults = [
            'context'        => 'meta-box',
            'selected'       => ['governorate' => 0, 'city' => 0, 'district' => 0],
            'governorates'   => [],
            'cities'         => [],
            'districts'      => [],
            'map'            => ['lat' => '', 'lng' => '', 'zoom' => 13, 'address' => ''],
            'placeholders'   => [],
            'labels'         => [],
            'inherit'        => ['enabled' => false, 'checked' => false, 'label' => '', 'description' => '', 'disabled' => false],
            'field_names'    => [
                'governorate' => 'loc_governorate_id',
                'city'        => 'loc_city_id',
                'district'    => 'loc_district_id',
                'lat'         => 'jawda_project_latitude',
                'lng'         => 'jawda_project_longitude',
            ],
            'include_map'    => true,
            'compact'        => false,
            'language'       => $is_ar ? 'ar' : 'en',
            'allow_no_change'=> false,
            'readonly'       => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $selected_governorate = absint($args['selected']['governorate'] ?? 0);
        $selected_city        = absint($args['selected']['city'] ?? 0);
        $selected_district    = absint($args['selected']['district'] ?? 0);

        $labels = wp_parse_args($args['labels'], [
            'gov'      => $is_ar ? 'المحافظة' : __('Governorate', 'jawda'),
            'city'     => $is_ar ? 'المدينة' : __('City', 'jawda'),
            'district' => $is_ar ? 'المنطقة/الحي' : __('District / Neighborhood', 'jawda'),
        ]);

        $placeholders = wp_parse_args($args['placeholders'], [
            'select_gov'        => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المحافظة —', __('— Select Governorate —', 'jawda'), $args['language'])
                : ($is_ar ? '— اختر المحافظة —' : __('— Select Governorate —', 'jawda')),
            'select_city'       => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المدينة —', __('— Select City —', 'jawda'), $args['language'])
                : ($is_ar ? '— اختر المدينة —' : __('— Select City —', 'jawda')),
            'select_city_first' => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المدينة أولًا —', __('— Select City First —', 'jawda'), $args['language'])
                : ($is_ar ? '— اختر المدينة أولًا —' : __('— Select City First —', 'jawda')),
            'select_gov_first'  => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المحافظة أولًا —', __('— Select Governorate First —', 'jawda'), $args['language'])
                : ($is_ar ? '— اختر المحافظة أولًا —' : __('— Select Governorate First —', 'jawda')),
            'select_district'   => function_exists('jawda_locations_get_placeholder')
                ? jawda_locations_get_placeholder('— اختر المنطقة/الحي —', __('— Select District / Neighborhood —', 'jawda'), $args['language'])
                : ($is_ar ? '— اختر المنطقة/الحي —' : __('— Select District / Neighborhood —', 'jawda')),
            'no_change'         => __('— No Change —', 'jawda'),
        ]);

        $map_payload = wp_parse_args(
            is_array($args['map']) ? $args['map'] : [],
            ['lat' => '', 'lng' => '', 'zoom' => 13, 'address' => '']
        );

        if ($map_payload['lat'] === '' || $map_payload['lng'] === '') {
            $resolved = class_exists('Jawda_Location_Service')
                ? Jawda_Location_Service::resolve_coordinates_from_hierarchy($selected_governorate, $selected_city, $selected_district)
                : null;

            if ($resolved) {
                if ($map_payload['lat'] === '') {
                    $map_payload['lat'] = (string) $resolved['lat'];
                }
                if ($map_payload['lng'] === '') {
                    $map_payload['lng'] = (string) $resolved['lng'];
                }
            }
        }

        $widget_classes = ['jawda-location-widget'];
        if ($args['compact']) {
            $widget_classes[] = 'jawda-location-widget--compact';
        }

        $widget_attrs = [
            'class'                    => implode(' ', $widget_classes),
            'data-context'             => esc_attr($args['context']),
            'data-language'            => esc_attr($args['language']),
            'data-selected-governorate'=> esc_attr($selected_governorate),
            'data-selected-city'       => esc_attr($selected_city),
            'data-selected-district'   => esc_attr($selected_district),
            'data-placeholders'        => esc_attr(wp_json_encode($placeholders)),
            'data-has-map'             => $args['include_map'] ? '1' : '0',
            'data-has-coordinates'     => ($map_payload['lat'] !== '' && $map_payload['lng'] !== '') ? '1' : '0',
            'data-no-change'           => $args['allow_no_change'] ? '1' : '0',
            'data-readonly'            => $args['readonly'] ? '1' : '0',
        ];

        $field_ids = [
            'governorate' => wp_unique_id($args['field_names']['governorate'] . '-'),
            'city'        => wp_unique_id($args['field_names']['city'] . '-'),
            'district'    => wp_unique_id($args['field_names']['district'] . '-'),
            'lat'         => wp_unique_id('jawda-lat-'),
            'lng'         => wp_unique_id('jawda-lng-'),
            'map'         => wp_unique_id('jawda-location-map-'),
        ];

        // Keep widgets visually interactive even when marked as read-only; field names are blanked
        // upstream to prevent saving, so we don't need to disable the selects themselves.
        $readonly_attr = '';
        ?>
        <div <?php foreach ($widget_attrs as $attr => $value) { printf('%s="%s" ', esc_attr($attr), esc_attr($value)); } ?>>
            <?php if (!empty($args['inherit']['enabled'])) : ?>
                <div class="jawda-location-inherit">
                    <label>
                        <input type="checkbox" name="jawda_inherit_project_location" value="1" <?php checked(!empty($args['inherit']['checked'])); ?> <?php disabled(!empty($args['inherit']['disabled'])); ?> />
                        <strong><?php echo esc_html($args['inherit']['label']); ?></strong>
                    </label>
                    <?php if (!empty($args['inherit']['description'])) : ?>
                        <p class="description"><?php echo esc_html($args['inherit']['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="jawda-location-grid">
                <div class="jawda-location-grid__fields">
                    <div class="jawda-location-field cf-dep-governorate">
                        <label for="<?php echo esc_attr($field_ids['governorate']); ?>"><strong><?php echo esc_html($labels['gov']); ?></strong></label>
                        <select id="<?php echo esc_attr($field_ids['governorate']); ?>" name="<?php echo esc_attr($args['readonly'] ? '' : $args['field_names']['governorate']); ?>"
                                data-selected="<?php echo esc_attr($selected_governorate); ?>"<?php echo $readonly_attr; ?>>
                            <?php if ($args['allow_no_change']) : ?>
                                <option value="-1"><?php echo esc_html($placeholders['no_change']); ?></option>
                            <?php endif; ?>
                            <option value="">
                                <?php echo esc_html($placeholders['select_gov']); ?>
                            </option>
                            <?php if (!empty($args['governorates'])) :
                                foreach ($args['governorates'] as $gov) :
                                    $label = isset($gov[ 'slug_ar' ], $gov['name_en'])
                                        ? (function_exists('jawda_locations_get_label')
                                            ? jawda_locations_get_label($gov[ 'slug_ar' ], $gov['name_en'], $args['language'], sprintf('#%d', (int) $gov['id']))
                                            : ($is_ar ? ($gov[ 'slug_ar' ] ?? '') : ($gov['name_en'] ?? '')))
                                        : '';
                                    ?>
                                    <option value="<?php echo esc_attr($gov['id']); ?>" data-lat="<?php echo esc_attr($gov['latitude'] ?? ''); ?>" data-lng="<?php echo esc_attr($gov['longitude'] ?? ''); ?>" <?php selected($selected_governorate, $gov['id']); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                        </select>
                    </div>

                    <div class="jawda-location-field cf-dep-city">
                        <label for="<?php echo esc_attr($field_ids['city']); ?>"><strong><?php echo esc_html($labels['city']); ?></strong></label>
                        <select id="<?php echo esc_attr($field_ids['city']); ?>" name="<?php echo esc_attr($args['readonly'] ? '' : $args['field_names']['city']); ?>"
                                class="jawda-city-select" data-selected="<?php echo esc_attr($selected_city); ?>"<?php echo $readonly_attr; ?>>
                            <?php if ($args['allow_no_change']) : ?>
                                <option value="-1"><?php echo esc_html($placeholders['no_change']); ?></option>
                            <?php endif; ?>
                            <option value=""><?php echo esc_html($selected_governorate ? $placeholders['select_city'] : $placeholders['select_gov_first']); ?></option>
                            <?php if ($args['cities']) :
                                foreach ($args['cities'] as $city) :
                                    $label = isset($city[ 'slug_ar' ], $city['name_en'])
                                        ? (function_exists('jawda_locations_get_label')
                                            ? jawda_locations_get_label($city[ 'slug_ar' ], $city['name_en'], $args['language'], sprintf('#%d', (int) $city['id']))
                                            : ($is_ar ? ($city[ 'slug_ar' ] ?? '') : ($city['name_en'] ?? '')))
                                        : '';
                                    ?>
                                    <option value="<?php echo esc_attr($city['id']); ?>" data-lat="<?php echo esc_attr($city['latitude'] ?? ''); ?>" data-lng="<?php echo esc_attr($city['longitude'] ?? ''); ?>" <?php selected($selected_city, $city['id']); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                        </select>
                    </div>

                    <div class="jawda-location-field cf-dep-district">
                        <label for="<?php echo esc_attr($field_ids['district']); ?>"><strong><?php echo esc_html($labels['district']); ?></strong></label>
                        <select id="<?php echo esc_attr($field_ids['district']); ?>" name="<?php echo esc_attr($args['readonly'] ? '' : $args['field_names']['district']); ?>"
                                class="jawda-district-select" data-selected="<?php echo esc_attr($selected_district); ?>"<?php echo $readonly_attr; ?>>
                            <?php if ($args['allow_no_change']) : ?>
                                <option value="-1"><?php echo esc_html($placeholders['no_change']); ?></option>
                            <?php endif; ?>
                            <option value=""><?php echo esc_html($selected_city ? $placeholders['select_district'] : $placeholders['select_city_first']); ?></option>
                            <?php if ($args['districts']) :
                                foreach ($args['districts'] as $district) :
                                    $label = isset($district[ 'slug_ar' ], $district['name_en'])
                                        ? (function_exists('jawda_locations_get_label')
                                            ? jawda_locations_get_label($district[ 'slug_ar' ], $district['name_en'], $args['language'], sprintf('#%d', (int) $district['id']))
                                            : ($is_ar ? ($district[ 'slug_ar' ] ?? '') : ($district['name_en'] ?? '')))
                                        : '';
                                    ?>
                                    <option value="<?php echo esc_attr($district['id']); ?>" data-lat="<?php echo esc_attr($district['latitude'] ?? ''); ?>" data-lng="<?php echo esc_attr($district['longitude'] ?? ''); ?>" <?php selected($selected_district, $district['id']); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="cf-dep-errors-box">
                        <div class="jawda-location-errors" role="alert"></div>
                    </div>
                </div>

                <?php if ($args['include_map']) : ?>
                    <div class="jawda-location-grid__map">
                        <?php
                        if (function_exists('jawda_locations_render_coordinate_fields')) {
                            jawda_locations_render_coordinate_fields([
                                'lat_id'    => $field_ids['lat'],
                                'lat_name'  => $args['readonly'] ? '' : $args['field_names']['lat'],
                                'lat_value' => (string) $map_payload['lat'],
                                'lng_id'    => $field_ids['lng'],
                                'lng_name'  => $args['readonly'] ? '' : $args['field_names']['lng'],
                                'lng_value' => (string) $map_payload['lng'],
                                'map_id'    => $field_ids['map'],
                                'label'     => $is_ar ? 'موقع الخريطة' : __('Map Preview', 'jawda'),
                            ]);
                        } else {
                            echo '<p>' . esc_html__('Map component is unavailable.', 'jawda') . '</p>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
