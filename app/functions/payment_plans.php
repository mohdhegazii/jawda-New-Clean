<?php

if (!defined('ABSPATH')) {
    exit;
}

use Carbon_Fields\Field;

if (!function_exists('jawda_get_payment_plan_fields')) {
    function jawda_get_payment_plan_fields() {
        $fields = [];

        $fields[] = Field::make('separator', 'jawda_payment_plans_intro', __('قوالب خطط السداد', 'jawda'))
            ->set_help_text(__('أنشئ قوالب للنسب والتقسيم يمكن تطبيقها لاحقًا على وحدات المشروع.', 'jawda'));

        $plan_fields = [
            Field::make('radio', 'plan_status', __('حالة القالب', 'jawda'))
                ->set_options([
                    'active' => __('مفعّل', 'jawda'),
                    'hidden' => __('مخفي', 'jawda'),
                ])
                ->set_default_value('active')
                ->set_help_text(__('اختر حالة ظهور القالب.', 'jawda')),

            Field::make('text', 'plan_title', __('اسم القالب', 'jawda'))
                ->set_attribute('readOnly', true)
                ->set_help_text(__('يتم توليد الاسم تلقائيًا (Payment 1، Payment 2 ...).', 'jawda')),

            Field::make('radio', 'payment_method', __('وسائل الدفع', 'jawda'))
                ->set_options(jawda_payment_plan_method_options_ar())
                ->set_default_value('installment')
                ->set_help_text(__('حدد وسيلة الدفع', 'jawda')),

            Field::make('select', 'handover_timeline', __('موعد الاستلام', 'jawda'))
                ->set_options('jawda_payment_plans_handover_options_ar')
                ->set_default_value('immediate')
                ->set_help_text(__('اختر التوقيت المتوقع للاستلام.', 'jawda')),

            Field::make('text', 'down_payment_percent', __('نسبة الدفعة المقدمة (٪)', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('max', '100')
                ->set_attribute('step', '0.01')
                ->set_help_text(__('أدخل نسبة المقدم كنسبة مئوية من إجمالي السعر.', 'jawda')),

            Field::make('text', 'installment_years', __('عدد سنوات التقسيط', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0.25')
                ->set_attribute('max', '15')
                ->set_attribute('step', '0.25')
                ->set_help_text(__('يمكن إدخال الأعداد الصحيحة أو الكسور .25 / .5 / .75.', 'jawda')),

            Field::make('radio', 'installment_frequency', __('المدة الزمنية للقسط', 'jawda'))
                ->set_options('jawda_payment_plan_frequency_options_ar')
                ->set_default_value('monthly')
                ->set_help_text(__('اختر المدة الزمنية للقسط', 'jawda')),

            Field::make('radio', 'payment_schedule_type', __('طريقة السداد', 'jawda'))
                ->set_options([
                    'equal'  => __('أقساط متساوية', 'jawda'),
                    'custom' => __('أقساط غير متساوية', 'jawda'),
                ])
                ->set_default_value('equal')
                ->set_help_text(__('حدد إذا كانت الأقساط متساوية أو غير متساوية.', 'jawda')),

            Field::make('text', 'target_percent_until_handover', __('النسبة الإجمالية حتى التسليم (٪)', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('max', '100')
                ->set_attribute('step', '0.01')
                ->set_help_text(__('أدخل إجمالي النسبة المطلوب تحصيلها حتى موعد الاستلام.', 'jawda'))
                ->set_conditional_logic([
                    [
                        'field'   => 'payment_schedule_type',
                        'value'   => 'custom',
                        'compare' => '=',
                    ],
                ]),

            Field::make('text', 'maintenance_percent', __('نسبة وديعة الصيانة (٪)', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('max', '100')
                ->set_attribute('step', '0.01')
                ->set_help_text(__('أدخل نسبة وديعة الصيانة من سعر الوحدة.', 'jawda')),

            Field::make('select', 'maintenance_due', __('موعد دفع وديعة الصيانة', 'jawda'))
                ->set_options('jawda_payment_plans_maintenance_due_options_ar')
                ->set_default_value('before_6')
                ->set_help_text(__('حدد موعد دفع وديعة الصيانة.', 'jawda')),

            Field::make('text', 'parking_price', __('سعر الجراج', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_help_text(__('اترك الحقل فارغًا إذا لم يوجد جراج.', 'jawda')),

            Field::make('text', 'clubhouse_price', __('رسوم الكلوب هاوس', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_help_text(__('اترك الحقل فارغًا إذا لم توجد رسوم.', 'jawda')),

            Field::make('radio', 'has_handover_payment', __('هل توجد دفعة عند الاستلام؟', 'jawda'))
                ->set_options([
                    'no'  => __('لا', 'jawda'),
                    'yes' => __('نعم', 'jawda'),
                ])
                ->set_default_value('no')
                ->set_help_text(__('حدد إذا كان هناك دفعة تدفع وقت الاستلام.', 'jawda')),

            Field::make('radio', 'handover_payment_type', __('نوع دفعة الاستلام', 'jawda'))
                ->set_options([
                    'percent' => __('نسبة', 'jawda'),
                    'value'   => __('قيمة', 'jawda'),
                ])
                ->set_default_value('percent')
                ->set_help_text(__('اختر طريقة إدخال دفعة الاستلام.', 'jawda'))
                ->set_conditional_logic([
                    [
                        'field'   => 'has_handover_payment',
                        'value'   => 'yes',
                        'compare' => '=',
                    ],
                ]),

            Field::make('text', 'handover_payment_value', __('قيمة دفعة الاستلام', 'jawda'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_help_text(__('أدخل قيمة دفعة الاستلام كنسبة أو مبلغ ثابت.', 'jawda'))
                ->set_conditional_logic([
                    [
                        'field'   => 'has_handover_payment',
                        'value'   => 'yes',
                        'compare' => '=',
                    ],
                ]),
        ];

        $fields[] = Field::make('complex', 'jawda_payment_plans', __('قوالب خطط السداد', 'jawda'))
            ->setup_labels([
                'plural_name'   => __('قوالب خطط السداد', 'jawda'),
                'singular_name' => __('قالب خطة سداد', 'jawda'),
                'add_new'       => __('+ إضافة قالب جديد', 'jawda'),
            ])
            ->set_layout('tabbed-vertical')
            ->set_header_template('<%- plan_title ? plan_title : ("Payment " + (parseInt(_index, 10) + 1)) %>')
            ->add_fields('plan', __('قالب خطة سداد', 'jawda'), $plan_fields);

        return $fields;
    }
}

if (!function_exists('jawda_payment_plan_method_options_ar')) {
    function jawda_payment_plan_method_options_ar() {
        return [
            'cash'        => __('كاش', 'jawda'),
            'installment' => __('تقسيط', 'jawda'),
        ];
    }
}

if (!function_exists('jawda_payment_plans_handover_options_ar')) {
    function jawda_payment_plans_handover_options_ar() {
        return [
            'immediate'       => __('استلام فوري', 'jawda'),
            'within_6_months' => __('في خلال 6 شهور', 'jawda'),
            'after_1_year'    => __('استلام بعد سنة', 'jawda'),
            'after_2_years'   => __('استلام بعد سنتين', 'jawda'),
            'after_3_years'   => __('استلام بعد 3 سنوات', 'jawda'),
            'after_4_years'   => __('استلام بعد 4 سنوات', 'jawda'),
            'after_5_years'   => __('استلام بعد 5 سنوات', 'jawda'),
            'after_6_years'   => __('استلام بعد 6 سنوات', 'jawda'),
        ];
    }
}

if (!function_exists('jawda_payment_plan_frequency_options_ar')) {
    function jawda_payment_plan_frequency_options_ar() {
        return [
            'monthly'       => __('شهريًا', 'jawda'),
            'quarterly'     => __('القسط الربع سنوي', 'jawda'),
            'semi_annually' => __('القسط النصف سنوي', 'jawda'),
            'annually'      => __('سنويًا', 'jawda'),
        ];
    }
}

if (!function_exists('jawda_payment_plans_maintenance_due_options_ar')) {
    function jawda_payment_plans_maintenance_due_options_ar() {
        return [
            'before_6'    => __('قبل الاستلام ب 6 أشهر', 'jawda'),
            'before_12'   => __('قبل الاستلام بسنة', 'jawda'),
            'on_handover' => __('في تاريخ الاستلام', 'jawda'),
        ];
    }
}

if (!function_exists('jawda_payment_plans_enqueue_assets')) {
    function jawda_payment_plans_enqueue_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'projects') {
            return;
        }

        $handle  = 'jawda-payment-plans';
        $src     = get_template_directory_uri() . '/assets/js/admin/payment-plans.js';
        $path    = get_template_directory() . '/assets/js/admin/payment-plans.js';
        $version = file_exists($path) ? filemtime($path) : false;

        wp_enqueue_script($handle, $src, ['jquery'], $version, true);
        wp_localize_script($handle, 'jawdaPaymentPlans', [
            'planLabelPrefix'      => 'Payment ',
            'removeConfirmMessage' => __('هل أنت متأكد من حذف قالب خطة السداد؟', 'jawda'),
        ]);
    }
    add_action('admin_enqueue_scripts', 'jawda_payment_plans_enqueue_assets');
}

if (!function_exists('jawda_payment_plans_handle_save')) {
    function jawda_payment_plans_handle_save($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $plans = carbon_get_post_meta($post_id, 'jawda_payment_plans');
        if (!is_array($plans) || empty($plans)) {
            return;
        }

        $prepared = jawda_payment_plans_prepare_for_storage($plans);
        carbon_set_post_meta($post_id, 'jawda_payment_plans', $prepared);
    }
    add_action('save_post_projects', 'jawda_payment_plans_handle_save', 20, 3);
}

if (!function_exists('jawda_payment_plans_prepare_for_storage')) {
    function jawda_payment_plans_prepare_for_storage(array $plans) {
        $prepared = [];

        $plans = array_values($plans);
        foreach ($plans as $index => $plan) {
            if (!is_array($plan)) {
                continue;
            }

            $prepared[] = jawda_payment_plans_prepare_single_plan($plan, $index);
        }

        return $prepared;
    }
}

if (!function_exists('jawda_payment_plans_prepare_single_plan')) {
    function jawda_payment_plans_prepare_single_plan(array $plan, $index) {
        $prepared = [];

        $prepared['plan_title'] = 'Payment ' . ($index + 1);

        $status_options = ['active', 'hidden'];
        $status = isset($plan['plan_status']) && in_array($plan['plan_status'], $status_options, true)
            ? $plan['plan_status']
            : 'active';
        $prepared['plan_status'] = $status;

        $method_options = jawda_payment_plan_method_options_ar();
        $method = isset($plan['payment_method'], $method_options[$plan['payment_method']]) ? $plan['payment_method'] : 'installment';
        $prepared['payment_method'] = $method;

        $handover_options = jawda_payment_plans_handover_options_ar();
        $handover = isset($plan['handover_timeline'], $handover_options[$plan['handover_timeline']]) ? $plan['handover_timeline'] : 'immediate';
        $prepared['handover_timeline'] = $handover;

        $years_raw = $plan['installment_years'] ?? '';
        $prepared['installment_years'] = jawda_payment_plans_normalize_years($years_raw);

        $frequency_options = jawda_payment_plan_frequency_options_ar();
        $frequency = isset($plan['installment_frequency'], $frequency_options[$plan['installment_frequency']])
            ? $plan['installment_frequency']
            : 'monthly';
        $prepared['installment_frequency'] = $frequency;

        $schedule_type = isset($plan['payment_schedule_type']) && in_array($plan['payment_schedule_type'], ['equal', 'custom'], true)
            ? $plan['payment_schedule_type']
            : 'equal';
        $prepared['payment_schedule_type'] = $schedule_type;

        $prepared['down_payment_percent'] = jawda_payment_plans_sanitize_percent($plan['down_payment_percent'] ?? 0);

        $prepared['maintenance_percent'] = jawda_payment_plans_sanitize_percent($plan['maintenance_percent'] ?? 0);

        $maintenance_due_options = jawda_payment_plans_maintenance_due_options_ar();
        $maintenance_due = isset($plan['maintenance_due'], $maintenance_due_options[$plan['maintenance_due']])
            ? $plan['maintenance_due']
            : 'before_6';
        $prepared['maintenance_due'] = $maintenance_due;

        $parking_raw = $plan['parking_price'] ?? '';
        $prepared['parking_price'] = jawda_payment_plans_prepare_optional_number($parking_raw, jawda_payment_plans_to_float($parking_raw));

        $clubhouse_raw = $plan['clubhouse_price'] ?? '';
        $prepared['clubhouse_price'] = jawda_payment_plans_prepare_optional_number($clubhouse_raw, jawda_payment_plans_to_float($clubhouse_raw));

        $has_handover = isset($plan['has_handover_payment']) && in_array($plan['has_handover_payment'], ['yes', 'no'], true)
            ? $plan['has_handover_payment']
            : 'no';
        $prepared['has_handover_payment'] = $has_handover;

        $handover_type = isset($plan['handover_payment_type']) && in_array($plan['handover_payment_type'], ['percent', 'value'], true)
            ? $plan['handover_payment_type']
            : 'percent';
        $prepared['handover_payment_type'] = $handover_type;

        $handover_raw = $plan['handover_payment_value'] ?? '';
        if ($has_handover === 'yes') {
            if ($handover_type === 'percent') {
                $prepared['handover_payment_value'] = jawda_payment_plans_sanitize_percent($handover_raw);
            } else {
                $prepared['handover_payment_value'] = jawda_payment_plans_prepare_optional_number($handover_raw, jawda_payment_plans_to_float($handover_raw));
            }
        } else {
            $prepared['handover_payment_value'] = '';
        }

        if ($schedule_type === 'custom') {
            $prepared['target_percent_until_handover'] = jawda_payment_plans_sanitize_percent($plan['target_percent_until_handover'] ?? 0);
        } else {
            $prepared['target_percent_until_handover'] = '';
        }

        return $prepared;
    }
}

if (!function_exists('jawda_payment_plans_prepare_optional_number')) {
    function jawda_payment_plans_prepare_optional_number($raw, $value) {
        $raw = is_string($raw) ? trim($raw) : $raw;
        if ($raw === '' || $raw === null) {
            return '';
        }

        return jawda_payment_plans_format_number($value);
    }
}

if (!function_exists('jawda_payment_plans_to_float')) {
    function jawda_payment_plans_to_float($value) {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^0-9,\\.-]/', '', $value);
            $normalized = str_replace(',', '', $normalized);
            if ($normalized === '' || $normalized === '-' || $normalized === '.') {
                return 0.0;
            }

            return (float) $normalized;
        }

        return 0.0;
    }
}

if (!function_exists('jawda_payment_plans_format_number')) {
    function jawda_payment_plans_format_number($value, $precision = 2) {
        $number = round((float) $value, $precision);
        $formatted = number_format($number, $precision, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}

if (!function_exists('jawda_payment_plans_sanitize_percent')) {
    function jawda_payment_plans_sanitize_percent($value, $precision = 3) {
        $float = jawda_payment_plans_to_float($value);
        if ($float < 0) {
            $float = 0;
        }
        if ($float > 100) {
            $float = 100;
        }

        return jawda_payment_plans_format_number($float, $precision);
    }
}

if (!function_exists('jawda_payment_plans_normalize_years')) {
    function jawda_payment_plans_normalize_years($value) {
        $float = jawda_payment_plans_to_float($value);
        if ($float < 0) {
            $float = 0;
        }
        if ($float > 15) {
            $float = 15;
        }

        if ($float === 0.0) {
            return '0';
        }

        $normalized = round($float / 0.25) * 0.25;
        $normalized = max(0.25, min(15, $normalized));

        return jawda_payment_plans_format_number($normalized, 2);
    }
}

if (!function_exists('jawda_payment_plans_get_frequency_meta')) {
    function jawda_payment_plans_get_frequency_meta($key) {
        $map = [
            'monthly'       => ['per_year' => 12, 'interval_months' => 1],
            'quarterly'     => ['per_year' => 4, 'interval_months' => 3],
            'semi_annually' => ['per_year' => 2, 'interval_months' => 6],
            'annually'      => ['per_year' => 1, 'interval_months' => 12],
        ];

        return isset($map[$key]) ? $map[$key] : null;
    }
}

if (!function_exists('jawda_payment_plans_get_handover_months')) {
    function jawda_payment_plans_get_handover_months() {
        return [
            'immediate'       => 0,
            'within_6_months' => 6,
            'after_1_year'    => 12,
            'after_2_years'   => 24,
            'after_3_years'   => 36,
            'after_4_years'   => 48,
            'after_5_years'   => 60,
            'after_6_years'   => 72,
        ];
    }
}

if (!function_exists('jawda_payment_plans_get_handover_label')) {
    function jawda_payment_plans_get_handover_label($key) {
        $options = jawda_payment_plans_handover_options_ar();

        return isset($options[$key]) ? $options[$key] : '';
    }
}

if (!function_exists('jawda_payment_plans_split_percent')) {
    function jawda_payment_plans_split_percent($total, $count) {
        $parts = [];
        $total = (float) $total;
        $count = (int) $count;

        if ($count <= 0 || $total <= 0) {
            return $parts;
        }

        $remaining = $total;
        $remaining_count = $count;

        for ($i = 0; $i < $count; $i++) {
            $portion = $remaining / $remaining_count;
            $parts[] = $portion;
            $remaining -= $portion;
            $remaining_count--;
        }

        return $parts;
    }
}

if (!function_exists('jawda_payment_plans_calculate_distribution')) {
    function jawda_payment_plans_calculate_distribution(array $plan) {
        $method = isset($plan['payment_method']) ? $plan['payment_method'] : 'installment';

        $down_percent = jawda_payment_plans_to_float($plan['down_payment_percent'] ?? 0);
        $down_percent = min(max($down_percent, 0), 100);

        $handover_percent = 0.0;
        $handover_type = isset($plan['handover_payment_type']) ? $plan['handover_payment_type'] : 'percent';
        $handover_value_raw = isset($plan['handover_payment_value']) ? $plan['handover_payment_value'] : '';
        $has_handover = isset($plan['has_handover_payment']) && $plan['has_handover_payment'] === 'yes';

        if ($has_handover && $handover_type === 'percent') {
            $handover_percent = jawda_payment_plans_to_float($handover_value_raw);
            $handover_percent = min(max($handover_percent, 0), 100);
        }

        if ($method === 'cash') {
            return [
                'down_percent'              => 100.0,
                'pre_percent'               => 0.0,
                'handover_percent'          => 0.0,
                'post_percent'              => 0.0,
                'pre_installments'          => [],
                'post_installments'         => [],
                'pre_installment_count'     => 0,
                'post_installment_count'    => 0,
                'total_installments'        => 0,
                'target_until_handover'     => 100.0,
                'schedule_type'             => isset($plan['payment_schedule_type']) ? $plan['payment_schedule_type'] : 'equal',
            ];
        }

        $frequency_key = isset($plan['installment_frequency']) ? $plan['installment_frequency'] : 'monthly';
        $frequency_meta = jawda_payment_plans_get_frequency_meta($frequency_key);
        if (!$frequency_meta) {
            $frequency_meta = jawda_payment_plans_get_frequency_meta('monthly');
        }

        $years = jawda_payment_plans_to_float($plan['installment_years'] ?? 0);
        if ($years < 0) {
            $years = 0;
        }
        if ($years > 15) {
            $years = 15;
        }
        if ($years > 0) {
            $years = round($years / 0.25) * 0.25;
        }

        $total_installments = (int) round($years * $frequency_meta['per_year']);
        if ($total_installments < 0) {
            $total_installments = 0;
        }

        $handover_key = isset($plan['handover_timeline']) ? $plan['handover_timeline'] : 'immediate';
        $handover_months_map = jawda_payment_plans_get_handover_months();
        $handover_months = isset($handover_months_map[$handover_key]) ? $handover_months_map[$handover_key] : 0;
        $max_months = $years * 12;
        if ($handover_months > $max_months && $max_months > 0) {
            $handover_months = $max_months;
        }
        if ($handover_months < 0) {
            $handover_months = 0;
        }

        $handover_years = $years > 0 ? $handover_months / 12 : 0;
        if ($handover_years > $years) {
            $handover_years = $years;
        }

        $pre_installments = 0;
        if ($total_installments > 0 && $handover_years > 0) {
            $pre_installments = (int) round($handover_years * $frequency_meta['per_year']);
            if ($pre_installments > $total_installments) {
                $pre_installments = $total_installments;
            }
        }

        $remaining_years = max(0, $years - $handover_years);
        $post_installments_capacity = max(0, $total_installments - $pre_installments);
        $post_installments_by_years = (int) round($remaining_years * $frequency_meta['per_year']);
        if ($post_installments_by_years < 0) {
            $post_installments_by_years = 0;
        }
        $post_installments = min($post_installments_capacity, $post_installments_by_years);

        if ($post_installments < 0) {
            $post_installments = 0;
        }

        $schedule_type = isset($plan['payment_schedule_type']) ? $plan['payment_schedule_type'] : 'equal';
        if (!in_array($schedule_type, ['equal', 'custom'], true)) {
            $schedule_type = 'equal';
        }

        $pre_parts = [];
        $post_parts = [];
        $pre_installment_total_raw = 0.0;
        $post_installment_total_raw = 0.0;

        $installment_pool = max(0, 100 - $down_percent - $handover_percent);

        if ($schedule_type === 'equal') {
            if ($total_installments > 0 && $installment_pool > 0) {
                $per_installment = $installment_pool / $total_installments;

                if ($pre_installments > 0) {
                    $pre_installment_total_raw = $per_installment * $pre_installments;
                    $pre_parts = jawda_payment_plans_split_percent($pre_installment_total_raw, $pre_installments);
                }

                if ($post_installments > 0) {
                    $post_installment_total_raw = $per_installment * $post_installments;
                    $post_parts = jawda_payment_plans_split_percent($post_installment_total_raw, $post_installments);
                } else {
                    $post_installment_total_raw = max(0, $installment_pool - $pre_installment_total_raw);
                }
            } else {
                $post_installment_total_raw = $installment_pool;
            }
        } else {
            $target_raw = jawda_payment_plans_to_float($plan['target_percent_until_handover'] ?? 0);
            $target_raw = min(max($target_raw, 0), 100);
            if ($target_raw < $down_percent + $handover_percent) {
                $target_raw = $down_percent + $handover_percent;
            }

            $pre_installment_total_raw = max(0, min($installment_pool, $target_raw - $down_percent - $handover_percent));
            if ($pre_installments === 0) {
                $pre_installment_total_raw = 0;
            }

            if ($pre_installments > 0 && $pre_installment_total_raw > 0) {
                $pre_parts = jawda_payment_plans_split_percent($pre_installment_total_raw, $pre_installments);
            }

            $post_installment_total_raw = max(0, $installment_pool - $pre_installment_total_raw);
            if ($post_installments > 0 && $post_installment_total_raw > 0) {
                $post_parts = jawda_payment_plans_split_percent($post_installment_total_raw, $post_installments);
            }
        }

        $pre_installment_total = !empty($pre_parts) ? array_sum($pre_parts) : $pre_installment_total_raw;
        $post_installment_total = !empty($post_parts) ? array_sum($post_parts) : $post_installment_total_raw;

        $pre_percent = $pre_installment_total;
        $post_percent = $post_installment_total;
        $target_until_handover = $down_percent + $handover_percent + $pre_installment_total;
        if ($target_until_handover < $down_percent + $handover_percent) {
            $target_until_handover = $down_percent + $handover_percent;
        }

        $total_percent = $down_percent + $handover_percent + $pre_percent + $post_percent;
        $diff = 100 - $total_percent;
        if (abs($diff) > 0.0001) {
            if (!empty($post_parts)) {
                $last_index = count($post_parts) - 1;
                $post_parts[$last_index] += $diff;
                $post_installment_total = array_sum($post_parts);
                $post_percent = $post_installment_total;
            } elseif (!empty($pre_parts)) {
                $last_index = count($pre_parts) - 1;
                $pre_parts[$last_index] += $diff;
                $pre_installment_total = array_sum($pre_parts);
                $pre_percent = $pre_installment_total;
            } else {
                $down_percent = min(max($down_percent + $diff, 0), 100);
            }

            $target_until_handover = $down_percent + $handover_percent + $pre_installment_total;
        }

        return [
            'down_percent'              => max(0.0, min(100.0, $down_percent)),
            'pre_percent'               => max(0.0, min(100.0, $pre_percent)),
            'handover_percent'          => max(0.0, min(100.0, $handover_percent)),
            'post_percent'              => max(0.0, min(100.0, $post_percent)),
            'pre_installments'          => $pre_parts,
            'post_installments'         => $post_parts,
            'pre_installment_count'     => $pre_installments,
            'post_installment_count'    => $post_installments,
            'total_installments'        => $total_installments,
            'installment_years'         => $years,
            'handover_years'            => $handover_years,
            'post_years'                => $remaining_years,
            'target_until_handover'     => max(0.0, min(100.0, $target_until_handover)),
            'schedule_type'             => $schedule_type,
        ];
    }
}

if (!function_exists('jawda_payment_plans_format_percent_string')) {
    function jawda_payment_plans_format_percent_string($value, $precision = 2) {
        $float = jawda_payment_plans_to_float($value);
        $formatted = jawda_payment_plans_format_number($float, $precision);

        return $formatted . '%';
    }
}

if (!function_exists('jawda_payment_plans_format_amount_display')) {
    function jawda_payment_plans_format_amount_display($raw) {
        if ($raw === '' || $raw === null) {
            return '';
        }

        $float = jawda_payment_plans_to_float($raw);
        $decimals = 0;

        if (is_string($raw) && strpos($raw, '.') !== false) {
            $decimal_part = substr($raw, strpos($raw, '.') + 1);
            $decimals = strlen(rtrim($decimal_part, '0'));
        } elseif (abs($float - round($float)) > 0.001) {
            $decimals = 2;
        }

        return number_format_i18n($float, min(max($decimals, 0), 4));
    }
}

if (!function_exists('jawda_payment_plans_build_installment_details')) {
    function jawda_payment_plans_build_installment_details(array $parts, $phase, array $context = []) {
        if (empty($parts)) {
            return [];
        }

        $details = [];
        $interval_months = 0;
        if (is_array($context) && array_key_exists('interval_months', $context)) {
            $interval_months = (int) $context['interval_months'];
        }

        $handover_months = 0;
        if (is_array($context) && array_key_exists('handover_months', $context)) {
            $handover_months = (int) $context['handover_months'];
        }
        $is_rtl = !empty($context['is_rtl']);

        foreach (array_values($parts) as $index => $value) {
            $percent = jawda_payment_plans_format_percent_string($value, 3);

            $due_months = $interval_months > 0 ? $interval_months * ($index + 1) : 0;
            if ($phase === 'pre') {
                $difference = max(0, $handover_months - $due_months);
                if ($difference === 0) {
                    $timing = $is_rtl ? 'عند الاستلام' : __('On handover', 'jawda');
                } else {
                    $timing = $is_rtl
                        ? sprintf('قبل الاستلام بـ %s شهر', jawda_payment_plans_format_number($difference, 0))
                        : sprintf(__('Within %s months before handover', 'jawda'), jawda_payment_plans_format_number($difference, 0));
                }
            } else {
                if ($due_months === 0) {
                    $timing = $is_rtl ? 'عند الاستلام' : __('On handover', 'jawda');
                } else {
                    $timing = $is_rtl
                        ? sprintf('بعد الاستلام بـ %s شهر', jawda_payment_plans_format_number($due_months, 0))
                        : sprintf(__('Within %s months after handover', 'jawda'), jawda_payment_plans_format_number($due_months, 0));
                }
            }

            $details[] = [
                'index'   => $index + 1,
                'percent' => $percent,
                'timing'  => $timing,
            ];
        }

        return $details;
    }
}

if (!function_exists('jawda_payment_plans_prepare_display_plan')) {
    function jawda_payment_plans_prepare_display_plan(array $plan, $index) {
        $title = isset($plan['plan_title']) && $plan['plan_title'] !== ''
            ? $plan['plan_title']
            : 'Payment ' . ($index + 1);

        $status = isset($plan['plan_status']) ? $plan['plan_status'] : 'active';
        $payment_method = isset($plan['payment_method']) ? $plan['payment_method'] : 'installment';
        $payment_method_label = jawda_payment_plan_method_options_ar()[$payment_method] ?? '';

        $frequency_key = isset($plan['installment_frequency']) ? $plan['installment_frequency'] : 'monthly';
        $installment_frequency_label = jawda_payment_plan_frequency_options_ar()[$frequency_key] ?? '';

        $handover_key = isset($plan['handover_timeline']) ? $plan['handover_timeline'] : 'immediate';
        $handover_label = jawda_payment_plans_get_handover_label($handover_key);

        $distribution = jawda_payment_plans_calculate_distribution($plan);

        $frequency_meta = jawda_payment_plans_get_frequency_meta($frequency_key);
        $handover_months_map = jawda_payment_plans_get_handover_months();
        $handover_months = isset($handover_months_map[$handover_key]) ? $handover_months_map[$handover_key] : 0;

        $detail_context = [
            'interval_months' => isset($frequency_meta['interval_months']) ? (int) $frequency_meta['interval_months'] : 0,
            'handover_months' => (int) $handover_months,
            'is_rtl'          => is_rtl(),
        ];

        $maintenance_percent = jawda_payment_plans_to_float($plan['maintenance_percent'] ?? 0);
        $maintenance_percent = min(max($maintenance_percent, 0), 100);
        $maintenance_display = jawda_payment_plans_format_percent_string($maintenance_percent);
        $maintenance_due_label = jawda_payment_plans_maintenance_due_options_ar()[$plan['maintenance_due'] ?? ''] ?? '';

        $parking_raw = $plan['parking_price'] ?? '';
        $parking_display = $parking_raw !== '' ? jawda_payment_plans_format_amount_display($parking_raw) : '';

        $clubhouse_raw = $plan['clubhouse_price'] ?? '';
        $clubhouse_display = $clubhouse_raw !== '' ? jawda_payment_plans_format_amount_display($clubhouse_raw) : '';

        $has_handover = isset($plan['has_handover_payment']) && $plan['has_handover_payment'] === 'yes';
        $handover_type = isset($plan['handover_payment_type']) ? $plan['handover_payment_type'] : 'percent';
        $handover_value_raw = isset($plan['handover_payment_value']) ? $plan['handover_payment_value'] : '';
        $handover_display = '';
        if ($has_handover && $handover_value_raw !== '') {
            $handover_display = $handover_type === 'percent'
                ? jawda_payment_plans_format_percent_string($handover_value_raw)
                : jawda_payment_plans_format_amount_display($handover_value_raw);
        }

        return [
            'index'                 => (int) $index,
            'title'                 => $title,
            'status'                => $status,
            'payment_method'        => $payment_method,
            'payment_method_label'  => $payment_method_label,
            'installment_years'     => $plan['installment_years'] ?? '',
            'installment_frequency' => $frequency_key,
            'installment_frequency_label' => $installment_frequency_label,
            'handover_timeline_label'     => $handover_label,
            'schedule_type'         => $plan['payment_schedule_type'] ?? 'equal',
            'distribution'          => [
                'down' => [
                    'percent'   => $distribution['down_percent'],
                    'formatted' => jawda_payment_plans_format_percent_string($distribution['down_percent']),
                ],
                'pre' => [
                    'percent'           => $distribution['pre_percent'],
                    'formatted'         => jawda_payment_plans_format_percent_string($distribution['pre_percent']),
                    'installment_count' => $distribution['pre_installment_count'],
                    'details'           => jawda_payment_plans_build_installment_details($distribution['pre_installments'], 'pre', $detail_context),
                ],
                'handover' => [
                    'percent'   => $distribution['handover_percent'],
                    'formatted' => $distribution['handover_percent'] > 0
                        ? jawda_payment_plans_format_percent_string($distribution['handover_percent'])
                        : '',
                    'type'      => $handover_type,
                    'display'   => $handover_display,
                ],
                'post' => [
                    'percent'           => $distribution['post_percent'],
                    'formatted'         => jawda_payment_plans_format_percent_string($distribution['post_percent']),
                    'installment_count' => $distribution['post_installment_count'],
                    'details'           => jawda_payment_plans_build_installment_details($distribution['post_installments'], 'post', $detail_context),
                ],
                'target_until_handover' => [
                    'percent'   => $distribution['target_until_handover'],
                    'formatted' => jawda_payment_plans_format_percent_string($distribution['target_until_handover']),
                ],
            ],
            'maintenance' => [
                'percent'   => $maintenance_percent,
                'formatted' => $maintenance_display,
                'due_label' => $maintenance_due_label,
            ],
            'parking_display'   => $parking_display,
            'clubhouse_display' => $clubhouse_display,
            'has_handover_payment' => $has_handover,
        ];
    }
}

if (!function_exists('jawda_get_project_payment_templates')) {
    function jawda_get_project_payment_templates($project_id = null, array $args = []) {
        if (!$project_id) {
            $project_id = get_the_ID();
        }

        if (!$project_id) {
            return [];
        }

        $defaults = [
            'include_hidden' => false,
        ];
        $args = wp_parse_args($args, $defaults);

        $raw_plans = carbon_get_post_meta($project_id, 'jawda_payment_plans');
        if (!is_array($raw_plans) || empty($raw_plans)) {
            return [];
        }

        $display_plans = [];
        foreach ($raw_plans as $index => $plan) {
            if (!is_array($plan)) {
                continue;
            }

            if (!$args['include_hidden'] && isset($plan['plan_status']) && $plan['plan_status'] === 'hidden') {
                continue;
            }

            $display_plans[] = jawda_payment_plans_prepare_display_plan($plan, $index);
        }

        return $display_plans;
    }
}
