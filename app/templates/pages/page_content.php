<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_header
----------------------------------------------------------------------------- */

function get_my_page_content(){

  ob_start();

  $post_id = get_the_ID();

  // phone number
  $phone = carbon_get_theme_option( 'jawda_phone' );

  // whatsapp Number
  $whatsapp = carbon_get_theme_option( 'jawda_whatsapp' );

  // whatsapp Link
  $whatsapplink = get_whatsapp_pro_link($whatsapp,$post_id);

  ?>


  <!--Project Main-->
	<div class="project-main">
		<div class="container">
			<div class="row">
				<div class="col-md-8">

          <?php if ( has_post_thumbnail() ) { ?>
            <div class="single-thumbnail">
              <img src="<?php echo esc_url( get_the_post_thumbnail_url(get_the_ID(),'large') ); ?>" alt="<?php the_title_attribute(); ?>" />
            </div>
          <?php }?>

					<div class="content-box contact-form hide-pc">
						<div class="headline-p"><?php get_text('للحجز او الاستفسار','For reservations or inquiries'); ?></div>
						<?php my_contact_form(); ?>
					</div>
					<!--cta-btns-->
					<div class="cta-btns hide-pc">
						<a target="_blank" href="<?php echo $whatsapplink; ?>" class="wts-btn"><i class="icon-whatsapp"></i><?php txt('whatsapp'); ?></a>
						<a href="tel:<?php echo $phone; ?>" class="call-btn"><i class="icon-phone"></i><?php txt('phone'); ?></a>
					</div>

					<!--post-content-->
					<div class="content-box maincontent">
						<?php wpautop(the_content()); ?>

						<!--cta-btns-->
						<div class="cta-btns hide-pc">
							<a href="<?php echo $whatsapplink; ?>" class="wts-btn"><i class="icon-whatsapp"></i><?php txt('whatsapp'); ?></a>
							<a href="tel:<?php echo $phone; ?>" class="call-btn"><i class="icon-phone"></i><?php txt('phone'); ?></a>
						</div>

					</div>

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
