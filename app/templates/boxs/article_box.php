<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects
----------------------------------------------------------------------------- */

function get_my_article_box(){

  ?>
  <div class="recent-box">
    <a href="<?php the_permalink(); ?>" class="recent-img">
      <img loading="lazy" src="<?php echo get_the_post_thumbnail_url(get_the_ID(),'medium'); ?>" width="500" height="300" alt="<?php the_title(); ?>" /> </a>
    <div class="recent-data">
      <div class="recent-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
      <a href="<?php the_permalink(); ?>" class="btn4"><?php get_text('إقرأ المزيد','Read more'); ?></a>
    </div>
    <a href="<?php the_permalink(); ?>" class="recent-btn" aria-label="Details"><i class="icon-left-big"></i></a>
  </div>
  <?php

}
