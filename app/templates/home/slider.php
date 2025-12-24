<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_slider(){

  ob_start();

  // FIX: define $langn before using it (Polylang/WPML fallback)
  $langn = (function_exists('pll_current_language') ? pll_current_language() : (defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : (is_rtl() ? 'ar' : 'en')));

  $cache_key = 'jawda_home_slider_html_' . $langn;

  $cached = get_transient($cache_key);
  if ($cached !== false) {
    echo $cached;
    return;
  }

  // Get latest 10 projects in current language (Polylang supported if exists)
  $args = array(
    'post_type'      => 'projects',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
  );

  // If Polylang exists, enforce language on query
  if ( function_exists('pll_current_language') ) {
    $args['lang'] = $langn;
  }

  $q = new WP_Query($args);

  if( $q->have_posts() ):
  ?>

  <!--Banner-->
  <div class="home-banner">
    <div class="container-fluid">
      <div class="row no-padding">
        <div id="banner-slider" class="col-md-12">

          <?php
            while ( $q->have_posts() ) {
              $q->the_post();
              $project_id = get_the_ID();

              $slideimage = get_the_post_thumbnail_url($project_id,'large');
              $slidetitle = get_the_title($project_id);
              $slidelink  = get_the_permalink($project_id);
          ?>

            <div class="slide">
              <div class="cover"></div>
              <img loading="lazy" src="<?php echo esc_url($slideimage); ?>" width="1600" height="767" alt="<?php echo esc_attr($slidetitle); ?>">
              <div class="container">
                <div class="banner-data">
                  <span class="data1"><a href="<?php echo esc_url($slidelink); ?>"><?php echo esc_html($slidetitle); ?></a></span>
                  <a href="<?php echo esc_url($slidelink); ?>" class="banner-btn"><?php get_text('المزيد من التفاصيل','More details'); ?></a>
                </div>
              </div>
            </div>

          <?php } wp_reset_postdata(); ?>

        </div>
      </div>
    </div>
  </div>
  <!--End Banner-->
  <?php get_search_box(); ?>
  <?php
  endif;

  $content = ob_get_clean();
  $content = minify_html($content);
  set_transient($cache_key, $content, 10 * MINUTE_IN_SECONDS);
  echo $content;
}

function get_search_box()
{
  ?>
  <!--Hero Search-->
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<div class="hero-search">
					<ul class="tabs">
            <li class="tab-link current" data-tab="tab-1"><?php get_text('المشروعات','Projects'); ?></li>
            <li class="tab-link" data-tab="tab-2"><?php get_text('الوحدات','Properties'); ?></li>
					</ul>

					<div id="tab-1" class="tab-content current">
            <?php jawda_projects_search_box(); ?>
					</div>

					<div id="tab-2" class="tab-content">
            <?php jawda_property_search_box(); ?>
					</div>

				</div>
        <!--
        <div class="advanced"><a href="<?php jawda_home_link(); ?>/?s="><i class="icon-plus"></i> بحث متقدم</a></div>
      -->
    </div>
		</div>
	</div>
	<!--End Search-->
  <?php
}

function jawda_projects_search_box()
{
    $projects_catalog_url = get_post_type_archive_link('projects');
  ?>
  <form method="GET" action="<?php echo esc_url($projects_catalog_url); ?>">
    <input type="hidden" name="hero_search" value="1">
    <div class="wpas-field">
      <input name="s" placeholder="<?php get_text('ابحث عن مشروع, مطور, او منطقة','Search for project, developer, or area'); ?>" class="search-input search-autocomplete-projects" aria-label="project-name">
    </div>
    <div class="wpas-submit-field wpas-field">
      <input type="submit" class="search-submit" value="<?php get_text('بحث','Search'); ?>">
    </div>
  </form>
  <?php
}

function jawda_property_search_box()
{
  ?>
  <form method="GET" action="<?php echo home_url( '/' ); ?>">
    <input type="hidden" name="st" value="2">
    <div class="wpas-field">
      <input name="s" placeholder="<?php get_text('ابحث عن','Search for'); ?>" class="search-input search-autocomplete-properties" aria-label="project-name">
    </div>
    <div class="wpas-field">
      <?php $projects_area = get_terms( array( 'taxonomy' => 'property_city','hide_empty' => true,'parent' => 0) ); ?>
      <select name="city" class="wpas-select search-select">
        <option selected disabled><?php get_text('الموقع','City'); ?></option>
        <?php if ( is_array($projects_area) AND !empty($projects_area) ): ?>
          <?php foreach ($projects_area as $area): ?>
            <option value="<?php echo $area->term_id; ?>"><?php echo $area->name; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>
    <div class="wpas-field">
        <?php
        global $wpdb;
        $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
        $name_col = $is_ar ?  'slug_ar'  : 'name_en';
        $property_types = $wpdb->get_results("SELECT id, {$name_col} as name FROM {$wpdb->prefix}property_types ORDER BY {$name_col} ASC");
        ?>
        <select name="type" class="wpas-select search-select">
            <option value=""><?php get_text('نوع الوحدة','Type'); ?></option>
            <?php if (!empty($property_types)) : ?>
                <?php foreach ($property_types as $type) : ?>
                    <option value="<?php echo esc_attr($type->id); ?>"><?php echo esc_html($type->name); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="wpas-submit-field wpas-field">
      <input type="submit" class="search-submit" value="<?php get_text('بحث','Search'); ?>">
    </div>
  </form>
  <?php
}
