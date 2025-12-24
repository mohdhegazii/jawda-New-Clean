<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }





/**
 * Get top city IDs ordered by number of published projects under them (meta: loc_city_id),
 * respecting language (Polylang if available). Cached for performance.
 */
function jawda_get_top_cities_by_project_count($lang, $limit = 8) {
    global $wpdb;

    $lang  = $lang ?: (function_exists('pll_current_language') ? pll_current_language() : (is_rtl() ? 'ar' : 'en'));
    $limit = max(1, (int) $limit);

    $cache_key = 'jawda_top_cities_by_projects_' . $lang . '_' . $limit;
    $cached = get_transient($cache_key);
    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    $posts = $wpdb->posts;
    $pm    = $wpdb->postmeta;
    $tr    = $wpdb->term_relationships;
    $tt    = $wpdb->term_taxonomy;
    $t     = $wpdb->terms;

    $sql = "
        SELECT pm.meta_value AS city_id, COUNT(DISTINCT p.ID) AS cnt
        FROM {$posts} p
        INNER JOIN {$pm} pm
            ON pm.post_id = p.ID
           AND pm.meta_key = 'loc_city_id'
    ";

    if (function_exists('pll_current_language')) {
        $sql .= "
        INNER JOIN {$tr} tr ON tr.object_id = p.ID
        INNER JOIN {$tt} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'language'
        INNER JOIN {$t} t ON t.term_id = tt.term_id AND t.slug = %s
        ";
    }

    $sql .= "
        WHERE p.post_type = 'projects'
          AND p.post_status = 'publish'
          AND pm.meta_value <> ''
        GROUP BY pm.meta_value
        ORDER BY cnt DESC
        LIMIT {$limit}
    ";

    $rows = function_exists('pll_current_language')
        ? $wpdb->get_results($wpdb->prepare($sql, $lang))
        : $wpdb->get_results($sql);

    $ids = array();
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $ids[] = (int) $r->city_id;
        }
    }

    set_transient($cache_key, $ids, 10 * MINUTE_IN_SECONDS);
    return $ids;
}

/**
 * Count published projects for a given area term, respecting current language (Polylang if available).
 * Cached for performance.
 */
function jawda_count_projects_for_area_term($term_id, $taxonomy = 'project_city') {
    $lang = function_exists('pll_current_language') ? pll_current_language() : (is_rtl() ? 'ar' : 'en');
    $cache_key = 'jawda_area_projects_count_' . (int)$term_id . '_' . $lang;

    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return (int) $cached;
    }

    $args = [
        'post_type'      => 'projects',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'tax_query'      => [
            [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => [(int)$term_id],
            ]
        ],
    ];

    if (function_exists('pll_current_language')) {
        $args['lang'] = $lang;
    }

    $q = new WP_Query($args);
    $count = (int) $q->found_posts;
    wp_reset_postdata();

    set_transient($cache_key, $count, 10 * MINUTE_IN_SECONDS);
    return $count;
}

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_featured_areas(){

  ob_start();

  $lang = function_exists('pll_current_language') ? pll_current_language() : (is_rtl() ? 'ar' : 'en');

  $featured_areas_ids = jawda_get_top_cities_by_project_count($lang, 8);
  if( !empty($featured_areas_ids) ):
    global $wpdb;
  ?>

  <!--Featured Area-->
	<div class="featured-area">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="headline">
						<div class="main-title"><?php get_text('المدن الاكثر شهرة','Most popular cities'); ?></div>
					</div>
				</div>
			</div>
			<div class="row">

        <?php
	  foreach ($featured_areas_ids as $city_id) {
          if (function_exists('jawda_get_new_projects_url_by_location')) {
              $city_link = jawda_get_new_projects_url_by_location('city', $city_id, $lang);
          } else {
              // Fallback if new routing helper missing
              $city_link = home_url('/search?city=' . $city_id);
          }

          if (function_exists('count_projects_for_location')) {
    $project_count = jawda_count_projects_for_area_term($area->term_id, 'project_city');
                    } else {
              // Fallback: count projects by meta location, respecting language (Polylang if available)
              $cache_lang = function_exists('pll_current_language') ? pll_current_language() : $lang;
              $cache_key  = 'jawda_city_projects_count_' . (int) $city_id . '_' . $cache_lang;

              $cached = get_transient($cache_key);
              if ($cached !== false) {
                  $project_count = (int) $cached;
              } else {
                  $args = [
                      'post_type'      => 'projects',
                      'post_status'    => 'publish',
                      'posts_per_page' => 1,
                      'fields'         => 'ids',
                      'no_found_rows'  => false,
                      'meta_query'     => [
                          [
                              'key'     => 'loc_city_id',
                              'value'   => (string) $city_id,
                              'compare' => '=',
                          ]
                      ],
                  ];

                  if (function_exists('pll_current_language')) {
                      $args['lang'] = $cache_lang;
                  }

                  $q = new WP_Query($args);
                  $project_count = (int) $q->found_posts;
                  wp_reset_postdata();

                  set_transient($cache_key, $project_count, 10 * MINUTE_IN_SECONDS);
              }
          }$name_col = 'name_' . $lang;
          $city_name = $wpdb->get_var($wpdb->prepare("SELECT $name_col FROM {$wpdb->prefix}jawda_cities WHERE id = %d", $city_id));

        

          $area_img = '';
          // Fallback: scan city projects for first thumbnail (not only latest one)
          if (empty($area_img)) {
              $args_img = [
                  'post_type'      => 'projects',
                  'post_status'    => 'publish',
                  'posts_per_page' => 30,
                  'orderby'        => 'date',
                  'order'          => 'DESC',
                  'fields'         => 'ids',
                  'meta_query'     => [
                      [
                          'key'     => 'loc_city_id',
                          'value'   => (int) $city_id,
                          'compare' => '=',
                      ]
                  ],
              ];

              if (function_exists('pll_current_language')) {
                  $args_img['lang'] = $lang;
              }

              $qimg = new WP_Query($args_img);

              if (!empty($qimg->posts)) {
                  foreach ($qimg->posts as $pid_img) {
                      $tid = (int) get_post_thumbnail_id($pid_img);
                      if ($tid) {
                          $u = wp_get_attachment_image_url($tid, 'large');
                          if (!empty($u)) {
                              $area_img = $u;
                              break;
                          }
                      }
                  }
              }

              wp_reset_postdata();
          }
          // End Fallback: scan city projects for first thumbnail

          if (function_exists('jawda_get_area_featured_image_url')) {
            $area_img = jawda_get_area_featured_image_url($city_id, $lang);
          }
?>

				<div class="col-md-3">
					<div class="area-box<?php echo !empty($area_img) ? "" : " no-image"; ?>">
						<?php if (!empty($area_img)) : ?>
                            <div class="area-img">
                                <img loading="lazy" src="<?php echo esc_url($area_img); ?>" alt="<?php echo esc_attr($city_name); ?>" width="600" height="400">
                            </div>
                            <?php endif; ?>
                            <div class="area-data">
							<span class="area-title"><a href="<?php echo esc_url($city_link); ?>"><?php echo esc_html($city_name); ?></a> </span>
							<span class="project-no"><?php echo $project_count; ?> <?php get_text('مشروع','Project'); ?></span>
							<a href="<?php echo esc_url($city_link); ?>" class="area-btn" aria-label="details"><i class="icon-left-big"></i></a>
						</div>
						<a href="<?php echo esc_url($city_link); ?>" class="area-link"></a>
					</div>
				</div>

      <?php } ?>

			</div>
		</div>
	</div>
	<!--End Featured Area-->

  <?php

  endif;

  $content = ob_get_clean();
  echo minify_html($content);


}


/**
 * Area featured image fallback:
 * 1) Try Catalog SEO featured image (if helper exists in theme)
 * 2) Fallback to latest project's featured image in this area
 */
if (!function_exists('jawda_get_area_featured_image_url')) {
  function jawda_get_area_featured_image_url($city_id, $lang = 'ar') {

    $img = '';

    // 1) If you already have a Catalog SEO helper, use it (adjust name if exists)
    // Examples (we try common names; if none exists, it will just skip)
    if (function_exists('jawda_get_catalog_seo_featured_image_url')) {
      $img = (string) jawda_get_catalog_seo_featured_image_url('city', (int)$city_id, $lang);
    } elseif (function_exists('jawda_get_catalog_seo_featured_image_url')) {
      $img = (string) jawda_get_catalog_seo_featured_image_url('city', (int)$city_id, $lang);
    }

    if (!empty($img)) {
      return $img;
    }

    // 2) Fallback: scan latest projects in this city until we find one with a featured image
    $args = array(
      'post_type'      => 'projects',
      'post_status'    => 'publish',
      'posts_per_page' => 30, // scan more than 1 to find first project that has a thumbnail
      'orderby'        => 'date',
      'order'          => 'DESC',
      'no_found_rows'  => true,
      'fields'         => 'ids',
      'meta_query'     => array(
        array(
          'key'     => 'loc_city_id',
          'value'   => (int)$city_id,
          'compare' => '=',
        ),
      ),
    );

    // Language awareness (Polylang)
    if (function_exists('pll_current_language')) {
      $args['lang'] = $lang;
    }

    $q = new WP_Query($args);

    if (!empty($q->posts) && is_array($q->posts)) {
      foreach ($q->posts as $pid) {
        $pid = (int) $pid;
        $thumb = get_the_post_thumbnail_url($pid, 'medium_large');
        if (!empty($thumb)) {
          return $thumb;
        }
      }
    }

    return '';
  }
}

