<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }


function get_my_favicons(){
?>
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo wfavurl; ?>apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo wfavurl; ?>favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo wfavurl; ?>favicon-16x16.png">
<link rel="manifest" href="<?php echo wfavurl; ?>site.webmanifest">
<link rel="mask-icon" href="<?php echo wfavurl; ?>safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="<?php echo wfavurl; ?>favicon.ico">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="msapplication-config" content="<?php echo wfavurl; ?>browserconfig.xml">
<meta name="theme-color" content="#ffffff">
<?php
}
