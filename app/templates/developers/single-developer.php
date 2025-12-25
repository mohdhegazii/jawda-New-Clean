<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }

// 1. بوابة الأمان وتجهيز البيانات
if (empty($GLOBALS['jawda_is_rendering_developer_template'])) return;
$developer = $GLOBALS['jawda_current_developer'];

// تعريف اللغة عشان الـ Warning يختفي
$is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : (strpos($_SERVER['REQUEST_URI'], '/en/') === false);

// 2. هيدر مشارف الأصلي
get_my_header();

// 3. استدعاء هيدر الصفحة (الهيرو والـ Breadcrumb والعنوان) من قلب الثيم
if (function_exists('get_my_property_header')) {
    get_my_property_header();
}

// 4. بناء جسم الصفحة باستخدام كلاسات الثيم الأصلية
?>
<div class="project-main">
    <div class="container">
        <div class="developer-description-wrapper" style="margin-bottom: 60px; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.05);">
            <div class="entry-content">
                <?php echo apply_filters('the_content', ($is_ar ? $developer['description_ar'] : $developer['description_en'])); ?>
            </div>
        </div>

        <div class="related-projects-section">
            <h3 class="section-title mb-5" style="border-right: 5px solid #2271b1; padding-right: 15px; font-weight: 700;">
                <?php echo $is_ar ? "مشروعات " . ($developer['name_ar'] ?? 'المطور') : "Projects by " . $developer['name_en']; ?>
            </h3>

            <?php 
            // 5. تشغيل ماكينة الكروت (project_box)
            require_once get_template_directory() . '/app/templates/boxs/project_box.php';

            $p_query = new WP_Query([
                'post_type'      => 'projects',
                'posts_per_page' => -1,
                'meta_query'     => [['key' => '_selected_developer_id', 'value' => $developer['id'], 'compare' => '=']]
            ]);

            if ($p_query->have_posts()) : ?>
                <div class="row">
                    <?php while ($p_query->have_posts()) : $p_query->the_post(); ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <?php 
                            // استدعاء الدالة الميكانيكية للثيم
                            if (function_exists('get_my_project_box')) {
                                echo get_my_project_box(get_the_ID());
                            }
                            ?>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <div class="no-projects-found text-center py-5">
                    <p class="text-muted"><?php echo $is_ar ? 'لا توجد مشروعات متاحة حالياً.' : 'No projects available.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// 6. فوتر مشارف
get_my_footer();
