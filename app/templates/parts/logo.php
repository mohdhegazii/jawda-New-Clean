<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }


function get_my_logo(){

  if ( carbon_get_theme_option( 'jawda_logo' )) {
    $logo = wp_get_attachment_url(carbon_get_theme_option( 'jawda_logo' ));
    ?>
    <a href="<?php echo siteurl; ?>" class="logo"><img loading="lazy" src="<?php echo $logo; ?>" width="300" height="106" alt="<?php echo sitename; ?>"/></a>
    <?php
  } else {

    $locallogo = wimgurl.'logo.png';
    if( is_rtl() ){$locallogo = wimgurl.'logo.png';}

    ?><a href="<?php echo siteurl; ?>" class="logo"><img loading="lazy" src="<?php echo $locallogo; ?>" width="300" height="106" alt="<?php echo sitename; ?>"/></a><?php
  }

}
