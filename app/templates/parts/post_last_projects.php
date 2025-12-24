<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }


function my_last_projects(){
  ob_start();
  ?>

  <?php
  $args = array(
    'post_type' => 'project',
      'posts_per_page' => 4,
    );
  $query = new WP_Query( $args );
  if( $query->have_posts() ) : while( $query->have_posts() ) : $query->the_post(); ?>

  <div class="col-md-12">
    <?php get_my_project_box(); ?>
  </div>

  <?php endwhile; endif; wp_reset_postdata();  ?>

  <?php

  $content = ob_get_clean();
  echo minify_html($content);
}
