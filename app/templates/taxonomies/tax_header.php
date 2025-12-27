<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# cat_header
----------------------------------------------------------------------------- */

function get_my_tax_header(){

  ob_start();
  $lang = is_rtl() ? 'ar' : 'en';
  $termid = get_queried_object()->term_id;
  $img_id = carbon_get_term_meta( $termid, 'jawda_thumb' );
  $image = wp_get_attachment_url($img_id);
  $jawda_page_projects = carbon_get_theme_option( 'jawda_page_properties_'.$lang );

  $taxonomy = get_queried_object()->taxonomy;

  $projects_tax = ['projects_category','projects_tag','projects_area','projects_type','projects_features'];
  $properties_tax = ['property_label','property_type','property_feature','property_city','property_area','property_state','property_status'];

  ?>

  <?php
  if( in_array($taxonomy, $projects_tax) ) get_projects_top_search();
  if( in_array($taxonomy, $properties_tax) ) get_properties_top_search();
  ?>

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
                <a class="breadcrumbs__link" href="<?php echo esc_url( get_page_link($jawda_page_projects) ); ?>" itemprop="item">
                    <span itemprop="name"><?php echo esc_html( get_the_title( $jawda_page_projects ) ); ?></span>
                  </a>
                <meta itemprop="position" content="2">
              </span>
              <span class="breadcrumbs__separator">›</span>
            </div>
						<h1 class="project-headline"><?php single_cat_title(); ?></h1>
					</div>
				</div>
			</div>
		</div>
	</div>


  <?php
  $content = ob_get_clean();
  echo minify_html($content);


}
