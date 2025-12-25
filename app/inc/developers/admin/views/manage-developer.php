<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = 'wp_jawda_developers';
$dev_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// معالجة الحفظ (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_developer'])) {
    $data = [
        'name_ar' => $_POST['name_ar'],
        'name_en' => $_POST['name_en'],
        'slug'    => $_POST['slug'],
        'slug_ar' => $_POST['slug_ar'],
        'logo'    => $_POST['logo'],
        'logo_alt_ar' => $_POST['logo_alt_ar'],
        'logo_alt_en' => $_POST['logo_alt_en'],
        'seo_title_ar' => $_POST['seo_title_ar'],
        'seo_title_en' => $_POST['seo_title_en'],
        'seo_desc_ar'  => $_POST['seo_desc_ar'],
        'seo_desc_en'  => $_POST['seo_desc_en'],
    ];

    if ($dev_id) {
        $wpdb->update($table_name, $data, ['id' => $dev_id]);
        echo '<div class="notice notice-success is-dismissible"><p>✅ تم تحديث بيانات المطور بنجاح.</p></div>';
    } else {
        $wpdb->insert($table_name, $data);
        $dev_id = $wpdb->insert_id;
        echo '<div class="notice notice-success is-dismissible"><p>✅ تم إضافة المطور الجديد بنجاح.</p></div>';
    }
}

$developer = $dev_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $dev_id)) : null;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo $dev_id ? 'تعديل مطور' : 'إضافة مطور جديد'; ?></h1>
    <a href="<?php echo admin_url('admin.php?page=jawda-developers'); ?>" class="page-title-action">العودة لكل المطورين</a>
    <hr class="wp-header-end">

    <form method="post" style="margin-top:20px;">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                
                <div id="post-body-content">
                    
                    <div class="postbox">
                        <h2 class="hndle"><span>البيانات الأساسية (Names & Slugs)</span></h2>
                        <div class="inside">
                            <div style="display:flex; gap:20px;">
                                <div style="flex:1;">
                                    <label><b>الاسم بالعربي</b></label>
                                    <input type="text" name="name_ar" value="<?php echo $developer ? esc_attr($developer->name_ar) : ''; ?>" class="large-text" required>
                                </div>
                                <div style="flex:1;">
                                    <label><b>Name in English</b></label>
                                    <input type="text" name="name_en" value="<?php echo $developer ? esc_attr($developer->name_en) : ''; ?>" class="large-text">
                                </div>
                            </div>
                            <div style="display:flex; gap:20px; margin-top:15px;">
                                <div style="flex:1;">
                                    <label>Slug (AR)</label>
                                    <input type="text" name="slug_ar" value="<?php echo $developer ? esc_attr($developer->slug_ar) : ''; ?>" class="large-text">
                                </div>
                                <div style="flex:1;">
                                    <label>Slug (EN)</label>
                                    <input type="text" name="slug" value="<?php echo $developer ? esc_attr($developer->slug) : ''; ?>" class="large-text">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span>إعدادات محركات البحث (SEO Factory Output)</span></h2>
                        <div class="inside" style="display:flex; gap:20px; background:#f6f7f7; padding:15px;">
                            <div style="flex:1; border-left:1px solid #ddd; padding-left:15px;">
                                <h4 style="color:#2271b1">العربية (AR)</h4>
                                <label>SEO Title</label>
                                <input type="text" name="seo_title_ar" value="<?php echo $developer ? esc_attr($developer->seo_title_ar) : ''; ?>" class="large-text">
                                <label>Meta Description</label>
                                <textarea name="seo_desc_ar" rows="4" class="large-text"><?php echo $developer ? esc_textarea($developer->seo_desc_ar) : ''; ?></textarea>
                            </div>
                            <div style="flex:1;">
                                <h4 style="color:#2271b1">English (EN)</h4>
                                <label>Meta Title</label>
                                <input type="text" name="seo_title_en" value="<?php echo $developer ? esc_attr($developer->seo_title_en) : ''; ?>" class="large-text">
                                <label>Meta Description</label>
                                <textarea name="seo_desc_en" rows="4" class="large-text"><?php echo $developer ? esc_textarea($developer->seo_desc_en) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><span>اللوجو والوسائط</span></h2>
                        <div class="inside">
                            <label><b>رابط الصورة (Logo URL)</b></label>
                            <input type="text" name="logo" value="<?php echo $developer ? esc_attr($developer->logo) : ''; ?>" class="large-text">
                            <hr>
                            <label>Alt Text (AR)</label>
                            <input type="text" name="logo_alt_ar" value="<?php echo $developer ? esc_attr($developer->logo_alt_ar) : ''; ?>" class="large-text" placeholder="وصف الصورة للعربي">
                            <label style="display:block; margin-top:10px;">Alt Text (EN)</label>
                            <input type="text" name="logo_alt_en" value="<?php echo $developer ? esc_attr($developer->logo_alt_en) : ''; ?>" class="large-text" placeholder="Alt for English">
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="inside">
                            <input type="submit" name="submit_developer" class="button button-primary button-large" style="width:100%" value="حفظ بيانات المطور">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
