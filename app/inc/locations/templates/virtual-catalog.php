<?php
/**
 * Virtual Catalog Template for Dynamic Location Routing.
 * Mimics the layout of single-catalogs.php but works with the Main Query.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_my_header();

// Add Search Bar (Top)
if ( function_exists( 'get_projects_top_search' ) ) {
    get_projects_top_search();
}

// --- Context Setup ---
global $jawda_current_location_context;
$context = $jawda_current_location_context ?? [];
$lang = function_exists('jawda_is_arabic_locale') && jawda_is_arabic_locale() ? 'ar' : 'en';
$seo_data = function_exists('jawda_get_current_catalog_seo') ? jawda_get_current_catalog_seo(false) : null;

// Helper wrapper to ensure we use the centralized logic
function _jawda_loc_url($gov=null, $city=null, $dist=null) {
    global $lang;
    if (function_exists('jawda_get_new_projects_url_by_location')) {
        return jawda_get_new_projects_url_by_location($gov, $city, $dist, $lang);
    }
    return home_url('/');
}

// Title & Name
$title = '';
$short_name = '';

if ( isset($context['level']) && $context['level'] === 'country' ) {
    $short_name = ($lang === 'ar') ? 'مصر' : 'Egypt';
    $title = ($lang === 'ar') ? 'مشاريع جديدة في مصر' : 'New Projects in Egypt';
} elseif ( isset($context['data']) && is_object($context['data']) ) {
    $data = $context['data'];
    $short_name = ($lang === 'ar') ? $data->name_ar : $data->name_en;
    // Title format mimicking legacy: "New Projects in {Name}"
    $title = ($lang === 'ar') ? 'مشاريع جديدة في ' . $short_name : 'New Projects in ' . $short_name;
}

if ($seo_data && !empty($seo_data['title']) && !empty($seo_data['is_override'])) {
    $title = $seo_data['title'];
}

$catalog_content = '';
$catalog_image = '';
if ($seo_data) {
    $service = function_exists('jawda_catalog_seo_service') ? jawda_catalog_seo_service() : null;
    $fallback_content = ($service && !empty($seo_data['context'])) ? $service->get_content($seo_data['context'], true) : '';

    if (!empty($seo_data['is_override']) && !empty($seo_data['content'])) {
        $catalog_content = $seo_data['content'];
    } elseif (!empty($fallback_content)) {
        $catalog_content = $fallback_content;
    }
    if (!empty($seo_data['featured_image_url'])) {
        $catalog_image = $seo_data['featured_image_url'];
    }
}

// Lightweight styling to align the intro block with existing catalog layout.
?>
<style>
.catalog-seo-intro { margin: 30px 0; }
.catalog-seo-intro__image { background-size: cover; background-position: center; border-radius: 6px; padding-top: 65%; }
.catalog-seo-intro__content { background: #fff; padding: 15px; border-radius: 6px; }
</style>
<?php

// --- Breadcrumbs Construction ---
$breadcrumbs = [];
// Home icon is now handled separately in the template loop to apply the sticky wrapper
// so we do NOT add it to the $breadcrumbs array here.

// Root Link
$root_link = _jawda_loc_url(null, null, null);

// If we are deeper than root, add Root as a link
if ( isset($context['level']) && $context['level'] !== 'country' ) {
    $breadcrumbs[] = [
        'name' => ($lang === 'ar') ? 'مشاريع مصر' : 'Egypt Projects',
        'link' => $root_link
    ];
}

// Add Hierarchy (using IDs and Helper instead of manual string building)
if ( isset($context['grandparent']) ) {
    $gp = $context['grandparent'];
    $gp_name = ($lang === 'ar') ? $gp->name_ar : $gp->name_en;
    // Grandparent is typically Governorate (level 1)
    $link = _jawda_loc_url($gp->id, null, null);
    $breadcrumbs[] = ['name' => $gp_name, 'link' => $link];
}

if ( isset($context['parent']) ) {
    $p = $context['parent'];
    $p_name = ($lang === 'ar') ? $p->name_ar : $p->name_en;
    $link = '';

    // Parent can be Gov or City.
    if ($context['level'] === 'city') {
        // Current is City -> Parent is Gov
        $link = _jawda_loc_url($p->id, null, null);
    } elseif ($context['level'] === 'district') {
        // Current is District -> Parent is City
        $link = _jawda_loc_url(null, $p->id, null);
    }

    $breadcrumbs[] = ['name' => $p_name, 'link' => $link];
}

// Current Item (No Link)
$breadcrumbs[] = ['name' => $short_name, 'link' => null];

?>

<div class="unit-hero">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="unit-info">
                    <!-- Custom Breadcrumbs -->
                    <!-- Custom Breadcrumbs -->
                    <div class="breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">
                        <div class="sticky-home-wrapper">
                            <span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem" class="breadcrumb-item">
                                <a class="breadcrumbs__link" href="<?php echo home_url('/'); ?>" itemprop="item">
                                    <span itemprop="name"><i class="fa-solid fa-house"></i></span>
                                </a>
                                <meta itemprop="position" content="1">
                            </span>
                            <span class="breadcrumbs__separator">›</span>
                        </div>

                        <?php
                        $is_first = true;
                        foreach ($breadcrumbs as $i => $crumb):
                            // The first item in the loop follows the sticky wrapper.
                            // The wrapper already has a separator, so we skip it for the very first item.
                            if (!$is_first) {
                                echo '<span class="breadcrumbs__separator">›</span>';
                            }
                            $is_first = false;
                        ?>
                            <span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem" class="breadcrumb-item <?php echo $crumb['link'] ? 'expandable' : 'active'; ?>">
                                <?php if ($crumb['link']): ?>
                                    <a class="breadcrumbs__link" href="<?php echo esc_url($crumb['link']); ?>" itemprop="item">
                                        <span itemprop="name"><?php echo !empty($crumb['is_html']) ? $crumb['name'] : esc_html($crumb['name']); ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="breadcrumb-item-current"><?php echo !empty($crumb['is_html']) ? $crumb['name'] : esc_html($crumb['name']); ?></span>
                                <?php endif; ?>
                                <meta itemprop="position" content="<?php echo $i + 2; ?>">
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <h1 class="project-headline">
                        <?php echo esc_html($title); ?>
                        <?php
                        $paged = max( 1, get_query_var('paged'), get_query_var('page') );
                        if ( $paged > 1 ) {
                            echo ($lang === 'ar') ? " - صفحة $paged" : " - Page $paged";
                        }
                        ?>
                    </h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="units-page">
    <div class="container">
        <div class="row">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <div class="col-md-4 projectbxspace">
                        <?php get_my_project_box(get_the_ID()); ?>
                    </div>
                <?php endwhile; ?>

                <!-- Custom Pagination Style (Matches Legacy Catalog) -->
                <div class="col-md-12 center">
                    <div class="blognavigation">
                        <?php
                        echo paginate_links([
                            'base'      => str_replace( 999999999, '%#%', get_pagenum_link( 999999999 ) ),
                            'format'    => '',
                            'current'   => max( 1, get_query_var('paged') ),
                            'total'     => $wp_query->max_num_pages,
                            'mid_size'  => 2,
                            'end_size'  => 1,
                            'prev_text' => ($lang === 'ar') ? '« السابق' : '« Previous',
                            'next_text' => ($lang === 'ar') ? 'التالي »' : 'Next »',
                            'type'      => 'plain',
                        ]);

?>
                    </div>

                    <?php if (!empty($catalog_content)) : ?>
                        <div class="row catalog-seo-intro__row">
                            <div class="col-md-12">
                                <div class="catalog-seo-intro__content">
                                    <?php echo wp_kses_post($catalog_content); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

            <?php else: ?>
                <div class="col-md-12">
                    <p><?php echo ($lang === 'ar') ? 'لا توجد مشاريع متاحة حالياً.' : 'No projects available at the moment.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_my_footer(); ?>
