<?php
if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Catalog_SEO_Admin_Page {
    private $table;
    private $form_state = [];

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'jawda_catalog_seo_overrides';
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu() {
        add_submenu_page(
            'jawda-lookups',
            __('Catalog SEO', 'jawda'),
            __('Catalog SEO', 'jawda'),
            'manage_options',
            'jawda-catalog-seo',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'jawda-lookups_page_jawda-catalog-seo') {
            return;
        }
        wp_enqueue_media();
        if (function_exists('wp_enqueue_editor')) {
            wp_enqueue_editor();
        }
        wp_enqueue_script('jawda-catalog-seo-admin', get_template_directory_uri() . '/assets/js/catalog-seo-admin.js', ['jquery'], '1.0', true);
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';

        if ($action === 'edit' || $action === 'add') {
            $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
            $this->render_form($id);
            return;
        }

        $this->maybe_handle_delete();
        $this->render_list();
    }

    private function render_list() {
        global $wpdb;
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $level_filter = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';

        $where = ' WHERE 1=1 ';
        $params = [];
        if ($type_filter) {
            $where .= ' AND type = %s';
            $params[] = $type_filter;
        }
        if ($level_filter) {
            $where .= ' AND location_level = %s';
            $params[] = $level_filter;
        }

        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY id DESC";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Catalog SEO List', 'jawda') . '</h1> ';
        echo '<a href="?page=jawda-catalog-seo&action=add" class="page-title-action">' . esc_html__('Add New', 'jawda') . '</a>';

        if (!empty($_GET['message'])) {
            echo '<div class="notice notice-success"><p>' . esc_html($_GET['message']) . '</p></div>';
        }

        echo '<form method="get" class="tablenav">';
        echo '<input type="hidden" name="page" value="jawda-catalog-seo" />';
        echo '<select name="type">';
        echo '<option value="">' . esc_html__('All Types', 'jawda') . '</option>';
        echo '<option value="projects"' . selected($type_filter, 'projects', false) . '>' . esc_html__('Projects', 'jawda') . '</option>';
        echo '</select> ';
        echo '<select name="level">';
        echo '<option value="">' . esc_html__('All Levels', 'jawda') . '</option>';
        $levels = [
            'country'      => __('Country', 'jawda'),
            'governorate'  => __('Governorate', 'jawda'),
            'city'         => __('City', 'jawda'),
            'district'     => __('District', 'jawda'),
        ];
        foreach ($levels as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($level_filter, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select> ';
        submit_button(__('Filter', 'jawda'), 'secondary', '', false);
        echo '</form>';

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        $headers = [
            __('ID', 'jawda'),
            __('Catalog Key', 'jawda'),
            __('Type', 'jawda'),
            __('Location Level', 'jawda'),
            __('Location', 'jawda'),
            __('Has AR', 'jawda'),
            __('Has EN', 'jawda'),
            __('Custom', 'jawda'),
            __('Meta Robots', 'jawda'),
            __('Active', 'jawda'),
            __('Actions', 'jawda'),
        ];
        foreach ($headers as $head) {
            echo '<th>' . esc_html($head) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if (!$rows) {
            echo '<tr><td colspan="9">' . esc_html__('No overrides found.', 'jawda') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                $location_label = $this->get_location_label($row);
                $edit_url = add_query_arg(['page' => 'jawda-catalog-seo', 'action' => 'edit', 'id' => $row->id], admin_url('admin.php'));
                $delete_url = wp_nonce_url(add_query_arg(['page' => 'jawda-catalog-seo', 'action' => 'delete', 'id' => $row->id], admin_url('admin.php')), 'jawda_catalog_seo_delete_' . $row->id);
                echo '<tr>';
                echo '<td>' . esc_html($row->id) . '</td>';
                echo '<td>' . esc_html($row->catalog_key) . '</td>';
                echo '<td>' . esc_html($row->type) . '</td>';
                echo '<td>' . esc_html($row->location_level) . '</td>';
                echo '<td>' . esc_html($location_label) . '</td>';
                echo '<td>' . (!empty($row->meta_title_ar) || !empty($row->content_ar) ? esc_html__('Yes', 'jawda') : esc_html__('No', 'jawda')) . '</td>';
                echo '<td>' . (!empty($row->meta_title_en) || !empty($row->content_en) ? esc_html__('Yes', 'jawda') : esc_html__('No', 'jawda')) . '</td>';
                echo '<td>' . ($row->is_custom_catalog ? esc_html__('Yes', 'jawda') : esc_html__('No', 'jawda')) . '</td>';
                echo '<td>' . esc_html($row->meta_robots ?: '-') . '</td>';
                echo '<td>' . ($row->is_active ? esc_html__('Active', 'jawda') : esc_html__('Inactive', 'jawda')) . '</td>';
                echo '<td><a href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'jawda') . '</a> | <a href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Delete this override?', 'jawda')) . '\');">' . esc_html__('Delete', 'jawda') . '</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_form($id = 0) {
        global $wpdb;
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id));
        }

        $is_edit = $row !== null;
        $title = $is_edit ? __('Edit Catalog SEO', 'jawda') : __('Add Catalog SEO', 'jawda');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_form_submit($row);
        }

        $governorates = $wpdb->get_results("SELECT id, name_ar, name_en, slug, slug_ar FROM {$wpdb->prefix}jawda_governorates WHERE is_deleted = 0 ORDER BY name_en ASC");
        $cities = $wpdb->get_results("SELECT id, governorate_id, name_ar, name_en, slug, slug_ar FROM {$wpdb->prefix}jawda_cities WHERE is_deleted = 0 ORDER BY name_en ASC");
        $districts = $wpdb->get_results("SELECT id, city_id, name_ar, name_en, slug, slug_ar FROM {$wpdb->prefix}jawda_districts WHERE is_deleted = 0 ORDER BY name_en ASC");

        $selected_type = $this->form_state['type'] ?? ($row->type ?? 'projects');
        $selected_level = $this->form_state['location_level'] ?? ($row->location_level ?? '');
        $selected_location_id = $this->form_state['location_id'] ?? ($row->location_id ?? 0);

        $selected_gov_id = $this->form_state['gov_id'] ?? 0;
        $selected_city_id = $this->form_state['city_id'] ?? 0;
        $selected_district_id = $this->form_state['district_id'] ?? 0;

        if ($row && !$this->form_state) {
            if ($row->location_level === 'governorate') {
                $selected_gov_id = (int) $row->location_id;
            } elseif ($row->location_level === 'city') {
                $selected_city_id = (int) $row->location_id;
                $city_data = jawda_get_city($selected_city_id);
                $selected_gov_id = isset($city_data['governorate_id']) ? (int) $city_data['governorate_id'] : 0;
            } elseif ($row->location_level === 'district') {
                $selected_district_id = (int) $row->location_id;
                $district_data = jawda_get_district($selected_district_id);
                $selected_city_id = isset($district_data['city_id']) ? (int) $district_data['city_id'] : 0;
                $city_data = $selected_city_id ? jawda_get_city($selected_city_id) : null;
                $selected_gov_id = isset($city_data['governorate_id']) ? (int) $city_data['governorate_id'] : 0;
            }
        }

        $meta_title_ar = $this->form_state['meta_title_ar'] ?? ($row->meta_title_ar ?? '');
        $meta_title_en = $this->form_state['meta_title_en'] ?? ($row->meta_title_en ?? '');
        $meta_desc_ar = $this->form_state['meta_desc_ar'] ?? ($row->meta_desc_ar ?? '');
        $meta_desc_en = $this->form_state['meta_desc_en'] ?? ($row->meta_desc_en ?? '');
        $intro_html_ar = $this->form_state['content_ar'] ?? ($row->intro_html_ar ?? '');
        $intro_html_en = $this->form_state['content_en'] ?? ($row->intro_html_en ?? '');
        $content_ar = $this->form_state['content_ar'] ?? ($row->content_ar ?? $intro_html_ar);
        $content_en = $this->form_state['content_en'] ?? ($row->content_en ?? $intro_html_en);
        $featured_image_id = $this->form_state['featured_image_id'] ?? ($row->featured_image_id ?? '');
        $meta_robots = $this->form_state['meta_robots'] ?? ($row->meta_robots ?? '');
        $is_active = isset($this->form_state['is_active']) ? (int) $this->form_state['is_active'] : (isset($row->is_active) ? (int) $row->is_active : 1);
        $is_custom = isset($this->form_state['is_custom_catalog']) ? (int) $this->form_state['is_custom_catalog'] : (isset($row->is_custom_catalog) ? (int) $row->is_custom_catalog : 0);

        $catalog_key = $this->form_state['catalog_key'] ?? ($row->catalog_key ?? '');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($title) . '</h1>';
        echo '<form method="post">';
        wp_nonce_field('jawda_catalog_seo_save', 'jawda_catalog_seo_nonce');
        echo '<input type="hidden" name="id" value="' . esc_attr($id) . '" />';

        echo '<h2>' . esc_html__('Catalog Identification', 'jawda') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Type', 'jawda') . '</th><td>';
        echo '<select name="type">';
        echo '<option value="projects"' . selected($selected_type, 'projects', false) . '>' . esc_html__('Projects', 'jawda') . '</option>';
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('Location Level', 'jawda') . '</th><td>';
        echo '<select name="location_level" id="jawda_location_level">';
        echo '<option value="country"' . selected($selected_level, 'country', false) . '>' . esc_html__('Country', 'jawda') . '</option>';
        echo '<option value="governorate"' . selected($selected_level, 'governorate', false) . '>' . esc_html__('Governorate', 'jawda') . '</option>';
        echo '<option value="city"' . selected($selected_level, 'city', false) . '>' . esc_html__('City', 'jawda') . '</option>';
        echo '<option value="district"' . selected($selected_level, 'district', false) . '>' . esc_html__('District', 'jawda') . '</option>';
        echo '<option value="custom"' . selected($selected_level, 'custom', false) . '>' . esc_html__('Custom', 'jawda') . '</option>';
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('Governorate', 'jawda') . '</th><td>';
        echo '<select name="gov_id" id="jawda_gov">';
        echo '<option value="">' . esc_html__('Select Governorate', 'jawda') . '</option>';
        foreach ($governorates as $gov) {
            $selected = ((int)$selected_gov_id === (int)$gov->id) ? 'selected' : '';
            echo '<option data-slug="' . esc_attr($gov->slug ?: $gov->slug_ar) . '" value="' . esc_attr($gov->id) . '" ' . $selected . '>' . esc_html($gov->name_en . ' / ' . $gov->name_ar) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('City', 'jawda') . '</th><td>';
        echo '<select name="city_id" id="jawda_city">';
        echo '<option value="">' . esc_html__('Select City', 'jawda') . '</option>';
        foreach ($cities as $city) {
            $selected = ((int)$selected_city_id === (int)$city->id) ? 'selected' : '';
            echo '<option data-gov="' . esc_attr($city->governorate_id) . '" data-slug="' . esc_attr($city->slug ?: $city->slug_ar) . '" value="' . esc_attr($city->id) . '" ' . $selected . '>' . esc_html($city->name_en . ' / ' . $city->name_ar) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('District', 'jawda') . '</th><td>';
        echo '<select name="district_id" id="jawda_district">';
        echo '<option value="">' . esc_html__('Select District', 'jawda') . '</option>';
        foreach ($districts as $dist) {
            $selected = ((int)$selected_district_id === (int)$dist->id) ? 'selected' : '';
            echo '<option data-city="' . esc_attr($dist->city_id) . '" data-slug="' . esc_attr($dist->slug ?: $dist->slug_ar) . '" value="' . esc_attr($dist->id) . '" ' . $selected . '>' . esc_html($dist->name_en . ' / ' . $dist->name_ar) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('Custom Catalog', 'jawda') . '</th><td>';
        echo '<label><input type="checkbox" name="is_custom_catalog" value="1"' . checked($is_custom, 1, false) . ' /> ' . esc_html__('Mark as custom catalog', 'jawda') . '</label>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('Catalog Key', 'jawda') . '</th><td>';
        echo '<input type="text" name="catalog_key" id="jawda_catalog_key" value="' . esc_attr($catalog_key) . '" class="regular-text" readonly />';
        echo '<p class="description">' . esc_html__('Generated automatically from type and location. Enabled when custom catalog is checked.', 'jawda') . '</p>';
        echo '</td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('SEO Content', 'jawda') . '</h2>';
        echo '<h3 class="nav-tab-wrapper">';
        echo '<a href="#tab-ar" class="nav-tab nav-tab-active">' . esc_html__('Arabic', 'jawda') . '</a>';
        echo '<a href="#tab-en" class="nav-tab">' . esc_html__('English', 'jawda') . '</a>';
        echo '</h3>';

        echo '<div id="tab-ar" class="jawda-tab active">';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Meta Title (AR)', 'jawda') . '</th><td><input type="text" name="meta_title_ar" value="' . esc_attr($meta_title_ar) . '" class="regular-text" /></td></tr>';
        echo '<tr><th>' . esc_html__('Meta Description (AR)', 'jawda') . '</th><td><textarea name="meta_desc_ar" rows="4" class="large-text">' . esc_textarea($meta_desc_ar) . '</textarea></td></tr>';
        echo '<tr><th>' . esc_html__('Content (AR)', 'jawda') . '</th><td>';
        wp_editor($content_ar, 'content_ar', ['textarea_name' => 'content_ar', 'editor_height' => 180]);
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<div id="tab-en" class="jawda-tab">';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Meta Title (EN)', 'jawda') . '</th><td><input type="text" name="meta_title_en" value="' . esc_attr($meta_title_en) . '" class="regular-text" /></td></tr>';
        echo '<tr><th>' . esc_html__('Meta Description (EN)', 'jawda') . '</th><td><textarea name="meta_desc_en" rows="4" class="large-text">' . esc_textarea($meta_desc_en) . '</textarea></td></tr>';
        echo '<tr><th>' . esc_html__('Content (EN)', 'jawda') . '</th><td>';
        wp_editor($content_en, 'content_en', ['textarea_name' => 'content_en', 'editor_height' => 180]);
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<h2>' . esc_html__('Extra SEO Settings', 'jawda') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Featured Image', 'jawda') . '</th><td>';
        echo '<input type="hidden" name="featured_image_id" id="jawda_featured_image_id" value="' . esc_attr($featured_image_id) . '" />';
        echo '<button type="button" class="button" id="jawda_featured_image_btn">' . esc_html__('Select Image', 'jawda') . '</button> ';
        echo '<span id="jawda_featured_image_preview">' . ($featured_image_id ? esc_html__('Image selected', 'jawda') : '') . '</span>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('Meta Robots', 'jawda') . '</th><td>';
        echo '<select name="meta_robots">';
        $robots_options = [
            ''                => __('Default', 'jawda'),
            'index,follow'    => 'index,follow',
            'noindex,follow'  => 'noindex,follow',
            'noindex,nofollow'=> 'noindex,nofollow',
        ];
        foreach ($robots_options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($meta_robots, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__('Active', 'jawda') . '</th><td>';
        echo '<label><input type="checkbox" name="is_active" value="1"' . checked($is_active, 1, false) . ' /> ' . esc_html__('Active', 'jawda') . '</label>';
        echo '</td></tr>';
        echo '</table>';

        submit_button($is_edit ? __('Update', 'jawda') : __('Save', 'jawda'));
        echo ' <a class="button" href="' . esc_url(admin_url('admin.php?page=jawda-catalog-seo')) . '">' . esc_html__('Back to list', 'jawda') . '</a>';

        echo '</form>';
        echo '</div>';

        $this->render_tabs_script();
    }

    private function render_tabs_script() {
        ?>
        <style>
            .jawda-tab { display:none; }
            .jawda-tab.active { display:block; }
        </style>
        <script>
            jQuery(function($){
                $('.nav-tab-wrapper a').on('click', function(e){
                    e.preventDefault();
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    $('.jawda-tab').removeClass('active');
                    $($(this).attr('href')).addClass('active');
                });

                function buildKey(){
                    var level = $('#jawda_location_level').val();
                    var parts = ['projects'];
                    if(level === 'country') { parts.push('country=egypt'); }
                    if(level === 'governorate') { var gov = $('#jawda_gov option:selected').data('slug'); if(gov){ parts.push('gov='+gov); } }
                    if(level === 'city') {
                        var govSel = $('#jawda_gov option:selected').data('slug');
                        var city = $('#jawda_city option:selected').data('slug');
                        if(govSel){ parts.push('gov='+govSel); }
                        if(city){ parts.push('city='+city); }
                    }
                    if(level === 'district') {
                        var govSel2 = $('#jawda_gov option:selected').data('slug');
                        var citySlug = $('#jawda_city option:selected').data('slug');
                        var dist = $('#jawda_district option:selected').data('slug');
                        if(govSel2){ parts.push('gov='+govSel2); }
                        if(citySlug){ parts.push('city='+citySlug); }
                        if(dist){ parts.push('district='+dist); }
                    }
                    $('#jawda_catalog_key').val(parts.join('|'));
                }

                $('#jawda_location_level, #jawda_gov, #jawda_city, #jawda_district').on('change', buildKey);
                buildKey();

                $('#jawda_featured_image_btn').on('click', function(e){
                    e.preventDefault();
                    var frame = wp.media({title: '<?php echo esc_js(__('Select or Upload Image', 'jawda')); ?>', button: {text: '<?php echo esc_js(__('Use this image', 'jawda')); ?>'}, multiple:false});
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#jawda_featured_image_id').val(attachment.id);
                        $('#jawda_featured_image_preview').text(attachment.filename);
                    });
                    frame.open();
                });

                $('input[name="is_custom_catalog"]').on('change', function(){
                    if($(this).is(':checked')){
                        $('#jawda_catalog_key').prop('readonly', false);
                    } else {
                        $('#jawda_catalog_key').prop('readonly', true);
                        buildKey();
                    }
                }).trigger('change');
            });
        </script>
        <?php
    }

    private function handle_form_submit($existing_row = null) {
        if (!isset($_POST['jawda_catalog_seo_nonce']) || !wp_verify_nonce($_POST['jawda_catalog_seo_nonce'], 'jawda_catalog_seo_save')) {
            return;
        }

        global $wpdb;

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $type = sanitize_text_field($_POST['type'] ?? 'projects');
        $location_level = sanitize_text_field($_POST['location_level'] ?? '');
        $gov_id = isset($_POST['gov_id']) ? absint($_POST['gov_id']) : 0;
        $city_id = isset($_POST['city_id']) ? absint($_POST['city_id']) : 0;
        $district_id = isset($_POST['district_id']) ? absint($_POST['district_id']) : 0;
        $is_custom = !empty($_POST['is_custom_catalog']) ? 1 : 0;
        $catalog_key = sanitize_text_field($_POST['catalog_key'] ?? '');

        $meta_title_ar = sanitize_text_field($_POST['meta_title_ar'] ?? '');
        $meta_title_en = sanitize_text_field($_POST['meta_title_en'] ?? '');
        $meta_desc_ar = wp_kses_post($_POST['meta_desc_ar'] ?? '');
        $meta_desc_en = wp_kses_post($_POST['meta_desc_en'] ?? '');
        $content_ar = wp_kses_post($_POST['content_ar'] ?? '');
        $content_en = wp_kses_post($_POST['content_en'] ?? '');
        $featured_image_id = isset($_POST['featured_image_id']) ? absint($_POST['featured_image_id']) : 0;
        $meta_robots = sanitize_text_field($_POST['meta_robots'] ?? '');
        $is_active = !empty($_POST['is_active']) ? 1 : 0;

        $this->form_state = [
            'type'              => $type,
            'location_level'    => $location_level,
            'gov_id'            => $gov_id,
            'city_id'           => $city_id,
            'district_id'       => $district_id,
            'is_custom_catalog' => $is_custom,
            'catalog_key'       => $catalog_key,
            'meta_title_ar'     => $meta_title_ar,
            'meta_title_en'     => $meta_title_en,
            'meta_desc_ar'      => $meta_desc_ar,
            'meta_desc_en'      => $meta_desc_en,
            'content_ar'        => $content_ar,
            'content_en'        => $content_en,
            'featured_image_id' => $featured_image_id,
            'meta_robots'       => $meta_robots,
            'is_active'         => $is_active,
        ];

        $error = '';

        if (empty($location_level)) {
            $error = __('Location level is required.', 'jawda');
        }

        if (!$error) {
            if ($location_level === 'country') {
                $gov_id = 0;
                $city_id = 0;
                $district_id = 0;
            } elseif ($location_level === 'governorate') {
                if (!$gov_id) {
                    $error = __('يجب اختيار محافظة للكتالوج على مستوى المحافظة', 'jawda');
                }
                $city_id = 0;
                $district_id = 0;
            } elseif ($location_level === 'city') {
                if (!$gov_id) {
                    $error = __('يجب اختيار محافظة للكتالوج على مستوى المدينة', 'jawda');
                } elseif (!$city_id) {
                    $error = __('يجب اختيار مدينة تابعة للمحافظة المختارة', 'jawda');
                } else {
                    $city = jawda_get_city($city_id);
                    if (!$city || (int) $city['governorate_id'] !== (int) $gov_id) {
                        $error = __('يجب اختيار مدينة صحيحة تابعة للمحافظة المختارة', 'jawda');
                    }
                }
                $district_id = 0;
            } elseif ($location_level === 'district') {
                if (!$gov_id) {
                    $error = __('يجب اختيار محافظة للكتالوج على مستوى الحي', 'jawda');
                } elseif (!$city_id) {
                    $error = __('يجب اختيار مدينة تابعة للمحافظة المختارة', 'jawda');
                } elseif (!$district_id) {
                    $error = __('يجب اختيار حي صحيح تابع للمدينة المختارة', 'jawda');
                } else {
                    $city = jawda_get_city($city_id);
                    $district = jawda_get_district($district_id);
                    if (!$city || (int) $city['governorate_id'] !== (int) $gov_id) {
                        $error = __('يجب اختيار مدينة صحيحة تابعة للمحافظة المختارة', 'jawda');
                    }
                    if (!$district || (int) $district['city_id'] !== (int) $city_id) {
                        $error = __('يجب اختيار حي صحيح تابع للمدينة المختارة', 'jawda');
                    }
                }
            }
        }

        $this->form_state['gov_id'] = $gov_id;
        $this->form_state['city_id'] = $city_id;
        $this->form_state['district_id'] = $district_id;
        $this->form_state['location_id'] = $this->resolve_location_id($location_level, $gov_id, $city_id, $district_id);

        if (!$is_custom && !$error) {
            $catalog_key = $this->generate_catalog_key($type, $location_level, $gov_id, $city_id, $district_id);
            $this->form_state['catalog_key'] = $catalog_key;
        }

        if (!$catalog_key && !$error) {
            $error = __('Catalog key is required.', 'jawda');
        }

        if (empty($meta_title_ar) && empty($meta_title_en) && !$error) {
            $error = __('Please provide at least one meta title (AR or EN).', 'jawda');
        }

        if ($error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            return;
        }

        // Prevent duplicate contexts.
        $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table} WHERE catalog_key = %s AND id != %d", $catalog_key, $id));
        if ($existing_id) {
            echo '<div class="notice notice-error"><p>' . esc_html__('This catalog already has an SEO override.', 'jawda') . '</p></div>';
            return;
        }

        $data = [
            'catalog_key'       => $catalog_key,
            'type'              => $type,
            'location_level'    => $location_level,
            'location_id'       => $this->resolve_location_id($location_level, $gov_id, $city_id, $district_id),
            'is_custom_catalog' => $is_custom,
            'meta_title_ar'     => $meta_title_ar,
            'meta_title_en'     => $meta_title_en,
            'meta_desc_ar'      => $meta_desc_ar,
            'meta_desc_en'      => $meta_desc_en,
            'intro_html_ar'     => $content_ar, // Legacy support
            'intro_html_en'     => $content_en, // Legacy support
            'content_ar'        => $content_ar,
            'content_en'        => $content_en,
            'featured_image_id' => $featured_image_id ?: null,
            'meta_robots'       => $meta_robots,
            'is_active'         => $is_active,
            'updated_at'        => current_time('mysql'),
        ];

        if ($id && $existing_row) {
            $wpdb->update($this->table, $data, ['id' => $id]);
            $msg = __('Catalog SEO updated.', 'jawda');
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($this->table, $data);
            $msg = __('Catalog SEO created.', 'jawda');
        }

        wp_safe_redirect(add_query_arg(['page' => 'jawda-catalog-seo', 'message' => rawurlencode($msg)], admin_url('admin.php')));
        exit;
    }

    private function generate_catalog_key($type, $level, $gov_id, $city_id, $district_id) {
        global $wpdb;
        $parts = [$type];
        if ($level === 'country') {
            $parts[] = 'country=egypt';
        }
        if ($gov_id) {
            $gov = $wpdb->get_row($wpdb->prepare("SELECT slug, slug_ar, name_en, name_ar FROM {$wpdb->prefix}jawda_governorates WHERE id = %d", $gov_id));
            if ($gov) {
                $parts[] = 'gov=' . sanitize_title($gov->slug ?: $gov->slug_ar ?: $gov->name_en ?: $gov->name_ar);
            }
        }
        if ($city_id) {
            $city = $wpdb->get_row($wpdb->prepare("SELECT slug, slug_ar, name_en, name_ar FROM {$wpdb->prefix}jawda_cities WHERE id = %d", $city_id));
            if ($city) {
                $parts[] = 'city=' . sanitize_title($city->slug ?: $city->slug_ar ?: $city->name_en ?: $city->name_ar);
            }
        }
        if ($district_id) {
            $dist = $wpdb->get_row($wpdb->prepare("SELECT slug, slug_ar, name_en, name_ar FROM {$wpdb->prefix}jawda_districts WHERE id = %d", $district_id));
            if ($dist) {
                $parts[] = 'district=' . sanitize_title($dist->slug ?: $dist->slug_ar ?: $dist->name_en ?: $dist->name_ar);
            }
        }

        return implode('|', $parts);
    }

    private function resolve_location_id($level, $gov_id, $city_id, $district_id) {
        if ($level === 'governorate') {
            return $gov_id ?: null;
        }
        if ($level === 'city') {
            return $city_id ?: null;
        }
        if ($level === 'district') {
            return $district_id ?: null;
        }
        return null;
    }

    private function get_location_label($row) {
        if ($row->location_level === 'country') {
            return __('Egypt', 'jawda');
        }
        if (!$row->location_id) {
            return '';
        }
        if ($row->location_level === 'governorate') {
            $data = jawda_get_governorate($row->location_id);
        } elseif ($row->location_level === 'city') {
            $data = jawda_get_city($row->location_id);
        } elseif ($row->location_level === 'district') {
            $data = jawda_get_district($row->location_id);
        } else {
            $data = null;
        }

        if (!$data) {
            return '';
        }
        return ($data['name_en'] ?? '') . ' / ' . ($data[ 'slug_ar' ] ?? '');
    }

    private function maybe_handle_delete() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete') {
            return;
        }
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if (!$id) {
            return;
        }
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'jawda_catalog_seo_delete_' . $id)) {
            return;
        }
        global $wpdb;
        $wpdb->delete($this->table, ['id' => $id]);
        wp_safe_redirect(add_query_arg(['page' => 'jawda-catalog-seo', 'message' => rawurlencode(__('Catalog SEO deleted.', 'jawda'))], admin_url('admin.php')));
        exit;
    }
}

new Jawda_Catalog_SEO_Admin_Page();
