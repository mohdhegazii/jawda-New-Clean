<?php
/**
 * Developers Service - manages CRUD and retrieval for the custom developers table.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Developers_Service {
    /**
     * @var string
     */
    protected $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'jawda_developers';
    }

    /**
     * Create a developer record.
     */
    public function create_developer(array $data) {
        $prepared = $this->prepare_developer_data($data);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        global $wpdb;
        $inserted = $wpdb->insert($this->table, $prepared, $this->get_format_for_data($prepared));
        if (false === $inserted) {
            return new WP_Error('jawda_developer_insert_failed', __('Unable to create developer.', 'jawda'));
        }

        $id = (int) $wpdb->insert_id;
        $this->prime_cache($id, $prepared);

        return $id;
    }

    /**
     * Update a developer record.
     */
    public function update_developer($id, array $data) {
        $id = (int) $id;
        if ($id <= 0) {
            return new WP_Error('jawda_developer_invalid_id', __('Invalid developer ID.', 'jawda'));
        }

        $existing = $this->get_developer_by_id($id);
        if (!$existing) {
            return new WP_Error('jawda_developer_not_found', __('Developer not found.', 'jawda'));
        }

        $prepared = $this->prepare_developer_data($data, $id, $existing);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        global $wpdb;
        $updated = $wpdb->update($this->table, $prepared, ['id' => $id], $this->get_format_for_data($prepared), ['%d']);
        if (false === $updated) {
            return new WP_Error('jawda_developer_update_failed', __('Unable to update developer.', 'jawda'));
        }

        $this->prime_cache($id, array_merge($existing, $prepared));

        return true;
    }

    /**
     * Soft delete (deactivate) a developer.
     */
    public function delete_developer($id) {
        $id = (int) $id;
        if ($id <= 0) {
            return new WP_Error('jawda_developer_invalid_id', __('Invalid developer ID.', 'jawda'));
        }

        $data = [
            'is_active'  => 0,
            'updated_at' => current_time('mysql'),
        ];

        global $wpdb;
        $updated = $wpdb->update($this->table, $data, ['id' => $id], ['%d', '%s'], ['%d']);
        if (false === $updated) {
            return new WP_Error('jawda_developer_delete_failed', __('Unable to deactivate developer.', 'jawda'));
        }

        wp_cache_delete($this->cache_key($id), 'jawda_developers');

        return true;
    }

    /**
     * Fetch developer by ID.
     */
    public function get_developer_by_id($id) {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $cache_key = $this->cache_key($id);
        $cached = wp_cache_get($cache_key, 'jawda_developers');
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $developer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        if ($developer) {
            wp_cache_set($cache_key, $developer, 'jawda_developers');
        }

        return $developer ?: null;
    }

    public function get_developer_by_slug_en($slug) {
        return $this->get_developer_by_slug($slug, 'slug_en');
    }

    public function get_developer_by_slug_ar($slug) {
        return $this->get_developer_by_slug($slug, 'slug_ar');
    }

    /**
     * List developers with optional filters.
     */
    public function get_developers($args = []) {
        global $wpdb;

        $defaults = [
            'is_active'         => null,
            'developer_type_id' => null,
            'search'            => '',
            'number'            => 50,
            'offset'            => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $where   = [];
        $params  = [];

        if (!is_null($args['is_active'])) {
            $where[] = 'is_active = %d';
            $params[] = (int) $args['is_active'];
        }

        if (!empty($args['developer_type_id'])) {
            $where[] = 'developer_type_id = %d';
            $params[] = (int) $args['developer_type_id'];
        }

        if (!empty($args['search'])) {
            $where[] = '(name_en LIKE %s OR name_ar LIKE %s)';
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', (int) $args['number'], (int) $args['offset']);

        $query = "SELECT * FROM {$this->table} {$where_sql} ORDER BY id DESC {$limit_sql}";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Prepare and validate data.
     */
    protected function prepare_developer_data(array $data, $existing_id = null, $existing = []) {
        $name_en = isset($data['name_en']) ? sanitize_text_field($data['name_en']) : ($existing['name_en'] ?? '');
        $name_ar = isset($data[ 'slug_ar' ]) ? sanitize_text_field($data[ 'slug_ar' ]) : ($existing[ 'slug_ar' ] ?? '');

        if ('' === $name_en || '' === $name_ar) {
            return new WP_Error('jawda_developer_missing_name', __('Developer names (EN/AR) are required.', 'jawda'));
        }

        $slug_en = isset($data['slug_en']) ? sanitize_title($data['slug_en']) : ($existing['slug_en'] ?? '');
        $slug_ar = isset($data['slug_ar']) ? $this->slugify_ar($data['slug_ar']) : ($existing['slug_ar'] ?? '');

        if ('' === $slug_en) {
            $slug_en = $this->slugify_en($name_en);
        }
        if ('' === $slug_ar) {
            $slug_ar = $this->slugify_ar($name_ar);
        }

        $slug_en = $this->ensure_unique_slug($slug_en, 'slug_en', $existing_id);
        $slug_ar = $this->ensure_unique_slug($slug_ar, 'slug_ar', $existing_id);

        $developer_type_id = isset($data['developer_type_id']) ? (int) $data['developer_type_id'] : ($existing['developer_type_id'] ?? null);
        if (!empty($developer_type_id) && !$this->is_valid_developer_type($developer_type_id)) {
            return new WP_Error('jawda_developer_invalid_type', __('Invalid developer type.', 'jawda'));
        }

        $prepared = [
            'name_en'           => $name_en,
             'slug_ar'            => $name_ar,
            'slug_en'           => $slug_en,
            'slug_ar'           => $slug_ar,
            'developer_type_id' => $developer_type_id ?: null,
            'logo_id'           => isset($data['logo_id']) ? (int) $data['logo_id'] : ($existing['logo_id'] ?? null),
            'description_en'    => isset($data['description_en']) ? wp_kses_post($data['description_en']) : ($existing['description_en'] ?? null),
            'description_ar'    => isset($data['description_ar']) ? wp_kses_post($data['description_ar']) : ($existing['description_ar'] ?? null),
            'seo_title_en'      => isset($data['seo_title_en']) ? sanitize_text_field($data['seo_title_en']) : ($existing['seo_title_en'] ?? null),
            'seo_title_ar'      => isset($data['seo_title_ar']) ? sanitize_text_field($data['seo_title_ar']) : ($existing['seo_title_ar'] ?? null),
            'seo_desc_en'       => isset($data['seo_desc_en']) ? sanitize_textarea_field($data['seo_desc_en']) : ($existing['seo_desc_en'] ?? null),
            'seo_desc_ar'       => isset($data['seo_desc_ar']) ? sanitize_textarea_field($data['seo_desc_ar']) : ($existing['seo_desc_ar'] ?? null),
            'is_active'         => isset($data['is_active']) ? (int) $data['is_active'] : ($existing['is_active'] ?? 1),
            'updated_at'        => current_time('mysql'),
        ];

        if (null === $existing_id) {
            $prepared['created_at'] = current_time('mysql');
        }

        return $prepared;
    }

    protected function ensure_unique_slug($slug, $column, $exclude_id = null) {
        global $wpdb;
        $base_slug = $slug ? $slug : uniqid('developer-');
        $counter   = 1;
        $candidate = $base_slug;

        while ($this->slug_exists($candidate, $column, $exclude_id)) {
            $counter++;
            $candidate = $base_slug . '-' . $counter;
        }

        return $candidate;
    }

    protected function slug_exists($slug, $column, $exclude_id = null) {
        global $wpdb;

        $column = $this->resolve_slug_column($column);
        $query = "SELECT id FROM {$this->table} WHERE {$column} = %s";
        $params = [$slug];
        if ($exclude_id) {
            $query .= ' AND id != %d';
            $params[] = (int) $exclude_id;
        }

        return (bool) $wpdb->get_var($wpdb->prepare($query, $params));
    }

    protected function slugify_en($value) {
        return sanitize_title($value);
    }

    protected function slugify_ar($value) {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', '-', $value);
        $value = preg_replace('/[^\p{Arabic}A-Za-z0-9\-]+/u', '', $value);
        $value = strtolower($value);
        return trim($value, '-');
    }

    protected function is_valid_developer_type($id) {
        if (function_exists('jawda_get_developer_types')) {
            $types = jawda_get_developer_types();
            foreach ($types as $type) {
                if ((int) ($type['id'] ?? 0) === (int) $id) {
                    return true;
                }
            }
            if (!empty($types)) {
                return apply_filters('jawda_validate_developer_type', false, $id, $types);
            }
        }

        return apply_filters('jawda_validate_developer_type', true, $id, []);
    }

    protected function get_format_for_data(array $data) {
        $map = [
            'name_en'           => '%s',
             'slug_ar'            => '%s',
            'slug_en'           => '%s',
            'slug_ar'           => '%s',
            'developer_type_id' => '%d',
            'logo_id'           => '%d',
            'description_en'    => '%s',
            'description_ar'    => '%s',
            'seo_title_en'      => '%s',
            'seo_title_ar'      => '%s',
            'seo_desc_en'       => '%s',
            'seo_desc_ar'       => '%s',
            'is_active'         => '%d',
            'created_at'        => '%s',
            'updated_at'        => '%s',
        ];

        $format = [];
        foreach (array_keys($data) as $key) {
            $format[] = $map[$key] ?? '%s';
        }

        return $format;
    }

    protected function get_developer_by_slug($slug, $column) {
        $slug = rawurldecode((string) $slug);
        $slug = trim($slug);
        if ('' === $slug) {
            return null;
        }

        $column = $this->resolve_slug_column($column);
        $cache_key = $this->cache_key($column . ':' . $slug);
        $cached = wp_cache_get($cache_key, 'jawda_developers');
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $developer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE {$column} = %s", $slug), ARRAY_A);
        if ($developer) {
            wp_cache_set($cache_key, $developer, 'jawda_developers');
        }

        return $developer ?: null;
    }

    protected function resolve_slug_column($column) {
        if (!in_array($column, ['slug_en', 'slug_ar'], true)) {
            return $column;
        }

        global $wpdb;
        static $available_columns = null;
        if (null === $available_columns) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$this->table}");
            $available_columns = $columns ? array_map('strval', $columns) : [];
        }

        if (in_array($column, $available_columns, true)) {
            return $column;
        }

        return in_array('slug', $available_columns, true) ? 'slug' : $column;
    }

    protected function prime_cache($id, array $developer) {
        $developer['id'] = $id;
        wp_cache_set($this->cache_key($id), $developer, 'jawda_developers');
        if (!empty($developer['slug_en'])) {
            wp_cache_set($this->cache_key('slug_en:' . $developer['slug_en']), $developer, 'jawda_developers');
        }
        if (!empty($developer['slug_ar'])) {
            wp_cache_set($this->cache_key('slug_ar:' . $developer['slug_ar']), $developer, 'jawda_developers');
        }
    }

    protected function cache_key($id) {
        return 'developer_' . $id;
    }
}
