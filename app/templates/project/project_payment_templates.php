<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('jawda_render_project_payment_templates')) {
    function jawda_render_project_payment_templates($post_id = null, array $args = []) {
        if (!function_exists('jawda_get_project_payment_templates')) {
            return;
        }

        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return;
        }

        $templates = jawda_get_project_payment_templates($post_id, $args);
        if (empty($templates)) {
            return;
        }

        $is_rtl = is_rtl();
        $labels = [
            'section_title'   => $is_rtl ? 'خطة الدفع' : __('Payment Plan', 'jawda'),
            'column_phase'    => $is_rtl ? 'الدفعات' : __('Milestone', 'jawda'),
            'column_amount'   => $is_rtl ? 'يتم دفع' : __('To Be Paid', 'jawda'),
            'down'            => $is_rtl ? 'الدفعة المقدمة' : __('Down Payment', 'jawda'),
            'pre'             => $is_rtl ? 'حتى التسليم' : __('Until Handover', 'jawda'),
            'handover'        => $is_rtl ? 'دفعة الاستلام' : __('On Handover', 'jawda'),
            'post'            => $is_rtl ? 'بعد التسليم' : __('Post Handover', 'jawda'),
            'maintenance'     => $is_rtl ? 'وديعة الصيانة' : __('Maintenance Deposit', 'jawda'),
            'parking'         => $is_rtl ? 'سعر الجراج' : __('Parking Price', 'jawda'),
            'clubhouse'       => $is_rtl ? 'رسوم الكلوب هاوس' : __('Clubhouse Fee', 'jawda'),
            'installment'     => $is_rtl ? 'قسط' : __('Installment', 'jawda'),
            'details_title'   => $is_rtl ? 'تفاصيل إضافية' : __('Additional Details', 'jawda'),
            'method'          => $is_rtl ? 'وسيلة الدفع' : __('Payment Method', 'jawda'),
            'years'           => $is_rtl ? 'عدد سنوات التقسيط' : __('Installment Years', 'jawda'),
            'frequency'       => $is_rtl ? 'المدة الزمنية للقسط' : __('Installment Frequency', 'jawda'),
            'handover_time'   => $is_rtl ? 'موعد الاستلام' : __('Handover Timing', 'jawda'),
            'schedule_type'   => $is_rtl ? 'طريقة السداد' : __('Schedule Type', 'jawda'),
            'equal_label'     => $is_rtl ? 'أقساط متساوية' : __('Equal Installments', 'jawda'),
            'custom_label'    => $is_rtl ? 'أقساط غير متساوية' : __('Custom Installments', 'jawda'),
            'installments'    => $is_rtl ? 'قسط' : __('Installments', 'jawda'),
            'popup_title'     => $is_rtl ? 'تفاصيل الدفعات' : __('Payment Details', 'jawda'),
            'popup_close'     => $is_rtl ? 'إغلاق' : __('Close', 'jawda'),
        ];

        $wrapper_id = 'jawda-payment-templates-' . absint($post_id);
        ?>
        <div class="content-box payment-templates">
            <div class="headline-p"><?php echo esc_html($labels['section_title']); ?></div>
            <div class="jawda-payment-templates" id="<?php echo esc_attr($wrapper_id); ?>">
                <div class="jawda-payment-templates__tabs" role="tablist">
                    <?php foreach ($templates as $i => $template) :
                        $plan_id = $wrapper_id . '-plan-' . ($template['index'] + 1);
                        $is_active = $i === 0;
                        ?>
                        <button
                            type="button"
                            class="jawda-payment-templates__tab<?php echo $is_active ? ' is-active' : ''; ?>"
                            role="tab"
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                            data-target="#<?php echo esc_attr($plan_id); ?>"
                        >
                            <?php echo esc_html($template['title']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="jawda-payment-templates__panels">
                    <?php foreach ($templates as $i => $template) :
                        $plan_id = $wrapper_id . '-plan-' . ($template['index'] + 1);
                        $is_active = $i === 0;
                        $distribution = $template['distribution'];

                        $rows = [];
                        $row_popups = [];
                        $rows[] = [
                            'key'            => 'down',
                            'label'          => $labels['down'],
                            'value'          => $distribution['down']['formatted'],
                            'count'          => 0,
                            'details'        => [],
                            'count_variant'  => '',
                        ];

                        $rows[] = [
                            'key'            => 'pre',
                            'label'          => $labels['pre'],
                            'value'          => $distribution['target_until_handover']['formatted'],
                            'count'          => (int) $distribution['pre']['installment_count'],
                            'details'        => $distribution['pre']['details'],
                            'count_variant'  => 'pre',
                        ];

                        $maintenance_caption = $template['maintenance']['formatted'];
                        if ($template['maintenance']['due_label']) {
                            $maintenance_caption .= ' - ' . $template['maintenance']['due_label'];
                        }
                        $rows[] = [
                            'key'            => 'maintenance',
                            'label'          => $labels['maintenance'],
                            'value'          => $maintenance_caption,
                            'count'          => 0,
                            'details'        => [],
                            'count_variant'  => '',
                        ];

                        $rows[] = [
                            'key'            => 'post',
                            'label'          => $labels['post'],
                            'value'          => $distribution['post']['formatted'],
                            'count'          => (int) $distribution['post']['installment_count'],
                            'details'        => $distribution['post']['details'],
                            'count_variant'  => 'post',
                        ];

                        ?>
                        <div
                            id="<?php echo esc_attr($plan_id); ?>"
                            class="jawda-payment-templates__panel<?php echo $is_active ? ' is-active' : ''; ?>"
                            role="tabpanel"
                            <?php echo $is_active ? '' : 'hidden'; ?>
                        >
                            <div class="jawda-payment-templates__table-wrapper">
                                <table class="jawda-payment-templates__table">
                                    <thead>
                                        <tr>
                                            <th scope="col"><?php echo esc_html($labels['column_phase']); ?></th>
                                            <th scope="col"><?php echo esc_html($labels['column_amount']); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $row) :
                                            $count = (int) $row['count'];
                                            $has_details = !empty($row['details']);
                                            $popup_id = '';
                                            if ($has_details) {
                                                $popup_id = $plan_id . '-popup-' . sanitize_key($row['key']);
                                                $row_popups[] = [
                                                    'id'      => $popup_id,
                                                    'label'   => $row['label'],
                                                    'details' => $row['details'],
                                                ];
                                            }
                                            ?>
                                            <tr>
                                                <td class="jawda-payment-templates__milestone"><?php echo esc_html($row['label']); ?></td>
                                            <td class="jawda-payment-templates__value">
                                                <span class="jawda-payment-templates__value-main"><?php echo esc_html($row['value']); ?></span>
                                                <?php if ($count > 0 || $has_details) : ?>
                                                    <?php
                                                    $count_label = $count > 0 ? ($count . ' ' . $labels['installments']) : '';
                                                    $count_class = 'jawda-payment-templates__count';
                                                    if (!empty($row['count_variant'])) {
                                                        $count_class .= ' jawda-payment-templates__count--' . $row['count_variant'];
                                                    }
                                                    ?>
                                                    <?php if ($has_details && $popup_id) : ?>
                                                        <a
                                                            href="#"
                                                            class="jawda-payment-templates__count-link <?php echo esc_attr($count_class); ?>"
                                                            data-popup-target="<?php echo esc_attr('#' . $popup_id); ?>"
                                                            aria-haspopup="dialog"
                                                            aria-expanded="false"
                                                        >
                                                            <?php echo esc_html($count_label !== '' ? $count_label : $labels['popup_title']); ?>
                                                        </a>
                                                    <?php elseif ($count_label !== '') : ?>
                                                        <span class="<?php echo esc_attr($count_class); ?>"><?php echo esc_html($count_label); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($row_popups)) : ?>
                                <?php foreach ($row_popups as $popup) : ?>
                                    <div class="jawda-payment-templates__popup" id="<?php echo esc_attr($popup['id']); ?>" hidden>
                                        <div class="jawda-payment-templates__popup-backdrop" data-popup-close></div>
                                        <div class="jawda-payment-templates__popup-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($popup['id']); ?>-title">
                                            <button type="button" class="jawda-payment-templates__popup-close" data-popup-close aria-label="<?php echo esc_attr($labels['popup_close']); ?>">&times;</button>
                                            <h3 class="jawda-payment-templates__popup-title" id="<?php echo esc_attr($popup['id']); ?>-title"><?php echo esc_html($labels['popup_title']); ?></h3>
                                            <p class="jawda-payment-templates__popup-subtitle"><?php echo esc_html($popup['label']); ?></p>
                                            <ul class="jawda-payment-templates__popup-list">
                                                <?php foreach ($popup['details'] as $detail) : ?>
                                                    <li>
                                                        <span class="jawda-payment-templates__popup-timing"><?php echo esc_html($detail['timing']); ?></span>
                                                        <span class="jawda-payment-templates__popup-percent"><?php echo esc_html($detail['percent']); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        static $payment_templates_script_printed = false;
        if (!$payment_templates_script_printed) {
            $payment_templates_script_printed = true;
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var activePopup = null;
                    var activeTrigger = null;

                    var closePopup = function (popup) {
                        if (!popup) {
                            return;
                        }

                        popup.classList.remove('is-open');
                        popup.setAttribute('hidden', 'hidden');
                        document.body.classList.remove('jawda-payment-templates--popup-open');
                        if (activePopup === popup) {
                            activePopup = null;
                        }
                        if (activeTrigger) {
                            var triggerToRefocus = activeTrigger;
                            activeTrigger.setAttribute('aria-expanded', 'false');
                            activeTrigger = null;
                            if (typeof triggerToRefocus.focus === 'function') {
                                triggerToRefocus.focus();
                            }
                        }
                    };

                    var openPopup = function (popup, trigger) {
                        if (!popup) {
                            return;
                        }

                        if (activePopup && activePopup !== popup) {
                            closePopup(activePopup);
                        }

                        popup.classList.add('is-open');
                        popup.removeAttribute('hidden');
                        document.body.classList.add('jawda-payment-templates--popup-open');
                        activePopup = popup;
                        if (trigger) {
                            activeTrigger = trigger;
                            trigger.setAttribute('aria-expanded', 'true');
                        }

                        var closeButton = popup.querySelector('.jawda-payment-templates__popup-close');
                        if (closeButton && typeof closeButton.focus === 'function') {
                            closeButton.focus();
                        }
                    };

                    document.querySelectorAll('.jawda-payment-templates').forEach(function (container) {
                        var tabs = container.querySelectorAll('.jawda-payment-templates__tab');
                        var panels = container.querySelectorAll('.jawda-payment-templates__panel');

                        tabs.forEach(function (tab) {
                            tab.addEventListener('click', function () {
                                var targetSelector = tab.getAttribute('data-target');
                                if (!targetSelector) {
                                    return;
                                }

                                var targetPanel = container.querySelector(targetSelector);
                                if (!targetPanel) {
                                    return;
                                }

                                tabs.forEach(function (innerTab) {
                                    innerTab.classList.remove('is-active');
                                });
                                panels.forEach(function (panel) {
                                    panel.classList.remove('is-active');
                                    panel.setAttribute('hidden', 'hidden');
                                });

                                tab.classList.add('is-active');
                                targetPanel.classList.add('is-active');
                                targetPanel.removeAttribute('hidden');
                            });
                        });

                        container.querySelectorAll('[data-popup-target]').forEach(function (trigger) {
                            trigger.addEventListener('click', function (event) {
                                event.preventDefault();
                                var selector = trigger.getAttribute('data-popup-target');
                                if (!selector) {
                                    return;
                                }

                                var popup = container.querySelector(selector);
                                if (!popup) {
                                    popup = document.querySelector(selector);
                                }

                                openPopup(popup, trigger);
                            });
                        });

                        container.querySelectorAll('.jawda-payment-templates__popup').forEach(function (popup) {
                            popup.addEventListener('click', function (event) {
                                if (event.target && event.target.hasAttribute('data-popup-close')) {
                                    event.preventDefault();
                                    closePopup(popup);
                                }
                            });
                        });
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape' && activePopup) {
                            closePopup(activePopup);
                        }
                    });
                });
            </script>
            <?php
        }
    }
}
