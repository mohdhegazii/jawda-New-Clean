<?php

/**
 * Database and migration utilities for project features.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns the name of the project features table.
 *
 * @return string
 */
function jawda_project_features_table() {
    global $wpdb;

    return $wpdb->prefix . 'project_features';
}

add_action('admin_init', 'jawda_project_features_install');
/**
 * Creates the lookup table for project features.
 */
function jawda_project_features_install() {
    global $wpdb;

    $current_version = (int) get_option('jawda_project_features_schema_version', 0);
    $target_version  = 3;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table_name      = jawda_project_features_table();
    $charset_collate = $wpdb->get_charset_collate();

    if ($current_version < $target_version) {
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name_ar VARCHAR(255) NOT NULL,
            name_en VARCHAR(255) NOT NULL,
            image_id BIGINT(20) UNSIGNED DEFAULT NULL,
            feature_type VARCHAR(50) NOT NULL DEFAULT 'feature',
            context_projects TINYINT(1) NOT NULL DEFAULT 1,
            context_properties TINYINT(1) NOT NULL DEFAULT 0,
            orientation_id BIGINT(20) UNSIGNED DEFAULT NULL,
            facade_id BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        dbDelta($sql);

        update_option('jawda_project_features_schema_version', $target_version);
        delete_option('jawda_project_features_installed_v1');
    }

    jawda_project_features_maybe_upgrade_schema();
    jawda_project_features_seed_defaults();
}

/**
 * Ensures newly required columns exist on the lookup table.
 */
function jawda_project_features_maybe_upgrade_schema() {
    global $wpdb;

    $table = jawda_project_features_table();
    $table_like = $wpdb->esc_like($table);

    // Bail if the table has not been created yet.
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_like));

    if (!$exists) {
        return;
    }

    $table_escaped = esc_sql($table);
    $columns       = $wpdb->get_col("SHOW COLUMNS FROM {$table_escaped}", 0);

    if (!in_array('feature_type', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_escaped} ADD COLUMN feature_type VARCHAR(50) NOT NULL DEFAULT 'feature' AFTER image_id");
    }

    if (!in_array('context_projects', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_escaped} ADD COLUMN context_projects TINYINT(1) NOT NULL DEFAULT 1 AFTER feature_type");
    }

    if (!in_array('context_properties', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_escaped} ADD COLUMN context_properties TINYINT(1) NOT NULL DEFAULT 0 AFTER context_projects");
    }

    if (!in_array('orientation_id', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_escaped} ADD COLUMN orientation_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER context_properties");
    }

    if (!in_array('facade_id', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_escaped} ADD COLUMN facade_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER orientation_id");
    }

    // Ensure legacy rows receive default values for the new columns.
    $wpdb->query("UPDATE {$table_escaped} SET feature_type = 'feature' WHERE feature_type = '' OR feature_type IS NULL");
    $wpdb->query("UPDATE {$table_escaped} SET context_projects = 1 WHERE context_projects IS NULL");
    $wpdb->query("UPDATE {$table_escaped} SET context_properties = 0 WHERE context_properties IS NULL");
}

add_filter('cron_schedules', 'jawda_project_features_register_cron_schedule');
/**
 * Adds a custom schedule with a 10 minute interval.
 *
 * @param array $schedules Existing schedules.
 * @return array
 */
function jawda_project_features_register_cron_schedule($schedules) {
    if (!isset($schedules['jawda_ten_minutes'])) {
        $schedules['jawda_ten_minutes'] = [
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display'  => __('Every 10 minutes', 'jawda'),
        ];
    }

    return $schedules;
}

add_action('init', 'jawda_project_features_schedule_migration');
/**
 * Schedules the migration batch job if it still needs to run.
 */
function jawda_project_features_schedule_migration() {
    if (jawda_project_features_migration_complete()) {
        return;
    }

    if (!wp_next_scheduled('jawda_project_features_migrate_batch')) {
        wp_schedule_event(time(), 'jawda_ten_minutes', 'jawda_project_features_migrate_batch');
    }
}

add_action('jawda_project_features_migrate_batch', 'jawda_project_features_run_migration_batch');
/**
 * Processes a batch of legacy taxonomy relationships.
 */
function jawda_project_features_run_migration_batch() {
    if (jawda_project_features_migration_complete()) {
        jawda_project_features_unschedule_migration();
        return;
    }

    $state = jawda_project_features_prepare_migration_state();

    if (empty($state['project_queue'])) {
        update_option('jawda_project_features_migration_complete', true);
        jawda_project_features_unschedule_migration();
        return;
    }

    $batch = array_splice($state['project_queue'], 0, 20);

    if (!$batch) {
        update_option('jawda_project_features_migration_complete', true);
        jawda_project_features_unschedule_migration();
        return;
    }

    $processed = isset($state['processed_projects']) && is_array($state['processed_projects'])
        ? $state['processed_projects']
        : [];

    $term_map = isset($state['term_map']) && is_array($state['term_map']) ? $state['term_map'] : [];

    foreach ($batch as $project_id) {
        $project_id = (int) $project_id;

        if ($project_id <= 0) {
            continue;
        }

        $processed[] = $project_id;

        $term_ids = get_object_term_ids($project_id, 'projects_features');

        if (is_wp_error($term_ids)) {
            continue;
        }

        $feature_ids = [];

        foreach ($term_ids as $term_id) {
            $term_id = (int) $term_id;

            if ($term_id <= 0) {
                continue;
            }

            if (!isset($term_map[$term_id])) {
                $feature_id = jawda_project_features_create_from_term($term_id, $term_map);
                if ($feature_id) {
                    $term_map[$term_id] = $feature_id;
                }
            }

            if (isset($term_map[$term_id])) {
                $feature_ids[] = (string) $term_map[$term_id];
            }
        }

        if (function_exists('jawda_project_features_normalize_selection')) {
            $feature_ids = jawda_project_features_normalize_selection($feature_ids, ['feature']);
        } elseif ($feature_ids) {
            $feature_ids = array_values(array_unique($feature_ids));
        }

        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($project_id, 'jawda_project_service_feature_ids', $feature_ids);
            carbon_set_post_meta($project_id, 'jawda_project_feature_ids', $feature_ids);
        } elseif ($feature_ids) {
            update_post_meta($project_id, 'jawda_project_service_feature_ids', $feature_ids);
            update_post_meta($project_id, 'jawda_project_feature_ids', $feature_ids);
        } else {
            delete_post_meta($project_id, 'jawda_project_service_feature_ids');
            delete_post_meta($project_id, 'jawda_project_feature_ids');
        }

        jawda_project_features_sync_translations($project_id, $feature_ids);
    }

    $state['term_map'] = $term_map;
    $state['processed_projects'] = array_values(array_unique($processed));
    $state['project_queue'] = array_values($state['project_queue']);

    update_option('jawda_project_features_migration_state', $state);

    if (empty($state['project_queue'])) {
        update_option('jawda_project_features_migration_complete', true);
        jawda_project_features_unschedule_migration();
    }

    if (function_exists('jawda_project_features_reset_cache')) {
        jawda_project_features_reset_cache();
    }
}

/**
 * Ensures the migration event is unscheduled.
 */
function jawda_project_features_unschedule_migration() {
    $timestamp = wp_next_scheduled('jawda_project_features_migrate_batch');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'jawda_project_features_migrate_batch');
    }
}

/**
 * Returns whether the migration has finished.
 *
 * @return bool
 */
function jawda_project_features_migration_complete() {
    return (bool) get_option('jawda_project_features_migration_complete');
}

/**
 * Prepares and returns the migration state, generating it if needed.
 *
 * @return array
 */
function jawda_project_features_prepare_migration_state() {
    $state = get_option('jawda_project_features_migration_state', []);

    if (!isset($state['term_map']) || !is_array($state['term_map'])) {
        $state['term_map'] = jawda_project_features_generate_term_map();
    }

    if (!isset($state['project_queue']) || !is_array($state['project_queue']) || !$state['project_queue']) {
        $state['project_queue'] = jawda_project_features_fetch_project_queue($state);
    }

    update_option('jawda_project_features_migration_state', $state);

    return $state;
}

/**
 * Creates lookup records for taxonomy terms and returns a map of term => feature id.
 *
 * @return array
 */
function jawda_project_features_generate_term_map() {
    if (!taxonomy_exists('projects_features')) {
        return [];
    }

    $terms = get_terms([
        'taxonomy'   => 'projects_features',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $table = jawda_project_features_table();
    global $wpdb;

    $map = [];
    $group_cache = [];

    foreach ($terms as $term) {
        $term_id = (int) $term->term_id;
        if ($term_id <= 0) {
            continue;
        }

        if (isset($map[$term_id])) {
            continue;
        }

        $group_key = $term_id;
        $name_ar = $term->name;
        $name_en = $term->name;
        $translations = [];
        $feature_id = 0;

        if (function_exists('pll_get_term_translations')) {
            $translations = pll_get_term_translations($term_id);
            if ($translations && is_array($translations)) {
                $group_key = min(array_map('intval', $translations));
                foreach ($translations as $lang => $translated_term_id) {
                    $translated_term_id = (int) $translated_term_id;
                    if ($translated_term_id <= 0) {
                        continue;
                    }
                    $translated_term = get_term($translated_term_id);
                    if (!$translated_term || is_wp_error($translated_term)) {
                        continue;
                    }
                    if ($lang === 'ar') {
                        $name_ar = $translated_term->name;
                    }
                    if ($lang === 'en') {
                        $name_en = $translated_term->name;
                    }
                }
            }
        }

        if (isset($group_cache[$group_key])) {
            $feature_id = $group_cache[$group_key];
        } else {
            $data = [
                 'slug_ar'             => $name_ar ? $name_ar : $name_en,
                'name_en'            => $name_en ? $name_en : $name_ar,
                'image_id'           => 0,
                'feature_type'       => 'feature',
                'context_projects'   => 1,
                'context_properties' => 0,
                'orientation_id'     => 0,
                'facade_id'          => 0,
            ];

            $inserted = $wpdb->insert($table, $data, ['%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d']);

            if ($inserted) {
                $feature_id = (int) $wpdb->insert_id;
                $group_cache[$group_key] = $feature_id;
                if (function_exists('jawda_project_features_reset_cache')) {
                    jawda_project_features_reset_cache();
                }
            } else {
                continue;
            }
        }

        if (!$feature_id) {
            continue;
        }

        $map[$term_id] = $feature_id;

        if (isset($translations) && is_array($translations)) {
            foreach ($translations as $translated_term_id) {
                $translated_term_id = (int) $translated_term_id;
                if ($translated_term_id > 0) {
                    $map[$translated_term_id] = $feature_id;
                }
            }
        }
    }

    return $map;
}

/**
 * Builds a queue of project IDs to migrate.
 *
 * @param array $state Migration state.
 * @return array
 */
function jawda_project_features_fetch_project_queue($state) {
    global $wpdb;

    $processed = isset($state['processed_projects']) && is_array($state['processed_projects'])
        ? array_map('intval', $state['processed_projects'])
        : [];

    $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'projects' ORDER BY ID ASC";
    $ids = $wpdb->get_col($query);

    if ($processed) {
        $ids = array_diff($ids, $processed);
    }

    return array_values(array_map('intval', $ids));
}

/**
 * Creates a lookup record for a single term if it does not yet exist.
 *
 * @param int   $term_id Term identifier.
 * @param array $existing_map Current map of term => feature id.
 * @return int Feature identifier.
 */
function jawda_project_features_create_from_term($term_id, &$existing_map) {
    $term = get_term($term_id);

    if (!$term || is_wp_error($term)) {
        return 0;
    }

    $translations = [];
    $name_ar = $term->name;
    $name_en = $term->name;

    if (function_exists('pll_get_term_translations')) {
        $translations = pll_get_term_translations($term_id);
        if ($translations && is_array($translations)) {
            foreach ($translations as $lang => $translated_id) {
                $translated_id = (int) $translated_id;
                if ($translated_id <= 0) {
                    continue;
                }
                $translated_term = get_term($translated_id);
                if (!$translated_term || is_wp_error($translated_term)) {
                    continue;
                }
                if ($lang === 'ar') {
                    $name_ar = $translated_term->name;
                }
                if ($lang === 'en') {
                    $name_en = $translated_term->name;
                }
                $existing_map[$translated_id] = 0; // reserve slot
            }
        }
    }

    global $wpdb;
    $table = jawda_project_features_table();

    $data = [
         'slug_ar'             => $name_ar ? $name_ar : $name_en,
        'name_en'            => $name_en ? $name_en : $name_ar,
        'image_id'           => 0,
        'feature_type'       => 'feature',
        'context_projects'   => 1,
        'context_properties' => 0,
        'orientation_id'     => 0,
        'facade_id'          => 0,
    ];

    $inserted = $wpdb->insert($table, $data, ['%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d']);

    if (!$inserted) {
        return 0;
    }

    $feature_id = (int) $wpdb->insert_id;

    if (function_exists('jawda_project_features_reset_cache')) {
        jawda_project_features_reset_cache();
    }

    $existing_map[$term_id] = $feature_id;

    if ($translations && is_array($translations)) {
        foreach ($translations as $translated_id) {
            $translated_id = (int) $translated_id;
            if ($translated_id > 0) {
                $existing_map[$translated_id] = $feature_id;
            }
        }
    }

    return $feature_id;
}

/**
 * Mirrors migrated feature selections to translations.
 *
 * @param int   $post_id     Post identifier.
 * @param array $feature_ids Selected feature identifiers.
 */
function jawda_project_features_sync_translations($post_id, $feature_ids) {
    if (!function_exists('pll_get_post_translations') || !function_exists('pll_get_post_language')) {
        return;
    }

    if (function_exists('jawda_project_features_normalize_selection')) {
        $feature_ids = jawda_project_features_normalize_selection($feature_ids, ['feature']);
    } elseif (!is_array($feature_ids)) {
        $feature_ids = $feature_ids !== '' ? [$feature_ids] : [];
    }

    $translations = pll_get_post_translations($post_id);

    if (!$translations || !is_array($translations)) {
        return;
    }

    foreach ($translations as $language => $translation_id) {
        $translation_id = (int) $translation_id;

        if ($translation_id <= 0 || $translation_id === (int) $post_id) {
            continue;
        }

        if (function_exists('carbon_set_post_meta')) {
            carbon_set_post_meta($translation_id, 'jawda_project_service_feature_ids', $feature_ids);
            carbon_set_post_meta($translation_id, 'jawda_project_feature_ids', $feature_ids);
        } elseif (!empty($feature_ids)) {
            update_post_meta($translation_id, 'jawda_project_service_feature_ids', $feature_ids);
            update_post_meta($translation_id, 'jawda_project_feature_ids', $feature_ids);
        } else {
            delete_post_meta($translation_id, 'jawda_project_service_feature_ids');
            delete_post_meta($translation_id, 'jawda_project_feature_ids');
        }
    }
}


function jawda_project_features_seed_normalize_contexts($relation) {
    $relation = strtolower((string) $relation);
    $relation = str_replace(["\xc2\xa0", ' ', "\xE2\x80\x8F", "\xE2\x80\x8E"], '', $relation);

    $projects = strpos($relation, 'مشروع') !== false || strpos($relation, 'project') !== false;
    $properties = strpos($relation, 'وحدة') !== false || strpos($relation, 'unit') !== false;

    if (!$projects && !$properties) {
        $projects = true;
    }

    return [
        'projects'   => $projects ? 1 : 0,
        'properties' => $properties ? 1 : 0,
    ];
}

function jawda_project_features_seed_normalize_key($value) {
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return '';
    }

    $value = str_replace(["\xc2\xa0", "\xE2\x80\x8F", "\xE2\x80\x8E"], '', $value);

    return $value;
}

function jawda_project_features_seed_upsert(array $record, &$inserted_flag = null) {
    global $wpdb;

    $table = jawda_project_features_table();

    $name_ar = isset($record[ 'slug_ar' ]) ? trim((string) $record[ 'slug_ar' ]) : '';
    $name_en = isset($record['name_en']) ? trim((string) $record['name_en']) : '';

    if ($name_ar === '' && $name_en === '') {
        return 0;
    }

    $feature_type = isset($record['feature_type']) ? (string) $record['feature_type'] : 'feature';
    $allowed_types = function_exists('jawda_project_features_get_feature_types')
        ? array_keys(jawda_project_features_get_feature_types())
        : ['feature', 'amenity', 'facility', 'finishing', 'view', 'orientation', 'facade', 'marketing_orientation'];

    if (!in_array($feature_type, $allowed_types, true)) {
        $feature_type = 'feature';
    }

    $context_projects   = !empty($record['context_projects']) ? 1 : 0;
    $context_properties = !empty($record['context_properties']) ? 1 : 0;
    $orientation_id     = isset($record['orientation_id']) ? (int) $record['orientation_id'] : 0;
    $facade_id          = isset($record['facade_id']) ? (int) $record['facade_id'] : 0;

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE feature_type = %s AND (name_ar = %s OR name_en = %s) LIMIT 1",
        $feature_type,
        $name_ar !== '' ? $name_ar : $name_en,
        $name_en !== '' ? $name_en : $name_ar
    ));

    $data = [
         'slug_ar'             => $name_ar !== '' ? $name_ar : $name_en,
        'name_en'            => $name_en !== '' ? $name_en : $name_ar,
        'image_id'           => 0,
        'feature_type'       => $feature_type,
        'context_projects'   => $context_projects,
        'context_properties' => $context_properties,
        'orientation_id'     => $orientation_id,
        'facade_id'          => $facade_id,
    ];

    if ($existing) {
        $wpdb->update(
            $table,
            $data,
            ['id' => (int) $existing],
            ['%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d'],
            ['%d']
        );

        if ($inserted_flag !== null) {
            $inserted_flag = true;
        }

        return (int) $existing;
    }

    $result = $wpdb->insert($table, $data, ['%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d']);

    if ($result) {
        if ($inserted_flag !== null) {
            $inserted_flag = true;
        }

        return (int) $wpdb->insert_id;
    }

    return 0;
}

function jawda_project_features_seed_defaults($force = false) {
    $seed_version = (int) get_option('jawda_project_features_seed_version', 0);
    $target_version = 3;

    if ($force) {
        $seed_version = 0;
    }

    if ($seed_version >= $target_version && !$force) {
        return;
    }

    global $wpdb;
    $table = jawda_project_features_table();
    $table_like = $wpdb->esc_like($table);
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_like));

    if (!$exists) {
        return;
    }

    $finishing_types = [
        [
             'slug_ar'             => 'بدون تشطيب (على الطوب الأحمر)',
            'name_en'            => 'Unfinished / Shell & Core',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
        [
             'slug_ar'             => 'نصف تشطيب (على المحارة أو الأساسيات)',
            'name_en'            => 'Semi-Finished / Half Finishing',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
        [
             'slug_ar'             => 'تشطيب كامل / جاهز للسكن',
            'name_en'            => 'Full Finishing / Ready to Move-in',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
        [
             'slug_ar'             => 'تشطيب لوكس',
            'name_en'            => 'Lux Finishing',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
        [
             'slug_ar'             => 'تشطيب سوبر لوكس',
            'name_en'            => 'Super Lux Finishing',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
        [
             'slug_ar'             => 'تشطيب ألترا سوبر لوكس',
            'name_en'            => 'Ultra Super Lux Finishing',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
        [
             'slug_ar'             => 'تشطيب ديلوكس',
            'name_en'            => 'Deluxe Finishing',
            'feature_type'       => 'finishing',
            'context_projects'   => 0,
            'context_properties' => 1,
        ],
    ];

    $lookup_defaults = [
        [ 'slug_ar'  => 'إطلالة على اللاندسكيب', 'name_en' => 'Landscape View', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'إطلالة على البحيرة', 'name_en' => 'Lake View', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'سقف مرتفع', 'name_en' => 'High Ceiling', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'واجهة زجاجية بالكامل', 'name_en' => 'Full Glass Façade', 'relation' => 'مشروع / وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'نظام سمارت هوم', 'name_en' => 'Smart Home System', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'مدخل فندقي', 'name_en' => 'Hotel-like Entrance', 'relation' => 'مشروع', 'category' => 'Feature'],
        [ 'slug_ar'  => 'إضاءة طبيعية واسعة', 'name_en' => 'Natural Light Spaces', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'تصميم معماري عصري', 'name_en' => 'Modern Architectural Design', 'relation' => 'مشروع', 'category' => 'Feature'],
        [ 'slug_ar'  => 'كلوب هاوس', 'name_en' => 'Clubhouse', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'جيم وساونا وسبا', 'name_en' => 'Gym, Sauna & Spa', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'حمام سباحة إنفينيتي', 'name_en' => 'Infinity Pool', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'ممشى للجري', 'name_en' => 'Jogging Track', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'منطقة ألعاب أطفال', 'name_en' => 'Kids Play Area', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'منطقة شواء', 'name_en' => 'BBQ Area', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'سينما خاصة', 'name_en' => 'Private Cinema', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'كافيهات ومطاعم', 'name_en' => 'Cafés & Restaurants', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'Roof Lounge', 'name_en' => 'Rooftop Lounge', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'مساحات خضراء واسعة', 'name_en' => 'Wide Green Areas', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'أمن وحراسة 24 ساعة', 'name_en' => '24/7 Security', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'نظام كاميرات مراقبة', 'name_en' => 'CCTV Surveillance', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'مصاعد كهربائية حديثة', 'name_en' => 'Modern Elevators', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'جراج تحت الأرض', 'name_en' => 'Underground Parking', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'مولّد كهرباء احتياطي', 'name_en' => 'Backup Generator', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'نظام مكافحة حريق', 'name_en' => 'Fire Fighting System', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'استقبال واستعلامات', 'name_en' => 'Reception & Concierge', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'خدمة تنظيف وصيانة', 'name_en' => 'Cleaning & Maintenance Service', 'relation' => 'مشروع / وحدة', 'category' => 'Facility'],
        [ 'slug_ar'  => 'إنترنت فائق السرعة', 'name_en' => 'High-speed Internet', 'relation' => 'مشروع / وحدة', 'category' => 'Facility'],
        [ 'slug_ar'  => 'محطة شحن سيارات كهربائية', 'name_en' => 'EV Charging Station', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'مولد مياه احتياطي', 'name_en' => 'Water Backup Tank', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'مسجد أو مصلى', 'name_en' => 'Mosque / Prayer Area', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'هايبر ماركت', 'name_en' => 'Supermarket', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'منطقة إدارية', 'name_en' => 'Administrative Area', 'relation' => 'مشروع', 'category' => 'Feature'],
        [ 'slug_ar'  => 'منطقة طبية', 'name_en' => 'Medical Area', 'relation' => 'مشروع', 'category' => 'Feature'],
        [ 'slug_ar'  => 'مداخل منفصلة للوحدات', 'name_en' => 'Separate Entrances', 'relation' => 'مشروع', 'category' => 'Feature'],
        [ 'slug_ar'  => 'نظام تكييف مركزي', 'name_en' => 'Central AC System', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'نظام إطفاء ذكي', 'name_en' => 'Smart Fire Detection', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'كاميرات داخلية للوحدة', 'name_en' => 'Internal Security Cameras', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'خدمات إدارة تأجير', 'name_en' => 'Rental Management Service', 'relation' => 'مشروع', 'category' => 'Facility'],
        [ 'slug_ar'  => 'مسارات للدراجات', 'name_en' => 'Cycling Paths', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'فندق أو خدمات فندقية', 'name_en' => 'Hotel / Serviced Units', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'عيادات وصيدليات', 'name_en' => 'Clinics & Pharmacy', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'مول تجاري داخلي', 'name_en' => 'Indoor Mall', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'منطقة ترفيه ليلي', 'name_en' => 'Entertainment Zone', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'صالة متعددة الاستخدام', 'name_en' => 'Multipurpose Hall', 'relation' => 'مشروع', 'category' => 'Amenity'],
        [ 'slug_ar'  => 'حديقة خاصة', 'name_en' => 'Private Garden', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'تراس واسع', 'name_en' => 'Large Terrace', 'relation' => 'وحدة', 'category' => 'Feature'],
        [ 'slug_ar'  => 'لوكيشن استراتيجي', 'name_en' => 'Prime Location', 'relation' => 'مشروع', 'category' => 'Feature'],
    ];

    $view_defaults = [
        [ 'slug_ar'  => 'على شارع رئيسي', 'name_en' => 'Main Road View'],
        [ 'slug_ar'  => 'على شارع داخلي', 'name_en' => 'Internal Street View'],
        [ 'slug_ar'  => 'على ميدان', 'name_en' => 'Square View'],
        [ 'slug_ar'  => 'على محور / طريق سريع', 'name_en' => 'Highway / Main Axis View'],
        [ 'slug_ar'  => 'على مدخل المشروع', 'name_en' => 'Project Entrance View'],
        [ 'slug_ar'  => 'على حدائق / لاندسكيب', 'name_en' => 'Gardens / Landscape View'],
        [ 'slug_ar'  => 'على بارك مركزي', 'name_en' => 'Central Park View'],
        [ 'slug_ar'  => 'على بحيرة صناعية', 'name_en' => 'Artificial Lake View'],
        [ 'slug_ar'  => 'على حمام السباحة', 'name_en' => 'Pool View'],
        [ 'slug_ar'  => 'على النادي / الكلوب هاوس', 'name_en' => 'Clubhouse / Club View'],
        [ 'slug_ar'  => 'على منطقة أطفال', 'name_en' => 'Kids Area View'],
        [ 'slug_ar'  => 'على منطقة تجارية (محلات)', 'name_en' => 'Retail / Commercial Strip View'],
        [ 'slug_ar'  => 'على الجراج / الباركينج', 'name_en' => 'Parking View'],
        [ 'slug_ar'  => 'على فناء داخلي (كورتيارد)', 'name_en' => 'Courtyard / Internal Garden View'],
        [ 'slug_ar'  => 'على واجهات مباني أخرى', 'name_en' => 'Other Buildings View'],
        [ 'slug_ar'  => 'على سور المشروع', 'name_en' => 'Compound Wall View'],
        [ 'slug_ar'  => 'على مدرسة / جامعة', 'name_en' => 'School / University View'],
        [ 'slug_ar'  => 'على منطقة خدمية (محطة، خدمات)', 'name_en' => 'Service Area View'],
        [ 'slug_ar'  => 'فيو مفتوح / بانوراما', 'name_en' => 'Open / Panoramic View'],
        [ 'slug_ar'  => 'بدون فيو مميز (منور / حارة خلفية)', 'name_en' => 'Light Well / Back Alley View'],
    ];

    $orientation_defaults = [
        [ 'slug_ar'  => 'بحري (شمالي)', 'name_en' => 'North / Bahary'],
        [ 'slug_ar'  => 'قبلي (جنوبي)', 'name_en' => 'South / Qebly'],
        [ 'slug_ar'  => 'شرقي', 'name_en' => 'East'],
        [ 'slug_ar'  => 'غربي', 'name_en' => 'West'],
        [ 'slug_ar'  => 'بحري شرقي', 'name_en' => 'North-East (Bahary Sharqy)'],
        [ 'slug_ar'  => 'بحري غربي', 'name_en' => 'North-West (Bahary Gharby)'],
        [ 'slug_ar'  => 'قبلي شرقي', 'name_en' => 'South-East (Qebly Sharqy)'],
        [ 'slug_ar'  => 'قبلي غربي', 'name_en' => 'South-West (Qebly Gharby)'],
        [ 'slug_ar'  => 'متعددة الاتجاهات', 'name_en' => 'Multiple Orientations'],
    ];

    $facade_defaults = [
        [ 'slug_ar'  => 'واجهة واحدة', 'name_en' => 'Single Facade'],
        [ 'slug_ar'  => 'واجهتين ناصية', 'name_en' => 'Corner Unit (Two Facades)'],
        [ 'slug_ar'  => 'ثلاث واجهات', 'name_en' => 'Three Facades (Head of Block)'],
        [ 'slug_ar'  => 'أربع واجهات / مستقلة', 'name_en' => 'Detached (Four Facades)'],
        [ 'slug_ar'  => 'بدون جيران جانبيين', 'name_en' => 'No Side Neighbors'],
        [ 'slug_ar'  => 'بدون جار علوي', 'name_en' => 'No Upper Neighbor (Top Floor)'],
        [ 'slug_ar'  => 'بدون جار سفلي', 'name_en' => 'No Lower Neighbor (Ground / Over Podium)'],
        [ 'slug_ar'  => 'بدون جيران (مستقلة بالكامل)', 'name_en' => 'Fully Detached / No Neighbors'],
    ];

    $marketing_orientation_defaults = [
        [
             'slug_ar'         => 'واجهة واحدة بحري',
            'name_en'        => 'Single Bahary Facade',
            'orientation_ar' => 'بحري (شمالي)',
            'orientation_en' => 'North / Bahary',
            'facade_ar'      => 'واجهة واحدة',
            'facade_en'      => 'Single Facade',
        ],
        [
             'slug_ar'         => 'واجهة واحدة قبلي',
            'name_en'        => 'Single Qebly Facade',
            'orientation_ar' => 'قبلي (جنوبي)',
            'orientation_en' => 'South / Qebly',
            'facade_ar'      => 'واجهة واحدة',
            'facade_en'      => 'Single Facade',
        ],
        [
             'slug_ar'         => 'واجهة واحدة شرقي',
            'name_en'        => 'Single East Facade',
            'orientation_ar' => 'شرقي',
            'orientation_en' => 'East',
            'facade_ar'      => 'واجهة واحدة',
            'facade_en'      => 'Single Facade',
        ],
        [
             'slug_ar'         => 'واجهة واحدة غربي',
            'name_en'        => 'Single West Facade',
            'orientation_ar' => 'غربي',
            'orientation_en' => 'West',
            'facade_ar'      => 'واجهة واحدة',
            'facade_en'      => 'Single Facade',
        ],
        [
             'slug_ar'         => 'ناصية بحري شرقي',
            'name_en'        => 'Corner Bahary-East (Two Facades)',
            'orientation_ar' => 'بحري شرقي',
            'orientation_en' => 'North-East (Bahary Sharqy)',
            'facade_ar'      => 'واجهتين ناصية',
            'facade_en'      => 'Corner Unit (Two Facades)',
        ],
        [
             'slug_ar'         => 'ناصية بحري غربي',
            'name_en'        => 'Corner Bahary-West (Two Facades)',
            'orientation_ar' => 'بحري غربي',
            'orientation_en' => 'North-West (Bahary Gharby)',
            'facade_ar'      => 'واجهتين ناصية',
            'facade_en'      => 'Corner Unit (Two Facades)',
        ],
        [
             'slug_ar'         => 'ناصية قبلي شرقي',
            'name_en'        => 'Corner Qebly-East',
            'orientation_ar' => 'قبلي شرقي',
            'orientation_en' => 'South-East (Qebly Sharqy)',
            'facade_ar'      => 'واجهتين ناصية',
            'facade_en'      => 'Corner Unit (Two Facades)',
        ],
        [
             'slug_ar'         => 'ناصية قبلي غربي',
            'name_en'        => 'Corner Qebly-West',
            'orientation_ar' => 'قبلي غربي',
            'orientation_en' => 'South-West (Qebly Gharby)',
            'facade_ar'      => 'واجهتين ناصية',
            'facade_en'      => 'Corner Unit (Two Facades)',
        ],
        [
             'slug_ar'         => 'ثلاث واجهات',
            'name_en'        => 'Three-Facade Unit',
            'orientation_ar' => '',
            'orientation_en' => '',
            'facade_ar'      => 'ثلاث واجهات',
            'facade_en'      => 'Three Facades (Head of Block)',
        ],
        [
             'slug_ar'         => 'بدون جيران',
            'name_en'        => 'Fully Detached / No Neighbors',
            'orientation_ar' => '',
            'orientation_en' => '',
            'facade_ar'      => 'بدون جيران (مستقلة بالكامل)',
            'facade_en'      => 'Fully Detached / No Neighbors',
        ],
    ];

    $inserted = false;

    foreach ($finishing_types as $record) {
        jawda_project_features_seed_upsert($record, $inserted);
    }

    foreach ($lookup_defaults as $item) {
        $contexts = jawda_project_features_seed_normalize_contexts(isset($item['relation']) ? $item['relation'] : '');
        $type     = isset($item['category']) ? strtolower($item['category']) : 'feature';
        if (!in_array($type, ['feature', 'amenity', 'facility', 'finishing'], true)) {
            $type = 'feature';
        }

        jawda_project_features_seed_upsert([
             'slug_ar'             => isset($item[ 'slug_ar' ]) ? $item[ 'slug_ar' ] : '',
            'name_en'            => isset($item['name_en']) ? $item['name_en'] : '',
            'feature_type'       => $type,
            'context_projects'   => $contexts['projects'],
            'context_properties' => $contexts['properties'],
        ], $inserted);
    }

    foreach ($view_defaults as $item) {
        jawda_project_features_seed_upsert([
             'slug_ar'             => $item[ 'slug_ar' ],
            'name_en'            => $item['name_en'],
            'feature_type'       => 'view',
            'context_projects'   => 0,
            'context_properties' => 1,
        ], $inserted);
    }

    $orientation_map = [];
    foreach ($orientation_defaults as $item) {
        $id = jawda_project_features_seed_upsert([
             'slug_ar'             => $item[ 'slug_ar' ],
            'name_en'            => $item['name_en'],
            'feature_type'       => 'orientation',
            'context_projects'   => 0,
            'context_properties' => 1,
        ], $inserted);

        if ($id) {
            $orientation_map[jawda_project_features_seed_normalize_key($item[ 'slug_ar' ])] = $id;
            $orientation_map[jawda_project_features_seed_normalize_key($item['name_en'])] = $id;
        }
    }

    $facade_map = [];
    foreach ($facade_defaults as $item) {
        $id = jawda_project_features_seed_upsert([
             'slug_ar'             => $item[ 'slug_ar' ],
            'name_en'            => $item['name_en'],
            'feature_type'       => 'facade',
            'context_projects'   => 0,
            'context_properties' => 1,
        ], $inserted);

        if ($id) {
            $facade_map[jawda_project_features_seed_normalize_key($item[ 'slug_ar' ])] = $id;
            $facade_map[jawda_project_features_seed_normalize_key($item['name_en'])] = $id;
        }
    }

    foreach ($marketing_orientation_defaults as $item) {
        $orientation_id = 0;
        $facade_id      = 0;

        $orientation_keys = [
            jawda_project_features_seed_normalize_key(isset($item['orientation_ar']) ? $item['orientation_ar'] : ''),
            jawda_project_features_seed_normalize_key(isset($item['orientation_en']) ? $item['orientation_en'] : ''),
        ];

        foreach ($orientation_keys as $key) {
            if ($key && isset($orientation_map[$key])) {
                $orientation_id = $orientation_map[$key];
                break;
            }
        }

        $facade_keys = [
            jawda_project_features_seed_normalize_key(isset($item['facade_ar']) ? $item['facade_ar'] : ''),
            jawda_project_features_seed_normalize_key(isset($item['facade_en']) ? $item['facade_en'] : ''),
        ];

        foreach ($facade_keys as $key) {
            if ($key && isset($facade_map[$key])) {
                $facade_id = $facade_map[$key];
                break;
            }
        }

        jawda_project_features_seed_upsert([
             'slug_ar'             => isset($item[ 'slug_ar' ]) ? $item[ 'slug_ar' ] : '',
            'name_en'            => isset($item['name_en']) ? $item['name_en'] : '',
            'feature_type'       => 'marketing_orientation',
            'context_projects'   => 0,
            'context_properties' => 1,
            'orientation_id'     => $orientation_id,
            'facade_id'          => $facade_id,
        ], $inserted);
    }

    if (function_exists('jawda_project_features_reset_cache')) {
        jawda_project_features_reset_cache();
    }

    update_option('jawda_project_features_seed_version', $target_version);
}
