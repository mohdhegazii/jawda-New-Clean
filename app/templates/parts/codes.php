<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }


function get_my_header_codes(){
  if( carbon_get_theme_option( 'jawda_header_script' ) ){
    echo carbon_get_theme_option( 'jawda_header_script' );
  }
}


function get_my_body_codes(){
  if( carbon_get_theme_option( 'crb_body_script' ) ){
    echo carbon_get_theme_option( 'crb_body_script' );
  }
}

function get_my_footer_codes(){

}
