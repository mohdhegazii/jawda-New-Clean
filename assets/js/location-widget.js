(function($) {
    'use strict';

    var api = {
        getSettings: function() {
            if (window.AQARAND_LOC && window.AQARAND_LOC.ajax_url) {
                return window.AQARAND_LOC;
            }

            if (window.CF_DEP && CF_DEP.ajax_url) {
                return CF_DEP;
            }

            return null;
        },
        getConfig: function($widget) {
            var placeholders = {};
            try {
                placeholders = JSON.parse($widget.attr('data-placeholders') || '{}');
            } catch (e) {
                placeholders = {};
            }

            var settings = api.getSettings() || {};
            var language = $widget.data('language') || settings.language || settings.lang || 'ar';
            if ($.inArray(language, ['ar', 'en']) === -1) {
                language = 'ar';
            }

            return {
                context: $widget.data('context') || 'meta-box',
                language: language,
                selected: {
                    gov: String($widget.data('selected-governorate') || ''),
                    city: String($widget.data('selected-city') || ''),
                    district: String($widget.data('selected-district') || ''),
                },
                placeholders: placeholders,
                allowNoChange: String($widget.data('no-change')) === '1',
                hasMap: String($widget.data('has-map')) === '1',
                hasCoordinates: String($widget.data('has-coordinates')) === '1',
                readonly: String($widget.data('readonly')) === '1'
            };
        },
        fillSelect: function($select, options, placeholder, allowNoChange) {
            $select.empty();

            if (allowNoChange) {
                $select.append($('<option>', { value: '-1', text: placeholder || '' }));
            } else if (placeholder) {
                $select.append($('<option>', { value: '', text: placeholder }));
            }

            $.each(options || {}, function(value, data) {
                var opt = data || {};
                var label = typeof opt === 'object' && opt.label ? opt.label : opt;

                if (value === '' || typeof label === 'undefined') {
                    return;
                }

                var $option = $('<option>', {
                    value: value,
                    text: label
                });

                if (opt.lat) {
                    $option.attr('data-lat', opt.lat);
                }
                if (opt.lng) {
                    $option.attr('data-lng', opt.lng);
                }

                $select.append($option);
            });
        },
        fetch: function(action, params) {
            var settings = api.getSettings();

            if (!settings || !settings.ajax_url) {
                return $.Deferred().reject().promise();
            }

            var lang = settings.lang || settings.language || 'ar';
            if ($.inArray(lang, ['ar', 'en']) === -1) {
                lang = 'ar';
            }

            var resolvedAction = action;
            if (settings.actions && settings.actions[action]) {
                resolvedAction = settings.actions[action];
            }

            var data = $.extend({
                action: resolvedAction,
                nonce: settings.nonce,
                lang: lang
            }, params || {});

            return $.getJSON(settings.ajax_url, data);
        },
        setSelected: function($select, value) {
            if (typeof value === 'undefined' || value === null || value === '') {
                $select.val('');
                return;
            }
            $select.val(String(value));
        },
        withMap: function($widget, callback) {
            if (typeof callback !== 'function') {
                return;
            }
            var picker = $widget.find('.jawda-location-picker').get(0);
            if (!picker) {
                return;
            }

            var controller = picker.jawdaLocationPicker;
            if (controller) {
                callback(controller);
                return;
            }

            picker.addEventListener('jawdaLocationPickerReady', function onReady(event) {
                picker.removeEventListener('jawdaLocationPickerReady', onReady);
                callback(event.detail);
            });
        },
        focusFromSelect: function($widget, $select, reason) {
            var value = $select.val();
            if (!value || value === '-1') {
                return;
            }

            var $option = $select.find('option[value="' + value + '"]');
            if (!$option.length) {
                return;
            }

            var lat = $option.data('lat');
            var lng = $option.data('lng');
            if (!lat && !lng) {
                return;
            }

            api.withMap($widget, function(controller) {
                if (!controller || typeof controller.setCoordinate !== 'function') {
                    return;
                }

                if (reason === 'hydrate' && typeof controller.getCoordinate === 'function') {
                    var current = controller.getCoordinate();
                    if (current && current.lat !== null && current.lng !== null) {
                        return;
                    }
                }

                controller.setCoordinate(lat, lng, { pan: true });
            });
        }
    };

    function resolvePlaceholder(config, key) {
        if (config.allowNoChange && config.placeholders && config.placeholders.no_change) {
            return config.placeholders.no_change;
        }

        return (config.placeholders && config.placeholders[key]) ? config.placeholders[key] : '';
    }

    function hydrateGovernorates($widget, config) {
        var $gov = $widget.find('.cf-dep-governorate select');
        if (!$gov.length) {
            return $.Deferred().resolve().promise();
        }

        if ($gov.children().length > 0 && $gov.find('option').length > (config.allowNoChange ? 1 : 0)) {
            api.setSelected($gov, config.selected.gov);
            return $.Deferred().resolve().promise();
        }

        return api.fetch('cf_dep_get_governorates').done(function(res) {
            if (res.success && res.data && res.data.options) {
                api.fillSelect($gov, res.data.options, resolvePlaceholder(config, 'select_gov'), config.allowNoChange);
                api.setSelected($gov, config.selected.gov);
            }
        });
    }

    function hydrateCities($widget, config, reason) {
        var $gov = $widget.find('.cf-dep-governorate select');
        var $city = $widget.find('.cf-dep-city select');
        var $district = $widget.find('.cf-dep-district select');
        var govId = $gov.val();

        if (!govId || govId === '-1') {
            api.fillSelect($city, {}, resolvePlaceholder(config, 'select_gov_first'), config.allowNoChange);
            api.fillSelect($district, {}, resolvePlaceholder(config, 'select_city_first'), config.allowNoChange);
            $city.prop('disabled', false);
            $district.prop('disabled', true);
            return $.Deferred().resolve().promise();
        }

        var hydratedCity = reason === 'hydrate' ? (config.selected.city || $city.val()) : '';
        var hasPreloadedCities = $city.children().length > (config.allowNoChange ? 1 : 0);

        if (reason === 'hydrate' && hasPreloadedCities && hydratedCity && $city.find('option[value="' + hydratedCity + '"]').length) {
            $city.prop('disabled', false);
            api.setSelected($city, hydratedCity);
            return hydrateDistricts($widget, config, 'hydrate');
        }

        $city.prop('disabled', true);
        api.fillSelect($city, {}, resolvePlaceholder(config, 'select_city'), config.allowNoChange);

        return api.fetch('cf_dep_get_cities', { gov_id: govId }).done(function(res) {
            $city.prop('disabled', false);
            if (res.success && res.data && res.data.options) {
                api.fillSelect($city, res.data.options, resolvePlaceholder(config, 'select_city'), config.allowNoChange);
                var selectedCity = reason === 'hydrate' ? config.selected.city : $city.val();
                if (selectedCity) {
                    api.setSelected($city, selectedCity);
                    hydrateDistricts($widget, config, 'hydrate');
                }
            }
        });
    }

    function hydrateDistricts($widget, config, reason) {
        var $city = $widget.find('.cf-dep-city select');
        var $district = $widget.find('.cf-dep-district select');
        var cityId = $city.val();

        if (!cityId || cityId === '-1') {
            api.fillSelect($district, {}, resolvePlaceholder(config, 'select_city_first'), config.allowNoChange);
            $district.prop('disabled', true);
            return $.Deferred().resolve().promise();
        }

        var hydratedDistrict = reason === 'hydrate' ? (config.selected.district || $district.val()) : '';
        var hasPreloadedDistricts = $district.children().length > (config.allowNoChange ? 1 : 0);

        if (reason === 'hydrate' && hasPreloadedDistricts && hydratedDistrict && $district.find('option[value="' + hydratedDistrict + '"]').length) {
            $district.prop('disabled', false);
            api.setSelected($district, hydratedDistrict);
            return $.Deferred().resolve().promise();
        }

        $district.prop('disabled', true);
        api.fillSelect($district, {}, resolvePlaceholder(config, 'select_district'), config.allowNoChange);

        return api.fetch('cf_dep_get_districts', { city_id: cityId }).done(function(res) {
            $district.prop('disabled', false);
            if (res.success && res.data && res.data.options) {
                api.fillSelect($district, res.data.options, resolvePlaceholder(config, 'select_district'), config.allowNoChange);
                if (reason === 'hydrate' && config.selected.district) {
                    api.setSelected($district, config.selected.district);
                }
            }
        });
    }

    function bindEvents($widget, config) {
        var $gov = $widget.find('.cf-dep-governorate select');
        var $city = $widget.find('.cf-dep-city select');
        var $district = $widget.find('.cf-dep-district select');

        $gov.on('change', function(event, reason) {
            config.selected.city = '';
            config.selected.district = '';
            hydrateCities($widget, config, reason).done(function() {
                api.focusFromSelect($widget, $gov, reason);
            });
        });

        $city.on('change', function(event, reason) {
            config.selected.district = '';
            hydrateDistricts($widget, config, reason);
            api.focusFromSelect($widget, $city, reason);
        });

        $district.on('change', function(event, reason) {
            api.focusFromSelect($widget, $district, reason);
        });
    }

    function initWidget($widget) {
        if ($widget.data('jawdaLocationWidgetInit')) {
            return;
        }

        var config = api.getConfig($widget);

        var $gov = $widget.find('.cf-dep-governorate select');
        var $city = $widget.find('.cf-dep-city select');
        var $district = $widget.find('.cf-dep-district select');

        if (!$gov.length || !$city.length || !$district.length) {
            return;
        }

        $district.prop('disabled', !$city.val());

        bindEvents($widget, config);
        hydrateGovernorates($widget, config).done(function() {
            var hasPreloadedCities = $city.find('option').length > (config.allowNoChange ? 1 : 0);
            var hasPreloadedDistricts = $district.find('option').length > (config.allowNoChange ? 1 : 0);

            if (config.selected.gov) {
                // If the page already rendered the city/district options with saved selections, reuse them
                // without re-triggering AJAX hydration that could wipe the city value.
                if (hasPreloadedCities && config.selected.city && $city.find('option[value="' + config.selected.city + '"]').length) {
                    $city.prop('disabled', false);
                    api.setSelected($city, config.selected.city);

                    if (hasPreloadedDistricts && config.selected.district && $district.find('option[value="' + config.selected.district + '"]').length) {
                        $district.prop('disabled', false);
                        api.setSelected($district, config.selected.district);
                        api.focusFromSelect($widget, $district, 'hydrate');
                        return;
                    }

                    // Districts need hydration even though cities are present.
                    $city.trigger('change', ['hydrate']);
                    return;
                }

                $gov.trigger('change', ['hydrate']);
            } else {
                api.fillSelect($city, {}, resolvePlaceholder(config, 'select_gov_first'), config.allowNoChange);
                api.fillSelect($district, {}, resolvePlaceholder(config, 'select_city_first'), config.allowNoChange);
            }
        });

        api.withMap($widget, function(controller) {
            if (!controller || typeof controller.getCoordinate !== 'function') {
                return;
            }

            var hasCoords = controller.getCoordinate();
            if (!hasCoords && config.selected.district) {
                api.focusFromSelect($widget, $district, 'hydrate');
            } else if (!hasCoords && config.selected.city) {
                api.focusFromSelect($widget, $city, 'hydrate');
            } else if (!hasCoords && config.selected.gov) {
                api.focusFromSelect($widget, $gov, 'hydrate');
            }
        });

        $widget.data('jawdaLocationWidgetInit', true);
    }

    window.JawdaLocationWidgets = window.JawdaLocationWidgets || {
        init: function(container) {
            var $root = container ? $(container) : $(document);
            $root.find('.jawda-location-widget').each(function() {
                initWidget($(this));
            });
        }
    };

    $(function() {
        window.JawdaLocationWidgets.init(document);
    });
})(jQuery);
