<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_featured_properties(){

  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  $featured_properties = carbon_get_theme_option( 'jawda_home_featured_properties_'.$lang );

  if( isset($featured_properties) && !empty($featured_properties) && $featured_properties !== false ):

  ?>
  <!--Featured Units-->
	<div class="featured-units">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="headline">
						<div class="main-title"><?php get_text('الوحدات المميزة','Featured Units'); ?></div>
					</div>
				</div>
			</div>
			<div class="row featured-slider">
        <?php foreach ($featured_properties as $property): ?>
          <div class="col-md-4">
            <?php get_my_property_box($property); ?>
  				</div>
        <?php endforeach; ?>
			</div>
		</div>
	</div>
	<!--End Featured Units-->
  <?php

  endif;

  $content = ob_get_clean();
  echo minify_html($content);

}
function get_my_home_featured_real_projects() {
  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  // الربط الصحيح مع إعدادات Carbon Fields كما في settings.php
  $projects = carbon_get_theme_option('jawda_featured_projects_' . $lang);

  if (isset($projects) && !empty($projects) && $projects !== false):
    ?>
    <!--Featured Projects-->
    <div class="featured-units"><!-- نفس خلفية الوحدات -->
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="headline">
              <div class="main-title"><?php get_text('المشروعات المميزة','Featured Projects'); ?></div>
            </div>
          </div>
        </div>
        <div class="row featured-slider">
          <?php foreach ($projects as $project_id): ?>
            <div class="col-md-4 projectbxspace">
              <?php get_my_project_box($project_id); // كارت المشروع بنفس الستايل ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <!--End Featured Projects-->
    <?php
  endif;

  echo minify_html(ob_get_clean());
}
function get_my_home_latest_fifth_settlement_projects() {
  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  // NEW: ID for "Fifth Settlement" district in the new locations system.
  // Assuming the ID is still 1031 for this example. A more robust solution might look it up by name.
  $district_id = 1031;

  // جلب أحدث 5 مشاريع مرتبطة بهذه المنطقة
  $projects = get_posts(array(
    'post_type' => 'projects',
    'posts_per_page' => 5,
    'meta_query' => array(
      array(
        'key' => 'loc_district_id',
        'value' => $district_id,
        'compare' => '=',
      ),
    ),
  ));

  if (!empty($projects)):
    ?>
    <!-- Latest Fifth Settlement Projects -->
    <div class="featured-units"><!-- نفس خلفية الوحدات -->
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="headline">
              <div class="main-title"><?php get_text('آخر مشاريع التجمع الخامس','Latest Fifth Settlement Projects'); ?></div>
            </div>
          </div>
        </div>
        <div class="row featured-slider">
          <?php foreach ($projects as $project): ?>
            <div class="col-md-4 projectbxspace">
              <?php get_my_project_box($project->ID); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <!-- End Latest Fifth Settlement Projects -->
    <?php
  endif;

  echo minify_html(ob_get_clean());
}