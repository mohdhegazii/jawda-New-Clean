<?php
if (!defined('ABSPATH')) exit;

function jawda_render_seo_templates_page() {
    global $wpdb;
    $table_name = 'wp_jawda_seo_templates';
    $message = "";

    // --- 1. معالجة الإضافة الجديدة ---
    if (isset($_POST['submit_new_template'])) {
        $wpdb->insert($table_name, [
            'lang'        => sanitize_text_field($_POST['new_lang']),
            'type'        => sanitize_text_field($_POST['new_type']),
            'entity_type' => sanitize_text_field($_POST['new_entity']),
            'content'     => $_POST['new_content']
        ]);
        $message = '<div class="notice notice-success is-dismissible"><p>✅ تم إضافة القالب الجديد بنجاح.</p></div>';
    }

    // --- 2. معالجة العمليات (حذف وتحديث) ---
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if ($_GET['action'] == 'delete') {
            $wpdb->delete($table_name, ['id' => $id]);
            $message = '<div class="notice notice-success is-dismissible"><p>✅ تم حذف القالب.</p></div>';
        }
    }
    if (isset($_POST['update_template'])) {
        $wpdb->update($table_name, ['content' => $_POST['template_content']], ['id' => intval($_POST['template_id'])]);
        $message = '<div class="notice notice-success is-dismissible"><p>✅ تم تحديث القالب.</p></div>';
    }

    // --- 3. التحكم في الفلاتر والتبويبات ---
    $active_entity = isset($_GET['entity']) ? sanitize_text_field($_GET['entity']) : 'developer';
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'title';
    $lang_filter = isset($_GET['lang_filter']) ? sanitize_text_field($_GET['lang_filter']) : '';
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    $query_where = "WHERE entity_type = %s AND type = %s";
    $query_args = [$active_entity, $active_tab];
    if ($lang_filter) {
        $query_where .= " AND lang = %s";
        $query_args[] = $lang_filter;
    }

    $total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name $query_where", ...$query_args));
    $templates = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $query_where ORDER BY id DESC LIMIT %d OFFSET %d", ...array_merge($query_args, [$per_page, $offset])));
    $total_pages = ceil($total_items / $per_page);

    echo $message;

    // --- واجهة إضافة قالب جديد ---
    if (isset($_GET['action']) && $_GET['action'] == 'add_new'): ?>
        <div style="background:#fff; border:2px solid #00a32a; padding:20px; margin-bottom:20px; border-radius:5px;">
            <h3>➕ إضافة قالب SEO جديد</h3>
            <form method="post" action="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>">
                <div style="display:flex; gap:15px; margin-bottom:10px;">
                    <select name="new_lang" required><option value="ar">العربية</option><option value="en">English</option></select>
                    <select name="new_type" required><option value="title">Title</option><option value="description">Description</option></select>
                    <select name="new_entity" required>
                        <option value="developer" <?php selected($active_entity, 'developer'); ?>>المطورين</option>
                        <option value="project" <?php selected($active_entity, 'project'); ?>>المشروعات</option>
                        <option value="unit" <?php selected($active_entity, 'unit'); ?>>الوحدات</option>
                        <option value="catalog" <?php selected($active_entity, 'catalog'); ?>>الكتالوجات</option>
                    </select>
                </div>
                <textarea name="new_content" style="width:100%;" rows="3" placeholder="استخدم {name} و {year}" required></textarea>
                <div style="margin-top:10px;">
                    <input type="submit" name="submit_new_template" class="button button-primary" value="إضافة القالب">
                    <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>" class="button">إلغاء</a>
                </div>
            </form>
        </div>
    <?php endif;

    // صندوق التعديل
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])):
        $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
        if ($edit_item): ?>
            <div style="background:#fff; border:2px solid #2271b1; padding:20px; margin-bottom:20px; border-radius:5px;">
                <h3>🛠️ تعديل القالب #<?php echo $edit_item->id; ?></h3>
                <form method="post" action="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>">
                    <input type="hidden" name="template_id" value="<?php echo $edit_item->id; ?>">
                    <textarea name="template_content" style="width:100%;" rows="3"><?php echo esc_textarea($edit_item->content); ?></textarea>
                    <div style="margin-top:10px;">
                        <input type="submit" name="update_template" class="button button-primary" value="حفظ">
                        <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>" class="button">إلغاء</a>
                    </div>
                </form>
            </div>
        <?php endif;
    endif;
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">⚙️ SEO Factory Templates</h1>
        <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>&action=add_new" class="page-title-action">Add New Template</a>
        <hr class="wp-header-end">

        <h2 class="nav-tab-wrapper" style="margin-top:20px;">
            <a href="?page=jawda-seo-templates&entity=developer" class="nav-tab <?php echo $active_entity == 'developer' ? 'nav-tab-active' : ''; ?>">🏗️ المطورين</a>
            <a href="?page=jawda-seo-templates&entity=project" class="nav-tab <?php echo $active_entity == 'project' ? 'nav-tab-active' : ''; ?>">🏢 المشروعات</a>
            <a href="?page=jawda-seo-templates&entity=unit" class="nav-tab <?php echo $active_entity == 'unit' ? 'nav-tab-active' : ''; ?>">🏠 الوحدات</a>
            <a href="?page=jawda-seo-templates&entity=catalog" class="nav-tab <?php echo $active_entity == 'catalog' ? 'nav-tab-active' : ''; ?>">📚 الكتالوجات</a>
        </h2>

        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 10px 15px; margin: 0; display: flex; align-items: center; justify-content: space-between; border-top:none;">
            <form method="get" style="display: flex; align-items: center; gap: 15px;">
                <input type="hidden" name="page" value="jawda-seo-templates">
                <input type="hidden" name="entity" value="<?php echo $active_entity; ?>">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                
                <label><b>اللغة:</b></label>
                <select name="lang_filter">
                    <option value="">الكل</option>
                    <option value="ar" <?php selected($lang_filter, 'ar'); ?>>العربية</option>
                    <option value="en" <?php selected($lang_filter, 'en'); ?>>EN</option>
                </select>

                <label><b>العناصر:</b></label>
                <input type="number" name="per_page" value="<?php echo $per_page; ?>" style="width: 60px;">
                <input type="submit" class="button" value="تحديث">
            </form>
            <div><span>إجمالي القسم: <b><?php echo $total_items; ?></b></span></div>
        </div>

        <div style="margin-top: 20px;">
            <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=title" class="button <?php echo $active_tab == 'title' ? 'button-primary' : ''; ?>">📑 Titles</a>
            <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=description" class="button <?php echo $active_tab == 'description' ? 'button-primary' : ''; ?>">📝 Descriptions</a>
        </div>

        <div style="background: #fff; border: 1px solid #ccc; padding: 20px; margin-top:10px;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">اللغة</th>
                        <th>محتوى القالب</th>
                        <th style="width: 120px;">العمليات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($templates): foreach ($templates as $item): ?>
                    <tr>
                        <td>#<?php echo $item->id; ?></td>
                        <td><span class="lang-badge <?php echo $item->lang; ?>"><?php echo strtoupper($item->lang); ?></span></td>
                        <td style="font-size: 13px;"><?php echo esc_html($item->content); ?></td>
                        <td>
                            <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>&action=edit&id=<?php echo $item->id; ?>" class="button button-small">Edit</a>
                            <a href="?page=jawda-seo-templates&entity=<?php echo $active_entity; ?>&tab=<?php echo $active_tab; ?>&action=delete&id=<?php echo $item->id; ?>" class="button button-small" style="color:red;" onclick="return confirm('حذف؟');">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4">لا يوجد قوالب في هذا القسم.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php echo paginate_links(['base'=>add_query_arg('paged','%#%'),'format'=>'','total'=>$total_pages,'current'=>$current_page,'prev_text'=>'&laquo;','next_text'=>'&raquo;']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .lang-badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; color: #fff; font-weight: bold; }
        .lang-badge.ar { background: #00a32a; }
        .lang-badge.en { background: #2271b1; }
        .pagination-links a, .pagination-links span { padding: 5px 10px; border: 1px solid #ccd0d4; text-decoration: none; background: #f6f7f7; margin-left:2px;}
        .pagination-links span.current { background: #2271b1; color: #fff; }
    </style>
    <?php
}
