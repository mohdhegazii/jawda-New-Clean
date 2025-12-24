<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# cat_posts
----------------------------------------------------------------------------- */


function get_my_cat_posts() {


  ob_start();

  ?>

  <div class="blogpage">
    <div class="container">
      <div class="row">

        <?php
        // Post Loop
        while ( have_posts() ) : the_post();

        ?>
        <div class="col-md-4">
          <?php get_my_article_box(); ?>
        </div>
        <?php

        // End Post Loop
        endwhile;

        ?><div class="col-md-12 center"><?php

        // pagination
        theme_pagination();

        ?></div><?php

        // Reset My Data
        wp_reset_postdata();

        ?>

      </div>
    </div>
  </div>


  <?php
  $archive_description = get_the_archive_description();
  if ( ! empty( $archive_description ) ) { ?>
  <div class="project-main">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="content-box">
            <?php echo wp_kses_post( $archive_description ); ?>
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
