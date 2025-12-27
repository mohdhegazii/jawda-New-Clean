<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_header
----------------------------------------------------------------------------- */

function get_my_page_developers(){

  ob_start();

  ?>

    <div class="units-page">
      <div class="container">
    		<div class="row">

          <?php

          // Vars
          $tpp = (int) get_option( 'posts_per_page' );
          $paged = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? (int) get_query_var( 'page' ) : 1 );
          $offset = ( $paged - 1 ) * $tpp;
          $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

          $developers = function_exists('jawda_get_developers')
            ? jawda_get_developers([
                'is_active' => 1,
                'number' => $tpp,
                'offset' => $offset,
            ])
            : [];

          global $wpdb;
          $table_name = $wpdb->prefix . 'jawda_developers';
          $total_developers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1");
          $max_num_pages = $tpp > 0 ? (int) ceil( $total_developers / $tpp ) : 1;

          if ( ! empty( $developers ) ) {
            foreach ( $developers as $developer ) {
              $dev_name = jawda_get_developer_display_name($developer, $is_ar);
              $dev_link = jawda_get_developer_url($developer, $is_ar);
              $logo_id = $developer['logo_id'] ?? $developer['logo'] ?? null;
              $dev_logo = $logo_id ? wp_get_attachment_url($logo_id, 'thumbnail') : '';
              ?>
              <div class="col-md-4">
                <div class="content-box center">
                  <div class="developer-info devbox">
                    <div class="dev-img">
                      <a href="<?php echo esc_url( $dev_link ); ?>"><img loading="lazy" src="<?php echo esc_url( $dev_logo ); ?>" width="600" height="318" alt="<?php echo esc_attr( $dev_name ); ?>" /></a>
                    </div>
                    <p><b><a href="<?php echo esc_url( $dev_link ); ?>"><?php echo esc_html( $dev_name ); ?></a></b></p>
                    <div class="btn-side"><a href="<?php echo esc_url( $dev_link ); ?>"><?php get_text('المزيد','More'); ?></a></div>
                  </div>
                </div>
              </div>
              <?php
            }
          }

          ?>
          <div class="col-md-12 center">
            <div class="blognavigation">
              <?php
                $big = 999999999;
                echo paginate_links(array(
                    'base' => str_replace($big, '%#%', get_pagenum_link($big)),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $max_num_pages
                ));
              ?>
            </div>
         </div>
          <?php





          ?>

    		</div>
    	</div>
    </div>

    <?php if ( !empty(get_the_content()) || get_the_content() !== "" ): ?>
    <div class="project-main">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="content-box maincontent">
            <?php wpautop(the_content()); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  <?php

  $content = ob_get_clean();
  echo minify_html($content);


}
