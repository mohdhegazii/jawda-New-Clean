<?php
/**
 * Admin UI for managing developers.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'jawda_register_developers_menu');
function jawda_register_developers_menu() {
    add_menu_page(
        __('Manage Developers', 'jawda'),
        __('Jawda Developers', 'jawda'),
        'manage_options',
        'jawda-developers',
        'jawda_render_developers_page',
        'dashicons-businessman',
        58
    );

    add_submenu_page(
        'jawda-developers',
        __('Add Developer', 'jawda'),
        __('Add Developer', 'jawda'),
        'manage_options',
        'jawda-add-developer',
        'jawda_render_add_developer_page'
    );
}

/**
 * Render a plugin SEO meta box snapshot for the given language if available.
 * The meta box is rendered using a shadow "page" post so Yoast/RankMath can audit content.
 */
function jawda_render_developer_seo_metabox($lang, $current) {
    if (!defined('ABSPATH')) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/template.php';
    require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

    $shadow = get_default_post_to_edit('page', false);
    $shadow->post_title   = $lang === 'en' ? ($current['name_en'] ?? '') : ($current[ 'slug_ar' ] ?? '');
    $shadow->post_name    = $lang === 'en' ? ($current['slug_en'] ?? '') : ($current['slug_ar'] ?? '');
    $shadow->post_content = $lang === 'en' ? ($current['description_en'] ?? '') : ($current['description_ar'] ?? '');

    global $wp_meta_boxes, $current_screen, $post, $typenow, $pagenow;
    if (!is_array($wp_meta_boxes)) {
        $wp_meta_boxes = [];
    }
    $backup_boxes   = $wp_meta_boxes;
    $backup_screen  = $current_screen;
    $backup_type    = $typenow;
    $backup_page    = $pagenow;
    $post           = $shadow;
    $typenow        = 'page';
    $pagenow        = 'post.php';
    $current_screen = convert_to_screen('page');

    if (function_exists('set_current_screen')) {
        set_current_screen($current_screen);
    }

    /**
     * Fire the same hooks core/Yoast rely on when loading the post editor so the SEO metabox registers
     * and enqueues its assets even inside this custom screen.
     */
    // We avoid re-firing admin enqueue hooks (which can double-load scripts like Polylang's pll_admin)
    // and instead manually enqueue any SEO plugin assets that are already registered on this request.
    if (wp_script_is('wpseo-post-scraper', 'registered')) {
        wp_enqueue_script('wpseo-post-scraper');
    }
    if (wp_style_is('wpseo-metabox', 'registered')) {
        wp_enqueue_style('wpseo-metabox');
    }
    if (wp_script_is('rank-math-editor', 'registered')) {
        wp_enqueue_script('rank-math-editor');
    }
    if (wp_style_is('rank-math-editor', 'registered')) {
        wp_enqueue_style('rank-math-editor');
    }

    // Trigger both generic and post-type specific hooks so plugins like Yoast/RankMath register their boxes.
    do_action('add_meta_boxes', 'page', $shadow);
    do_action('add_meta_boxes_page', $shadow);

    // Keep only SEO plugin boxes to avoid cluttering the admin screen.
    if (isset($wp_meta_boxes['page'])) {
        foreach ($wp_meta_boxes['page'] as $context => $priorities) {
            foreach ($priorities as $priority => $boxes) {
                foreach ($boxes as $id => $box) {
                    if (strpos($id, 'wpseo') !== 0 && strpos($id, 'rank_math') !== 0) {
                        unset($wp_meta_boxes['page'][$context][$priority][$id]);
                    }
                }
            }
        }
    }

    echo '<div class="jawda-seo-plugin-box">';
    echo '<h3>' . sprintf(esc_html__('%s SEO Audit (Yoast/RankMath)', 'jawda'), strtoupper($lang)) . '</h3>';
    if (!empty($wp_meta_boxes['page'])) {
        do_meta_boxes('page', 'normal', $shadow);
        do_meta_boxes('page', 'advanced', $shadow);
        do_meta_boxes('page', 'side', $shadow);
    } else {
        echo '<p class="description">' . esc_html__('Activate Yoast SEO or Rank Math to view the SEO audit box here.', 'jawda') . '</p>';
    }
    echo '</div>';

    $wp_meta_boxes  = $backup_boxes;
    $current_screen = $backup_screen;
    $typenow        = $backup_type;
    $pagenow        = $backup_page;
    if (function_exists('set_current_screen') && $backup_screen) {
        set_current_screen($backup_screen);
    }
    wp_reset_postdata();
}

/**
 * Handle form submissions.
 */
function jawda_handle_developer_form() {
    if (!isset($_POST['jawda_developer_nonce']) || !wp_verify_nonce($_POST['jawda_developer_nonce'], 'jawda_save_developer')) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $service = jawda_developers_service();
    $developer_id = isset($_POST['developer_id']) ? (int) $_POST['developer_id'] : 0;

    $payload = [
        'name_en'           => $_POST['name_en'] ?? '',
         'slug_ar'            => $_POST[ 'slug_ar' ] ?? '',
        'slug_en'           => $_POST['slug_en'] ?? '',
        'slug_ar'           => $_POST['slug_ar'] ?? '',
        'developer_type_id' => $_POST['developer_type_id'] ?? null,
        'logo_id'           => $_POST['logo_id'] ?? null,
        'description_en'    => $_POST['description_en'] ?? '',
        'description_ar'    => $_POST['description_ar'] ?? '',
        'seo_title_en'      => $_POST['seo_title_en'] ?? '',
        'seo_title_ar'      => $_POST['seo_title_ar'] ?? '',
        'seo_desc_en'       => $_POST['seo_desc_en'] ?? '',
        'seo_desc_ar'       => $_POST['seo_desc_ar'] ?? '',
        'is_active'         => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($developer_id) {
        $result = $service->update_developer($developer_id, $payload);
    } else {
        $result = $service->create_developer($payload);
    }

    if (is_wp_error($result)) {
        add_settings_error('jawda_developers', 'jawda_developers_error', $result->get_error_message(), 'error');
    } else {
        add_settings_error('jawda_developers', 'jawda_developers_saved', __('Developer saved successfully.', 'jawda'), 'updated');
    }
}
add_action('admin_init', 'jawda_handle_developer_form');

/**
 * Render the developer form.
 */
function jawda_render_developer_form($args) {
    $service = jawda_developers_service();
    $is_edit = !empty($args['is_edit']);
    $current = $args['current'] ?? null;
    $types = jawda_get_developer_types();
    $show_seo = !empty($args['show_seo']);

    // Ensure WordPress meta box scripts/styles are available for embedded SEO boxes.
    wp_enqueue_script('postbox');
    ?>
    <form method="post">
        <?php wp_nonce_field('jawda_save_developer', 'jawda_developer_nonce'); ?>
        <?php if ($is_edit) : ?>
            <input type="hidden" name="developer_id" value="<?php echo esc_attr($current['id']); ?>">
        <?php endif; ?>

        <h3><?php esc_html_e('Basic Info', 'jawda'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="jawda-logo-preview"><?php esc_html_e('Logo', 'jawda'); ?></label></th>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div id="jawda-logo-preview" style="width:80px;height:80px;border:1px solid #ccd0d4;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                            <?php if (!empty($current['logo_id'])) { echo wp_get_attachment_image((int) $current['logo_id'], [80,80]); } else { echo '<span class="description">' . esc_html__('No logo selected', 'jawda') . '</span>'; } ?>
                        </div>
                        <div>
                            <input name="logo_id" id="logo_id" type="hidden" value="<?php echo esc_attr($current['logo_id'] ?? ''); ?>">
                            <button type="button" class="button" id="jawda-upload-logo"><?php esc_html_e('Choose/Upload Logo', 'jawda'); ?></button>
                            <button type="button" class="button-link" id="jawda-remove-logo"><?php esc_html_e('Remove', 'jawda'); ?></button>
                            <p class="description"><?php esc_html_e('Select from media library or upload a new logo.', 'jawda'); ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="developer_type_id"><?php esc_html_e('Developer Type', 'jawda'); ?></label></th>
                <td>
                    <select name="developer_type_id" id="developer_type_id">
                        <option value="">--</option>
                        <?php foreach ($types as $type) : ?>
                            <option value="<?php echo esc_attr($type['id']); ?>" <?php selected($current['developer_type_id'] ?? '', $type['id']); ?>><?php echo esc_html($type['label'] ?? ($type['name'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Options are managed from Jawda Lookups → Developer Types.', 'jawda'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="is_active"><?php esc_html_e('Active', 'jawda'); ?></label></th>
                <td><label><input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($current['is_active'] ?? 1, 1); ?>> <?php esc_html_e('Visible on frontend', 'jawda'); ?></label></td>
            </tr>
        </table>

        <h3 class="nav-tab-wrapper">
            <a href="#jawda-tab-en" class="nav-tab nav-tab-active" data-target="jawda-tab-en"><?php esc_html_e('English', 'jawda'); ?></a>
            <a href="#jawda-tab-ar" class="nav-tab" data-target="jawda-tab-ar"><?php esc_html_e('Arabic', 'jawda'); ?></a>
        </h3>

        <div id="jawda-tab-en" class="jawda-tab-panel" style="display:block;">
            <table class="form-table">
                <tr>
                    <th><label for="name_en"><?php esc_html_e('Name (English)', 'jawda'); ?></label></th>
                    <td><input name="name_en" id="name_en" type="text" class="regular-text" value="<?php echo esc_attr($current['name_en'] ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="description_en"><?php esc_html_e('Description (English)', 'jawda'); ?></label></th>
                    <td>
                        <?php
                        wp_editor(
                            $current['description_en'] ?? '',
                            'description_en',
                            [
                                'textarea_name' => 'description_en',
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                                'tinymce'       => true,
                                'quicktags'     => true,
                            ]
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="seo_title_en"><?php esc_html_e('SEO Title (English)', 'jawda'); ?></label></th>
                    <td><input name="seo_title_en" id="seo_title_en" type="text" class="regular-text" value="<?php echo esc_attr($current['seo_title_en'] ?? ''); ?>" placeholder="<?php esc_attr_e('Auto-generated from name', 'jawda'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="seo_desc_en"><?php esc_html_e('SEO Description (English)', 'jawda'); ?></label></th>
                    <td><textarea name="seo_desc_en" id="seo_desc_en" rows="3" class="large-text" placeholder="<?php esc_attr_e('Auto-generated from description', 'jawda'); ?>"><?php echo esc_textarea($current['seo_desc_en'] ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="slug_en"><?php esc_html_e('Slug (English)', 'jawda'); ?></label></th>
                    <td><input name="slug_en" id="slug_en" type="text" class="regular-text" value="<?php echo esc_attr($current['slug_en'] ?? ''); ?>" placeholder="<?php esc_attr_e('Matches the English name', 'jawda'); ?>" readonly></td>
                </tr>
            </table>
            <?php if ($show_seo) { jawda_render_developer_seo_metabox('en', $current); } ?>
        </div>

        <div id="jawda-tab-ar" class="jawda-tab-panel" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for= 'slug_ar' ><?php esc_html_e('Name (Arabic)', 'jawda'); ?></label></th>
                    <td><input name= 'slug_ar'  id= 'slug_ar'  type="text" class="regular-text" value="<?php echo esc_attr($current[ 'slug_ar' ] ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="description_ar"><?php esc_html_e('Description (Arabic)', 'jawda'); ?></label></th>
                    <td>
                        <?php
                        wp_editor(
                            $current['description_ar'] ?? '',
                            'description_ar',
                            [
                                'textarea_name' => 'description_ar',
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                                'tinymce'       => true,
                                'quicktags'     => true,
                            ]
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="seo_title_ar"><?php esc_html_e('SEO Title (Arabic)', 'jawda'); ?></label></th>
                    <td><input name="seo_title_ar" id="seo_title_ar" type="text" class="regular-text" value="<?php echo esc_attr($current['seo_title_ar'] ?? ''); ?>" placeholder="<?php esc_attr_e('Auto-generated from name', 'jawda'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="seo_desc_ar"><?php esc_html_e('SEO Description (Arabic)', 'jawda'); ?></label></th>
                    <td><textarea name="seo_desc_ar" id="seo_desc_ar" rows="3" class="large-text" placeholder="<?php esc_attr_e('Auto-generated from description', 'jawda'); ?>"><?php echo esc_textarea($current['seo_desc_ar'] ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="slug_ar"><?php esc_html_e('Slug (Arabic)', 'jawda'); ?></label></th>
                    <td><input name="slug_ar" id="slug_ar" type="text" class="regular-text" value="<?php echo esc_attr($current['slug_ar'] ?? ''); ?>" placeholder="<?php esc_attr_e('Matches the Arabic name', 'jawda'); ?>" readonly></td>
                </tr>
            </table>
            <?php if ($show_seo) { jawda_render_developer_seo_metabox('ar', $current); } ?>
        </div>

        <?php submit_button($is_edit ? __('Update Developer', 'jawda') : __('Add Developer', 'jawda')); ?>
    </form>
    <script>
        (function($){
            const $logoField = $('#logo_id');
            const $preview = $('#jawda-logo-preview');

            function updatePreview(html) {
                $preview.html(html || '<span class="description"><?php echo esc_js(__('No logo selected', 'jawda')); ?></span>');
            }

            $('#jawda-upload-logo').on('click', function(e){
                e.preventDefault();
                const frame = wp.media({ title: '<?php echo esc_js(__('Select Logo', 'jawda')); ?>', button: { text: '<?php echo esc_js(__('Use Logo', 'jawda')); ?>' }, multiple: false });
                frame.on('select', function(){
                    const attachment = frame.state().get('selection').first().toJSON();
                    $logoField.val(attachment.id);
                    updatePreview('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:80px;max-height:80px;" />');
                });
                frame.open();
            });

            $('#jawda-remove-logo').on('click', function(e){
                e.preventDefault();
                $logoField.val('');
                updatePreview();
            });

            $('.nav-tab-wrapper a').on('click', function(e){
                e.preventDefault();
                const target = $(this).data('target');
                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.jawda-tab-panel').hide();
                $('#' + target).show();
            });

            function slugifyAr(text) {
                return text.trim().replace(/\s+/g, '-').replace(/[^\u0600-\u06FF\w-]+/g, '').toLowerCase();
            }

            function slugifyEn(text) {
                return text.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }

            $('#name_en').on('input', function(){
                const val = $(this).val();
                $('#slug_en').val(slugifyEn(val));
            });
            $('#name_ar').on('input', function(){
                const val = $(this).val();
                $('#slug_ar').val(slugifyAr(val));
            });
        })(jQuery);
    </script>
    <?php
}

/**
 * Render the admin page.
 */
function jawda_render_developers_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    wp_enqueue_media();
    $service = jawda_developers_service();
    $is_edit = isset($_GET['action'], $_GET['id']) && 'edit' === $_GET['action'];
    $current = $is_edit ? $service->get_developer_by_id((int) $_GET['id']) : null;
    $types = jawda_get_developer_types();
    $developers = $service->get_developers(['number' => 200]);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Jawda Developers', 'jawda'); ?></h1>
        <?php settings_errors('jawda_developers'); ?>

        <?php if ($is_edit && $current) : ?>
            <h2><?php esc_html_e('Edit Developer', 'jawda'); ?></h2>
            <?php jawda_render_developer_form([
                'is_edit'   => true,
                'current'   => $current,
                'show_seo'  => true,
            ]); ?>
            <hr>
        <?php endif; ?>

        <h2><?php esc_html_e('Developers List', 'jawda'); ?></h2>
        <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=jawda-add-developer')); ?>"><?php esc_html_e('Add New Developer', 'jawda'); ?></a></p>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'jawda'); ?></th>
                    <th><?php esc_html_e('Logo', 'jawda'); ?></th>
                    <th><?php esc_html_e('Name EN', 'jawda'); ?></th>
                    <th><?php esc_html_e('Name AR', 'jawda'); ?></th>
                    <th><?php esc_html_e('Type', 'jawda'); ?></th>
                    <th><?php esc_html_e('Slug EN', 'jawda'); ?></th>
                    <th><?php esc_html_e('Slug AR', 'jawda'); ?></th>
                    <th><?php esc_html_e('Active', 'jawda'); ?></th>
                    <th><?php esc_html_e('Actions', 'jawda'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($developers) : foreach ($developers as $developer) : ?>
                <tr>
                    <td><?php echo esc_html($developer['id']); ?></td>
                    <td>
                        <?php if (!empty($developer['logo_id'])) : ?>
                            <?php echo wp_get_attachment_image($developer['logo_id'], [50,50]); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($developer['name_en']); ?></td>
                    <td><?php echo esc_html($developer[ 'slug_ar' ]); ?></td>
                    <td>
                        <?php
                        $label = '';
                        foreach ($types as $type) {
                            if ((int) ($type['id'] ?? 0) === (int) ($developer['developer_type_id'] ?? 0)) {
                                $label = $type['label'] ?? ($type['name'] ?? '');
                                break;
                            }
                        }
                        echo esc_html($label);
                        ?>
                    </td>
                    <td><?php echo esc_html($developer['slug_en']); ?></td>
                    <td><?php echo esc_html($developer['slug_ar']); ?></td>
                    <td><?php echo $developer['is_active'] ? esc_html__('Yes', 'jawda') : esc_html__('No', 'jawda'); ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'jawda-developers', 'action' => 'edit', 'id' => $developer['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edit', 'jawda'); ?></a>
                        |
                        <?php
                        $toggle_args = ['page' => 'jawda-developers', 'action' => 'deactivate', 'id' => $developer['id']];
                        if (!$developer['is_active']) {
                            $toggle_args['reactivate'] = 1;
                        }
                        ?>
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg($toggle_args, admin_url('admin.php')), 'jawda_delete_developer')); ?>">
                            <?php echo $developer['is_active'] ? esc_html__('Deactivate', 'jawda') : esc_html__('Activate', 'jawda'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="9"><?php esc_html_e('No developers found.', 'jawda'); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Add-only page without SEO plugin metabox embedding.
 */
function jawda_render_add_developer_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    wp_enqueue_media();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Add Developer', 'jawda'); ?></h1>
        <?php settings_errors('jawda_developers'); ?>
        <?php jawda_render_developer_form([
            'is_edit'  => false,
            // Show SEO plugin metaboxes on the add screen so editors can audit immediately.
            'show_seo' => true,
        ]); ?>
        <hr>
        <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=jawda-developers')); ?>"><?php esc_html_e('Back to list', 'jawda'); ?></a></p>
    </div>
    <?php
}

add_action('admin_init', function() {
    if (!isset($_GET['page'], $_GET['action'], $_GET['id'])) {
        return;
    }

    if ('jawda-developers' !== $_GET['page']) {
        return;
    }

    jawda_require_manage_options('jawda_developer_delete_handler');

    $id = (int) $_GET['id'];
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if ('deactivate' === $_GET['action']) {
        if (!wp_verify_nonce($nonce, 'jawda_delete_developer')) {
            jawda_log_blocked_request('jawda_developer_delete_handler');
            wp_die(esc_html__('Unauthorized', 'jawda'), esc_html__('Unauthorized', 'jawda'), ['response' => 403]);
        }
        $service = jawda_developers_service();
        if (isset($_GET['reactivate'])) {
            $service->update_developer($id, ['is_active' => 1]);
        } else {
            $service->delete_developer($id);
        }
        wp_safe_redirect(remove_query_arg(['action', 'id', '_wpnonce', 'reactivate']));
        exit;
    }
});
