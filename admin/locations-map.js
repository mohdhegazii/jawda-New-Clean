(function($) {
    'use strict';

    function getSelectionData($picker) {
        const $form = $picker.closest('form');
        const $selects = $form.length
            ? $form.find('select[name="district_id"], select[name="city_id"], select[name="governorate_id"]')
            : $('select[name="district_id"], select[name="city_id"], select[name="governorate_id"]');

        let selected = null;
        $selects.each(function() {
            const $option = $(this).find('option:selected');
            if ($option.length && $option.val()) {
                selected = $option;
                return false;
            }
        });

        if (!selected) {
            return null;
        }

        return {
            lat: parseFloat(selected.attr('data-lat')),
            lng: parseFloat(selected.attr('data-lng')),
            polygon: selected.attr('data-polygon') || ''
        };
    }

    function fitPolygonToMap(map, polygonLayer) {
        if (!map || !polygonLayer) {
            return;
        }

        const bounds = polygonLayer.getBounds();
        if (bounds && bounds.isValid()) {
            map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
            map.invalidateSize();
        }
    }

    function parsePolygonData(polygonData) {
        if (!polygonData) {
            return null;
        }

        const trimmed = String(polygonData).trim();
        if (!trimmed) {
            return null;
        }

        try {
            return JSON.parse(trimmed);
        } catch (error) {
            const hasSeparator = trimmed.indexOf(';') !== -1;
            const rawPoints = hasSeparator ? trimmed.split(';') : trimmed.split('\n');
            const points = rawPoints
                .map(function(item) {
                    const cleaned = item.trim().replace(/[()]/g, '');
                    if (!cleaned) {
                        return null;
                    }
                    const parts = cleaned.split(',').map(function(part) {
                        return part.trim();
                    });
                    if (parts.length < 2) {
                        return null;
                    }
                    const lat = parseFloat(parts[0]);
                    const lng = parseFloat(parts[1]);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) {
                        return null;
                    }
                    return [lng, lat];
                })
                .filter(Boolean);

            if (points.length < 3) {
                return null;
            }

            if (points[0][0] !== points[points.length - 1][0] || points[0][1] !== points[points.length - 1][1]) {
                points.push(points[0]);
            }

            return {
                type: 'Polygon',
                coordinates: [points]
            };
        }
    }

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
            const map = L.map($mapDiv[0]).setView([initialLat, initialLng], 12);
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
            this._polygonLayer = null;

            // رسم البوليجون إذا كان موجوداً في الحقول
            const polygonData = $picker.closest('form').find('textarea[name="polygon_coordinates"]').val();
            if (polygonData) {
                try {
                    const geojson = parsePolygonData(polygonData);
                    if (geojson) {
                        this._polygonLayer = L.geoJSON(geojson, { style: { color: '#ff7800', weight: 2 } }).addTo(map);
                        fitPolygonToMap(map, this._polygonLayer);
                    }
                } catch (e) {
                    console.error('Error parsing polygon data:', e);
                }
            } else {
                const selectionData = getSelectionData($picker);
                if (selectionData) {
                    drawPolygonOnMap(map, selectionData.polygon, selectionData.lat, selectionData.lng, $picker.get(0));
                }
            }
            
            // حل مشكلة الظهور الجزئي
            setTimeout(() => map.invalidateSize(), 500);

            // تعيين مرجع عام للتكامل مع البحث
            window.map = map;
            window.marker = marker;
        });
    }

    // دالة لرسم البوليجون
    function drawPolygonOnMap(map, polygonData, lat, lng, pickerEl) {
        if (!map) return;

        const picker = pickerEl || null;
        if (picker && picker._polygonLayer) {
            map.removeLayer(picker._polygonLayer);
            picker._polygonLayer = null;
        }

        if (polygonData) {
            try {
                const geojson = parsePolygonData(polygonData);
                if (geojson) {
                    const layer = L.geoJSON(geojson, { style: { color: '#ff7800', weight: 2 } }).addTo(map);
                    if (picker) {
                        picker._polygonLayer = layer;
                    }
                    fitPolygonToMap(map, layer);
                }
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
    $(document).on('change', 'select[name="governorate_id"], select[name="city_id"], select[name="district_id"]', function() {
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
                drawPolygonOnMap(map, polygon, lat, lng, this);
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
