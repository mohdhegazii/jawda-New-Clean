<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_developers(){

  ob_start();

  ?>
  <!--Developers	-->
  <div class="developers">
    <div class="container">
      <div class="row">
        <div class="col-md-12 developers-slider">
          <?php
             $developers = get_terms('developer', array('hide_empty' => 1));
             foreach($developers as $developer) :
             ?>
             <?php
             $img_id = carbon_get_term_meta( $developer->term_id, 'jawda_dev_logo' );
             $image = wp_get_attachment_url($img_id,'medium');
             ?>
					<a href="<?php echo get_term_link( $developer->slug, $developer->taxonomy ); ?>" class="dev-logo">
            <img loading="lazy" src="<?php echo $image; ?>" alt="<?php echo $developer->name; ?>" width="256" height="185" />
          </a>
          <?php endforeach;  ?>
        </div>
      </div>
    </div>
  </div>
  <!--End Developers	-->

  <!--important links-->
  <div class="imp-links">
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <div class="link-box">
            <div class="imp-title"><?php txt('Latest Projects'); ?></div>
            <?php if ( has_nav_menu( 'latest_projects' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'latest_projects', 'menu_class' => 'quick-links' ) ); } ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="link-box">
            <div class="imp-title"><?php txt('The most popular Cities'); ?></div>
            <?php if ( has_nav_menu( 'popular_cities' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'popular_cities', 'menu_class' => 'quick-links' ) ); } ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="link-box">
            <div class="imp-title"><?php txt('Most Popular Developers'); ?></div>
            <?php if ( has_nav_menu( 'popular_developers' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'popular_developers', 'menu_class' => 'quick-links' ) ); } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--End important links-->


  <?php

  $content = ob_get_clean();
  echo minify_html($content);


}
