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
          $tpp = get_option( 'posts_per_page' );
          $taxonomy = 'projects_developer';

          // Check tax
          if ( taxonomy_exists( $taxonomy ) ){

            // count Terms
            $term_count = get_terms( $taxonomy, ['fields' => 'count'] );

            // Check count
            if ( $term_count > 0 ) {

              // We have terms, now calculate pagination
              $max_num_pages = ceil( $term_count / $tpp );

              // Page_number
              if ( get_query_var( 'paged' ) ) {  $paged = get_query_var( 'paged' );}
              elseif ( get_query_var( 'page' ) ) { $paged = get_query_var( 'page' ); }
              else { $paged = 1; }

              // Calculate term offset
              $offset = ( ( $paged - 1 ) * $tpp );

              // We can now get our terms and paginate it
              $args = ['number' => $tpp,'offset' => $offset];
              $wpbtags = get_terms( $taxonomy, $args );

              // Loop
              if ( ! is_wp_error( $wpbtags ) && ! empty( $wpbtags ) ) {
                foreach($wpbtags as $tag) {
                  $dev = $tag;
                  $dev_name = $dev->name;
                  $dev_link = esc_url( get_term_link( $dev ) );
                  $img_id = carbon_get_term_meta( $dev->term_id, 'jawda_thumb' );
                  $dev_logo = wp_get_attachment_url($img_id,'thumbnail');
                  $dev_description = term_description($dev);
                  ?>
                  <div class="col-md-4">
                    <div class="content-box center">
                      <div class="developer-info devbox">
                        <div class="dev-img">
                          <a href="<?php echo $dev_link; ?>"><img loading="lazy" src="<?php echo esc_url( $dev_logo ); ?>" width="600" height="318" alt="<?php echo esc_attr( $dev_name ); ?>" /></a>
                        </div>
                        <p><b><a href="<?php echo $dev_link; ?>"><?php echo esc_html( $dev_name ); ?></a></b></p>
                        <div class="btn-side"><a href="<?php echo $dev_link; ?>"><?php get_text('المزيد','More'); ?></a></div>
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

            }

          }





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
