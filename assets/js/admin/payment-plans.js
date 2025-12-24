(function ($) {
    'use strict';

    var config = window.jawdaPaymentPlans || {};
    var planLabelPrefix = config.planLabelPrefix || 'Payment ';
    var removeConfirmMessage = config.removeConfirmMessage || 'هل أنت متأكد من حذف قالب خطة السداد؟';
    var planFieldSelector = '.carbon-fields__field[data-name="_jawda_payment_plans"]';
    var planContainerSelector = planFieldSelector + ' .carbon-fields__complex-group';

    function fieldSelector(key) {
        return '[name^="carbon_fields_compact_input"][name*="[_jawda_payment_plans]"][name$="[' + key + ']"]';
    }

    function updatePlanTitle($plan) {
        if (!$plan || !$plan.length) {
            return;
        }

        var index = parseInt($plan.attr('data-index'), 10);
        if (isNaN(index)) {
            index = 0;
            $plan.parent().children('.carbon-fields__complex-group').each(function (i) {
                if (this === $plan.get(0)) {
                    index = i;
                    return false;
                }
                return undefined;
            });
        }

        var title = planLabelPrefix + (index + 1);
        var $titleField = $plan.find(fieldSelector('plan_title'));
        if ($titleField.length && $titleField.val() !== title) {
            $titleField.val(title);
        }
    }

    function refreshAllPlans() {
        $(planContainerSelector).each(function () {
            updatePlanTitle($(this));
        });
    }

    $(document).ready(function () {
        refreshAllPlans();

        var container = document.querySelector(planFieldSelector + ' .carbon-fields__complex-container');
        if (container && typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function () {
                refreshAllPlans();
            });
            observer.observe(container, { childList: true, subtree: false });
        }

        $(document).on('click', planFieldSelector + ' .carbon-fields__complex-add', function () {
            setTimeout(refreshAllPlans, 120);
        });

        $(document).on('click', planFieldSelector + ' .carbon-fields__complex-remove', function (event) {
            if (!window.confirm(removeConfirmMessage)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            }

            setTimeout(refreshAllPlans, 120);
            return true;
        });
    });
})(jQuery);
