<?php
$location = $location ?? ""; // Global Safety Net


// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# get_my_property_header
----------------------------------------------------------------------------- */

function get_my_property_header()
{

  ob_start();

  $property_id = get_the_ID();

  // Project
  $pro_projects = carbon_get_post_meta( $property_id, 'jawda_project' );
  $proj_name = $proj_link = NULL;
  if( is_array($pro_projects) AND count($pro_projects) > 0 )
  {
    $proj_name = get_the_title($pro_projects[0]);
    $proj_link = get_the_permalink($pro_projects[0]);
  }

  // Details
  $price = carbon_get_post_meta( $property_id, 'jawda_price' );
  $size = carbon_get_post_meta( $property_id, 'jawda_size' );

  $bedrooms = carbon_get_post_meta( $property_id, 'jawda_bedrooms' );
  $bathrooms = carbon_get_post_meta( $property_id, 'jawda_bathrooms' );
  $garage = carbon_get_post_meta( $property_id, 'jawda_garage' );

  $location_label = '';
  if (class_exists('Jawda_Listing_Location_Service')) {
      $loc = Jawda_Listing_Location_Service::get_location($property_id);
      $name_key = function_exists('jawda_is_arabic_locale') && jawda_is_arabic_locale() ?  'slug_ar'  : 'name_en';

      if (!empty($loc['names']['district'][$name_key])) {
          $location_label = $loc['names']['district'][$name_key];
      } elseif (!empty($loc['names']['city'][$name_key])) {
          $location_label = $loc['names']['city'][$name_key];
      } elseif (!empty($loc['names']['governorate'][$name_key])) {
          $location_label = $loc['names']['governorate'][$name_key];
      }
  }

  if ($location_label === '') {
      $location_label = carbon_get_post_meta( $property_id, 'jawda_location' );
  }

  $category_selection = class_exists('Jawda_Listing_Category_Service')
      ? Jawda_Listing_Category_Service::get_selection_with_labels($property_id)
      : ['main_categories' => [], 'property_types' => []];

  $main_category_label = $category_selection['main_categories'][0]['label'] ?? '';
  $property_type_labels = array_column($category_selection['property_types'], 'label');

  ?>

  <?php // get_properties_top_search(); // Removed from single property page as requested ?>

  <!--Project Hero-->
	<div class="unit-hero">
		<div class="container">
			<div class="row">
				<div class="col-md-8">
					<div class="unit-info">
						<!--Breadcrumbs-->
            <div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">

              <?php $i = 1; ?>

              <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                <a class="breadcrumbs__link" href="<?php echo siteurl; ?>" itemprop="item"><span itemprop="name"><i class="fa-solid fa-house"></i></span></a>
                <meta itemprop="position" content="<?php echo $i; $i++; ?>">
              </span>
              <span class="breadcrumbs__separator">›</span>

              <?php if ( $location_label !== NULL && $location_label !== '' ): ?>
                <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                  <span class="breadcrumbs__link" itemprop="item">
                    <span itemprop="name"><?php echo esc_html( $location_label ); ?></span></span>
                  <meta itemprop="position" content="<?php echo $i; $i++; ?>">
                </span>
                <span class="breadcrumbs__separator">›</span>
              <?php endif; ?>

              <?php if ($proj_name !== NULL AND $proj_link !== NULL ): ?>
                <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
                  <a class="breadcrumbs__link" href="<?php echo esc_url( $proj_link ); ?>" itemprop="item">
                    <span itemprop="name"><?php echo esc_html( $proj_name ); ?></span></a>
                  <meta itemprop="position" content="<?php echo $i; $i++; ?>">
                </span>
                <span class="breadcrumbs__separator">›</span>
              <?php endif; ?>

            </div>
						<!--End Breadcrumbs-->
						<h1 class="project-headline"><?php echo get_the_title(get_the_ID()); ?></h1>
                                                <div class="location"><i class="icon-location"></i> <?php echo esc_html( $location_label ); ?></div>
                                                <?php if ( $main_category_label || !empty( $property_type_labels ) ) : ?>
                                                    <div class="unit-tags">
                                                        <?php if ( $main_category_label ) : ?>
                                                            <span class="unit-tag-item"><?php echo esc_html( $main_category_label ); ?></span>
                                                        <?php endif; ?>
                                                        <?php foreach ( $property_type_labels as $type_label ) : ?>
                                                            <span class="unit-tag-item"><?php echo esc_html( $type_label ); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
						<!--unit details-->
						<div class="unit-details3">
							<span><?php echo esc_html( $bedrooms ); ?> <i class="icon-bed"></i></span>
							<span><?php echo esc_html( $bathrooms ); ?> <i class="icon-bath"></i></span>
							<span><?php echo esc_html( $garage ); ?> <i class="icon-warehouse"></i></span>
							<span><?php echo esc_html( $size ); ?> <?php get_text('م²','m²'); ?> <i class="icon-resize-full"></i></span>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="unit-price"><span><?php echo number_format(floatval($price)); ?></span> <?php get_text('ج.م','EGP'); ?></div>
				</div>
			</div>
		</div>
	</div>
	<!--End Project Hero-->

  <?php

  $content = ob_get_clean();
  echo minify_html($content);

}



/* -----------------------------------------------------------------------------
# get_my_property_main
----------------------------------------------------------------------------- */

function get_my_property_main()
{

  ob_start();

  $property_id = get_the_ID();

  // phone number
  $phone = carbon_get_theme_option( 'jawda_phone' );

  // whatsapp Number
  $whatsapp = carbon_get_theme_option( 'jawda_whatsapp' );

  // whatsapp Link
  $whatsapplink = get_whatsapp_pro_link($whatsapp,$property_id);


  $price = carbon_get_post_meta( $property_id, 'jawda_price' );
  $bedrooms = carbon_get_post_meta( $property_id, 'jawda_bedrooms' );
  $bathrooms = carbon_get_post_meta( $property_id, 'jawda_bathrooms' );
  $garage = carbon_get_post_meta( $property_id, 'jawda_garage' );
  $size = carbon_get_post_meta( $property_id, 'jawda_size' );
  $year = carbon_get_post_meta( $property_id, 'jawda_year' );
  $location_text = carbon_get_post_meta( $property_id, 'jawda_location' );
  $location_label = '';
  $map = ['lat' => '', 'lng' => '', 'zoom' => 13, 'address' => ''];
  if (class_exists('Jawda_Listing_Location_Service')) {
      $loc = Jawda_Listing_Location_Service::get_location($property_id);
      $name_key = function_exists('jawda_is_arabic_locale') && jawda_is_arabic_locale() ?  'slug_ar'  : 'name_en';

      if (!empty($loc['map'])) {
          $map = array_merge($map, $loc['map']);
      }

      if (!empty($loc['names']['district'][$name_key])) {
          $location_label = $loc['names']['district'][$name_key];
      } elseif (!empty($loc['names']['city'][$name_key])) {
          $location_label = $loc['names']['city'][$name_key];
      } elseif (!empty($loc['names']['governorate'][$name_key])) {
          $location_label = $loc['names']['governorate'][$name_key];
      }
  }

  if ($location_label === '') {
      $location_label = $location_text;
  }
  $installment = carbon_get_post_meta( $property_id, 'jawda_installment' );
  $down_payment = carbon_get_post_meta( $property_id, 'jawda_down_payment' );
  $payment_systems = carbon_get_post_meta( $property_id, 'jawda_payment_systems' );
  $finishing = carbon_get_post_meta( $property_id, 'jawda_finishing' );

  $category_selection = class_exists('Jawda_Listing_Category_Service')
      ? Jawda_Listing_Category_Service::get_selection_with_labels($property_id)
      : ['main_categories' => [], 'property_types' => []];

  $main_category_label = $category_selection['main_categories'][0]['label'] ?? '';
  $property_type_labels = array_column($category_selection['property_types'], 'label');
  $type_badge = $property_type_labels[0] ?? $main_category_label;

  $property_status = featured_city_tag($property_id,'property_status');

  $prop_gallery = carbon_get_post_meta( $property_id, 'jawda_attachments' );
  $faqs = carbon_get_post_meta( $property_id, 'jawda_faq' );
  $priperty_plan = carbon_get_post_meta( $property_id, 'jawda_priperty_plan' );
  $video_url = carbon_get_post_meta( $property_id, 'jawda_video_url' );
  if (isset($map['lat']) && isset($map['lng']) && $map['lat'] === '' && $map['lng'] === '') {
      $map = carbon_get_post_meta( $property_id, 'jawda_map' );
  }

  $pro_projects = carbon_get_post_meta( $property_id, 'jawda_project' );
  $proj_id = NULL;
  if( is_array($pro_projects) AND count($pro_projects) > 0 )
  {
    $proj_id = $pro_projects[0];
  }

  ?>


  <!--Project Main-->
	<div class="project-main">
		<div class="container">
			<div class="row">
				<div class="col-md-8">

          <?php if( is_array($prop_gallery) and count($prop_gallery) > 0 ): ?>
            <div class="unit-banner">
  						<div id="project-slider">
                <?php foreach ($prop_gallery as $galleryphoto) {
                    $photourl = wp_get_attachment_image_src($galleryphoto,'medium_large');
                    echo '<img loading="lazy" src='.$photourl[0].' alt="'.get_the_title().'" width="500" height="300">';
                } ?>
  						</div>

  						<div class="slider-nav">
                <?php foreach ($prop_gallery as $galleryphoto) {
                    $photourl = wp_get_attachment_image_src($galleryphoto,'thumbnail');
                    echo '<img loading="lazy" class="item-slick" src='.$photourl[0].' alt="'.get_the_title().'" width="500" height="300">';
                } ?>
  						</div>
  					</div>
          <?php endif; ?>

					<div class="headline-p"><?php echo get_text('تفاصيل العقار','Unit Details'); ?></div>

					<div class="content-box" style="padding: 0">

                                                <table class="infotable">
                                                        <tbody>

                <?php if ( $location_label !== NULL && $location_label !== '' ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('الموقع','Location'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $location_label ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $main_category_label ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('التصنيف الرئيسي','Main Category'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $main_category_label ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( !empty( $property_type_labels ) ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('أنواع الوحدات','Property Types'); ?></th>
                    <td class="tvalue"><?php echo esc_html( implode(', ', $property_type_labels ) ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $bedrooms !== NUll AND $bedrooms != '' ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('عدد الغرف','number of rooms'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $bedrooms ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $bathrooms !== NUll AND $bathrooms != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('عدد الحمامات','number of bathrooms'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $bathrooms ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $year !== NUll AND $year != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('موعد الاستلام','receiving date'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $year ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $payment_systems !== NUll AND $payment_systems != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('انظمة السداد','payment systems'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $payment_systems ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $finishing !== NUll AND $finishing != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('نوع التشطيب','Finishing type'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $finishing ); ?></td>
  								</tr>
                <?php endif; ?>

                <?php if ( $size !== NUll AND $size != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('المساحة','space'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $size ); ?></td>
                  </tr>
                <?php endif; ?>

                <?php if ( $price !== NUll AND $price != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('السعر','price'); ?></th>
                    <td class="tvalue"><?php echo esc_html( $price ); ?></td>
  								</tr>
                <?php endif; ?>

                <?php if ( ($location ?? "") !== NUll AND ($location ?? "") != ''  ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('الموقع','Location'); ?></th>
                    <td class="tvalue"><?php echo esc_html( ($location ?? "") ); ?></td>
                  </tr>
                <?php endif; ?>

							</tbody>
						</table>
					</div>

					<div class="content-box contact-form hide-pc">
						<div class="headline-p"><?php get_text('للحجز او الاستفسار','For reservations or inquiries'); ?></div>
						<?php my_contact_form(); ?>
					</div>
					<!--cta-btns-->
					<div class="cta-btns hide-pc">
						<a target="_blank" href="<?php echo $whatsapplink; ?>" class="wts-btn"><i class="icon-whatsapp"></i><?php txt('whatsapp'); ?></a>
						<a href="tel:<?php echo $phone; ?>" class="call-btn"><i class="icon-phone"></i><?php txt('phone'); ?></a>
					</div>
					<!--Video-->
          <?php if ( $video_url !== NULL AND $video_url != '' ): ?>
            <?php $videoid = get_youtube_id($video_url); ?>
            <div class="headline-p"><?php get_text('فيديو','Video'); ?></div>
            <div class="content-box">
              <div class="video">
                <iframe
                  width="560"
                  height="315"
                  src="https://www.youtube.com/embed/<?php echo esc_attr($videoid); ?>"
                  srcdoc="<style>*{padding:0;margin:0;overflow:hidden}html,body{height:100%}img,span{position:absolute;width:100%;top:0;bottom:0;margin:auto}span{height:1.5em;text-align:center;font:48px/1.5 sans-serif;color:white;text-shadow:0 0 0.5em black}</style><a href=https://www.youtube.com/embed/<?php echo esc_attr($videoid); ?>?autoplay=1><img loading=lazy src=https://img.youtube.com/vi/<?php echo esc_attr($videoid); ?>/hqdefault.jpg alt='<?php the_title(); ?>'><span>▶</span></a>"
                  frameborder="0"
                  allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen
                  title="<?php the_title(); ?>"
                  style="width:100%;height:auto;min-height:350px;"
                ></iframe>
                <script type="application/ld+json">
                {
                  "@context": "https://schema.org",
                  "@type": "VideoObject",
                  "name": "<?php the_title(); ?>",
                  "description": "<?php the_title(); ?>",
                  "thumbnailUrl": [
                    "https://img.youtube.com/vi/<?php echo esc_attr($videoid); ?>/hqdefault.jpg"
                   ],
                  "uploadDate": "<?php echo get_the_date('Y-m-d'); ?>T08:00:00+08:00",
                  "duration": "PT1M54S",
                  "contentUrl": "<?php echo get_permalink(); ?>",
                  "interactionStatistic": {
                    "@type": "InteractionCounter",
                    "interactionType": { "@type": "http://schema.org/WatchAction" },
                    "userInteractionCount": <?php echo rand(15, 35); ?>
                  }
                }
                </script>
              </div>
            </div>
          <?php endif; ?>

					<!--Master Plan-->
          <?php if ( $priperty_plan !== NULL AND $priperty_plan != '' ): ?>
            <?php $planimg = wp_get_attachment_image_src($priperty_plan,'medium_large'); ?>
            <div class="headline-p"><?php get_text(' مخطط العقار','property plan'); ?></div>
  					<div class="content-box">
  						<div class="master-plan">
  							<img loading="lazy" src="<?php echo $planimg[0]; ?>" width="2332" height="1240" alt="<?php the_title(); ?>" />
  						</div>
  					</div>
          <?php endif; ?>


					<!--Map-->
          <?php if ( is_array($map) AND is_numeric($zoom = $map['zoom']) ): ?>
            <?php
            $lat = $map['lat'];
            $lng = $map['lng'];
            $zoom = $map['zoom'];

            $lat = number_format((float)$lat, 4, '.', '');
            $lng = number_format((float)$lng, 4, '.', '');

            $xtile = floor((($lng + 180) / 360) * pow(2, $zoom));
            $ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
            $n = pow(2, $zoom);
            $lon_deg = ($xtile / $n) * 360.0 - 180.0;
            $lat_deg = rad2deg(atan(sinh(pi() * (1 - 2 * $ytile / $n))));

            $mapurl = 'https://www.openstreetmap.org/export/embed.html?bbox='.$lng.','.$lat.','.$lon_deg.','.$lat_deg.'&amp;layer=mapquest&amp;marker='.$lat.','.$lng.'&amp;zoom=12';
            $mapimg = 'https://tile.openstreetmap.org/'.$zoom.'/'.$xtile.'/'.$ytile.'.png';
            ?>
            <div class="headline-p"><?php get_text('مكان العقار على الخريطة','property on the map'); ?></div>
  					<div class="content-box">
  						<div class="google-location">
                <iframe
                  width="100%"
                  height="400"
                  src="<?php echo $mapurl; ?>"
                  srcdoc="<style>
                    *{padding:0;margin:0;overflow:hidden}
                    html,body{height:100%}
                    img,span{position:absolute;width:100%;top:0;bottom:0;margin:auto}
                    span{height:1.5em;text-align:center;font:48px/1.5 sans-serif;color:#000;text-shadow:0 0 5px black;font-family:Cairo,Arial,Ubuntu;}</style>
                    <a href=<?php echo $mapurl; ?>><img loading=lazy src=<?php echo $mapimg; ?> alt='<?php the_title(); ?>'><span><?php echo 'View Map'; /*get_text('عرض الخريطة','View Map');*/ ?></span></a>"
                  frameborder="0"
                  allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen
                  title="<?php the_title(); ?>"
                  style="width:100%;height:auto;min-height:350px;"
                ></iframe>
                <?php /* <img loading="lazy" src="https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/<?php echo esc_attr($lng).','.esc_attr($lat).','.esc_attr($zoom); ?>,0/500x300?access_token=pk.eyJ1Ijoic2Ficnk5MSIsImEiOiJja3Z2dG5lN2kzaWFrMnBrbGI3ZXU1b296In0.QAXgot7bHTSBjiqY0tunNQ" alt="map"> */ ?>
  						</div>
  					</div>
          <?php endif; ?>


					<!--Project Units-->
          <?php if ( $proj_id !== NULL ): ?>
            <?php project_properties($proj_id); ?>
          <?php endif; ?>

					<!--post-content-->
					<div class="headline-p"><span id="11111"><?php get_text('تفاصيل الوحدة','Unit details'); ?></span></div>
					<div class="content-box maincontent">
            <div class="entry-content">
              <?php wpautop(the_content()); ?>
            </div>
            <div class="contact-center">
							<p><?php get_text('اعجبك المقال؟! شارك','Did you like the article?! Share it'); ?></p>
							<div class="sharing-buttons"><?php theme_share_buttons(); ?></div>
						</div>
						<!--cta-btns-->
						<div class="cta-btns hide-pc">
							<a href="<?php echo $whatsapplink; ?>" class="wts-btn"><i class="icon-whatsapp"></i><?php txt('whatsapp'); ?></a>
							<a href="tel:<?php echo $phone; ?>" class="call-btn"><i class="icon-phone"></i><?php txt('phone'); ?></a>
						</div>
					</div>

          <?php if( isset($faqs[0]['jawda_faq_q']) ): ?>
          <div class="faq">
            <h2 class="headline-p"><?php get_text("أسئلة شائعة","Frequently Asked Questions"); ?></h2>
            <div class="content">
              <div class="acc">
              <?php
                $i = 1;
                if( isset($faqs) && !empty($faqs) ):
                foreach ($faqs as $faq) {
                  $active_class = $i === 1 ? 'active' : '';
                  $active_style = $i === 1 ? 'style="display: block"' : '';
                  ?>
                  <div class="acc__card">
                    <div class="acc__title <?php echo $active_class; ?>"><?php echo esc_html( $faq['jawda_faq_q'] ); ?></div>
                    <div class="acc__panel" <?php echo $active_style; ?>><?php echo esc_html( $faq['jawda_faq_a'] ); ?></div>
                  </div>
                  <?php
                  $i++;
                }
                endif;
                ?>
                <script type="application/ld+json">{"@context": "https://schema.org","@type": "FAQPage","mainEntity": [<?php $i = 0; foreach ($faqs as $faq): ?><?php if( $i != 0 ){ echo ','; } $i++; ?>{"@type": "Question","name": <?php echo json_encode($faq['jawda_faq_q'], JSON_UNESCAPED_UNICODE); ?>,"acceptedAnswer": {"@type": "Answer","text": <?php echo json_encode($faq['jawda_faq_a'], JSON_UNESCAPED_UNICODE); ?>}}<?php endforeach; ?>]}</script>
              </div>
            </div>
          </div>
          <?php endif; ?>
				</div>
				<div class="col-md-4 sticky">
          <?php get_my_sidbar(2,$property_id,$proj_id); ?>
				</div>
			</div>
		</div>
	</div>
	<!--End main-->

  <?php

  $content = ob_get_clean();
  echo minify_html($content);

}


/* -----------------------------------------------------------------------------
# get_my_property_header
----------------------------------------------------------------------------- */

function get_my_related_properties()
{

  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  $featured_projects = carbon_get_theme_option( 'jawda_featured_projects_'.$lang );

  if( isset($featured_projects) && !empty($featured_projects) && $featured_projects !== false ):


  ?>

  <div class="related-projects">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="headline">
						<div class="main-title"><?php get_text('المشروعات المميزة','Featured Projects'); ?></div>
					</div>
				</div>
			</div>
			<div class="row">

        <?php foreach ($featured_projects as $project): ?>

				<div class="col-md-4">
          <?php get_my_project_box($project); ?>
				</div>

        <?php endforeach; ?>

			</div>
		</div>
	</div>
  <?php


    endif;

    $content = ob_get_clean();
    echo minify_html($content);

}




function project_properties($proj_id)
{
  ?>
  <div class="project-units">
    <div class="headline-p"><?php get_text('وحدات اخرى بداخل الكومباوند ','Other units inside the compound'); ?></div>
    <div class="">

        <?php
        $query = new WP_Query( array(
            'post_type'=>'property',
            'posts_per_page' => 5,
            'orderby' => 'rand',
            'meta_query'=>[['key' => 'jawda_project','value' => $proj_id]]
        ) );
        ?>

        <?php if( $query->have_posts() ) : while( $query->have_posts() ) : $query->the_post(); ?>

          <?php
          $project_id = get_the_ID();
          $price = carbon_get_post_meta( $project_id, 'jawda_price' );
          $bedrooms = carbon_get_post_meta( $project_id, 'jawda_bedrooms' );
          $bathrooms = carbon_get_post_meta( $project_id, 'jawda_bathrooms' );
          $garage = carbon_get_post_meta( $project_id, 'jawda_garage' );
          $size = carbon_get_post_meta( $project_id, 'jawda_size' );

          $location_label = '';
          if (class_exists('Jawda_Listing_Location_Service')) {
              $loc = Jawda_Listing_Location_Service::get_location($project_id);
              $name_key = function_exists('jawda_is_arabic_locale') && jawda_is_arabic_locale() ?  'slug_ar'  : 'name_en';

              if (!empty($loc['names']['district'][$name_key])) {
                  $location_label = $loc['names']['district'][$name_key];
              } elseif (!empty($loc['names']['city'][$name_key])) {
                  $location_label = $loc['names']['city'][$name_key];
              } elseif (!empty($loc['names']['governorate'][$name_key])) {
                  $location_label = $loc['names']['governorate'][$name_key];
              }
          }

          if ($location_label === '') {
              $location_label = carbon_get_post_meta( $project_id, 'jawda_location' );
          }

          $category_selection = class_exists('Jawda_Listing_Category_Service')
              ? Jawda_Listing_Category_Service::get_selection_with_labels($project_id)
              : ['main_categories' => [], 'property_types' => []];

          $main_category_label = $category_selection['main_categories'][0]['label'] ?? '';
          $property_type_labels = array_column($category_selection['property_types'], 'label');
          $type_badge = $property_type_labels[0] ?? $main_category_label;

          $property_status = featured_city_tag($project_id,'property_status');

          $img = get_the_post_thumbnail_url($project_id,'medium');
          $title = get_the_title($project_id);
          $url = get_the_permalink($project_id);
           ?>

        <div class="unit-box2">
          <div class="unit-img2">
            <a href="<?php echo $url; ?>"><img loading="lazy" src="<?php echo $img; ?>" width="500" height="300" alt="<?php echo $title; ?>" class="fix-height" /></a>
            <div class="unit-tag2"><?php echo $property_status; ?><?php echo esc_html($type_badge); ?></div>
          </div>

          <div class="unit-data2">
            <div class="unit-title2"><a href="<?php echo $url; ?>"><?php echo $title; ?></a></div>
            <span class="unit-location2"><i class="icon-location"></i> <?php echo esc_html($location_label); ?></span>
            <div class="unit-price2"><span class="price-color"><?php echo $price; ?> <?php get_text('ج.م','EGP'); ?></span></div>
            <div class="unit-details2">
              <span><?php echo $bedrooms; ?> <i class="icon-bed"></i></span>
              <span><?php echo $bathrooms; ?> <i class="icon-bath"></i></span>
              <span><?php echo $garage; ?> <i class="icon-warehouse"></i></span>
              <span><?php echo $size; ?> <i class="icon-resize-full"></i></span>
            </div>
          </div>
        </div>
      <?php endwhile; endif; wp_reset_postdata();  ?>
    </div>
  </div>
  <?php
}



function get_properties_top_search()
{
  ?>
  <div class="topsearchbar">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <?php jawda_property_search_box(); ?>
        </div>
      </div>
    </div>
  </div>
  <?php
}
