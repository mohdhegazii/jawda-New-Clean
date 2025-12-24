<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_recent_articles(){

  ob_start();
  ?>

  <!--Recent Articles-->
	<div class="recent-articles">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="headline">
						<div class="main-title"><?php get_text('احدث اخبار العقارات','Latest real estate news'); ?></div>
					</div>
				</div>
			</div>
			<div class="row">

        <?php
        $query = new WP_Query(array('post_type' => 'post','posts_per_page' =>'3','meta_query' => array(array('key' => '_thumbnail_id')) ));
        while ( $query->have_posts() ) : $query->the_post();
        ?>
				<div class="col-md-4">
          <?php get_my_article_box(); ?>
				</div>
			 <?php endwhile; wp_reset_query(); ?>
			</div>
		</div>
	</div>
	<!--End Recent Articles-->

  <?php
  $content = ob_get_clean();
  echo minify_html($content);


}
