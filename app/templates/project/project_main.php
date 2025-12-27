<?php
$finishing = ($finishing ?? "") ?? '';
$item = $item ?? [];

$finishing = ($finishing ?? "") ?? "";

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_project_main(){

  ob_start();

  $phone = carbon_get_theme_option( 'jawda_phone' );
  $whatsapp = carbon_get_theme_option( 'jawda_whatsapp' );
  $mail = carbon_get_theme_option( 'jawda_email' );
  $address = carbon_get_theme_option( 'jawda_address' );
  $whatsapplink = get_whatsapp_link($whatsapp);


  $post_id = get_the_ID();
  $title = get_the_title($post_id);
  $price = carbon_get_post_meta($post_id, 'jawda_price' ?: "");
  $installment = carbon_get_post_meta($post_id, 'jawda_installment' ?: "");
  $down_payment = carbon_get_post_meta($post_id, 'jawda_down_payment' ?: "");
  $size = carbon_get_post_meta($post_id, 'jawda_size' ?: "");
  $year = carbon_get_post_meta($post_id, 'jawda_year' ?: "");

  $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();

  $location = carbon_get_post_meta($post_id, 'jawda_location' ?: "");
  // Removed legacy Jawda_Project_Category_Service check
  $unit_types = carbon_get_post_meta($post_id, 'jawda_unit_types' ?: ""); // Fallback to plain text field for now
  $payment_systems = carbon_get_post_meta($post_id, 'jawda_payment_systems' ?: "");
  $priperty_plan = carbon_get_post_meta($post_id, 'jawda_priperty_plan' ?: "");
  $video_url = carbon_get_post_meta($post_id, 'jawda_video_url' ?: "");
  $map = carbon_get_post_meta($post_id, 'jawda_map' ?: "");

  $attachments = carbon_get_post_meta($post_id, 'jawda_attachments' ?: "");
  $faqs = carbon_get_post_meta($post_id, 'jawda_faq' ?: "");

  // Developer
  $developer = jawda_get_project_developer($post_id);
  $dev_name = $dev_link = null;
  if (!empty($developer)) {
    $dev_name = jawda_get_developer_display_name($developer);
    $dev_link = jawda_get_developer_url($developer);
  }

  // New Location Structure
  global $wpdb;
  $name_col = $is_ar ?  'slug_ar'  : 'name_en';

  $location_string = '';
  if (class_exists('Jawda_Location_Service')) {
      $location_data = Jawda_Location_Service::get_location_for_post(get_the_ID());
      $lang = $is_ar ? 'ar' : 'en';
      $location_parts = [];

      foreach (['district', 'city', 'governorate'] as $level) {
          if (empty($location_data['names'][$level])) {
              continue;
          }

          $row = $location_data['names'][$level];
          $label = function_exists('jawda_locations_get_label')
              ? jawda_locations_get_label($row[ 'slug_ar' ] ?? '', $row['name_en'] ?? '', $lang, '')
              : ($is_ar ? ($row[ 'slug_ar' ] ?? '') : ($row['name_en'] ?? ''));

          if ($label !== '') {
              $location_parts[] = $label;
          }
      }

      $location_string = implode(', ', array_filter($location_parts));
  } else {
      $district_id = get_post_meta(get_the_ID(), 'loc_district_id', true) ?: '';
      $city_id = get_post_meta(get_the_ID(), 'loc_city_id', true) ?: '';
      $gov_id = get_post_meta(get_the_ID(), 'loc_governorate_id', true) ?: '';

      $location_parts = [];
      if($district_id){
          $location_parts[] = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}jawda_districts WHERE id = %d", $district_id));
      }
      if($city_id){
          $location_parts[] = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}jawda_cities WHERE id = %d", $city_id));
      }
      if($gov_id){
          $location_parts[] = $wpdb->get_var($wpdb->prepare("SELECT {$name_col} FROM {$wpdb->prefix}jawda_governorates WHERE id = %d", $gov_id));
      }
      $location_string = implode(', ', array_filter($location_parts));
  }

  $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
  $is_ar = function_exists('jawda_is_arabic_locale') ? jawda_is_arabic_locale() : is_rtl();
  $services_language = $is_ar ? 'ar' : 'en';
  $project_services = [];
  if (function_exists('jawda_prepare_project_features_for_display')) {
      $project_services = jawda_prepare_project_features_for_display(get_the_ID(), $services_language);
  }
  $projects_type = get_the_terms( get_the_ID(), 'projects_type' );
  $projects_category = get_the_terms( get_the_ID(), 'projects_category' );
  $projects_tag = get_the_terms( get_the_ID(), 'projects_tag' );


  ?>




  <!--Project Main-->
	<div class="project-main">
		<div class="container">
			<div class="row">
				<div class="col-md-8">
					<div class="headline-p"><?php get_text('تفاصيل','Details'); echo ' '.$title; ?></div>

					<div class="content-box" style="padding: 0">

						<table class="infotable">
							<tbody>
								<tr>
									<th class="ttitle"><?php get_text('اسم المشروع','project name'); ?></th>
									<td class="tvalue"><?php echo get_the_title(get_the_ID()); ?></td>
								</tr>
                <?php if ( !empty($location_string) ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('موقع المشروع','project Location'); ?></th>
                    <td class="tvalue"><?php echo esc_html($location_string); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if ( $unit_types !== NULL AND $unit_types != '' ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('وحدات المشروع','project units'); ?></th>
                    <td class="tvalue"><?php echo $unit_types; ?></td>
                  </tr>
                <?php endif; ?>
                <?php if ( $year !== NULL AND $year != '' ): ?>

                  <tr>
                    <th class="ttitle"><?php get_text('موعد التسليم','Delivery date'); ?></th>
                    <td class="tvalue"><?php echo $year; ?></td>
                  </tr>
                <?php endif; ?>
                <?php if ( $payment_systems !== NULL AND $payment_systems != '' ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('انظمة سداد','Payment Systems'); ?></th>
                    <td class="tvalue"><?php echo $payment_systems; ?></td>
                  </tr>
                <?php endif; ?>
                <?php if ( ($finishing ?? "") !== NULL AND ($finishing ?? "") != '' ): ?>
<?php endif; ?>
                <?php if ( $size !== NULL AND $size != '' ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('مساحات تبدا من','Spaces starting from'); ?></th>
                    <td class="tvalue"><?php echo $size; ?></td>
                  </tr>
                <?php endif; ?>
                <?php if ( $price !== NULL AND $price != '' ): ?>
                  <tr>
                    <th class="ttitle"><?php get_text('اسعار تبدأ من','Prices starting from'); ?></th>
                    <td class="tvalue"><?php echo number_format($price); ?></td>
                  </tr>
                <?php endif; ?>
							</tbody>
						</table>
                                        </div>

                                        <?php
                                        if (function_exists('jawda_render_project_payment_templates')) {
                                            jawda_render_project_payment_templates($post_id);
                                        }
                                        ?>

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
            <div class="headline-p"><?php get_text('فيديو','Video'); echo ' '.$title; ?></div>
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
            <div class="headline-p"><?php get_text('مخطط','plan of'); echo " ".$title; ?></div>
  					<div class="content-box">
  						<div class="master-plan">
  							<img loading="lazy" src="<?php echo $planimg[0]; ?>" width="2332" height="1240" alt="<?php the_title(); ?>" />
  						</div>
  					</div>
          <?php endif; ?>

          <!-- Map -->
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
            <div class="headline-p"><?php get_text('خريطة','map of'); echo " ".$title; ?></div>
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

              </div>
            </div>
          <?php endif; ?>

					<!--Project Units-->
            <?php project_properties($post_id); ?>

					<!--post-content-->
					<div class="content-box maincontent">
						<div class="headline-p"><span id="11111"><?php get_text('تفاصيل المشروع','Project details'); ?></span></div>
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

                                        <!--project-services-->
          <?php if ( !empty($project_services) ): ?>
            <div class="headline-p"><?php get_text('خدمات المشروع','Project Services'); ?></div>
                                        <div class="content-box">
              <?php
                $flattened_services = [];
                foreach ($project_services as $group) {
                    $type  = isset($group['type']) ? (string) $group['type'] : 'feature';
                    $items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
                    if (!$items) {
                        continue;
                    }

                    foreach ($items as $service) {
                        $flattened_services[] = [
                            'type'    => $type,
                            'service' => $service,
                        ];
                    }
                }

                $total_services = count($flattened_services);
                $collapsed_limit = min(4, $total_services);
                $primary_services = array_slice($flattened_services, 0, $collapsed_limit);
                $extra_services = array_slice($flattened_services, $collapsed_limit);
                $hidden_count = count($extra_services);

                $list_classes = ['project-services__list'];
                $list_attributes = [];
                $columns_collapsed = 0;
                $columns_expanded = 0;

                if ($total_services > 0) {
                    if ($hidden_count > 0) {
                        $columns_collapsed = min(5, max(1, $collapsed_limit + 1));
                        $columns_expanded = min(5, max(1, max($total_services, $columns_collapsed)));
                    } else {
                        $columns_collapsed = min(5, max(1, $total_services));
                        $columns_expanded = $columns_collapsed;
                    }
                }

                if ($columns_collapsed > 0) {
                    $list_classes[] = 'project-services__list--columns-' . $columns_collapsed;
                    $list_attributes['data-columns-collapsed'] = (string) $columns_collapsed;
                }

                if ($columns_expanded > 0) {
                    $list_attributes['data-columns-expanded'] = (string) $columns_expanded;
                }

                if ($hidden_count > 0) {
                    $list_attributes['data-collapsed-limit'] = (string) $collapsed_limit;
                }

                $list_attributes['class'] = implode(' ', $list_classes);

                $list_attr_html_parts = [];
                foreach ($list_attributes as $attr_name => $attr_value) {
                    if ($attr_value === '' && $attr_name !== 'class') {
                        continue;
                    }
                    $list_attr_html_parts[] = sprintf('%s="%s"', esc_attr($attr_name), esc_attr($attr_value));
                }
                $list_attr_html = implode(' ', $list_attr_html_parts);

                $more_label = '';
                $less_label = '';
                if ($hidden_count > 0) {
                    if ($services_language === 'ar') {
                        $more_label = sprintf(esc_html__('عرض المزيد (+%d)', 'jawda'), $hidden_count);
                        $less_label = esc_html__('عرض أقل', 'jawda');
                    } else {
                        $more_label = sprintf(esc_html__('Show more (+%d)', 'jawda'), $hidden_count);
                        $less_label = esc_html__('Show less', 'jawda');
                    }
                }
              ?>
              <div class="project-services<?php echo $hidden_count > 0 ? ' project-services--collapsible' : ''; ?>">
                <div <?php echo $list_attr_html; ?>>
                  <?php foreach ($primary_services as $entry_index => $entry): ?>
                    <?php
                      $service = $entry['service'];
                      $label = isset($service['label']) ? (string) $service['label'] : '';
                      $display_name = $services_language === 'ar'
                          ? (isset($service[ 'slug_ar' ]) ? (string) $service[ 'slug_ar' ] : '')
                          : (isset($service['name_en']) ? (string) $service['name_en'] : '');
                      if ($display_name === '') {
                          $display_name = $label;
                      }
                      $image_html = isset($service['image_html']) ? (string) $service['image_html'] : '';
                      $initial = '';
                      if ($image_html === '' && $display_name !== '') {
                          $initial = function_exists('mb_substr') ? mb_substr($display_name, 0, 1, 'UTF-8') : substr($display_name, 0, 1);
                      }
                    ?>
                    <div class="project-services__item project-services__item--primary">
                      <div class="project-services__icon">
                        <?php if ($image_html !== ''): ?>
                          <?php echo wp_kses_post($image_html); ?>
                        <?php elseif ($initial !== ''): ?>
                          <span class="project-services__icon-placeholder" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                        <?php else: ?>
                          <span class="project-services__icon-placeholder" aria-hidden="true">•</span>
                        <?php endif; ?>
                      </div>
                      <div class="project-services__name"><?php echo esc_html($display_name); ?></div>
                    </div>
                  <?php endforeach; ?>

                  <?php if ($hidden_count > 0): ?>
                    <button type="button"
                            class="project-services__item project-services__item--toggle"
                            data-more-label="<?php echo esc_attr($more_label); ?>"
                            data-less-label="<?php echo esc_attr($less_label); ?>"
                            aria-expanded="false">
                      <?php echo esc_html($more_label); ?>
                    </button>

                    <?php foreach ($extra_services as $entry): ?>
                      <?php
                        $service = $entry['service'];
                        $label = isset($service['label']) ? (string) $service['label'] : '';
                        $display_name = $services_language === 'ar'
                            ? (isset($service[ 'slug_ar' ]) ? (string) $service[ 'slug_ar' ] : '')
                            : (isset($service['name_en']) ? (string) $service['name_en'] : '');
                        if ($display_name === '') {
                            $display_name = $label;
                        }
                        $image_html = isset($service['image_html']) ? (string) $service['image_html'] : '';
                        $initial = '';
                        if ($image_html === '' && $display_name !== '') {
                            $initial = function_exists('mb_substr') ? mb_substr($display_name, 0, 1, 'UTF-8') : substr($display_name, 0, 1);
                        }
                      ?>
                      <div class="project-services__item project-services__item--primary project-services__item--extra"<?php echo $hidden_count > 0 ? ' hidden' : ''; ?>>
                        <div class="project-services__icon">
                          <?php if ($image_html !== ''): ?>
                            <?php echo wp_kses_post($image_html); ?>
                          <?php elseif ($initial !== ''): ?>
                            <span class="project-services__icon-placeholder" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                          <?php else: ?>
                            <span class="project-services__icon-placeholder" aria-hidden="true">•</span>
                          <?php endif; ?>
                        </div>
                        <div class="project-services__name"><?php echo esc_html($display_name); ?></div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>

                </div>
              </div>
                                        </div>
          <?php endif; ?>

					<!--End-->
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
                    <div class="acc__title <?php echo $active_class; ?>"><?php echo esc_html($faq['jawda_faq_q']); ?></div>
                    <div class="acc__panel" <?php echo $active_style; ?>><?php echo esc_html($faq['jawda_faq_a']); ?></div>
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
          <?php get_my_sidbar(2,$post_id,$post_id); ?>
				</div>
			</div>
		</div>
	</div>
	<!--End main-->








  <?php
  $content = ob_get_clean();
  echo minify_html($content);


}
