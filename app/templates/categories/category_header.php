<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# cat_header
----------------------------------------------------------------------------- */

function get_my_cat_header(){

  ob_start();
  $lang = is_rtl() ? 'ar' : 'en';
  $jawda_page_blog = carbon_get_theme_option( 'jawda_page_blog_'.$lang );

  ?>

  <!--Project Hero-->
	<div class="unit-hero">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="unit-info">
						<!--Breadcrumbs-->
            <div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">
              <?php $i = 1; ?>
              <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                <a class="breadcrumbs__link" href="<?php echo siteurl; ?>" itemprop="item"><span itemprop="name"><i class="fa-solid fa-house"></i></span></a>
                <meta itemprop="position" content="<?php echo $i; $i++; ?>">
              </span>
              <span class="breadcrumbs__separator">›</span>
              <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                <a class="breadcrumbs__link" href="<?php echo esc_url( get_page_link($jawda_page_blog) ); ?>" itemprop="item">
                    <span itemprop="name"><?php echo esc_html( get_the_title( $jawda_page_blog ) ); ?></span>
                  </a>
                <meta itemprop="position" content="2">
              </span>
              <span class="breadcrumbs__separator">›</span>
            </div>
						<h1 class="project-headline"><?php echo esc_html( single_cat_title( '', false ) ); ?></h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--End Project Hero-->

  <?php
  $content = ob_get_clean();
  echo minify_html($content);


}
