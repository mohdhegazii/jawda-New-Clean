<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* --------------------------------------------------------------
# get_my_sidbar
-------------------------------------------------------------- */

function get_my_sidbar($type,$postid,$porjid){
  ob_start();

  // phone number
  $phone = carbon_get_theme_option( 'jawda_phone' );

  // whatsapp Number
  $whatsapp = carbon_get_theme_option( 'jawda_whatsapp' );

  // whatsapp Link
  $whatsapplink = get_whatsapp_pro_link($whatsapp,$postid);

  ?>
  <div class="side-bar">
    <div class="headline-p"><?php get_text('للحجز او الاستفسار','For reservations or inquiries'); ?></div>
    <div class="content-box contact-form">
      <?php my_contact_form(); ?>
    </div>
    <div class="cta-btns">
      <a target="_blank" href="<?php echo $whatsapplink; ?>" class="wts-btn"><i class="icon-whatsapp"></i><?php txt('whatsapp'); ?></a>
      <a href="tel:<?php echo $phone; ?>" class="call-btn"><i class="icon-phone"></i><?php txt('phone'); ?></a>
    </div>
    <?php if ( $porjid !== NULL ): ?>
      <?php
      $term_obj_list = get_the_terms( $porjid, 'projects_developer' );
      if ( is_array($term_obj_list) AND count($term_obj_list) > 0 ) {
        $dev = $term_obj_list[0];
        $dev_name = $dev->name;
        $dev_link = esc_url( get_term_link( $dev ) );
        $img_id = carbon_get_term_meta( $dev->term_id, 'jawda_thumb' );
        $dev_logo = wp_get_attachment_url($img_id,'thumbnail');

        ?>
        <div class="content-box center">
          <div class="developer-info">
            <div class="dev-img">
              <a href="<?php echo $dev_link; ?>"><img loading="lazy" src="<?php echo $dev_logo; ?>" width="600" height="318" alt="<?php echo $dev_name; ?>" /></a>
            </div>
            <p><b><a href="<?php echo $dev_link; ?>"><?php echo $dev_name; ?></a></b></p>
            <div class="btn-side"><a href="<?php echo $dev_link; ?>"><?php get_text('المزيد','More'); ?></a></div>
          </div>
        </div>
        <?php

      }

      ?>

    <?php endif; ?>
  </div>
  <?php
  $content = ob_get_clean();
  echo minify_html($content);

}

/* --------------------------------------------------------------
# sidbar_contacts
-------------------------------------------------------------- */

function get_my_sidbar_contacts(){
  ob_start();
  $phone = carbon_get_theme_option( 'jawda_phone' );
  $mail = carbon_get_theme_option( 'jawda_email' );
  ?>

    <div class="content-box">
      <h2 class="headline-p"><?php txt('to contact us'); ?></h2>
      <div class="contact-info">
        <ul>
          <li><a href="tel:<?php echo $phone; ?>"><i class="icon-phone"></i> <?php echo $phone; ?></a></li>
          <li><a href="mailto:<?php echo $mail; ?>"><i class="icon-mail-alt"></i> <?php echo $mail; ?></a></li>
        </ul>
        <div class="side-social">
          <?php get_my_social(); ?>
        </div>
      </div>
    </div>

  <?php
  $content = ob_get_clean();
  echo minify_html($content);

}



/* --------------------------------------------------------------
# sidbar_form
-------------------------------------------------------------- */

function get_my_sidbar_form($type,$postid){
  ob_start();
  ?>
  <?php if ( $type == 1 ): ?>
    <?php $price = carbon_get_post_meta( $postid, 'jawda_price' ); ?>
    <div class="dark-box"><?php txt('Prices starting from'); echo " ".number_format($price); txt('EGP'); ?></div>
  <?php endif; ?>



  <?php
  $content = ob_get_clean();
  echo minify_html($content);

}
