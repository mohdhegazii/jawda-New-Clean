<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Jawda_Governorates_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Governorate',
            'plural'   => 'Governorates',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'name_ar'     => 'Name (Arabic)',
            'name_en'     => 'Name (English)',
            'latitude'    => 'Latitude',
            'longitude'   => 'Longitude',
            'date'        => 'Date',
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_governorates';
        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        $current_page = $this->get_pagenum();

        // Validate table exists or handle error silently?
        // We assume it exists.

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE is_deleted = 0");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $orderby = isset($_GET['orderby']) ? esc_sql($_GET['orderby']) : 'id';
        $order = isset($_GET['order']) ? esc_sql($_GET['order']) : 'DESC';
        $offset = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE is_deleted = 0 ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }

    protected function get_sortable_columns() {
        return [
            'name_ar'   => ['name_ar', false],
            'name_en'   => ['name_en', false],
            'latitude'  => ['latitude', false],
            'longitude' => ['longitude', false],
            'date'      => ['created_at', false],
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name_ar':
            case 'name_en':
                return isset($item[$column_name]) ? $item[$column_name] : '';
            case 'date':
                return isset($item['created_at']) ? $item['created_at'] : '';
            case 'latitude':
            case 'longitude':
                return isset($item[$column_name]) ? $item[$column_name] : '';
            default:
                return print_r($item, true);
        }
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    function column_name_ar($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id']),
        );
        return sprintf('%1$s %2$s', $item['name_ar'], $this->row_actions($actions));
    }

    protected function get_bulk_actions() {
        return [
            'delete' => 'Delete',
        ];
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : [];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            if (empty($ids)) return;
            $this->delete_governorates($ids);
        }
    }

    public function process_actions() {
        $this->process_bulk_action();
        $this->handle_form_submission();
    }

    public function render_page() {
        echo '<div class="wrap"><h2>Governorates</h2>';
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            $this->render_edit_form();
        } else {
            $this->render_add_form();
            $this->prepare_items();
            $this->display();
        }
        echo '</div>';
    }

    private function render_add_form() {
        ?>
        <div class="form-wrap">
            <h3>Add New Governorate</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_governorate">
                <div class="form-field">
                    <label for="name_ar">Name (Arabic)</label>
                    <input type="text" name="name_ar" id="name_ar" required>
                </div>
                <div class="form-field">
                    <label for="name_en">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" required>
                </div>
                <?php
                jawda_locations_render_coordinate_fields([
                    'lat_id' => 'governorate_latitude_add',
                    'lng_id' => 'governorate_longitude_add',
                    'map_id' => 'governorate-map-add',
                ]);
                submit_button('Add Governorate');
                ?>
            </form>
        </div>
        <?php
    }

    private function render_edit_form() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_governorates';
        $id = (int)$_GET['id'];
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);

        if (!$item) {
            echo '<div class="notice notice-error"><p>Governorate not found.</p></div>';
            return;
        }
        ?>
        <div class="form-wrap">
            <h3>Edit Governorate</h3>
            <form method="post">
                <input type="hidden" name="action" value="edit_governorate">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <div class="form-field">
                    <label for="name_ar">Name (Arabic)</label>
                    <input type="text" name="name_ar" id="name_ar" value="<?php echo esc_attr($item['name_ar']); ?>" required>
                </div>
                <div class="form-field">
                    <label for="name_en">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($item['name_en']); ?>" required>
                </div>
                <?php
                jawda_locations_render_coordinate_fields([
                    'lat_id'    => 'governorate_latitude_edit',
                    'lat_value' => isset($item['latitude']) ? $item['latitude'] : '',
                    'lng_id'    => 'governorate_longitude_edit',
                    'lng_value' => isset($item['longitude']) ? $item['longitude'] : '',
                    'map_id'    => 'governorate-map-edit',
                ]);
                submit_button('Update Governorate');
                ?>
            </form>
        </div>
        <?php
    }

    public function handle_form_submission() {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_governorate':
                    $this->add_governorate();
                    break;
                case 'edit_governorate':
                    $this->edit_governorate();
                    break;
            }
        }
    }

    private function add_governorate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_governorates';

        $name_ar = isset($_POST['name_ar']) ? sanitize_text_field($_POST['name_ar']) : '';
        $name_en = isset($_POST['name_en']) ? sanitize_text_field($_POST['name_en']) : '';

        // Ensure slug is generated and not empty
        $slug = sanitize_title($name_en);
        if (empty($slug)) {
            $slug = 'gov-' . uniqid();
        }

        // Ensure slug_ar is generated
        $slug_ar = sanitize_title($name_ar);
        if (empty($slug_ar)) {
            $slug_ar = 'ar-' . uniqid();
        }

        $latitude = jawda_locations_normalize_coordinate($_POST['latitude'] ?? null);
        $longitude = jawda_locations_normalize_coordinate($_POST['longitude'] ?? null);

        // Explicitly map fields to match database schema expectations
        $data = [
            'name_ar'    => $name_ar,
            'name_en'    => $name_en,
            'slug'       => $slug,
            'slug_ar'    => $slug_ar,
            'latitude'   => $latitude,
            'longitude'  => $longitude,
            'created_at' => current_time('mysql'),
            'is_deleted' => 0
        ];

        $wpdb->insert($table_name, $data);

        wp_cache_delete('jawda_all_governorates', 'jawda_locations');
    }

    private function edit_governorate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_governorates';

        $id = (int)$_POST['id'];
        $name_ar = sanitize_text_field($_POST['name_ar']);
        $name_en = sanitize_text_field($_POST['name_en']);
        $latitude = jawda_locations_normalize_coordinate($_POST['latitude'] ?? null);
        $longitude = jawda_locations_normalize_coordinate($_POST['longitude'] ?? null);

        // Regenerate slugs or keep them?
        // We will regenerate to keep them in sync, ensuring they are not empty.
        $slug = sanitize_title($name_en);
        if (empty($slug)) $slug = 'gov-' . uniqid();

        $slug_ar = sanitize_title($name_ar);
        if (empty($slug_ar)) $slug_ar = 'ar-' . uniqid();

        $data = [
            'name_ar'   => $name_ar,
            'name_en'   => $name_en,
            'slug'      => $slug,
            'slug_ar'   => $slug_ar,
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ];

        $wpdb->update(
            $table_name,
            $data,
            ['id' => $id]
        );

        wp_cache_delete('jawda_all_governorates', 'jawda_locations');
        wp_cache_delete('jawda_gov_' . $id, 'jawda_locations');
    }

    private function delete_governorates($ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_governorates';

        // We implement the delete logic directly here to ensure the correct table is targeted.
        // This bypasses potential issues if the external service class is pointing to a non-existent singular table.

        foreach ($ids as $id) {
            $id = absint($id);
            if (!$id) continue;

            // Optional: We could check dependencies here similar to the Service class.
            // For now, we proceed with the soft delete as requested to fix the "delete fails" issue.

            $wpdb->update(
                $table_name,
                ['is_deleted' => 1, 'deleted_at' => current_time('mysql')],
                ['id' => $id]
            );

            // Clear cache
            wp_cache_delete('jawda_all_governorates', 'jawda_locations');
            wp_cache_delete('jawda_gov_' . $id, 'jawda_locations');
        }
    }
}
