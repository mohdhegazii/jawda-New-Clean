<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_header
----------------------------------------------------------------------------- */

function get_my_page_contact(){

  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

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

  <!--Project Main-->
  <div class="project-main">
    <div class="container">
      <div class="row">
        <div class="col-md-12">

        <?php if ( !empty(get_the_content()) || get_the_content() !== "" ): ?>
          <div class="content-box maincontent">
            <?php wpautop(the_content()); ?>
          </div>
        <?php endif; ?>

        </div>
        <div class="col-md-6">
          <div class="content-box" id="dbx">
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

        <div class="col-md-6">
          <div class="content-box contact-form" id="form">
            <?php my_contact_form(); ?>
          </div>
        </div>

      </div>
    </div>
  </div>
  <!--End main-->

  <script>
  function adjustContactBoxHeight() {
    var formBox = document.getElementById("form");
    var detailsBox = document.getElementById("dbx");

    if (window.innerWidth > 760) {
      // Reset height to auto before getting the new height to ensure it's calculated correctly
      detailsBox.style.height = 'auto';
      var formHeight = formBox.offsetHeight;
      detailsBox.style.height = formHeight + 'px';
    } else {
      // On smaller screens, reset the height to default
      detailsBox.style.height = 'auto';
    }
  }

  // Adjust on page load
  window.addEventListener('load', adjustContactBoxHeight);

  // Adjust on window resize
  window.addEventListener('resize', adjustContactBoxHeight);
  </script>

  <?php

  $content = ob_get_clean();
  echo minify_html($content);


}
