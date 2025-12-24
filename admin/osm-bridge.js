(function($) {
    $(document).ready(function() {
        setTimeout(function() {
            const $btn = $('#osm-search-btn');
            if (!$btn.length) return;

            $btn.off('click').on('click', function(e) {
                e.preventDefault();
                const q = $('#osm-search-input').val();
                if (!q) return alert('الرجاء إدخال اسم المكان');

                $btn.text('جاري المعالجة...').prop('disabled', true);
                const proxyUrl = window.location.origin + '/masharf/wp-content/themes/jawda-New-Clean/admin/osm-proxy.php?q=' + encodeURIComponent(q);

                $.getJSON(proxyUrl, function(data) {
                    if (data && data.length > 0) {
                        const res = data[0];
                        
                        // 1. رسم الـ Polygon أولاً
                        if (window.map) {
                            if (window.currentPolygon) window.map.removeLayer(window.currentPolygon);
                            window.currentPolygon = L.geoJSON(res.geojson, {
                                style: { color: '#ff7800', weight: 3, fillOpacity: 0.3 }
                            }).addTo(window.map);

                            // أ- Fit to 100% bounds
                            window.map.fitBounds(window.currentPolygon.getBounds(), { padding: [30, 30] });

                            // ب- وضع الماركر في السنتر الحقيقي للمضلع
                            const center = window.currentPolygon.getBounds().getCenter();
                            if (window.marker) window.marker.setLatLng(center);
                            
                            // 2. ملأ الحقول النصية (نستخدم إحداثيات السنتر)
                            $('input[name*="latitude"]').val(center.lat);
                            $('input[name*="longitude"]').val(center.lng);
                            $('#polygon_coordinates').val(JSON.stringify(res.geojson));
                        }
                        
                        alert('✅ تم الجلب والضبط.. اضغط حفظ الآن');
                    } else {
                        alert('❌ لم يتم العثور على نتائج');
                    }
                }).always(function() {
                    $btn.text('جلب الحدود').prop('disabled', false);
                });
            });
        }, 1000);
    });
})(jQuery);
