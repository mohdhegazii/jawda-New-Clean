<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }


function get_my_footer(){

  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  $jawda_page_contact_us = carbon_get_theme_option( 'jawda_page_contact_us' );

  // footer about us
  $aboutus = carbon_get_theme_option( 'jawda_footer_about_'.$lang );

  // phone number
  $phone = carbon_get_theme_option( 'jawda_phone' );

  // whatsapp Number
  $whatsapp = carbon_get_theme_option( 'jawda_whatsapp' );

  // whatsapp Link
  $whatsapplink = get_whatsapp_link($whatsapp);

  // email
  $mail = carbon_get_theme_option( 'jawda_email' );

  // Adress
  $address = carbon_get_theme_option( 'jawda_address_'.$lang );

  ?>



  <!--important links-->
	<div class="imp-links">
		<div class="container">
			<div class="row">
				<div class="col-md-3">
					<div class="link-box">
						<div class="imp-title"><?php get_text('أحدث المشروعات','Latest Projects'); ?></div>
						<?php
						$args = array(
							'post_type'      => 'projects',
							'posts_per_page' => 15,
							'post_status'    => 'publish',
							'orderby'        => 'date',
							'order'          => 'DESC',
						);
						$latest_projects_query = new WP_Query( $args );
						if ( $latest_projects_query->have_posts() ) {
							echo '<ul class="quick-links initial-links">';
							$count = 0;
							$more_links = '';
							while ( $latest_projects_query->have_posts() ) {
								$latest_projects_query->the_post();
								$link = '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
								if ( $count < 5 ) {
									echo $link;
								} else {
									$more_links .= $link;
								}
								$count++;
							}
							echo '</ul>';

							if ( ! empty( $more_links ) ) {
								echo '<ul class="quick-links more-links" style="display: none;">' . $more_links . '</ul>';
								ob_start();
								get_text( 'المزيد', 'Show More' );
								$more_text = ob_get_clean();
								echo '<button class="more-less-button">' . esc_html( $more_text ) . '</button>';
							}
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="link-box">
						<div class="imp-title"><?php get_text('أفضل المشروعات','Best Projects'); ?></div>
            <?php if ( has_nav_menu( 'important_links_4' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'important_links_4', 'menu_class' => 'quick-links' ) ); } ?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="link-box">
						<div class="imp-title"><?php get_text('أشهر المناطق','Most popular regions'); ?></div>
            <?php if ( has_nav_menu( 'important_links_2' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'important_links_2', 'menu_class' => 'quick-links' ) ); } ?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="link-box">
						<div class="imp-title"><?php get_text('أفضل المطورين','Best Developers'); ?></div>
            <?php if ( has_nav_menu( 'important_links_3' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'important_links_3', 'menu_class' => 'quick-links' ) ); } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--End important links-->

	<!--Footer-->
	<div id="footer">

		<div class="container">
			<div class="row">
				<div class="col-md-4">
					<div class="headline right">
						<div class="main-title"><?php get_text('عن الشركة','About company'); ?></div>
					</div>
					<div class="about-us">
            <?php echo esc_attr($aboutus); ?>
					</div>
					<div class="footer-social">
						<?php get_my_social(); ?>
					</div>
				</div>
				<div class="col-md-4">
					<div class="headline right">
						<div class="main-title"><?php get_text('تواصل معنا','Connect with us'); ?></div>
					</div>
					<div class="contact">
						<ul>
              <?php if ( $phone !== NULL AND $phone != '' ): ?>
                <li><a href="tel:<?php echo esc_attr($phone); ?>"><i class="icon-phone"></i> <?php echo esc_attr($phone); ?></a></li>
              <?php endif; ?>
              <?php if ( $mail !== NULL  AND $mail != '' ): ?>
                <li><a href="mailto:<?php echo sanitize_email($mail); ?>"><i class="icon-mail-alt"></i> <?php echo sanitize_email($mail); ?></a></li>
              <?php endif; ?>
              <?php if ( $whatsapplink != NULL AND $whatsapplink != '' ): ?>
                <li><a href="<?php echo esc_url($whatsapplink); ?>" target="_blank"><i class="icon-whatsapp"></i> Whatsapp</a></li>
              <?php endif; ?>
              <?php if ( $address !== NULL AND $address != '' ): ?>
                <li><i class="icon-location"></i> <?php echo esc_attr($address); ?></li>
              <?php endif; ?>
						</ul>
					</div>
				</div>
				<div class="col-md-2">
					<div class="headline right">
						<div class="main-title"><?php get_text('الدعم','Support'); ?></div>
					</div>
					<div class="contact">
            <?php if ( has_nav_menu( 'footer_menu_1' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'footer_menu_1', 'menu_class' => 'quick-links' ) ); } ?>
					</div>
				</div>
				<div class="col-md-2">
					<div class="headline right">
						<div class="main-title"><?php get_text('روابط مهمة','Important links'); ?></div>
					</div>
					<div class="contact">
            <?php if ( has_nav_menu( 'footer_menu_2' ) ) { wp_nav_menu( array( 'container'=> false, 'theme_location' => 'footer_menu_2', 'menu_class' => 'quick-links' ) ); } ?>
					</div>
				</div>
			</div>
		</div>
		<div id="copyright">
			<div class="container">
        <div class="row">
          <div class="col-md-6 <?php get_text('right', 'left'); ?>">
            <?php get_text('جميع الحقوق محفوظة','Copyright'); ?> <?php echo date("Y"); ?> © <a href="<?php echo siteurl; ?>"> <?php echo sitename; ?> </a>
          </div>

        </div>
			</div>
		</div>
	</div>
	<!--End Footer-->

	<!--floating icons-->
	<a href="#" id="back-top" class="hide-me"><i class="icon-up-big"></i></a>
	<div id="floating-icons" class="hide-me">
    <?php if ( $phone !== NULL AND $phone != '' ): ?>
      <a href="tel:<?php echo esc_attr($phone); ?>" aria-label="call"><i class="icon-phone"></i></a>
    <?php endif; ?>
    <?php if ( $whatsapplink != NULL AND $whatsapplink != '' ): ?>
      <a target="_blank" href="<?php echo esc_url($whatsapplink); ?>" aria-label="whatsapp"><i class="icon-whatsapp"></i></a>
    <?php endif; ?>
		<a href="#contact" aria-label="contact-us"><i class="icon-mail"></i></a>
	</div>

	<!--Light Box Booking Form-->
	<div class="lightbox-target" id="contact">
		<div class="popup-form">
			<div class="form-title"><?php get_text('تواصل معنا','Connect with us'); ?></div>
			<?php my_contact_form(); ?>
			<a href="#close"><i class="icon-cancel"></i></a>
		</div>
		<a class="lightbox-close" href="#close" aria-label="form"></a>
	</div>
	<!--End Light Box Form-->

  <div class="responsebox">
    <div class="responsecolse">❌</div>
    <div class="responsehere"></div>
  </div>

  <?php

  if( carbon_get_theme_option( 'jawda_footer_script' ) ){
    echo carbon_get_theme_option( 'jawda_footer_script' );
  }

  // Theme Scripts
  get_my_scripts();

  // Wp Scripts
  wp_footer();
  ?>

  </body>
  </html>

  <?php
  $content = ob_get_clean();
  echo minify_html($content);

}