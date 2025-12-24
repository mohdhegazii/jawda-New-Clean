(function($) {
    'use strict';

    function initMap() {
        $('.jawda-location-picker').each(function() {
            const $picker = $(this);
            const $mapDiv = $picker.find('.jawda-location-picker__map');
            const $latInput = $($picker.data('lat-input'));
            const $lngInput = $($picker.data('lng-input'));

            if ($mapDiv.data('initialized')) return;

            const initialLat = parseFloat($mapDiv.data('initial-lat')) || 30.0444;
            const initialLng = parseFloat($mapDiv.data('initial-lng')) || 31.2357;

            // إنشاء الخريطة
            window.map = L.map($mapDiv[0]).setView([initialLat, initialLng], 12);
            $mapDiv.data('initialized', true);

            // حدود مصر
            const egyptBounds = L.latLngBounds([22.0, 24.5], [31.9, 37.0]);
            map.setMaxBounds(egyptBounds);
            map.on('drag', function() { map.panInsideBounds(egyptBounds, { animate: false }); });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

            // تحديث الإحداثيات عند تحريك الماركر
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                $latInput.val(pos.lat.toFixed(7));
                $lngInput.val(pos.lng.toFixed(7));
            });

            // تحديث عند الضغط على الخريطة
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                $latInput.val(e.latlng.lat.toFixed(7));
                $lngInput.val(e.latlng.lng.toFixed(7));
            });

            // تخزين كائن الخريطة للوصول إليه لاحقاً
            this.jawdaLocationPicker = map;
            this._marker = marker;

            // رسم البوليجون إذا كان موجوداً
            const polygonData = $('textarea[name="polygon_coordinates"]').val();
            if (polygonData) {
                try {
                    const geojson = JSON.parse(polygonData);
                    if (window.currentPolygon) map.removeLayer(window.currentPolygon);
                    window.currentPolygon = L.geoJSON(geojson, {style:{color:'#ff7800', weight:2}}).addTo(map);
                    map.fitBounds(window.currentPolygon.getBounds());
                } catch (e) {
                    console.error('Error parsing polygon data:', e);
                }
            }
            
            // حل مشكلة الظهور الجزئي
            setTimeout(() => map.invalidateSize(), 500);
        });
    }

    // دالة لرسم البوليجون
    function drawPolygonOnMap(map, polygonData, lat, lng) {
        if (!map) return;

        // إزالة البوليجون الحالي
        if (window.currentPolygon) {
            map.removeLayer(window.currentPolygon);
            window.currentPolygon = null;
        }

        if (polygonData) {
            try {
                const geojson = JSON.parse(polygonData);
                window.currentPolygon = L.geoJSON(geojson, {style:{color:'#ff7800', weight:2}}).addTo(map);
                map.fitBounds(window.currentPolygon.getBounds());
            } catch (e) {
                console.error('Error parsing polygon data from option:', e);
                // Fallback to flyTo if polygon parsing fails
                if (lat && lng) {
                    map.flyTo([lat, lng], 13);
                }
            }
        } else if (lat && lng) {
             map.flyTo([lat, lng], 13);
        }
    }

    // مراقبة الـ Dropdown (المحافظات والمدن)
    $(document).on('change', 'select[name="governorate_id"], select[name="city_id"]', function() {
        const $option = $(this).find('option:selected');
        const lat = parseFloat($option.attr('data-lat'));
        const lng = parseFloat($option.attr('data-lng'));
        const polygon = $option.attr('data-polygon');

        $('.jawda-location-picker').each(function() {
            if (this.jawdaLocationPicker) {
                const map = this.jawdaLocationPicker;
                if (lat && lng) {
                     this._marker.setLatLng([lat, lng]);
                }
                drawPolygonOnMap(map, polygon, lat, lng);
            }
        });
    });

    $(window).on('load', initMap);
    $(document).ready(initMap);
    // لدعم الـ Tabs في ووردبريس
    $('.nav-tab').on('click', () => setTimeout(initMap, 200));

})(jQuery);

// --- Jawda OSM Search Extension ---
(function($) {
    $(window).on('load', function() {
        const mapTarget = document.getElementById('locations-map');
        if (!mapTarget) return;

        const searchUI = `
            <div id="osm-search-wrap" style="background:#fff; padding:10px; border:1px solid #ccd0d4; border-bottom:none; margin-top:15px; display:flex; gap:5px;">
                <input type="text" id="osm-search-input" placeholder="🔍 ابحث عن المكان..." style="flex:1; height:30px;">
                <button type="button" id="osm-search-btn" class="button button-secondary">جلب الحدود</button>
            </div>`;
        
        $(mapTarget).before(searchUI);
        $(mapTarget).css({'border-top': 'none'});

        $('#osm-search-btn').on('click', function() {
            const q = $('#osm-search-input').val();
            if (!q) return;

            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&polygon_geojson=1&limit=1')
                .then(r => r.json())
                .then(data => {
                    if (data.length > 0) {
                        const res = data[0];
                        // استخدام window.map اللي متعرف في الكود الأصلي للنسخة 23
                        if (typeof window.map !== 'undefined') {
                            window.map.setView([res.lat, res.lon], 13);
                            // تحديث الحقول
                            $('input[name*="latitude"]').val(res.lat);
                            $('input[name*="longitude"]').val(res.lon);
                            $('textarea[name="polygon_coordinates"]').val(JSON.stringify(res.geojson));
                            
                            // رسم الـ Polygon
                            if (window.currentPolygon) window.map.removeLayer(window.currentPolygon);
                            window.currentPolygon = L.geoJSON(res.geojson, {style:{color:'#ff7800', weight:2}}).addTo(window.map);
                        }
                        alert('✅ تم جلب البيانات بنجاح');
                    }
                });
        });
    });
})(jQuery);

// Load Bridge
jQuery.getScript(window.location.origin + '/masharf/wp-content/themes/jawda-New-Clean/admin/osm-bridge.js');

