<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

function get_my_header(){

  ob_start();

  $phone = carbon_get_theme_option( 'jawda_phone' );

  ?>
  <!DOCTYPE html>
  <html <?php language_attributes(); ?>>
    <head>
      <meta charset="<?php bloginfo( 'charset' ); ?>">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php wp_head(); ?>
      <?php get_my_favicons(); ?>
      <?php get_my_styles(); ?>
      <?php get_my_header_codes(); ?>
    <script>(function(){try{document.body&&document.body.removeAttribute('unresolved');}catch(e){}try{document.documentElement&&document.documentElement.removeAttribute('unresolved');}catch(e){}})();</script>
</head>
    <body <?php body_class(); ?>>
      <div class="wrapper">

      <?php get_my_body_codes(); ?>

      <header class="header" id="header">
    		<div class="logo-bar">
    			<div class="container">
    				<div class="row">
    					<div class="col-md-12">

    						<?php get_my_logo(); ?>

                <?php if( $phone !== NULL || $phone != '' ): ?>
    						<a class="header-phone" href="tel:<?php echo $phone; ?>"><i class="icon-phone"></i> <?php echo $phone; ?></a>
                <?php endif; ?>

    					</div>
    				</div>
    			</div>
    		</div>
    	</header>
    	<!--Top Header-->
    	<div class="menu-bar">
    		<div class="container">
    			<div class="row no-padding">
    				<div class="col-md-12 flex">
    					<div class="h-right">
    						<!--Navigation-->
    						<div class="navi">
    							<div class="menu">
    								<div class="menutoggel">
    									<i id="menu-icon" class="icon-menu"></i>
    								</div>
                    <?php if ( has_nav_menu( 'header_menu' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'header_menu' , 'menu_id' => 'respMenu' , 'menu_class' => 'ace-responsive-menu' ) ); } ?>
    							</div>
    						</div>
    					</div>
    					<div class="h-left">
    						<div class="language">
    							<?php
                  if ( function_exists('pll_the_languages') ) {
                    $languages = pll_the_languages( array( 'raw' => 1 ) );
                    $queried_id = get_queried_object_id();
                    $current_developer = $GLOBALS['jawda_current_developer'] ?? null;
                    $project_developer = null;
                    $project_slug = '';

                    if ( !$current_developer && is_singular('projects') && function_exists('jawda_get_project_developer_id') ) {
                      $project_id = get_the_ID();
                      $developer_id = jawda_get_project_developer_id($project_id);
                      if ($developer_id && function_exists('jawda_get_developer_by_id')) {
                        $project_developer = jawda_get_developer_by_id($developer_id);
                        $project_slug = (string) get_post_field('post_name', $project_id);
                      }
                    }

                    if ( $languages ) {
                      foreach ( $languages as $slug => $lang ) {
                        $is_ar = $slug === 'ar';

                        if ( $current_developer ) {
                          $developer_url = jawda_get_developer_url($current_developer, $is_ar);
                          if ($developer_url) {
                            $languages[ $slug ]['url'] = $developer_url;
                          }
                          continue;
                        }

                        if ( $project_developer && $project_slug !== '' ) {
                          $developer_slug = jawda_get_developer_slug($project_developer, $is_ar);
                          if ($developer_slug !== '') {
                            $base = $is_ar ? 'مشروعات-جديدة' : 'en/new-projects';
                            $languages[ $slug ]['url'] = home_url(user_trailingslashit($base . '/' . $developer_slug . '/' . $project_slug));
                          }
                          continue;
                        }

                        if ( $queried_id ) {
                          if ( is_singular() && function_exists('pll_get_post_translations') ) {
                            $translations = pll_get_post_translations( $queried_id );
                            if ( isset( $translations[ $slug ] ) ) {
                              $translated_id = $translations[ $slug ];
                              if ( $translated_id ) {
                                $languages[ $slug ]['url'] = get_permalink( $translated_id );
                              }
                            }
                          } elseif ( ( is_category() || is_tag() || is_tax() ) && function_exists('pll_get_term_translations') ) {
                            $term_translations = pll_get_term_translations( $queried_id );
                            if ( isset( $term_translations[ $slug ] ) ) {
                              $translated_term_id = $term_translations[ $slug ];
                              $term_link = get_term_link( (int) $translated_term_id );

                              if ( ! is_wp_error( $term_link ) ) {
                                $languages[ $slug ]['url'] = $term_link;
                              }
                            }
                          }
                        }
                      }
                    }

                    if ( $languages ) {
                      echo '<ul>';
                      foreach ( $languages as $lang ) {
                        $classes = array( 'lang-item', 'lang-item-' . $lang['slug'] );
                        if ( ! empty( $lang['current_lang'] ) ) {
                          $classes[] = 'current-lang';
                        }

                        echo '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
                        echo '<a href="' . esc_url( $lang['url'] ) . '">' . esc_html( $lang['name'] ) . '</a>';
                        echo '</li>';
                      }
                      echo '</ul>';
                    }
                  }
                  ?>
    						</div>
    					</div>
    				</div>
    			</div>
    		</div>
    	</div>

  <?php
  $content = ob_get_clean();
  echo minify_html($content);

}
