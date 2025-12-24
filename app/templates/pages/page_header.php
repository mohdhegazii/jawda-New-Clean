<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_header
----------------------------------------------------------------------------- */

function get_my_page_header(){

  ob_start();

  if (get_page_template_slug() === 'page-projects.php') {get_projects_top_search();}
  if (get_page_template_slug() === 'page-properties.php') {get_properties_top_search();}
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
            </div>
						<h1 class="project-headline"><?php echo get_the_title(get_the_ID()); ?></h1>
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
