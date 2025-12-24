<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_featured_projects(){

  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  $featured_projects = carbon_get_theme_option( 'jawda_home_featured_projects_'.$lang );

  if( isset($featured_projects) && !empty($featured_projects) && $featured_projects !== false ):

  ?>

  <!--Featured Projects-->
  <div class="featured-projects">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="headline">
            <div class="main-title"><?php get_text('المشروعات المميزة','Featured Projects'); ?></div>
          </div>
        </div>
      </div>
      <div class="row">

        <?php

    	  foreach ($featured_projects as $ptype) {
          $ptypeid = $ptype['id'];
          $term = get_term( $ptypeid );

          // Skip if term is invalid
          if ( is_wp_error( $term ) || is_null( $term ) ) {
              continue;
          }

          $img_id = carbon_get_term_meta( $ptypeid, 'jawda_thumb' );
          $image = wp_get_attachment_url($img_id,'medium');
          $iconimg = $ptype['img'];
          $cityname = $term->name;
          $citylink = get_term_link($term);
          $projectscount = $term->count;
        ?>

        <div class="col-lg-2 col-sm-4">
          <div class="project-box">
            <a href="<?php echo esc_url( $citylink ); ?>">
              <div class="project-img"><img loading="lazy" src="<?php echo esc_url( $image ); ?>" width="700" height="654" alt="<?php echo esc_attr( $cityname ); ?>" /></div>
              <div class="project-data">
                <div class="project-icon"><img loading="lazy" src="<?php echo esc_url( wimgurl.$iconimg.'.png' ); ?>" width="48" height="48" alt="<?php echo esc_attr( $cityname ); ?>" /></div>
                <?php echo esc_html( $cityname ); ?>
              </div>
            </a>
          </div>
        </div>

        <?php } ?>

      </div>
    </div>
  </div>
  <!--End Featured Projects-->


  <?php

  endif;

  $content = ob_get_clean();
  echo minify_html($content);


}
