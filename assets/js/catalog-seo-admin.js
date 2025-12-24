jQuery(function ($) {
    var $level = $('#hegzz_location_level');
    var $gov = $('#hegzz_gov');
    var $city = $('#hegzz_city');
    var $district = $('#hegzz_district');

    function resetSelect($el) {
        $el.val('');
    }

    function toggleDisabled($el, disabled) {
        if (disabled) {
            $el.prop('disabled', true);
        } else {
            $el.prop('disabled', false);
        }
    }

    function filterCities() {
        var govVal = $gov.val();
        $city.find('option').each(function () {
            var govId = $(this).data('gov');
            if (!govVal || !govId) {
                $(this).show();
                return;
            }
            if (parseInt(govId, 10) === parseInt(govVal, 10)) {
                $(this).show();
            } else {
                if ($(this).is(':selected')) {
                    resetSelect($city);
                }
                $(this).hide();
            }
        });
    }

    function filterDistricts() {
        var cityVal = $city.val();
        $district.find('option').each(function () {
            var cityId = $(this).data('city');
            if (!cityVal || !cityId) {
                $(this).show();
                return;
            }
            if (parseInt(cityId, 10) === parseInt(cityVal, 10)) {
                $(this).show();
            } else {
                if ($(this).is(':selected')) {
                    resetSelect($district);
                }
                $(this).hide();
            }
        });
    }

    function applyLevelRules() {
        var level = $level.val();

        if (level === 'country') {
            toggleDisabled($gov, true);
            toggleDisabled($city, true);
            toggleDisabled($district, true);
            resetSelect($gov);
            resetSelect($city);
            resetSelect($district);
            return;
        }

        if (level === 'governorate') {
            toggleDisabled($gov, false);
            toggleDisabled($city, true);
            toggleDisabled($district, true);
            resetSelect($city);
            resetSelect($district);
            return;
        }

        if (level === 'city') {
            toggleDisabled($gov, false);
            toggleDisabled($city, false);
            toggleDisabled($district, true);
            resetSelect($district);
            filterCities();
            return;
        }

        if (level === 'district') {
            toggleDisabled($gov, false);
            toggleDisabled($city, false);
            toggleDisabled($district, false);
            filterCities();
            filterDistricts();
            return;
        }

        // Custom or any other future level: keep controls enabled.
        toggleDisabled($gov, false);
        toggleDisabled($city, false);
        toggleDisabled($district, false);
    }

    $level.on('change', function () {
        applyLevelRules();
    });

    $gov.on('change', function () {
        if ($level.val() === 'city' || $level.val() === 'district') {
            resetSelect($city);
            resetSelect($district);
            filterCities();
            filterDistricts();
        }
    });

    $city.on('change', function () {
        if ($level.val() === 'district') {
            resetSelect($district);
            filterDistricts();
        }
    });

    // Initial state on load.
    filterCities();
    filterDistricts();
    applyLevelRules();
});
