<?php
/**
 * Page handler for managing project features.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Project_Features_Page {
    public $list_table;
    private $page_slug = 'jawda-project-features';
    private $forced_type = '';
    private $allowed_types = [];
    private $labels = [];
    private $default_contexts = ['projects' => 1, 'properties' => 0];

    public function __construct($args = []) {
        $defaults = [
            'page_slug'        => 'jawda-project-features',
            'forced_type'      => '',
            'default_contexts' => ['projects' => 1, 'properties' => 0],
            'allowed_types'    => [],
            'labels'           => [
                'list_title'      => __('Featured', 'jawda'),
                'add_new'         => __('Add New', 'jawda'),
                'add_heading'     => __('Add Featured Item', 'jawda'),
                'edit_heading'    => __('Edit Featured Item', 'jawda'),
                'add_button'      => __('Add Featured Item', 'jawda'),
                'update_button'   => __('Update', 'jawda'),
                'success_message' => __('Featured item saved successfully.', 'jawda'),
                'delete_success'  => __('Featured item deleted.', 'jawda'),
                'delete_error'    => __('Failed to delete featured item.', 'jawda'),
            ],
        ];

        $args = wp_parse_args($args, $defaults);

        $this->page_slug = is_string($args['page_slug']) && $args['page_slug'] !== '' ? $args['page_slug'] : 'jawda-project-features';
        $this->forced_type = is_string($args['forced_type']) ? $args['forced_type'] : '';
        $this->default_contexts = is_array($args['default_contexts']) ? $args['default_contexts'] : $defaults['default_contexts'];
        $this->allowed_types = is_array($args['allowed_types']) ? array_filter(array_map('sanitize_key', $args['allowed_types'])) : [];
        $this->labels = wp_parse_args(is_array($args['labels']) ? $args['labels'] : [], $defaults['labels']);

        $this->list_table = new Jawda_Project_Features_List_Table();
        $this->list_table->set_base_page($this->page_slug);
        if ($this->forced_type !== '') {
            $this->list_table->set_forced_type($this->forced_type);
        }
        if (!empty($this->allowed_types)) {
            $this->list_table->set_allowed_types($this->allowed_types);
        }
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'start_session'], 1);
        add_action('admin_notices', [$this, 'display_notices']);
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    private function build_item_label($item) {
        $name_ar = isset($item[ 'slug_ar' ]) ? (string) $item[ 'slug_ar' ] : '';
        $name_en = isset($item['name_en']) ? (string) $item['name_en'] : '';

        if (function_exists('jawda_project_features_build_label')) {
            return jawda_project_features_build_label($name_ar, $name_en);
        }

        if ($name_ar !== '' && $name_en !== '') {
            return trim($name_ar . ' / ' . $name_en);
        }

        return $name_en !== '' ? $name_en : $name_ar;
    }

    private function get_items_by_type($type) {
        if (!function_exists('jawda_project_features_fetch_all')) {
            return [];
        }

        $items = jawda_project_features_fetch_all();

        return array_values(array_filter($items, static function ($item) use ($type) {
            return isset($item['feature_type']) && (string) $item['feature_type'] === (string) $type;
        }));
    }

    private function get_dropdown_options_for_type($type, $placeholder_label) {
        $options = ['0' => $placeholder_label];
        $items   = $this->get_items_by_type($type);

        foreach ($items as $item) {
            if (empty($item['id'])) {
                continue;
            }

            $options[(string) $item['id']] = $this->build_item_label($item);
        }

        return $options;
    }

    private function get_label($key, $fallback) {
        if (isset($this->labels[$key]) && $this->labels[$key] !== '') {
            return $this->labels[$key];
        }

        return $fallback;
    }

    private function get_page_slug() {
        return $this->page_slug !== '' ? $this->page_slug : 'jawda-project-features';
    }

    private function get_page_url($args = []) {
        $base = ['page' => $this->get_page_slug()];
        if (!empty($args) && is_array($args)) {
            $base = array_merge($base, $args);
        }

        return add_query_arg($base, admin_url('admin.php'));
    }

    private function redirect_to($args = []) {
        wp_redirect($this->get_page_url($args));
        exit;
    }

    private function redirect_to_form($id = 0) {
        $args = ['action' => $id ? 'edit' : 'add'];
        if ($id) {
            $args['id'] = $id;
        }

        $this->redirect_to($args);
    }

    public function display_notices() {
        if (isset($_SESSION['jawda_project_features_notice'])) {
            $notice = $_SESSION['jawda_project_features_notice'];
            printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
            unset($_SESSION['jawda_project_features_notice']);
        }
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, $this->get_page_slug()) === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'jawda-project-features-admin',
            get_template_directory_uri() . '/app/inc/project-features/js/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script(
            'jawda-project-features-admin',
            'JawdaFeaturedAdmin',
            [
                'allowedMimeTypes' => ['image/png', 'image/svg+xml'],
                'i18n'             => [
                    'upload' => esc_html__('Use this image', 'jawda'),
                    'remove' => esc_html__('Remove Image', 'jawda'),
                    'mimeError' => esc_html__('Only PNG and SVG images are allowed.', 'jawda'),
                ],
            ]
        );
    }

    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $id     = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if ($action === 'seed_defaults') {
            $this->handle_seed_defaults();
            return;
        }

        if (($action === 'edit' && $id) || $action === 'add') {
            $this->render_form($id);
        } else {
            $this->render_list();
        }
    }

    private function render_list() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html($this->get_label('list_title', __('Featured', 'jawda'))); ?></h1>
            <?php $current_page = esc_attr($this->get_page_slug()); ?>
            <a href="?page=<?php echo $current_page; ?>&action=add" class="page-title-action"><?php echo esc_html($this->get_label('add_new', __('Add New', 'jawda'))); ?></a>
            <?php
            $seed_url = wp_nonce_url(
                add_query_arg(
                    [
                        'page'   => $this->get_page_slug(),
                        'action' => 'seed_defaults',
                    ],
                    admin_url('admin.php')
                ),
                'jawda_project_features_seed_defaults'
            );
            ?>
            <a href="<?php echo esc_url($seed_url); ?>" class="page-title-action"><?php esc_html_e('Restore defaults', 'jawda'); ?></a>
            <form method="post">
                <?php $this->list_table->display(); ?>
            </form>
        </div>
        <?php
    }

    private function render_form($id = 0) {
        global $wpdb;
        $table = jawda_project_features_table();

        $feature            = null;
        $name_ar            = '';
        $name_en            = '';
        $image_id           = 0;
        $feature_type       = 'feature';
        $context_projects   = 1;
        $context_properties = 0;
        $orientation_id     = 0;
        $facade_id          = 0;

        if ($id) {
            $feature = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
            if ($feature) {
                $name_ar = $feature[ 'slug_ar' ];
                $name_en = $feature['name_en'];
                $image_id = (int) $feature['image_id'];
                $feature_type = isset($feature['feature_type']) ? (string) $feature['feature_type'] : 'feature';
                $context_projects = isset($feature['context_projects']) ? (int) $feature['context_projects'] : 1;
                $context_properties = isset($feature['context_properties']) ? (int) $feature['context_properties'] : 0;
                $orientation_id = isset($feature['orientation_id']) ? (int) $feature['orientation_id'] : 0;
                $facade_id = isset($feature['facade_id']) ? (int) $feature['facade_id'] : 0;
            }
        }

        if (!$id) {
            $context_projects   = !empty($this->default_contexts['projects']) ? 1 : 0;
            $context_properties = !empty($this->default_contexts['properties']) ? 1 : 0;
        }

        if ($this->forced_type !== '') {
            $feature_type = $this->forced_type;
        }

        $feature_types_all = function_exists('jawda_project_features_get_feature_types') ? jawda_project_features_get_feature_types() : [];
        $feature_types  = $feature_types_all;

        if (!empty($this->allowed_types)) {
            $feature_types = array_intersect_key($feature_types_all, array_fill_keys($this->allowed_types, true));
        }

        if ($this->forced_type !== '') {
            $feature_types = array_intersect_key($feature_types, [$this->forced_type => true]);
        }

        if (!empty($this->allowed_types) && !in_array($feature_type, $this->allowed_types, true)) {
            $feature_type = reset($this->allowed_types);
        }

        $context_labels = function_exists('jawda_project_features_get_context_labels') ? jawda_project_features_get_context_labels() : [];
        $is_marketing_combination = ($this->forced_type === 'marketing_orientation' || $feature_type === 'marketing_orientation');

        if (!$is_marketing_combination) {
            $orientation_id = 0;
            $facade_id = 0;
        }

        $orientation_options = [];
        $facade_options      = [];

        if ($is_marketing_combination) {
            $orientation_options = $this->get_dropdown_options_for_type('orientation', __('— Select orientation —', 'jawda'));
            $facade_options      = $this->get_dropdown_options_for_type('facade', __('— Select facade / position —', 'jawda'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html($id ? $this->get_label('edit_heading', __('Edit Featured Item', 'jawda')) : $this->get_label('add_heading', __('Add Featured Item', 'jawda'))); ?></h1>

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr($this->get_page_slug()); ?>" />
                <input type="hidden" name="action" value="save" />
                <?php if ($id) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>" />
                <?php endif; ?>
                <?php wp_nonce_field('jawda_save_project_feature', 'jawda_project_feature_nonce'); ?>

                <table class="form-table">
                    <tbody>
                        <tr class="form-field form-required">
                            <th scope="row"><label for="name_en"><?php esc_html_e('Name (English)', 'jawda'); ?></label></th>
                            <td><input name="name_en" id="name_en" type="text" value="<?php echo esc_attr($name_en); ?>" required /></td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row"><label for= 'slug_ar' ><?php esc_html_e('Name (Arabic)', 'jawda'); ?></label></th>
                            <td><input name= 'slug_ar'  id= 'slug_ar'  type="text" value="<?php echo esc_attr($name_ar); ?>" required /></td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row"><label for="feature_type"><?php esc_html_e('Type', 'jawda'); ?></label></th>
                            <td>
                                <?php if ($this->forced_type === '') : ?>
                                    <select name="feature_type" id="feature_type">
                                        <?php foreach ($feature_types as $type_key => $type_label) : ?>
                                            <option value="<?php echo esc_attr($type_key); ?>" <?php selected($feature_type, $type_key); ?>><?php echo esc_html($type_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Classify whether this is a feature, amenity, or facility.', 'jawda'); ?></p>
                                <?php else : ?>
                                    <input type="hidden" name="feature_type" id="feature_type" value="<?php echo esc_attr($feature_type); ?>" />
                                    <strong><?php echo esc_html(isset($feature_types_all[$feature_type]) ? $feature_types_all[$feature_type] : $feature_type); ?></strong>
                                    <p class="description"><?php esc_html_e('This screen manages a dedicated list for this type.', 'jawda'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row"><?php esc_html_e('Available For', 'jawda'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="feature_contexts[]" value="projects" <?php checked($context_projects); ?> />
                                        <?php echo isset($context_labels['projects']) ? esc_html($context_labels['projects']) : esc_html__('Projects', 'jawda'); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="checkbox" name="feature_contexts[]" value="properties" <?php checked($context_properties); ?> />
                                        <?php echo isset($context_labels['properties']) ? esc_html($context_labels['properties']) : esc_html__('Units', 'jawda'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Choose whether this item is selectable for projects, units, or both.', 'jawda'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        <?php if ($is_marketing_combination) : ?>
                            <tr class="form-field">
                                <th scope="row"><label for="orientation_id"><?php esc_html_e('Orientation', 'jawda'); ?></label></th>
                                <td>
                                    <select name="orientation_id" id="orientation_id">
                                        <?php foreach ($orientation_options as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $orientation_id, (string) $value); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php
                                        printf(
                                            /* translators: %s: link to orientations management screen */
                                            esc_html__('Manage orientation values from the %s screen.', 'jawda'),
                                            sprintf(
                                                '<a href="%s">%s</a>',
                                                esc_url(admin_url('admin.php?page=jawda-project-features-orientations')),
                                                esc_html__('Orientations', 'jawda')
                                            )
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row"><label for="facade_id"><?php esc_html_e('Facade / Position', 'jawda'); ?></label></th>
                                <td>
                                    <select name="facade_id" id="facade_id">
                                        <?php foreach ($facade_options as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $facade_id, (string) $value); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php
                                        printf(
                                            /* translators: %s: link to facades management screen */
                                            esc_html__('Manage facade values from the %s screen.', 'jawda'),
                                            sprintf(
                                                '<a href="%s">%s</a>',
                                                esc_url(admin_url('admin.php?page=jawda-project-features-facades')),
                                                esc_html__('Facades & Positions', 'jawda')
                                            )
                                        );
                                        ?>
                                    </p>
                                    <p class="description"><?php esc_html_e('Select at least one orientation or facade to compose the marketing label.', 'jawda'); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr class="form-field">
                            <th scope="row"><label for="image_id"><?php esc_html_e('Image', 'jawda'); ?></label></th>
                            <td>
                                <div class="jawda-feature-image-field">
                                    <input type="hidden" name="image_id" id="image_id" value="<?php echo esc_attr($image_id); ?>" />
                                    <button type="button" class="button button-secondary jawda-upload-button"><?php esc_html_e('Upload Image', 'jawda'); ?></button>
                                    <button type="button" class="button-link jawda-remove-button" <?php if (!$image_id) : ?>style="display:none;"<?php endif; ?>><?php esc_html_e('Remove Image', 'jawda'); ?></button>
                                    <div class="jawda-image-preview" style="margin-top: 10px;">
                                        <?php if ($image_id) : ?>
                                            <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                                        <?php endif; ?>
                                    </div>
                                    <p class="description"><?php esc_html_e('PNG or SVG images work best for consistent display.', 'jawda'); ?></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php
                $button_label = $id
                    ? $this->get_label('update_button', __('Update', 'jawda'))
                    : $this->get_label('add_button', __('Add Featured Item', 'jawda'));
                submit_button($button_label);
                ?>
            </form>
        </div>
        <?php
    }

    private function handle_seed_defaults() {
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, 'jawda_project_features_seed_defaults')) {
            wp_die(esc_html__('Security check failed.', 'jawda'));
        }

        if (function_exists('jawda_project_features_seed_defaults')) {
            jawda_project_features_seed_defaults(true);
        }

        if (function_exists('jawda_project_features_reset_cache')) {
            jawda_project_features_reset_cache();
        }

        $this->set_notice('success', __('Default lookup data restored successfully.', 'jawda'));
        $this->redirect_to();
    }

    public function handle_form_submission() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'save') {
            if (isset($_GET['action']) && $_GET['action'] === 'delete') {
                $this->handle_delete();
            }
            return;
        }

        if (!isset($_POST['jawda_project_feature_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jawda_project_feature_nonce'])), 'jawda_save_project_feature')) {
            wp_die(esc_html__('Security check failed.', 'jawda'));
        }

        global $wpdb;
        $table = jawda_project_features_table();

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $name_en = isset($_POST['name_en']) ? sanitize_text_field(wp_unslash($_POST['name_en'])) : '';
        $name_ar = isset($_POST[ 'slug_ar' ]) ? sanitize_text_field(wp_unslash($_POST[ 'slug_ar' ])) : '';
        $image_id = isset($_POST['image_id']) ? absint($_POST['image_id']) : 0;
        $feature_type = isset($_POST['feature_type']) ? sanitize_key($_POST['feature_type']) : 'feature';
        $contexts_raw = isset($_POST['feature_contexts']) ? (array) $_POST['feature_contexts'] : [];
        $orientation_id = isset($_POST['orientation_id']) ? absint($_POST['orientation_id']) : 0;
        $facade_id = isset($_POST['facade_id']) ? absint($_POST['facade_id']) : 0;

        if (!empty($this->allowed_types)) {
            $available_types = $this->allowed_types;
        } else {
            $available_types = function_exists('jawda_project_features_get_feature_types')
                ? array_keys(jawda_project_features_get_feature_types())
                : ['feature', 'amenity', 'facility', 'finishing'];
        }

        if ($this->forced_type !== '') {
            $feature_type = $this->forced_type;
            $available_types[] = $this->forced_type;
            $available_types = array_values(array_unique($available_types));
        }

        if (!in_array($feature_type, $available_types, true)) {
            $feature_type = $available_types ? (string) reset($available_types) : 'feature';
        }

        $context_projects = in_array('projects', $contexts_raw, true) ? 1 : 0;
        $context_properties = in_array('properties', $contexts_raw, true) ? 1 : 0;

        if ($feature_type !== 'marketing_orientation') {
            $orientation_id = 0;
            $facade_id = 0;
        }

        if ($name_en === '' || $name_ar === '') {
            $this->set_notice('error', __('Both Arabic and English names are required.', 'jawda'));
            $this->redirect_to_form($id);
        }

        if (!$context_projects && !$context_properties) {
            $this->set_notice('error', __('Please choose at least one placement (projects or units).', 'jawda'));
            $this->redirect_to_form($id);
        }

        if ($feature_type === 'marketing_orientation' && !$orientation_id && !$facade_id) {
            $this->set_notice('error', __('Please select an orientation or a facade to build the marketing label.', 'jawda'));
            $this->redirect_to_form($id);
        }

        if ($image_id) {
            $mime = get_post_mime_type($image_id);
            if (!in_array($mime, ['image/png', 'image/svg+xml'], true)) {
                $this->set_notice('error', __('Only PNG and SVG images are allowed.', 'jawda'));
                $this->redirect_to_form($id);
            }
        }

        $data = [
            'name_en'            => $name_en,
             'slug_ar'             => $name_ar,
            'image_id'           => $image_id,
            'feature_type'       => $feature_type,
            'context_projects'   => $context_projects,
            'context_properties' => $context_properties,
            'orientation_id'     => $orientation_id,
            'facade_id'          => $facade_id,
        ];
        $formats = ['%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d'];

        $wpdb->hide_errors();

        if ($id) {
            $result = $wpdb->update($table, $data, ['id' => $id], $formats, ['%d']);
        } else {
            $result = $wpdb->insert($table, $data, $formats);
            if ($result) {
                $id = (int) $wpdb->insert_id;
            }
        }

        if ($result === false) {
            $this->set_notice('error', __('Database error:', 'jawda') . ' ' . $wpdb->last_error);
            $this->redirect_to_form($id);
        }

        if (function_exists('jawda_project_features_reset_cache')) {
            jawda_project_features_reset_cache();
        }

        $this->set_notice('success', $this->get_label('success_message', __('Featured item saved successfully.', 'jawda')));
        $this->redirect_to();
    }

    private function handle_delete() {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if (!$id) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'jawda_delete_project_feature_' . $id)) {
            wp_die(esc_html__('Security check failed.', 'jawda'));
        }

        if ($this->forced_type !== '') {
            global $wpdb;
            $table = jawda_project_features_table();
            $record_type = $wpdb->get_var($wpdb->prepare("SELECT feature_type FROM {$table} WHERE id = %d", $id));
            if ($record_type && $record_type !== $this->forced_type) {
                $this->set_notice('error', __('This item cannot be modified from this screen.', 'jawda'));
                $this->redirect_to();
            }
        }

        $deleted = jawda_project_features_delete($id);

        if ($deleted) {
            $this->set_notice('success', $this->get_label('delete_success', __('Featured item deleted.', 'jawda')));
        } else {
            $this->set_notice('error', $this->get_label('delete_error', __('Failed to delete featured item.', 'jawda')));
        }

        $this->redirect_to();
    }

    private function set_notice($type, $message) {
        $_SESSION['jawda_project_features_notice'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }
}

/**
 * Deletes a feature and removes it from project selections.
 *
 * @param int $feature_id Feature identifier.
 * @return bool
 */
function jawda_project_features_delete($feature_id) {
    $feature_id = (int) $feature_id;

    if ($feature_id <= 0) {
        return false;
    }

    global $wpdb;
    $table = jawda_project_features_table();

    $deleted = (bool) $wpdb->delete($table, ['id' => $feature_id], ['%d']);

    if ($deleted) {
        jawda_project_features_remove_from_posts($feature_id);
        if (function_exists('jawda_project_features_reset_cache')) {
            jawda_project_features_reset_cache();
        }
    }

    return $deleted;
}

/**
 * Removes a deleted feature from post meta selections.
 *
 * @param int $feature_id Feature identifier.
 */
function jawda_project_features_remove_from_posts($feature_id) {
    global $wpdb;

    $feature_id = (int) $feature_id;
    if ($feature_id <= 0) {
        return;
    }

    $meta_keys = [
        'jawda_project_feature_ids',
        'jawda_project_service_feature_ids',
        'jawda_project_service_amenity_ids',
        'jawda_project_service_facility_ids',
    ];

    foreach ($meta_keys as $meta_key) {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
                $meta_key
            )
        );

        if (!$rows) {
            continue;
        }

        foreach ($rows as $row) {
            $values = maybe_unserialize($row->meta_value);
            if (!is_array($values)) {
                $values = $values !== '' ? [$values] : [];
            }

            $values = array_map('intval', $values);
            $values = array_values(array_diff($values, [$feature_id]));

            if ($values) {
                update_post_meta($row->post_id, $meta_key, $values);
            } else {
                delete_post_meta($row->post_id, $meta_key);
            }
        }
    }
}

