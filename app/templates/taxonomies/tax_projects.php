<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# cat_posts
----------------------------------------------------------------------------- */


function get_my_tax_pojects() {


  ob_start();

  ?>

  <div class="units-page">
    <div class="container">
      <div class="row">
        <?php while ( have_posts() ) : the_post(); ?>
          <div class="col-md-4 projectbxspace">
            <?php get_my_project_box(get_the_ID()); ?>
          </div>
        <?php endwhile; ?>
        <div class="col-md-12 center">
          <?php theme_pagination(); ?>
        </div>
        <?php wp_reset_postdata(); ?>
      </div>
    </div>
  </div>

  <?php if (category_description()) { ?>
  <div class="project-main">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="content-box">
          <?php echo category_description(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>

  <?php
  $content = ob_get_clean();
  echo minify_html($content);


}
