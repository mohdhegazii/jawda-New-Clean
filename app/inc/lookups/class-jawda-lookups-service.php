<?php
/**
 * Service class for managing the 4-level Jawda Lookups system.
 */

if (!defined('ABSPATH')) exit;

class Jawda_Lookups_Service {

    private static function table($name) { global $wpdb; return $wpdb->prefix . 'jawda_' . $name; }
    private static function now() { return current_time('mysql'); }

    private static function sanitize_entity_data($table, $data, $required_fields = ['name_en',  'slug_ar' ], $id = null) {
        $base_slug = $data['slug'] ?? ($data['name_en'] ?? $data[ 'slug_ar' ] ?? '');
        if (empty($base_slug) && $id) {
            $existing = self::get_entity($table, $id);
            $base_slug = $existing['slug'] ?? '';
        }
        $fields = [
            'slug' => self::generate_unique_slug($table, $base_slug, $id),
            'name_en' => sanitize_text_field($data['name_en'] ?? ''),
             'slug_ar'  => sanitize_text_field($data[ 'slug_ar' ] ?? ''),
            'description_en' => isset($data['description_en']) ? wp_kses_post($data['description_en']) : '',
            'description_ar' => isset($data['description_ar']) ? wp_kses_post($data['description_ar']) : '',
            'icon_class' => sanitize_text_field($data['icon_class'] ?? ''),
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
        ];

        if ($table === 'sub_properties') {
            $fields['property_type_id'] = isset($data['property_type_id']) ? (int) $data['property_type_id'] : 0;
            $required_fields[] = 'property_type_id';
        }

        foreach ($required_fields as $field) {
            if (empty($fields[$field])) {
                return new WP_Error('missing_data', "$field is required.");
            }
        }

        return $fields;
    }

    private static function generate_unique_slug($table, $base_slug, $id = null) {
        $slug = sanitize_title($base_slug);
        if (empty($slug)) {
            $slug = 'item';
        }
        $unique_slug = $slug;
        $suffix = 2;
        while (!self::is_slug_unique($table, $unique_slug, $id)) {
            $unique_slug = $slug . '-' . $suffix;
            $suffix++;
            if ($suffix > 50) {
                $unique_slug = $slug . '-' . uniqid();
                break;
            }
        }
        return $unique_slug;
    }

    private static function is_slug_unique($table, $slug, $id = null) {
        global $wpdb;
        $sql = "SELECT id FROM " . self::table($table) . " WHERE slug = %s";
        $params = [$slug];
        if ($id) {
            $sql .= " AND id != %d";
            $params[] = (int) $id;
        }
        $exists = $wpdb->get_var($wpdb->prepare($sql, $params));
        return !$exists;
    }

    // --- Generic CRUD Methods ---
    private static function create_entity($table, $data, $required_fields = ['name_en',  'slug_ar' ]) {
        global $wpdb;
        $fields = self::sanitize_entity_data($table, $data, $required_fields);
        if (is_wp_error($fields)) {
            return $fields;
        }
        $fields['created_at'] = self::now();
        $fields['updated_at'] = self::now();
        $wpdb->insert(self::table($table), $fields);
        return $wpdb->insert_id;
    }

    private static function update_entity($table, $id, $data, $required_fields = ['name_en',  'slug_ar' ]) {
        global $wpdb;
        $fields = self::sanitize_entity_data($table, $data, $required_fields, $id);
        if (is_wp_error($fields)) {
            return $fields;
        }
        $fields['updated_at'] = self::now();
        return $wpdb->update(self::table($table), $fields, ['id' => (int)$id]);
    }

    private static function delete_entity($table, $id) {
        global $wpdb;
        return $wpdb->update(self::table($table), ['is_active' => 0, 'updated_at' => self::now()], ['id' => (int)$id]);
    }

    private static function set_active_state($table, $ids, $state) {
        global $wpdb;
        $ids = array_filter(array_map('intval', (array) $ids));
        if (empty($ids)) return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $wpdb->prepare("UPDATE " . self::table($table) . " SET is_active = %d, updated_at = %s WHERE id IN ({$placeholders})", array_merge([(int)$state, self::now()], $ids));
        return $wpdb->query($sql);
    }

    private static function get_entity($table, $id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table($table) . " WHERE id = %d", $id), ARRAY_A);
    }

    private static function get_all_entities($table, $args = []) {
        global $wpdb;
        $defaults = ['is_active' => 1, 'orderby' => 'sort_order', 'order' => 'ASC'];
        $args = wp_parse_args($args, $defaults);
        $where = '';
        if ($args['is_active'] !== null) {
            $where = $wpdb->prepare(" WHERE is_active = %d", $args['is_active']);
        }
        $sql = "SELECT * FROM " . self::table($table) . $where . " ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        return $wpdb->get_results($sql, ARRAY_A);
    }

    // --- Categories (Level 1) ---
    public static function create_category($data) { return self::create_entity('categories', $data); }
    public static function update_category($id, $data) { return self::update_entity('categories', $id, $data); }
    public static function delete_category($id) { return self::delete_entity('categories', $id); }
    public static function set_category_active_state($ids, $state) { return self::set_active_state('categories', $ids, $state); }
    public static function get_category($id) { return self::get_entity('categories', $id); }
    public static function get_all_categories($args = []) { return self::get_all_entities('categories', $args); }

    // --- Usages (Level 4) ---
    public static function create_usage($data) { return self::create_entity('usages', $data); }
    public static function update_usage($id, $data) { return self::update_entity('usages', $id, $data); }
    public static function delete_usage($id) { return self::delete_entity('usages', $id); }
    public static function set_usage_active_state($ids, $state) { return self::set_active_state('usages', $ids, $state); }
    public static function get_usage($id) { return self::get_entity('usages', $id); }
    public static function get_all_usages($args = []) { return self::get_all_entities('usages', $args); }

    // --- Property Types (Level 2) ---
    public static function create_property_type($data, $category_ids = [], $usage_ids = []) {
        $id = self::create_entity('property_types', $data);
        if ($id && !is_wp_error($id)) {
            self::sync_relations($id, 'categories', $category_ids);
            self::sync_relations($id, 'usages', $usage_ids);
        }
        return $id;
    }
    public static function update_property_type($id, $data, $category_ids = [], $usage_ids = []) {
        $result = self::update_entity('property_types', $id, $data);
        if (!is_wp_error($result)) {
            self::sync_relations($id, 'categories', $category_ids);
            self::sync_relations($id, 'usages', $usage_ids);
        }
        return $id;
    }
    public static function delete_property_type($id) { return self::delete_entity('property_types', $id); }
    public static function set_property_type_active_state($ids, $state) { return self::set_active_state('property_types', $ids, $state); }
    public static function get_property_type($id) { return self::get_entity('property_types', $id); }
    public static function get_all_property_types($args = []) { return self::get_all_entities('property_types', $args); }
    public static function get_categories_for_property_type($id) { return self::get_related_ids($id, 'categories'); }
    public static function get_usages_for_property_type($id) { return self::get_related_ids($id, 'usages'); }

    // --- Sub-Properties (Level 3) ---
    public static function create_sub_property($data) { return self::create_entity('sub_properties', $data, ['name_en',  'slug_ar' , 'property_type_id']); }
    public static function update_sub_property($id, $data) { return self::update_entity('sub_properties', $id, $data, ['name_en',  'slug_ar' , 'property_type_id']); }
    public static function delete_sub_property($id) { return self::delete_entity('sub_properties', $id); }
    public static function set_sub_property_active_state($ids, $state) { return self::set_active_state('sub_properties', $ids, $state); }
    public static function get_sub_property($id) { return self::get_entity('sub_properties', $id); }
    public static function get_all_sub_properties($args = []) { return self::get_all_entities('sub_properties', $args); }

    // --- Aliases (Operates on Sub-Properties) ---
    public static function create_alias($data) {
        global $wpdb;
        $table = self::table('aliases');
        $fields = self::sanitize_alias_fields($data);
        if (is_wp_error($fields)) {
            return $fields;
        }
        $fields['created_at'] = self::now();
        $fields['updated_at'] = self::now();
        $fields['is_deleted'] = 0;
        $wpdb->insert($table, $fields);
        return $wpdb->insert_id;
    }
    public static function update_alias($id, $data) {
        global $wpdb;
        $table = self::table('aliases');
        $fields = self::sanitize_alias_fields($data);
        if (is_wp_error($fields)) {
            return $fields;
        }
        $fields['updated_at'] = self::now();
        return $wpdb->update($table, $fields, ['id' => (int) $id]);
    }
    public static function delete_alias($id) {
        global $wpdb;
        return $wpdb->update(self::table('aliases'), ['is_deleted' => 1, 'updated_at' => self::now()], ['id' => (int) $id]);
    }
    public static function get_alias($id) { return self::get_entity('aliases', $id); }
    public static function get_all_aliases($args = []) {
        global $wpdb;
        $defaults = ['is_deleted' => 0, 'orderby' => 'id', 'order' => 'DESC'];
        $args = wp_parse_args($args, $defaults);
        $where = ($args['is_deleted'] !== null) ? $wpdb->prepare(" WHERE is_deleted = %d", $args['is_deleted']) : "";
        $sql = "SELECT * FROM " . self::table('aliases') . $where . " ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        return $wpdb->get_results($sql, ARRAY_A);
    }
    private static function sanitize_alias_fields($data) {
        $fields = [
            'sub_property_id' => isset($data['sub_property_id']) ? (int) $data['sub_property_id'] : 0,
              'slug_ar'  => isset($data[ 'slug_ar' ]) ? sanitize_text_field($data[ 'slug_ar' ]) : '',
            'name_en' => isset($data['name_en']) ? sanitize_text_field($data['name_en']) : '',
        ];
        if (empty($fields['sub_property_id']) || (empty($fields[ 'slug_ar' ]) && empty($fields['name_en']))) {
            return new WP_Error('invalid_alias', 'Sub-Property, project, and a name are required.');
        }
        return $fields;
    }


    // --- Relationship Helpers ---
    public static function get_all_property_type_category_relations() {
        global $wpdb;
        $table = self::table('property_type_categories');
        return $wpdb->get_results("SELECT property_type_id, category_id FROM {$table}");
    }
    public static function get_all_property_type_usage_relations() {
        global $wpdb;
        $table = self::table('property_type_usages');
        return $wpdb->get_results("SELECT property_type_id, usage_id FROM {$table}");
    }
    private static function sync_relations($property_type_id, $relation, $ids) {
        global $wpdb;
        $table = self::table('property_type_' . $relation);
        $key = self::get_relation_key($relation);
        if (!$key) {
            return;
        }

        $wpdb->delete($table, ['property_type_id' => (int)$property_type_id]);
        foreach (array_unique(array_filter(array_map('intval', (array)$ids))) as $id) {
            $wpdb->insert($table, ['property_type_id' => $property_type_id, $key => $id]);
        }
    }
    private static function get_related_ids($property_type_id, $relation) {
        global $wpdb;
        $table = self::table('property_type_' . $relation);
        $key = self::get_relation_key($relation);
        if (!$key) {
            return [];
        }

        return $wpdb->get_col($wpdb->prepare("SELECT {$key} FROM {$table} WHERE property_type_id = %d", $property_type_id));
    }

    private static function get_relation_key($relation) {
        switch ($relation) {
            case 'categories':
                return 'category_id';
            case 'usages':
                return 'usage_id';
            default:
                return '';
        }
    }


    /* === jawda PROPERTY MODELS LOOKUP (AUTO) === */

    public function get_property_models($args = []) {
        global $wpdb;
        $t_models = $wpdb->prefix . 'jawda_property_models';

        $limit = isset($args['limit']) ? (int)$args['limit'] : 20;
        if ($limit <= 0) $limit = 20;
        $offset = isset($args['offset']) ? (int)$args['offset'] : 0;
        if ($offset < 0) $offset = 0;

        $t_pm_cats = $wpdb->prefix . 'jawda_property_model_categories';

        $defaults = [
            'is_active' => null,
            'search' => '',
            'limit' => 500,
            'offset' => 0,
        ];
        $args = array_merge($defaults, is_array($args) ? $args : []);

        $where = "1=1";
        $params = [];

        if ($args['is_active'] !== null) {
            $where .= " AND is_active = %d";
            $params[] = (int)$args['is_active'];
        }
        if (!empty($args['search'])) {
            $where .= " AND (name_ar LIKE %s OR name_en LIKE %s OR slug LIKE %s)";

            if (!empty($args['property_type_id'])) {
                $where .= " AND property_type_id = %d";
                $params[] = (int)$args['property_type_id'];
            }
            if (array_key_exists('sub_property_id', $args) && $args['sub_property_id'] !== '' && $args['sub_property_id'] !== null) {
                $where .= " AND sub_property_id = %d";
                $params[] = (int)$args['sub_property_id'];
            }
            if (isset($args['bedrooms']) && $args['bedrooms'] !== '' && $args['bedrooms'] !== null) {
                $where .= " AND bedrooms = %d";
                $params[] = (int)$args['bedrooms'];
            }
            if (isset($args['is_active']) && $args['is_active'] !== '' && $args['is_active'] !== null) {
                $where .= " AND is_active = %d";
                $params[] = (int)$args['is_active'];
            }

            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $limit = max(1, min(2000, (int)$args['limit']));
        $offset = max(0, (int)$args['offset']);

        $sql = "SELECT * FROM {$t_models} WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        if (!empty($rows)) {
            $ids = array_map(fn($r) => (int)$r['id'], $rows);
            $ids_in = implode(',', array_fill(0, count($ids), '%d'));
            $q = $wpdb->prepare("SELECT property_model_id, category_id FROM {$t_pm_cats} WHERE property_model_id IN ({$ids_in})", ...$ids);
            $pairs = $wpdb->get_results($q, ARRAY_A);
            $map = [];
            foreach ($pairs as $p) {
                $mid = (int)$p['property_model_id'];
                $map[$mid][] = (int)$p['category_id'];
            }
            foreach ($rows as &$r) {
                $r['category_ids'] = $map[(int)$r['id']] ?? [];
            }
        }

        return $rows ?: [];
    }

    public function count_property_models($args = []) {
        global $wpdb;
        $t_models = $wpdb->prefix . 'jawda_property_models';
        $where = " WHERE 1=1 ";
        $params = [];

        $search = isset($args['search']) ? trim((string)$args['search']) : '';
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= " AND (name_ar LIKE %s OR name_en LIKE %s OR slug LIKE %s)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        if (!empty($args['property_type_id'])) {
            $where .= " AND property_type_id = %d";
            $params[] = (int)$args['property_type_id'];
        }
        if (array_key_exists('sub_property_id', $args) && $args['sub_property_id'] !== '' && $args['sub_property_id'] !== null) {
            $where .= " AND sub_property_id = %d";
            $params[] = (int)$args['sub_property_id'];
        }
        if (!empty($args['bedrooms']) && (int)$args['bedrooms'] >= 0) {
            $where .= " AND bedrooms = %d";
            $params[] = (int)$args['bedrooms'];
        }
        if (isset($args['is_active']) && $args['is_active'] !== '' && $args['is_active'] !== null) {
            $where .= " AND is_active = %d";
            $params[] = (int)$args['is_active'];
        }

        $sql = "SELECT COUNT(*) FROM {$t_models} " . $where;
        if ($params) {
            $sql = $wpdb->prepare($sql, ...$params);
        }
        return (int)$wpdb->get_var($sql);
    }


    public function upsert_property_model($data) {
        global $wpdb;
        $t_models = $wpdb->prefix . 'jawda_property_models';

        $data = is_array($data) ? $data : [];
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        $name_ar = isset($data[ 'slug_ar' ]) ? sanitize_text_field($data[ 'slug_ar' ]) : '';
        $name_en = isset($data['name_en']) ? sanitize_text_field($data['name_en']) : '';
        $slug = isset($data['slug']) ? sanitize_title($data['slug']) : '';
        $property_type_id = isset($data['property_type_id']) ? (int)$data['property_type_id'] : 0;
        $sub_property_id = isset($data['sub_property_id']) ? (int)$data['sub_property_id'] : 0;
        $bedrooms = isset($data['bedrooms']) ? (int)$data['bedrooms'] : 0;
        $icon = isset($data['icon']) && $data['icon'] !== '' ? sanitize_text_field($data['icon']) : null;
        $is_active = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

        if ($slug === '') {
            $slug = sanitize_title($name_en ?: $name_ar ?: ('model-' . time()));
        }

        $now = current_time('mysql');

        $row = [
             'slug_ar'  => $name_ar,
            'name_en' => $name_en,
            'slug' => $slug,
            'property_type_id' => $property_type_id,
            'sub_property_id' => $sub_property_id,
            'bedrooms' => max(0, $bedrooms),
            'icon' => $icon,
            'is_active' => $is_active,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $wpdb->update($t_models, $row, ['id' => $id]);
            return $id;
        }

        $row['created_at'] = $now;
        $wpdb->insert($t_models, $row);
        return (int)$wpdb->insert_id;
    }

    public function delete_property_model($id) {
        global $wpdb;
        $id = (int)$id;
        if ($id <= 0) return false;

        $t_models = $wpdb->prefix . 'jawda_property_models';
        $t_pm_cats = $wpdb->prefix . 'jawda_property_model_categories';

        $wpdb->delete($t_pm_cats, ['property_model_id' => $id]);
        $wpdb->delete($t_models, ['id' => $id]);
        return true;
    }

    public function set_property_model_categories($model_id, $category_ids) {
        global $wpdb;
        $model_id = (int)$model_id;
        $t_pm_cats = $wpdb->prefix . 'jawda_property_model_categories';

        $category_ids = is_array($category_ids) ? $category_ids : [];
        $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids), fn($v)=>$v>0)));

        $wpdb->delete($t_pm_cats, ['property_model_id' => $model_id]);

        foreach ($category_ids as $cid) {
            $wpdb->insert($t_pm_cats, [
                'property_model_id' => $model_id,
                'category_id' => $cid,
            ]);
        }
        return true;
    }

    /* === END jawda PROPERTY MODELS LOOKUP (AUTO) === */

}
