<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# cat_posts
----------------------------------------------------------------------------- */


function get_my_catalogs_main()
{

  ob_start();
  $paged = max( 1, (int) get_query_var('paged'), (int) get_query_var('page') );
  $lang = is_rtl() ? 'ar' : 'en';
  $catalog_id = get_the_ID();
  $catalog_permalink = get_permalink($catalog_id);

  $is_master_catalog = get_post_meta($catalog_id, 'jawda_is_master_catalog', true);
  $title = get_the_title($catalog_id);


  $catalog_type = carbon_get_post_meta( $catalog_id, 'jawda_catalog_type' );
  $project_type = carbon_get_post_meta( $catalog_id, 'jawda_project_type' );

  // $property_state = carbon_get_post_meta( $catalog_id, 'jawda_property_state' );
  $property_type = carbon_get_post_meta( $catalog_id, 'jawda_property_type' );

  $project_price_from = carbon_get_post_meta( $catalog_id, 'jawda_project_price_from' );
  $project_price_to = carbon_get_post_meta( $catalog_id, 'jawda_project_price_to' );

  $jawda_page_projects = carbon_get_theme_option( 'jawda_page_projects_'.$lang );
  $jawda_page_properties = carbon_get_theme_option( 'jawda_page_properties_'.$lang );

  $jawda_property_main_project = [];
  if ( isset(carbon_get_post_meta( $catalog_id, 'jawda_property_main_project' )[0]) ) {
    $jawda_property_main_project = carbon_get_post_meta( $catalog_id, 'jawda_property_main_project' )[0];
  }


  if( $catalog_type === 1 ) {
    $breadcrumbspage = $jawda_page_projects;
    get_projects_top_search();
  }
  if( $catalog_type === 2 ) {
    $breadcrumbspage = $jawda_page_properties;
    get_properties_top_search();
  }

  $thumbnail_url = 'https://masharf.com/wp-content/uploads/2023/12/Masharf-real-estate.jpg';
  if (has_post_thumbnail()) {
    $thumbnail_url = get_the_post_thumbnail_url();
  }


  ?>
  <style>
    .hero-photo {height:150px;width:auto;float:left;border-radius:5px;}
    @media screen AND (max-width:720px) {
      .hero-photo {height:250px;width:100%;object-fit:cover;}
    }
  </style>
  <div class="unit-hero">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="unit-info">
						<!--Breadcrumbs-->
            <div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">
                <?php
                global $wpdb;
                $lang = is_rtl() ? 'ar' : 'en';
                $name_col = is_rtl() ?  'slug_ar'  : 'name_en';

                $breadcrumbs_locations = [];

                if (!$is_master_catalog) {
                    $location_type = get_post_meta($catalog_id, 'jawda_location_type', true);
                    $location_id = get_post_meta($catalog_id, 'jawda_location_id', true);

                    if ($location_type && $location_id) {
                        if ($location_type === 'governorate') {
                            $query = $wpdb->prepare("SELECT id, {$name_col} as name FROM {$wpdb->prefix}jawda_governorates WHERE id = %d", $location_id);
                            $gov = $wpdb->get_row($query);
                            if ($gov) {
                                $breadcrumbs_locations[] = ['type' => 'governorate', 'id' => $gov->id, 'name' => $gov->name];
                            }
                        } elseif ($location_type === 'city') {
                            $query = $wpdb->prepare("
                                SELECT c.id as city_id, c.{$name_col} as city_name, g.id as gov_id, g.{$name_col} as gov_name
                                FROM {$wpdb->prefix}jawda_cities c
                                JOIN {$wpdb->prefix}jawda_governorates g ON c.governorate_id = g.id
                                WHERE c.id = %d", $location_id);
                            $loc = $wpdb->get_row($query);
                            if ($loc) {
                                $breadcrumbs_locations[] = ['type' => 'governorate', 'id' => $loc->gov_id, 'name' => $loc->gov_name];
                                $breadcrumbs_locations[] = ['type' => 'city', 'id' => $loc->city_id, 'name' => $loc->city_name];
                            }
                        } elseif ($location_type === 'district') {
                            $query = $wpdb->prepare("
                                SELECT d.id as dist_id, d.{$name_col} as dist_name, c.id as city_id, c.{$name_col} as city_name, g.id as gov_id, g.{$name_col} as gov_name
                                FROM {$wpdb->prefix}jawda_districts d
                                JOIN {$wpdb->prefix}jawda_cities c ON d.city_id = c.id
                                JOIN {$wpdb->prefix}jawda_governorates g ON c.governorate_id = g.id
                                WHERE d.id = %d", $location_id);
                            $loc = $wpdb->get_row($query);
                            if ($loc) {
                                $breadcrumbs_locations[] = ['type' => 'governorate', 'id' => $loc->gov_id, 'name' => $loc->gov_name];
                                $breadcrumbs_locations[] = ['type' => 'city', 'id' => $loc->city_id, 'name' => $loc->city_name];
                                $breadcrumbs_locations[] = ['type' => 'district', 'id' => $loc->dist_id, 'name' => $loc->dist_name];
                            }
                        }

                        // Prepare breadcrumbs for display
                        $temp_breadcrumbs = [];
                        foreach ($breadcrumbs_locations as $loc) {
                            $catalog_link_id = find_catalog_by_location($loc['type'], $loc['id'], $lang);
                            if ($catalog_link_id) {
                                $temp_breadcrumbs[] = [
                                    'short_name' => $loc['name'],
                                    'full_title' => get_the_title($catalog_link_id),
                                    'link' => get_permalink($catalog_link_id),
                                    'is_current' => ($loc['type'] === $location_type && $loc['id'] == $location_id)
                                ];
                            }
                        }
                        $breadcrumbs_locations = $temp_breadcrumbs;
                    }
                }

                $master_catalog_id = find_master_catalog($lang);
                if ($master_catalog_id) {
                    $master_catalog_title = get_the_title($master_catalog_id);
                    $master_catalog_short_name = is_rtl() ? 'مصر' : 'Egypt';
                    $master_catalog_link = get_permalink($master_catalog_id);
                } else {
                    $master_catalog_title = is_rtl() ? 'المشروعات الجديدة' : 'New Projects';
                    $master_catalog_short_name = is_rtl() ? 'مصر' : 'Egypt';
                    $master_catalog_link = '#';
                }
                ?>
                <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item">
                    <a class="breadcrumbs__link" href="<?php echo siteurl; ?>" itemprop="item"><span itemprop="name"><i class="fa-solid fa-house"></i></span></a>
                    <meta itemprop="position" content="1">
                </span>
                <span class="breadcrumbs__separator">›</span>
                <?php if ($is_master_catalog): ?>
                    <span class="breadcrumb-item-current"><?php echo esc_html($title); ?></span>
                <?php else: ?>
                    <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item expandable">
                        <a class="breadcrumbs__link" href="<?php echo esc_url($master_catalog_link); ?>" itemprop="item">
                            <span itemprop="name"><?php echo esc_html($master_catalog_short_name); ?></span>
                        </a>
                        <meta itemprop="position" content="2">
                    </span>
                    <?php $i = 3; foreach ($breadcrumbs_locations as $loc): ?>
                        <span class="breadcrumbs__separator">›</span>
                        <?php if ($loc['is_current']): ?>
                            <span class="breadcrumb-item-current"><?php echo esc_html($loc['short_name']); ?></span>
                        <?php else: ?>
                            <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item expandable">
                                <a class="breadcrumbs__link" href="<?php echo esc_url($loc['link']); ?>" itemprop="item">
                                    <span itemprop="name"><?php echo esc_html($loc['short_name']); ?></span>
                                </a>
                                <meta itemprop="position" content="<?php echo $i++; ?>">
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
						<h1 class="project-headline"><?php echo esc_html( $title ); ?><?php if ( $paged > 1 ) { echo page_suffix( $paged ); } ?></h1>
					</div>
				</div>
			</div>
		</div>
	</div>

  <div class="units-page">
    <div class="container">
      <div class="row">

        <?php

          $args = get_unified_catalog_args( $catalog_id, $paged );
          $the_query = new WP_Query( $args );

        ?>

        <?php
        //
        if ( $the_query->have_posts() ) :
          while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
          <div class="col-md-4 projectbxspace">
            <?php if ( get_post_type() === 'projects' ): ?>
              <?php get_my_project_box(get_the_ID()); ?>
            <?php elseif( get_post_type() === 'property' ): ?>
              <?php get_my_property_box(get_the_ID()); ?>
            <?php endif; ?>
          </div>
          <?php endwhile; ?>
       <?php  endif;
        ?>

        <?php if ($the_query->max_num_pages > 1) : ?>
          <div class="col-md-12 center">
            <div class="blognavigation">
              <?php
              $base = rtrim($catalog_permalink, '/') . '/page/%#%/';
              $pagination_links = paginate_links([
                'base'      => $base,
                'format'    => '',
                'current'   => $paged,
                'total'     => (int) $the_query->max_num_pages,
                'mid_size'  => 2,
                'end_size'  => 1,
                'prev_text' => __('« Previous'),
                'next_text' => __('Next »'),
                'type'      => 'plain',
              ]);

              // Remove /page/1/ from the first page link
              $pagination_links = str_replace( "page/1/'", "'", $pagination_links );
              $pagination_links = str_replace( 'page/1/"', '"', $pagination_links );

              echo $pagination_links;
              ?>
            </div>
          </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
      </div>
    </div>
  </div>

  <?php if ( !empty(get_the_content()) || get_the_content() !== "" ): ?>
  <div class="project-main">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="content-box">
            <?php wpautop(the_content()); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>


  <?php

  $content = ob_get_clean();
  echo minify_html($content);

}


function get_unified_catalog_args( $catalog_id, $paged = 1 ) {
    $catalog_type = carbon_get_post_meta( $catalog_id, 'jawda_catalog_type' );
    $is_master_catalog = get_post_meta($catalog_id, 'jawda_is_master_catalog', true);

    $post_type = ( $catalog_type === 1 ) ? 'projects' : 'property';

    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => 9,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
		'suppress_filters' => false,
		'no_found_rows'  => false,
    ];

    // If it's a master catalog, we don't need location filters.
    if ($is_master_catalog) {
        return $args;
    }

    $tax_query = ['relation' => 'AND'];
    $meta_query = ['relation' => 'AND'];

    // Location filters
    $gov_id = get_post_meta($catalog_id, 'loc_governorate_id', true);
    $city_id = get_post_meta($catalog_id, 'loc_city_id', true);
    $district_id = get_post_meta($catalog_id, 'loc_district_id', true);

    $location_meta_query = [];
    if (!empty($gov_id)) {
        $location_meta_query[] = ['key' => 'loc_governorate_id', 'value' => $gov_id, 'compare' => '='];
    }
    if (!empty($city_id)) {
        $location_meta_query[] = ['key' => 'loc_city_id', 'value' => $city_id, 'compare' => '='];
    }
    if (!empty($district_id)) {
        $location_meta_query[] = ['key' => 'loc_district_id', 'value' => $district_id, 'compare' => '='];
    }

    if ( $catalog_type === 1 ) { // Projects
        $project_type = carbon_get_post_meta( $catalog_id, 'jawda_project_type' );
        $project_price_from = carbon_get_post_meta( $catalog_id, 'jawda_project_price_from' );
        $project_price_to = carbon_get_post_meta( $catalog_id, 'jawda_project_price_to' );

        if ( !empty($location_meta_query) ) {
            $meta_query = array_merge($meta_query, $location_meta_query);
        }
        if ( is_numeric($project_type) && $project_type != '0' ) {
            $meta_query[] = ['key' => 'jawda_property_type_ids', 'value' => '"' . $project_type . '"', 'compare' => 'LIKE'];
        }
        if ( ! empty($project_price_from) && ! empty($project_price_to) ) {
            $meta_query[] = ['key' => 'jawda_price', 'value' => [$project_price_from, $project_price_to], 'type' => 'numeric', 'compare' => 'BETWEEN'];
        }

    } elseif ( $catalog_type === 2 ) { // Properties
        $property_type = carbon_get_post_meta( $catalog_id, 'jawda_property_type' );
        $property_main_project_raw = carbon_get_post_meta( $catalog_id, 'jawda_property_main_project' );
        $property_main_project = !empty($property_main_project_raw[0]) ? $property_main_project_raw[0] : null;

        if ( !empty($location_meta_query) ) {
            $project_args = [
                'post_type' => 'projects',
                'posts_per_page' => -1,
                'meta_query' => $location_meta_query,
                'fields' => 'ids'
            ];
            $project_ids = get_posts($project_args);

            if (!empty($project_ids)) {
                $meta_query[] = [
                    'key' => 'jawda_project',
                    'value' => $project_ids,
                    'compare' => 'IN'
                ];
            } else {
                // No projects found for this location, so no properties will be found.
                // Force query to return no results.
                $args['post__in'] = [0];
            }
        }

        if ( is_numeric($property_type) && $property_type != '0' ) {
            $meta_query[] = ['key' => 'jawda_property_type_id', 'value' => $property_type, 'compare' => '='];
        }
        if ( !empty($property_main_project) ) {
            $meta_query[] = ['key' => 'jawda_project', 'value' => $property_main_project];
        }
    }

    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    if ( count( $meta_query ) > 1 ) {
        $args['meta_query'] = $meta_query;
    }

    return $args;
}
