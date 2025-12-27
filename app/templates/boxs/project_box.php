<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects
----------------------------------------------------------------------------- */

function get_my_project_box($project_id){
  global $wpdb;
  $price = carbon_get_post_meta( $project_id, 'jawda_price' );
  $name_col = is_rtl() ?  'slug_ar'  : 'name_en';

  if (class_exists('Jawda_Location_Service')) {
      $location_data = Jawda_Location_Service::get_location_for_post($project_id);
      $lang = is_rtl() ? 'ar' : 'en';
      $location_parts = [];

      foreach (['city', 'district'] as $level) {
          if (empty($location_data['names'][$level])) {
              continue;
          }

          $row = $location_data['names'][$level];
          $label = function_exists('jawda_locations_get_label')
              ? jawda_locations_get_label($row[ 'slug_ar' ] ?? '', $row['name_en'] ?? '', $lang, '')
              : ($lang === 'ar' ? ($row[ 'slug_ar' ] ?? '') : ($row['name_en'] ?? ''));

          if ($label !== '') {
              $location_parts[] = $label;
          }
      }

      $project_location = implode(', ', $location_parts);
  } else {
      $city_id = get_post_meta($project_id, 'loc_city_id', true);
      $district_id = get_post_meta($project_id, 'loc_district_id', true);

      $location_parts = [];
      if ($city_id) {
          $city_name = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}jawda_cities WHERE id = %d", $city_id));
          if ($city_name) {
              $location_parts[] = $city_name;
          }
      }
      if ($district_id) {
          $district_name = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}jawda_districts WHERE id = %d", $district_id));
          if ($district_name) {
              $location_parts[] = $district_name;
          }
      }
      $project_location = implode(', ', $location_parts);
  }

  $img = get_the_post_thumbnail_url($project_id,'medium');
  $title = get_the_title($project_id);
  $url = get_the_permalink($project_id);

  $display_title = $title;
  if ( mb_strlen( $title ) > 70 ) {
      $display_title = mb_substr( $title, 0, 70 ) . '...';
  }
  ?>
  <div class="related-box">
    <a href="<?php echo $url; ?>" class="related-img">
      <img loading="lazy" src="<?php echo $img; ?>" width="500" height="300" alt="<?php echo $title; ?>" /> </a>
    <div class="related-data">
      <div class="related-title"><a href="<?php echo $url; ?>"><?php echo $display_title; ?></a></div>
      <span class="project-location">
        <i class="icon-location"></i><?php echo $project_location; ?>
      </span>
      <?php
      $developer = jawda_get_project_developer($project_id);
      if (!empty($developer)) {
          $developer_name = jawda_get_developer_display_name($developer);
          echo '<span class="project-developer"><i class="icon-building"></i>' . esc_html($developer_name) . '</span>';
      }
      ?>
      <div class="related-price-container"><a href="<?php echo $url; ?>" class="project-price"><?php get_text('اسعار تبدأ من','Prices starting from'); ?>
        <span><?php echo number_format( intval($price) ); ?> <?php get_text('ج.م','EGP'); ?></span>
      </a></div>
    </div>
    <a href="<?php echo $url; ?>" class="related-btn" aria-label="details"><i class="icon-left-big"></i></a>
  </div>
  <?php

}
