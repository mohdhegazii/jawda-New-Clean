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
    							<?php if( function_exists('pll_the_languages') ){ pll_the_languages(); } ?>
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
