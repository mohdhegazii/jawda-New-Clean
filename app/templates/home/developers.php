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
             $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
             $developers = function_exists('jawda_get_developers')
               ? jawda_get_developers([
                   'is_active' => 1,
                   'number' => 50,
                   'offset' => 0,
               ])
               : [];
             foreach ($developers as $developer) :
               $logo_id = $developer['logo_id'] ?? $developer['logo'] ?? null;
               $image = $logo_id ? wp_get_attachment_url($logo_id, 'medium') : '';
               $dev_link = jawda_get_developer_url($developer, $is_ar);
               $dev_name = jawda_get_developer_display_name($developer, $is_ar);
             ?>
          <a href="<?php echo esc_url($dev_link); ?>" class="dev-logo">
            <img loading="lazy" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($dev_name); ?>" width="256" height="185" />
          </a>
          <?php endforeach; ?>
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
