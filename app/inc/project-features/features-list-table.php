<?php
/**
 * List table for project features.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Jawda_Project_Features_List_Table extends WP_List_Table {
    private $current_type = '';
    private $current_context = '';
    private $forced_type = '';
    private $base_page_slug = 'jawda-project-features';
    private $allowed_types = [];

    public function set_base_page($slug) {
        if (is_string($slug) && $slug !== '') {
            $this->base_page_slug = $slug;
        }
    }

    public function set_forced_type($type) {
        if (is_string($type) && $type !== '') {
            $this->forced_type = $type;
        }
    }

    public function set_allowed_types($types) {
        if (is_array($types)) {
            $sanitized = array_filter(array_map('sanitize_key', $types));
            $this->allowed_types = array_values(array_unique($sanitized));
        }
    }

    private function get_base_page_slug() {
        return $this->base_page_slug !== '' ? $this->base_page_slug : 'jawda-project-features';
    }

    public function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
             'slug_ar'  => __('Name (Arabic)', 'jawda'),
            'name_en' => __('Name (English)', 'jawda'),
        ];

        if ($this->forced_type === 'marketing_orientation') {
            $columns['orientation'] = __('Orientation', 'jawda');
            $columns['facade']      = __('Facade / Position', 'jawda');
        }

        $columns['feature_type'] = __('Type', 'jawda');
        $columns['contexts']     = __('Available For', 'jawda');
        $columns['image']        = __('Image', 'jawda');
        $columns['updated']      = __('Last Updated', 'jawda');

        return $columns;
    }

    protected function get_sortable_columns() {
        return [
             'slug_ar'  => [ 'slug_ar' , true],
            'name_en' => ['name_en', true],
            'feature_type' => ['feature_type', true],
            'updated' => ['updated_at', true],
        ];
    }

    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="feature_ids[]" value="%d" />',
            (int) $item['id']
        );
    }

    protected function column_name_ar($item) {
        $page_slug = $this->get_base_page_slug();

        $edit_url = add_query_arg(
            [
                'page'   => $page_slug,
                'action' => 'edit',
                'id'     => (int) $item['id'],
            ],
            admin_url('admin.php')
        );

        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                esc_html__('Edit', 'jawda')
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url(wp_nonce_url(add_query_arg([
                    'page'   => $page_slug,
                    'action' => 'delete',
                    'id'     => (int) $item['id'],
                ], admin_url('admin.php')), 'jawda_delete_project_feature_' . (int) $item['id'])),
                esc_attr__('Are you sure you want to delete this feature?', 'jawda'),
                esc_html__('Delete', 'jawda')
            ),
        ];

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong> %s',
            esc_url($edit_url),
            esc_html($item[ 'slug_ar' ]),
            $this->row_actions($actions)
        );
    }

    protected function column_name_en($item) {
        return esc_html($item['name_en']);
    }

    protected function column_feature_type($item) {
        if (function_exists('jawda_project_features_get_feature_types')) {
            $types = jawda_project_features_get_feature_types();
            $type_key = isset($item['feature_type']) ? (string) $item['feature_type'] : 'feature';
            if (isset($types[$type_key])) {
                return esc_html($types[$type_key]);
            }
        }

        return esc_html($item['feature_type']);
    }

    protected function column_orientation($item) {
        if (empty($item['orientation_id'])) {
            return '&mdash;';
        }

        if (function_exists('jawda_project_features_get_feature_label')) {
            $label = jawda_project_features_get_feature_label($item['orientation_id']);
            if ($label !== '') {
                return esc_html($label);
            }
        }

        return esc_html((string) $item['orientation_id']);
    }

    protected function column_facade($item) {
        if (empty($item['facade_id'])) {
            return '&mdash;';
        }

        if (function_exists('jawda_project_features_get_feature_label')) {
            $label = jawda_project_features_get_feature_label($item['facade_id']);
            if ($label !== '') {
                return esc_html($label);
            }
        }

        return esc_html((string) $item['facade_id']);
    }

    protected function column_contexts($item) {
        if (!function_exists('jawda_project_features_get_context_labels')) {
            return esc_html__('—', 'jawda');
        }

        $labels = jawda_project_features_get_context_labels();
        $values = [];

        if (!empty($item['context_projects'])) {
            $values[] = isset($labels['projects']) ? $labels['projects'] : __('Projects', 'jawda');
        }

        if (!empty($item['context_properties'])) {
            $values[] = isset($labels['properties']) ? $labels['properties'] : __('Units', 'jawda');
        }

        if (!$values) {
            return esc_html__('None', 'jawda');
        }

        return esc_html(implode(', ', $values));
    }

    protected function column_image($item) {
        if (!empty($item['image_id'])) {
            return wp_get_attachment_image((int) $item['image_id'], 'thumbnail');
        }

        return '&mdash;';
    }

    protected function column_updated($item) {
        if (empty($item['updated_at'])) {
            return '&mdash;';
        }

        $timestamp = strtotime($item['updated_at']);

        if (!$timestamp) {
            return esc_html($item['updated_at']);
        }

        return esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp));
    }

    public function get_bulk_actions() {
        return [
            'bulk-delete' => __('Delete', 'jawda'),
        ];
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = $this->get_items_per_page('jawda_project_features_per_page', 20);
        $current_page = $this->get_pagenum();

        $this->current_type = isset($_REQUEST['feature_type']) ? sanitize_key(wp_unslash($_REQUEST['feature_type'])) : '';
        $this->current_context = isset($_REQUEST['feature_context']) ? sanitize_key(wp_unslash($_REQUEST['feature_context'])) : '';

        $type_choices = function_exists('jawda_project_features_get_feature_types') ? jawda_project_features_get_feature_types() : [];

        if (!empty($this->allowed_types)) {
            $type_choices = array_intersect_key($type_choices, array_fill_keys($this->allowed_types, true));
        }

        if ($this->current_type !== '' && !array_key_exists($this->current_type, $type_choices)) {
            $this->current_type = '';
        }

        if ($this->forced_type !== '') {
            $this->current_type = $this->forced_type;
        }

        $allowed_contexts = ['projects', 'properties', 'both'];
        if (!in_array($this->current_context, $allowed_contexts, true)) {
            $this->current_context = '';
        }

        $data = $this->fetch_features([
            'feature_type' => $this->current_type,
            'context'      => $this->current_context,
        ]);

        $total_items = count($data);
        $this->items = array_slice($data, ($current_page - 1) * $per_page, $per_page);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    protected function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }

        echo '<div class="alignleft actions">';

        if ($this->forced_type === '') {
            if (function_exists('jawda_project_features_get_feature_types')) {
                $types = jawda_project_features_get_feature_types();
                if (!empty($this->allowed_types)) {
                    $types = array_intersect_key($types, array_fill_keys($this->allowed_types, true));
                }
                printf('<label class="screen-reader-text" for="filter-by-feature-type">%s</label>', esc_html__('Filter by type', 'jawda'));
                echo '<select name="feature_type" id="filter-by-feature-type">';
                echo '<option value="">' . esc_html__('All types', 'jawda') . '</option>';
                foreach ($types as $type_key => $type_label) {
                    printf('<option value="%s" %s>%s</option>', esc_attr($type_key), selected($this->current_type, $type_key, false), esc_html($type_label));
                }
                echo '</select>';
            }
        } else {
            printf('<input type="hidden" name="feature_type" value="%s" />', esc_attr($this->forced_type));
        }

        $context_options = [];
        if (function_exists('jawda_project_features_get_context_labels')) {
            $context_options = jawda_project_features_get_context_labels();
        }

        if ($context_options) {
            printf('<label class="screen-reader-text" for="filter-by-feature-context">%s</label>', esc_html__('Filter by availability', 'jawda'));
            echo '<select name="feature_context" id="filter-by-feature-context">';
            echo '<option value="">' . esc_html__('All placements', 'jawda') . '</option>';
            foreach (['projects', 'properties', 'both'] as $context_key) {
                $label = isset($context_options[$context_key]) ? $context_options[$context_key] : $context_key;
                printf('<option value="%s" %s>%s</option>', esc_attr($context_key), selected($this->current_context, $context_key, false), esc_html($label));
            }
            echo '</select>';
        }

        submit_button(__('Filter'), '', 'filter_action', false);
        echo '</div>';
    }

    private function fetch_features($filters = []) {
        global $wpdb;
        $table = jawda_project_features_table();

        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) :  'slug_ar' ;
        $order   = isset($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'asc';

        $allowed_orderby = [ 'slug_ar' , 'name_en', 'feature_type', 'updated_at'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby =  'slug_ar' ;
        }

        $order = $order === 'desc' ? 'DESC' : 'ASC';

        $where   = [];
        $prepare = [];

        if (!empty($filters['feature_type'])) {
            $where[] = 'feature_type = %s';
            $prepare[] = $filters['feature_type'];
        } elseif ($this->forced_type === '' && !empty($this->allowed_types)) {
            $placeholders = implode(', ', array_fill(0, count($this->allowed_types), '%s'));
            $where[] = "feature_type IN ({$placeholders})";
            $prepare = array_merge($prepare, $this->allowed_types);
        }

        if (!empty($filters['context'])) {
            if ($filters['context'] === 'projects') {
                $where[] = 'context_projects = 1';
            } elseif ($filters['context'] === 'properties') {
                $where[] = 'context_properties = 1';
            } elseif ($filters['context'] === 'both') {
                $where[] = 'context_projects = 1 AND context_properties = 1';
            }
        }

        $where_sql = '';

        if ($where) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }

        $query = "SELECT id, name_ar, name_en, image_id, feature_type, context_projects, context_properties, orientation_id, facade_id, updated_at FROM {$table} {$where_sql} ORDER BY {$orderby} {$order}";

        if ($prepare) {
            $query = $wpdb->prepare($query, $prepare);
        }

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    public function process_bulk_action() {
        if ($this->current_action() === 'bulk-delete') {
            $ids = isset($_POST['feature_ids']) ? array_map('intval', (array) $_POST['feature_ids']) : [];
            foreach ($ids as $id) {
                jawda_project_features_delete($id);
            }
            wp_redirect(admin_url('admin.php?page=' . $this->get_base_page_slug()));
            exit;
        }
    }
}
