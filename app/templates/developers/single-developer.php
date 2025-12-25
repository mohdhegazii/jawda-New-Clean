<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }

if (empty($GLOBALS['jawda_is_rendering_developer_template'])) {
    return;
}

$developer = $GLOBALS['jawda_current_developer'];
$is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

get_my_header();

if (function_exists('get_my_property_header')) {
    get_my_property_header();
}

$paged = max(1, get_query_var('paged'), get_query_var('page'));
$projects_query = new WP_Query([
    'post_type' => 'projects',
    'posts_per_page' => 9,
    'paged' => $paged,
    'meta_query' => [
        [
            'key' => '_selected_developer_id',
            'value' => $developer['id'],
            'compare' => '=',
        ],
    ],
]);

$previous_wp_query = $GLOBALS['wp_query'] ?? null;
$GLOBALS['wp_query'] = $projects_query;

require_once get_template_directory() . '/app/templates/boxs/project_box.php';
?>
<div class="project-main">
    <div class="container">
        <div class="content-box">
            <?php echo apply_filters('the_content', ($is_ar ? $developer['description_ar'] : $developer['description_en'])); ?>
        </div>
    </div>
</div>

<div class="units-page">
    <div class="container">
        <div class="row">
            <?php if ($projects_query->have_posts()) : ?>
                <?php while ($projects_query->have_posts()) : $projects_query->the_post(); ?>
                    <div class="col-md-4 projectbxspace">
                        <?php echo get_my_project_box(get_the_ID()); ?>
                    </div>
                <?php endwhile; ?>
                <div class="col-md-12 center">
                    <?php if (function_exists('theme_pagination')) {
                        theme_pagination();
                    } ?>
                </div>
            <?php else : ?>
                <div class="col-md-12 center">
                    <p><?php echo $is_ar ? 'لا توجد مشروعات متاحة حالياً.' : 'No projects available.'; ?></p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</div>
<?php
$GLOBALS['wp_query'] = $previous_wp_query;

get_my_footer();
