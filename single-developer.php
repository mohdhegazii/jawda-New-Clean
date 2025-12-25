<?php
/**
 * Template Name: Single Developer Custom
 */
get_header(); 

global $current_dev_data;
$dev = $current_dev_data;

if ($dev) :
    $is_en = (strpos($_SERVER['REQUEST_URI'], '/en/') !== false);
    $name = $is_en ? $dev->name_en : $dev->name_ar;
    $desc = $is_en ? $dev->description_en : $dev->description_ar;
    $logo_url = is_numeric($dev->logo) ? wp_get_attachment_url($dev->logo) : $dev->logo;
?>

<div id="primary" class="content-area container" style="margin-top: 50px; margin-bottom: 50px;">
    <main id="main" class="site-main">
        <header class="entry-header text-center" style="margin-bottom: 40px;">
            <?php if ($logo_url): ?>
                <img src="<?php echo $logo_url; ?>" alt="<?php echo esc_attr($name); ?>" style="max-width: 180px; margin-bottom: 20px;">
            <?php endif; ?>
            <h1 class="entry-title" style="font-weight: 800;"><?php echo esc_html($name); ?></h1>
        </header>

        <div class="entry-content">
            <div class="developer-bio mb-5" style="line-height: 1.8; font-size: 16px;">
                <?php echo apply_filters('the_content', $desc); ?>
            </div>

            <h3 style="border-right: 5px solid #2271b1; padding-right: 15px; margin-bottom: 30px;">
                <?php echo $is_en ? "Projects" : "مشروعات " . $name; ?>
            </h3>

            <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -15px;">
                <?php
                $p_query = new WP_Query([
                    'post_type' => 'projects',
                    'posts_per_page' => -1,
                    'meta_query' => [['key' => '_selected_developer_id', 'value' => $dev->id, 'compare' => '=']]
                ]);

                if ($p_query->have_posts()) : 
                    while ($p_query->have_posts()) : $p_query->the_post(); ?>
                        <div class="col-md-4" style="flex: 0 0 33.333%; padding: 15px; box-sizing: border-box;">
                            <div class="project-card" style="border: 1px solid #eee; border-radius: 12px; overflow: hidden; background: #fff; height: 100%;">
                                <?php if (has_post_thumbnail()): ?>
                                    <div style="height: 200px; overflow: hidden;">
                                        <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium_large', ['style' => 'width:100%; height:100%; object-fit:cover;']); ?></a>
                                    </div>
                                <?php endif; ?>
                                <div style="padding: 20px; text-align: center;">
                                    <h4 style="font-size: 18px; margin-bottom: 15px;"><a href="<?php the_permalink(); ?>" style="color: #333; text-decoration: none;"><?php the_title(); ?></a></h4>
                                    <a href="<?php the_permalink(); ?>" style="background: #2271b1; color: #fff; padding: 8px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">التفاصيل</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="col-12"><p class="text-center">لا توجد مشاريع مرتبطة حالياً.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php 
endif;
get_footer(); 
