<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class Jawda_Districts_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'District',
            'plural'   => 'Districts',
            'ajax'     => false
        ]);
    }
    public function get_columns() {
        return [
            'cb'      => '<input type="checkbox" />',
            'name_ar' => 'Name (Arabic)',
            'name_en' => 'Name (English)',
            'city'    => 'City',
            'latitude'=> 'Latitude',
            'longitude'=> 'Longitude',
            'date'    => 'Date',
        ];
    }
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_districts';
        $city_table = $wpdb->prefix . 'jawda_cities';
        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        $current_page = $this->get_pagenum();
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
                "SELECT d.*, c.name_ar as city_name_ar FROM $table_name d
                 LEFT JOIN $city_table c ON d.city_id = c.id
                 WHERE d.is_deleted = 0
                 ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }
    protected function get_sortable_columns() {
        return [
            'name_ar' => ['name_ar', false],
            'name_en' => ['name_en', false],
            'city'    => ['city_id', false],
            'latitude'=> ['latitude', false],
            'longitude'=> ['longitude', false],
            'date'    => ['created_at', false],
        ];
    }
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name_ar':
            case 'name_en':
                return $item[$column_name];
            case 'city':
                return $item['city_name_ar'];
            case 'date':
                return $item['created_at'];
            case 'latitude':
            case 'longitude':
                return $item[$column_name];
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
      return sprintf('%1$s %2$s', $item['name_ar'], $this->row_actions($actions) );
    }

    protected function get_bulk_actions() {
        return ['delete' => 'Delete'];
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : [];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            if (empty($ids)) return;
            $this->delete_districts($ids);
        }
    }

    public function render_page() {
        echo '<div class="wrap"><h2>Districts</h2>';
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
        global $wpdb;
        $city_table = $wpdb->prefix . 'jawda_cities';
        $cities = $wpdb->get_results("SELECT * FROM $city_table WHERE is_deleted = 0");
        ?>
        <div class="form-wrap">
            <h3>Add New District</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_district">
                <div class="form-field">
                    <label for="name_ar">Name (Arabic)</label>
                    <input type="text" name="name_ar" id="name_ar" required>
                </div>
                <div class="form-field">
                    <label for="name_en">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" required>
                </div>
                <div class="form-field">
                    <label for="city_id">City</label>
                    <select name="city_id" id="city_id" required>
            <option value="">-- Select City --</option>
            <?php foreach ($cities as $row) : ?>
                <option value="<?php echo $row->id; ?>" 
                        data-lat="<?php echo esc_attr($row->latitude); ?>" 
                        data-lng="<?php echo esc_attr($row->longitude); ?>" 
                        <?php echo (isset($item) && $item["city_id"] == $row->id) ? "selected" : ""; ?>>
                    <?php echo (is_object($row) ? $row->name_ar : $row["name_ar"]); ?>
                </option>
            <?php endforeach; ?>
        </select>
                </div>
                <?php
                jawda_locations_render_coordinate_fields([
                    'lat_id' => 'district_latitude_add',
                    'lng_id' => 'district_longitude_add',
                    'map_id' => 'district-map-add',
                ]);
                submit_button('Add District');
                ?>
            </form>
        </div>
        <?php
    }

    private function render_edit_form() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_districts';
        $city_table = $wpdb->prefix . 'jawda_cities';
        $id = (int)$_GET['id'];
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
        $cities = $wpdb->get_results("SELECT * FROM $city_table WHERE is_deleted = 0");
        ?>
        <div class="form-wrap">
            <h3>Edit District</h3>
            <form method="post">
                <input type="hidden" name="action" value="edit_district">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <div class="form-field">
                    <label for="name_ar">Name (Arabic)</label>
                    <input type="text" name="name_ar" id="name_ar" value="<?php echo esc_attr($item['name_ar']); ?>" required>
                </div>
                <div class="form-field">
                    <label for="name_en">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($item['name_en']); ?>" required>
                </div>
                <div class="form-field">
                    <label for="city__id">City</label>
                    <select name="city_id" id="city_id" required>
            <option value="">-- Select City --</option>
            <?php foreach ($cities as $row) : ?>
                <option value="<?php echo $row->id; ?>" 
                        data-lat="<?php echo esc_attr($row->latitude); ?>" 
                        data-lng="<?php echo esc_attr($row->longitude); ?>" 
                        <?php echo (isset($item) && $item["city_id"] == $row->id) ? "selected" : ""; ?>>
                    <?php echo (is_object($row) ? $row->name_ar : $row["name_ar"]); ?>
                </option>
            <?php endforeach; ?>
        </select>
                </div>
                <?php
                jawda_locations_render_coordinate_fields([
                    'lat_id'    => 'district_latitude_edit',
                    'lat_value' => isset($item['latitude']) ? $item['latitude'] : '',
                    'lng_id'    => 'district_longitude_edit',
                    'lng_value' => isset($item['longitude']) ? $item['longitude'] : '',
                    'map_id'    => 'district-map-edit',
                ]);
                submit_button('Update District');
                ?>
            </form>
        </div>
        <?php
    }

    public function handle_form_submission() {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_district':
                    $this->add_district();
                    break;
                case 'edit_district':
                    $this->edit_district();
                    break;
            }
        }
    }

    private function add_district() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_districts';
        $name_ar = sanitize_text_field($_POST['name_ar']);
        $name_en = sanitize_text_field($_POST['name_en']);
        $slug = sanitize_title($name_en);
        $slug_ar = sanitize_title($name_ar);
        if (empty($slug)) $slug = 'dist-' . uniqid();
        if (empty($slug_ar)) $slug_ar = 'ar-' . uniqid();

        $city_id = (int)$_POST['city_id'];
        $latitude = jawda_locations_normalize_coordinate($_POST['latitude'] ?? null);
        $longitude = jawda_locations_normalize_coordinate($_POST['longitude'] ?? null);

        $wpdb->insert($table_name, [
            'name_ar'   => $name_ar,
            'name_en'   => $name_en,
            'slug'      => $slug,
            'slug_ar'   => $slug_ar,
            'city_id'   => $city_id,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'created_at'=> current_time('mysql'),
        ]);

        wp_cache_delete('jawda_districts_city_' . $city_id, 'jawda_locations');
    }

    private function edit_district() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_districts';
        $id = (int)$_POST['id'];
        $name_ar = sanitize_text_field($_POST['name_ar']);
        $name_en = sanitize_text_field($_POST['name_en']);
        $city_id = (int)$_POST['city_id'];
        $latitude = jawda_locations_normalize_coordinate($_POST['latitude'] ?? null);
        $longitude = jawda_locations_normalize_coordinate($_POST['longitude'] ?? null);

        $slug = sanitize_title($name_en);
        $slug_ar = sanitize_title($name_ar);

        // Get old city to clear cache
        $old_city = $wpdb->get_var($wpdb->prepare("SELECT city_id FROM $table_name WHERE id = %d", $id));

        $data = [
            'name_ar'   => $name_ar,
            'name_en'   => $name_en,
            'city_id'   => $city_id,
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ];
        if (!empty($slug)) $data['slug'] = $slug;
        if (!empty($slug_ar)) $data['slug_ar'] = $slug_ar;

        $wpdb->update(
            $table_name,
            $data,
            ['id' => $id]
        );

        wp_cache_delete('jawda_district_' . $id, 'jawda_locations');
        wp_cache_delete('jawda_districts_city_' . $city_id, 'jawda_locations');
        if ($old_city && $old_city != $city_id) {
            wp_cache_delete('jawda_districts_city_' . $old_city, 'jawda_locations');
        }
    }

    private function delete_districts($ids) {
        // Use Jawda_Location_Service::soft_delete_location to handle soft delete and dependency checks
        if (!class_exists('Jawda_Location_Service')) {
            require_once get_template_directory() . '/app/inc/locations/class-jawda-location-service.php';
        }

        foreach ($ids as $id) {
            $result = Jawda_Location_Service::soft_delete_location('district', $id);
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            }
        }
    }
}
