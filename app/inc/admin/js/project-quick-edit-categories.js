(function($) {
    'use strict';

    if (typeof window.jawdaProjectQuickEdit === 'undefined') {
        return;
    }

    var settings = window.jawdaProjectQuickEdit || {};
    var typesMap = settings.types_by_category || {};
    var strings = $.extend({
        select_main_category: 'Select Main Category',
        select_main_category_first: 'Select the main category first',
        select_property_types: 'Select property types',
        no_types: 'No property types available for this category.',
        no_categories: 'No categories available.',
        type_fallback: 'Type #%s'
    }, settings.strings || {});

    function normalizeId(value) {
        if (value === undefined || value === null) {
            return '';
        }

        var stringValue = $.trim(String(value));

        if (!stringValue || stringValue === '0' || stringValue === '-1') {
            return '';
        }

        return stringValue;
    }

    function normalizeIds(values) {
        var normalized = [];

        if (Array.isArray(values)) {
            values.forEach(function(value) {
                var normalizedId = normalizeId(value);
                if (normalizedId) {
                    normalized.push(normalizedId);
                }
            });
        } else if (typeof values === 'string') {
            if (values.indexOf(',') !== -1) {
                normalized = normalizeIds(values.split(','));
            } else {
                var id = normalizeId(values);
                if (id) {
                    normalized.push(id);
                }
            }
        } else if (values) {
            var fallbackId = normalizeId(values);
            if (fallbackId) {
                normalized.push(fallbackId);
            }
        }

        if (normalized.length > 1) {
            normalized = normalized.filter(function(value, index, self) {
                return self.indexOf(value) === index;
            });
        }

        return normalized;
    }

    function parseTypeIds(raw) {
        if (Array.isArray(raw)) {
            return normalizeIds(raw);
        }

        if (typeof raw === 'string') {
            var trimmed = $.trim(raw);
            if (!trimmed) {
                return [];
            }

            if (trimmed.charAt(0) === '[') {
                try {
                    var parsed = JSON.parse(trimmed);
                    return normalizeIds(parsed);
                } catch (error) {
                    return normalizeIds(trimmed.split(','));
                }
            }

            return normalizeIds(trimmed.split(','));
        }

        if (raw && typeof raw === 'object') {
            var collected = [];
            $.each(raw, function(_, value) {
                collected.push(value);
            });
            return normalizeIds(collected);
        }

        return [];
    }

    function getAllowedTypes(categoryIds) {
        var ids = normalizeIds(categoryIds || []);
        if (!ids.length || !typesMap) {
            return [];
        }

        var collected = {};

        ids.forEach(function(id) {
            var key = normalizeId(id);
            if (!key || !typesMap[key]) {
                return;
            }

            var group = typesMap[key];
            if (Array.isArray(group)) {
                group.forEach(function(payload) {
                    var payloadId = normalizeId(payload && (payload.id || payload.ID || payload.value || payload.slug));
                    if (!payloadId) {
                        return;
                    }
                    collected[payloadId] = payload;
                });
                return;
            }

            $.each(group, function(typeId, payload) {
                var payloadId = normalizeId(typeId);
                if (!payloadId) {
                    return;
                }
                if (payload && typeof payload === 'object') {
                    collected[payloadId] = payload;
                } else {
                    collected[payloadId] = {
                        id: payloadId,
                        label: payload
                    };
                }
            });
        });

        var normalized = [];
        $.each(collected, function(typeId, payload) {
            normalized.push(payload);
        });

        return normalized;
    }

    function getAllowedTypeIds(allowedTypes) {
        if (!allowedTypes || !allowedTypes.length) {
            return [];
        }

        return allowedTypes
            .map(function(type) {
                if (!type || typeof type !== 'object') {
                    return '';
                }
                return normalizeId(type.id || type.ID || type.value || type.slug);
            })
            .filter(function(id) {
                return !!id;
            });
    }

    function filterSelectedIds(allowedTypes, selectedIds) {
        if (!allowedTypes.length || !selectedIds.length) {
            return [];
        }

        var allowedIds = getAllowedTypeIds(allowedTypes);

        return selectedIds.filter(function(id) {
            return allowedIds.indexOf(id) !== -1;
        });
    }

    function getTypeLabel(type) {
        if (!type || typeof type !== 'object') {
            return '';
        }

        return type.label || type.name || type.name_en || type.name_ar || strings.type_fallback.replace('%s', type.id || '');
    }

    function setSelectDisabled($select, disabled) {
        $select.prop('disabled', !!disabled);
    }

    function buildPropertyTypeOptions($select, allowedTypes, selectedIds, state) {
        if (!$select || !$select.length) {
            return;
        }

        var placeholderText = strings.select_property_types;

        if (state === 'no-category') {
            placeholderText = strings.select_main_category_first;
        } else if (state === 'no-types') {
            placeholderText = strings.no_types;
        } else if (!typesMap || Object.keys(typesMap).length === 0) {
            placeholderText = strings.no_categories;
        }

        $select.empty();

        var placeholderOption = $('<option>', {
            value: '',
            text: placeholderText,
            disabled: true
        });

        $select.append(placeholderOption);

        if (!allowedTypes || !allowedTypes.length) {
            setSelectDisabled($select, state !== 'has-types');
            $select.trigger('change');
            return;
        }

        allowedTypes.forEach(function(type) {
            var typeId = normalizeId(type && (type.id || type.ID || type.value || type.slug));
            if (!typeId) {
                return;
            }

            var option = $('<option>', {
                value: typeId,
                text: getTypeLabel(type)
            });

            if (selectedIds.indexOf(typeId) !== -1) {
                option.prop('selected', true);
            }

            $select.append(option);
        });

        setSelectDisabled($select, false);
        $select.trigger('change');
    }

    function getSelectedTypeIdsFromSelect($select) {
        if (!$select || !$select.length) {
            return [];
        }

        var value = $select.val();
        if (Array.isArray(value)) {
            return normalizeIds(value);
        }

        return normalizeIds(value || []);
    }

    function populateQuickEditRow($row, mainCategoryIds, propertyTypeIds) {
        if (!$row || !$row.length) {
            return;
        }

        var $mainSelect = $('.jawda-qe-main-category-select', $row);
        var $typesSelect = $('.jawda-qe-property-type-select', $row);

        if (!$mainSelect.length || !$typesSelect.length) {
            return;
        }

        var normalizedMainIds = normalizeIds(mainCategoryIds);

        $mainSelect.val(normalizedMainIds);

        var allowedTypes = normalizedMainIds.length ? getAllowedTypes(normalizedMainIds) : [];
        var filteredTypes = filterSelectedIds(allowedTypes, propertyTypeIds);
        var state = !normalizedMainIds.length
            ? 'no-category'
            : (allowedTypes.length ? 'has-types' : 'no-types');

        buildPropertyTypeOptions($typesSelect, allowedTypes, filteredTypes, state);
    }

    function setupQuickEditWatcher() {
        if (typeof inlineEditPost === 'undefined') {
            return;
        }

        var originalEdit = inlineEditPost.edit;

        inlineEditPost.edit = function(id) {
            if (originalEdit && typeof originalEdit === 'function') {
                originalEdit.apply(this, arguments);
            }

            var postId = 0;
            if (typeof id === 'object') {
                postId = parseInt(this.getId(id), 10) || 0;
            } else {
                postId = parseInt(id, 10) || 0;
            }

            if (!postId) {
                return;
            }

            var $editRow = $('#edit-' + postId);
            var $postRow = $('#post-' + postId);
            var $dataNode = $('.jawda-project-category-data', $postRow);

            if (!$editRow.length || !$dataNode.length) {
                return;
            }

            var mainCategoryIds = parseTypeIds($dataNode.data('main-category-ids'));
            var propertyTypeIds = parseTypeIds($dataNode.data('property-type-ids'));

            populateQuickEditRow($editRow, mainCategoryIds, propertyTypeIds);
        };
    }

    function attachChangeHandlers() {
        $('body').on('change', '.jawda-qe-main-category-select', function() {
            var $select = $(this);
            var $row = $select.closest('.inline-edit-row');
            var $typesSelect = $('.jawda-qe-property-type-select', $row);

            if (!$typesSelect.length) {
                return;
            }

            var categoryIds = normalizeIds($select.val());
            var allowedTypes = categoryIds.length ? getAllowedTypes(categoryIds) : [];
            var existingSelection = getSelectedTypeIdsFromSelect($typesSelect);
            var filteredSelection = filterSelectedIds(allowedTypes, existingSelection);
            var state = !categoryIds.length
                ? 'no-category'
                : (allowedTypes.length ? 'has-types' : 'no-types');

            buildPropertyTypeOptions($typesSelect, allowedTypes, filteredSelection, state);
        });

        $('body').on('change', '.jawda-be-main-category-select', function() {
            var $select = $(this);
            var $bulk = $('#bulk-edit');
            var $typesSelect = $('.jawda-be-property-type-select', $bulk);

            if (!$typesSelect.length) {
                return;
            }

            var categoryIds = normalizeIds($select.val());
            var allowedTypes = categoryIds.length ? getAllowedTypes(categoryIds) : [];
            var state = !categoryIds.length
                ? 'no-category'
                : (allowedTypes.length ? 'has-types' : 'no-types');

            buildPropertyTypeOptions($typesSelect, allowedTypes, [], state);
        });
    }

    function initializeBulkEditState() {
        var $bulk = $('#bulk-edit');
        if (!$bulk.length) {
            return;
        }

        var $typesSelect = $('.jawda-be-property-type-select', $bulk);
        if ($typesSelect.length) {
            buildPropertyTypeOptions($typesSelect, [], [], 'no-category');
        }
    }

    function bindMetaBoxHandlers() {
        var $mainSelect = $('.jawda-meta-main-category-select');
        var $typesSelect = $('.jawda-meta-property-type-select');

        if (!$mainSelect.length || !$typesSelect.length) {
            return;
        }

        var preselectedTypes = parseTypeIds($typesSelect.data('selected'));

        var applyState = function() {
            var categoryIds = normalizeIds($mainSelect.val());
            var allowedTypes = categoryIds.length ? getAllowedTypes(categoryIds) : [];
            var filteredSelection = filterSelectedIds(allowedTypes, preselectedTypes.length ? preselectedTypes : getSelectedTypeIdsFromSelect($typesSelect));
            var state = !categoryIds.length ? 'no-category' : (allowedTypes.length ? 'has-types' : 'no-types');
            buildPropertyTypeOptions($typesSelect, allowedTypes, filteredSelection, state);
        };

        applyState();

        $mainSelect.on('change', function() {
            preselectedTypes = parseTypeIds($typesSelect.data('selected'));
            applyState();
        });
    }

    $(function() {
        setupQuickEditWatcher();
        attachChangeHandlers();
        initializeBulkEditState();
        bindMetaBoxHandlers();
    });
})(jQuery);
