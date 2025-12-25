<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    $page = add_menu_page('Jawda Developers', 'Jawda Developers', 'manage_options', 'jawda-developers', 'jawda_render_developers_list', 'dashicons-businessman', 30);
    add_submenu_page('jawda-developers', 'Add New Developer', 'Add New Developer', 'manage_options', 'jawda-add-developer', 'jawda_render_developer_form');
    
    add_action("load-$page", function() {
        add_screen_option('per_page', [
            'label'   => 'Developers per page',
            'default' => 20,
            'option'  => 'developers_per_page'
        ]);
    });
});

add_filter('set-screen-option', function($status, $option, $value) {
    return ($option === 'developers_per_page') ? $value : $status;
}, 10, 3);

function jawda_smart_slug($title) {
    $slug = str_replace(' ', '-', trim($title));
    $slug = preg_replace('/[^\x{0600}-\x{06FF}a-zA-Z0-9\-]/u', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return mb_strtolower($slug, 'UTF-8');
}

function jawda_render_developers_list() {
    global $wpdb;
    $table_name = 'wp_jawda_developers';

    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->update($table_name, ['deleted_at' => current_time('mysql')], ['id' => intval($_GET['id'])]);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Deleted successfully.</p></div>';
    }

    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $user_id = get_current_user_id();
    $per_page = get_user_meta($user_id, 'developers_per_page', true) ?: 20;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    $where = "WHERE deleted_at IS NULL";
    if ($search) {
        $where .= $wpdb->prepare(" AND (name_ar LIKE %s OR name_en LIKE %s)", '%'.$search.'%', '%'.$search.'%');
    }

    $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where");
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset));
    $total_pages = ceil($total_items / $per_page);

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Developers Management</h1>
        <a href="admin.php?page=jawda-add-developer" class="page-title-action">Add New</a>
        <hr class="wp-header-end">

        <form method="get" style="margin: 20px 0; display: flex; gap: 10px;">
            <input type="hidden" name="page" value="jawda-developers">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name...">
            <input type="submit" class="button" value="Search">
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Name (AR)</th>
                    <th>Name (EN)</th>
                    <th style="width: 150px;">View Links</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($results): foreach($results as $row): 
                    $site_url = home_url("/");
                    $link_ar = $site_url . "مشروعات-جديدة/" . $row->slug_ar . "/";
                    $link_en = $site_url . "en/new-projects/" . $row->slug . "/";
                ?>
                <tr>
                    <td><?php echo $row->id; ?></td>
                    <td><strong><?php echo esc_html($row->name_ar); ?></strong></td>
                    <td><?php echo esc_html($row->name_en); ?></td>
                    <td>
                        <a href="<?php echo $link_ar; ?>" target="_blank" style="text-decoration:none; font-weight:bold; color:#00a32a;">🔗 AR</a>
                        <span style="color:#ccc; margin:0 5px;">|</span>
                        <a href="<?php echo $link_en; ?>" target="_blank" style="text-decoration:none; font-weight:bold; color:#2271b1;">🔗 EN</a>
                    </td>
                    <td>
                        <a href="admin.php?page=jawda-add-developer&id=<?php echo $row->id; ?>" class="button button-small">Edit</a>
                        <a href="admin.php?page=jawda-developers&action=delete&id=<?php echo $row->id; ?>" class="button button-small" style="color:red;" onclick="return confirm('Delete?');">Del</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No results found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'),'format' => '','total' => $total_pages,'current' => $paged]); ?>
                </span>
            </div>
        </div>
    </div>
    <?php
}

function jawda_render_developer_form() {
    global $wpdb;
    $table_name = 'wp_jawda_developers';
    $dev_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $is_edit = ($dev_id > 0);

    $old = $is_edit ? $wpdb->get_row($wpdb->prepare("SELECT name_ar, name_en FROM $table_name WHERE id = %d", $dev_id)) : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_jawda_dev'])) {
        $n_ar = trim($_POST['name_ar']);
        $n_en = trim($_POST['name_en']);
        $logo = trim($_POST['logo_id']);

        if (!empty($n_ar) && !empty($n_en) && !empty($logo)) {
            $year = date('Y');
            $changed = (!$old || $old->name_ar !== $n_ar || $old->name_en !== $n_en);

            $slug_ar = ($changed) ? jawda_smart_slug($n_ar . " للتطوير") : (!empty($_POST['slug_ar']) ? jawda_smart_slug($_POST['slug_ar']) : jawda_smart_slug($n_ar . " للتطوير"));
            $slug_en = ($changed) ? jawda_smart_slug($n_en . " developments") : (!empty($_POST['slug_en']) ? jawda_smart_slug($_POST['slug_en']) : jawda_smart_slug($n_en . " developments"));
            $alt_ar  = ($changed) ? "لوجو شركة " . $n_ar . " للتطوير العقاري" : $_POST['logo_alt_ar'];
            $alt_en  = ($changed) ? $n_en . " developments logo" : $_POST['logo_alt_en'];

            $st_ar = $_POST['seo_title_ar']; $sd_ar = $_POST['seo_desc_ar'];
            $st_en = $_POST['seo_title_en']; $sd_en = $_POST['seo_desc_en'];

            if($changed || empty($st_ar)) { 
                $tmp = $wpdb->get_var("SELECT content FROM wp_jawda_seo_templates WHERE lang='ar' AND type='title' AND entity_type='developer' ORDER BY RAND() LIMIT 1");
                $st_ar = str_replace(['{name}', '{year}'], [$n_ar, $year], $tmp);
            }
            if($changed || empty($sd_ar)) {
                $tmp = $wpdb->get_var("SELECT content FROM wp_jawda_seo_templates WHERE lang='ar' AND type='description' AND entity_type='developer' ORDER BY RAND() LIMIT 1");
                $sd_ar = str_replace(['{name}', '{year}'], [$n_ar, $year], $tmp);
            }
            if($changed || empty($st_en)) {
                $tmp = $wpdb->get_var("SELECT content FROM wp_jawda_seo_templates WHERE lang='en' AND type='title' AND entity_type='developer' ORDER BY RAND() LIMIT 1");
                $st_en = str_replace(['{name}', '{year}'], [$n_en, $year], $tmp);
            }
            if($changed || empty($sd_en)) {
                $tmp = $wpdb->get_var("SELECT content FROM wp_jawda_seo_templates WHERE lang='en' AND type='description' AND entity_type='developer' ORDER BY RAND() LIMIT 1");
                $sd_en = str_replace(['{name}', '{year}'], [$n_en, $year], $tmp);
            }

            $data = [
                'name_ar' => $n_ar, 'name_en' => $n_en, 'slug' => $slug_en, 'slug_ar' => $slug_ar,
                'logo' => $logo, 'logo_alt_ar' => $alt_ar, 'logo_alt_en' => $alt_en,
                'seo_title_ar' => $st_ar, 'seo_title_en' => $st_en, 'seo_desc_ar' => $sd_ar, 'seo_desc_en' => $sd_en,
                'description_ar' => $_POST['description_ar'], 'description_en' => $_POST['description_en']
            ];

            if ($is_edit) { $wpdb->update($table_name, $data, ['id' => $dev_id]); }
            else { $wpdb->insert($table_name, $data); $dev_id = $wpdb->insert_id; }
            echo '<div class="notice notice-success is-dismissible"><p>✅ Saved & Auto-Synced.</p></div>';
        }
    }

    $dev = $is_edit ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $dev_id)) : null;
    ?>
    <div class="wrap">
        <h1 style="font-weight:800; border-bottom: 2px solid #2271b1; padding-bottom:10px; margin-bottom:25px;">
            <?php echo $is_edit ? '🔄 UPDATE DEVELOPER' : '➕ SAVE NEW DEVELOPER'; ?>
        </h1>
        <form method="post">
            <input type="hidden" name="save_jawda_dev" value="1">
            <div id="poststuff"><div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox" style="padding:15px;"><div style="display:flex; gap:15px;">
                        <div style="flex:1;"><label><b>الاسم بالعربي *</b></label><input type="text" name="name_ar" value="<?php echo $dev?esc_attr($dev->name_ar):''; ?>" style="width:100%"></div>
                        <div style="flex:1;"><label><b>English Name *</b></label><input type="text" name="name_en" value="<?php echo $dev?esc_attr($dev->name_en):''; ?>" style="width:100%"></div>
                    </div></div>
                    <div class="postbox" style="padding:15px;"><label><b>Description</b></label>
                        <div style="margin-top:10px;"><?php wp_editor($dev?$dev->description_ar:'', 'description_ar', ['textarea_rows'=>6]); ?></div>
                        <div style="margin-top:20px;"><?php wp_editor($dev?$dev->description_en:'', 'description_en', ['textarea_rows'=>6]); ?></div>
                    </div>
                    <div class="postbox" style="padding:15px; background: #f0f6fb; border: 1px solid #2271b1;">
                        <h3 style="margin:0 0 10px 0; color:#2271b1;">SEO Output</h3>
                        <div style="display:flex; gap:20px;">
                            <div style="flex:1;"><label>Title (AR)</label><input type="text" name="seo_title_ar" value="<?php echo $dev?esc_attr($dev->seo_title_ar):''; ?>" style="width:100%">
                            <textarea name="seo_desc_ar" style="width:100%; margin-top:5px;" rows="3"><?php echo $dev?esc_textarea($dev->seo_desc_ar):''; ?></textarea></div>
                            <div style="flex:1;"><label>Title (EN)</label><input type="text" name="seo_title_en" value="<?php echo $dev?esc_attr($dev->seo_title_en):''; ?>" style="width:100%">
                            <textarea name="seo_desc_en" style="width:100%; margin-top:5px;" rows="3"><?php echo $dev?esc_textarea($dev->seo_desc_en):''; ?></textarea></div>
                        </div>
                    </div>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox" style="padding: 15px;"><label><b>Logo *</b></label>
                        <div id="logo-preview" style="background:#f9f9f9; height:120px; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; margin:10px 0;">
                            <?php if($dev && $dev->logo) { $url = is_numeric($dev->logo) ? wp_get_attachment_url($dev->logo) : $dev->logo; echo '<img src="'.$url.'" style="max-height:100%;">'; } ?>
                        </div>
                        <input type="hidden" name="logo_id" id="logo_id" value="<?php echo $dev?$dev->logo:''; ?>">
                        <button type="button" class="button" id="upload-logo-btn" style="width:100%">Choose Logo</button>
                        <div style="margin-top:10px;"><input type="text" name="logo_alt_ar" value="<?php echo $dev?esc_attr($dev->logo_alt_ar):''; ?>" placeholder="Alt AR" style="width:100%"><input type="text" name="logo_alt_en" value="<?php echo $dev?esc_attr($dev->logo_alt_en):''; ?>" placeholder="Alt EN" style="width:100%; margin-top:5px;"></div>
                    </div>
                    <div class="postbox" style="padding: 15px;"><label><b>Slugs</b></label>
                        <input type="text" name="slug_ar" value="<?php echo $dev?esc_attr(urldecode($dev->slug_ar)):''; ?>" style="width:100%; margin-bottom:5px;">
                        <input type="text" name="slug_en" value="<?php echo $dev?esc_attr($dev->slug):''; ?>" style="width:100%">
                    </div>
                    <div class="postbox" style="padding: 10px;"><input type="submit" name="save_jawda_dev" class="button button-primary button-large" value="<?php echo $is_edit ? '🔄 UPDATE DEVELOPER' : '➕ SAVE NEW DEVELOPER'; ?>" style="width:100%; height:45px;"></div>
                </div>
            </div></div>
        </form>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('#upload-logo-btn').on('click', function(e) {
            e.preventDefault();
            var frame = wp.media({ title: 'Select Logo', button: { text: 'Use Logo' }, multiple: false }).on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#logo_id').val(attachment.id);
                $('#logo-preview').html('<img src="'+attachment.url+'" style="max-height:100%">');
            }).open();
        });
    });
    </script>
    <?php
}
