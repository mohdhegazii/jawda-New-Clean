<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }

if (empty($GLOBALS['jawda_is_rendering_developer_template'])) {
    return;
}

$developer = $GLOBALS['jawda_current_developer'];
$is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
$lang = $is_ar ? 'ar' : 'en';
$jawda_page_projects = function_exists('carbon_get_theme_option')
    ? carbon_get_theme_option('jawda_page_properties_' . $lang)
    : null;
$developer_name = $is_ar ? ($developer['name_ar'] ?? '') : ($developer['name_en'] ?? '');
$developer_description = $is_ar ? ($developer['description_ar'] ?? '') : ($developer['description_en'] ?? '');

if (function_exists('get_my_header')) {
    get_my_header();
} else {
    get_header();
}

if (function_exists('get_projects_top_search')) {
    get_projects_top_search();
}

?>
<div class="unit-hero">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="unit-info">
                    <div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">
                        <?php $i = 1; ?>
                        <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                            <a class="breadcrumbs__link" href="<?php echo siteurl; ?>" itemprop="item"><span itemprop="name"><i class="fa-solid fa-house"></i></span></a>
                            <meta itemprop="position" content="<?php echo $i; $i++; ?>">
                        </span>
                        <span class="breadcrumbs__separator">›</span>
                        <?php if ($jawda_page_projects) : ?>
                            <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                                <a class="breadcrumbs__link" href="<?php echo esc_url(get_page_link($jawda_page_projects)); ?>" itemprop="item">
                                    <span itemprop="name"><?php echo esc_html(get_the_title($jawda_page_projects)); ?></span>
                                </a>
                                <meta itemprop="position" content="<?php echo $i; $i++; ?>">
                            </span>
                            <span class="breadcrumbs__separator">›</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="project-headline"><?php echo esc_html($developer_name); ?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

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
<?php if (!empty($developer_description)) : ?>
    <div class="project-main">
        <div class="container">
            <div class="content-box">
                <?php echo apply_filters('the_content', $developer_description); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php
$GLOBALS['wp_query'] = $previous_wp_query;

if (function_exists('get_my_footer')) {
    get_my_footer();
} else {
    get_footer();
}
