(function($) {
    'use strict';

    function hydrateWidget($container, data) {
        var $widget = $container.find('.jawda-location-widget');
        if (!$widget.length) {
            return;
        }

        if (typeof data.gov !== 'undefined') {
            $widget.attr('data-selected-governorate', data.gov || '');
        }
        if (typeof data.city !== 'undefined') {
            $widget.attr('data-selected-city', data.city || '');
        }
        if (typeof data.district !== 'undefined') {
            $widget.attr('data-selected-district', data.district || '');
        }

        if (data.mapLat || data.mapLng) {
            $widget.attr('data-has-coordinates', '1');
            $widget.find('input[name="jawda_project_latitude"]').val(data.mapLat || '');
            $widget.find('input[name="jawda_project_longitude"]').val(data.mapLng || '');
        }

        if (window.jawdaLocationWidgets && typeof window.jawdaLocationWidgets.init === 'function') {
            window.jawdaLocationWidgets.init($container);
        }
    }

    if (typeof inlineEditPost !== 'undefined') {
        var originalEdit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            originalEdit.apply(this, arguments);

            var postId = (typeof id === 'object') ? parseInt(this.getId(id), 10) : parseInt(id, 10);
            if (!postId) {
                return;
            }

            var $editRow = $('#edit-' + postId);
            var $postRow = $('#post-' + postId);
            var locationData = $('.jawda-location-data', $postRow);

            hydrateWidget($editRow, {
                gov: locationData.data('gov-id') || '',
                city: locationData.data('city-id') || '',
                district: locationData.data('district-id') || '',
                mapLat: locationData.data('map-lat') || '',
                mapLng: locationData.data('map-lng') || ''
            });
        };
    }

    $(function() {
        var $bulk = $('#bulk-edit');
        if ($bulk.length && window.jawdaLocationWidgets && typeof window.jawdaLocationWidgets.init === 'function') {
            window.jawdaLocationWidgets.init($bulk);
        }
    });
})(jQuery);
