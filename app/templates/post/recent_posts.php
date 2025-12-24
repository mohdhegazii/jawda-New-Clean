<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects
----------------------------------------------------------------------------- */

function get_my_post_recent_posts() {

  ob_start();

  ?>

  <!--Featured Projects-->
	<div class="featured-projects">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<h2 class="headline"><?php get_text('مقالات مشابهة','Related Posts'); ?></h2>
				</div>
			</div>
			<div class="row">

        <?php
        $cats = get_the_category( get_the_ID() );
        $cat_id = $cats[0]->term_id;

        $args = array(
          'post_type' => 'post',
            'post__not_in' => array( get_the_ID() ),
            'posts_per_page' => 3,
            'cat' => $cat_id,
        );
        $query = new WP_Query( $args );
        if( $query->have_posts() ) : while( $query->have_posts() ) : $query->the_post(); ?>

  				<div class="col-sm-6 col-md-4">

            <?php get_my_article_box(); ?>

  				</div>

        <?php endwhile; endif; wp_reset_postdata();  ?>

			</div>
		</div>
	</div>

  <?php

  $content = ob_get_clean();
  echo minify_html($content);

}
