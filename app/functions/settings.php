<?php

// Security Check
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Theme Options
----------------------------------------------------------------------------- */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', 'jawda_attach_theme_options' );
function jawda_attach_theme_options() {
  if (isset($_POST['run_jawda_catalog_update'])) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    jawda_update_location_catalogs();
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>Location catalogs have been successfully updated.</p></div>';
    });
  }

  // Options
  $basic_options_container = Container::make( 'theme_options', __( 'Jawda Settings' ) )
    ->set_page_file( 'jawda-welcome-page' )
    ->set_icon( 'dashicons-carrot' )

    ->add_tab(__('Welcome Page'),array(

      Field::make( 'html', 'jawda_html_1', __( 'Section Description' ) )->set_html( 'jawda_welcome_message' ),

    ));

    /* ---------------------------------------------------
    # Site Settings
    --------------------------------------------------- */

    // Codes Options
    Container::make( 'theme_options', __( 'Site Settings' ) )
      ->set_page_file( 'jawda-site-options' )
      ->set_page_parent( $basic_options_container ) // reference to a top level container
      // Site Settings Tab
      ->add_tab(__('Site Settings'),array(

        // Logo
        Field::make( 'image', 'jawda_logo', __( 'Logo' ) ),

        // Phone
        Field::make( 'text', 'jawda_phone', __( 'Phone Number' ) ),

        // Phone
        Field::make( 'text', 'jawda_whatsapp', __( 'Whatsapp' ) ),

        // Email
        Field::make( 'text', 'jawda_email', __( 'Email' ) ),

        // Address
        Field::make( 'text', 'jawda_address_ar', __( 'Address Arabic' ) ),
        Field::make( 'text', 'jawda_address_en', __( 'Address English' ) ),

        // about
        Field::make( 'textarea', 'jawda_footer_about_ar', __( 'Footer About Us Arabic' ) ),
        Field::make( 'textarea', 'jawda_footer_about_en', __( 'Footer About Us English' ) ),


        Field::make( 'html', 'crb_html', __( '' ) )->set_html( 'jawda_support' ),

      ))

      // Social Links Tab
      ->add_tab(__('Site Pages'),array(

        // Contact Page
        Field::make( 'separator', 'jawda_separator_z01', __( 'contact-us page' ) ),
        Field::make( 'select', 'jawda_page_contact_us_ar', __( 'contact-us page Arabic' ) )->add_options( 'get_my_pages_list' ),
        Field::make( 'select', 'jawda_page_contact_us_en', __( 'contact-us page English' ) )->add_options( 'get_my_pages_list' ),

        // Contact Page
        Field::make( 'separator', 'jawda_separator_z02', __( 'Thankyou page' ) ),
        Field::make( 'select', 'jawda_page_thankyou_ar', __( 'Thankyou page Arabic' ) )->add_options( 'get_my_pages_list' ),
        Field::make( 'select', 'jawda_page_thankyou_en', __( 'Thankyou page English' ) )->add_options( 'get_my_pages_list' ),

        // Contact Page
        Field::make( 'separator', 'jawda_separator_z03', __( 'Blog page' ) ),
        Field::make( 'select', 'jawda_page_blog_ar', __( 'Blog page Arabic' ) )->add_options( 'get_my_pages_list' ),
        Field::make( 'select', 'jawda_page_blog_en', __( 'Blog page English' ) )->add_options( 'get_my_pages_list' ),

        // Contact Page
        Field::make( 'separator', 'jawda_separator_z04', __( 'Projects page' ) ),
        Field::make( 'select', 'jawda_page_projects_ar', __( 'Projects page Arabic' ) )->add_options( 'get_my_pages_list' ),
        Field::make( 'select', 'jawda_page_projects_en', __( 'Projects page English' ) )->add_options( 'get_my_pages_list' ),

        // Contact Page
        Field::make( 'separator', 'jawda_separator_z74', __( 'Properties page' ) ),
        Field::make( 'select', 'jawda_page_properties_ar', __( 'Properties page Arabic' ) )->add_options( 'get_my_pages_list' ),
        Field::make( 'select', 'jawda_page_properties_en', __( 'Properties page English' ) )->add_options( 'get_my_pages_list' ),

      ))


      // Social Links Tab
      ->add_tab(__('Social Links'),array(

        Field::make( 'text', 'jawda_facebook_link', __( 'Facebook Link' ) ),
        Field::make( 'text', 'jawda_twitter_link', __( 'Twitter Link' ) ),
        Field::make( 'text', 'jawda_youtube_link', __( 'YouTube Link' ) ),
        Field::make( 'text', 'jawda_pinterest_link', __( 'Pinterest Link' ) ),
        Field::make( 'text', 'jawda_instagram_link', __( 'Instagram Link' ) ),
        Field::make( 'text', 'jawda_linkedin_link', __( 'LinkedIn Link' ) ),

      ))

      ->add_tab(__('Colors'),array(

        Field::make( 'color', 'jawda_color_1', __( 'Color 1' ) ),
        Field::make( 'color', 'jawda_color_2', __( 'Color 2' ) ),
        Field::make( 'color', 'jawda_color_3', __( 'Color 3' ) ),


      ))
      ->add_tab(__('Advanced'), array(
          Field::make( 'html', 'jawda_manual_catalog_update_button' )
              ->set_html( '
                  <div style="padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
                      <h3>' . __( 'Manual Catalog Update', 'jawda' ) . '</h3>
                      <p>' . __( 'Click the button below to manually run the script that creates and updates all location-based catalogs. This is useful after making significant changes to locations or projects.', 'jawda' ) . '</p>
                      <form method="post">
                          <input type="submit" name="run_jawda_catalog_update" class="button button-primary" value="' . __( 'Run Catalog Update Script', 'jawda' ) . '">
                      </form>
                  </div>
              ' )
      ))
      ;

      /* ---------------------------------------------------
      # Home Settings
      --------------------------------------------------- */

  // Codes Options
  Container::make( 'theme_options', __( 'Home Page Settings' ) )
    ->set_page_file( 'jawda-homepage-options' )
    ->set_page_parent( $basic_options_container ) // reference to a top level container



    // Featured Projects
    ->add_tab( __('Home Featured Properties'), array(

        // Featured Projects
        Field::make( 'separator', 'jawda_separator_031111', __( 'Featured Properties Arabic' ) ),
        Field::make( 'multiselect', 'jawda_home_featured_properties_ar', __( 'Featured Properties Arabic' ) )->add_options( 'get_my_properties_list' ),

        // Featured Projects
        Field::make( 'separator', 'jawda_separator_031211', __( 'Featured Properties English' ) ),
        Field::make( 'multiselect', 'jawda_home_featured_properties_en', __( 'Featured Properties English' ) )->add_options( 'get_my_properties_list' ),

    )
    )


    // Featured Projects
    ->add_tab( __('Home Featured Projects Categories'), array(

        // Featured Projects
        Field::make( 'separator', 'jawda_separator_034521', __( 'Featured Projects Arabic' ) ),
        Field::make( 'complex', 'jawda_home_featured_projects_ar', __( 'Featured Projects Arabic' ) )
        ->add_fields( array(
            Field::make( 'select', 'id', __( 'Choose Project' ) )->add_options( 'get_my_projects_type_list' ),
            Field::make( 'select', 'img', __( 'Choose Options' ) )
	           ->set_options( array('administrative' => 'administrative','coastal' => 'coastal','commercial' => 'commercial','medical' => 'medical','residential' => 'residential',) )
        )),


        // Featured Projects
        Field::make( 'separator', 'jawda_separator_0345711', __( 'Featured Projects English' ) ),
        Field::make( 'complex', 'jawda_home_featured_projects_en', __( 'Featured Projects English' ) )
        ->add_fields( array(
            Field::make( 'select', 'id', __( 'Choose Project' ) )->add_options( 'get_my_projects_type_list' ),
            Field::make( 'select', 'img', __( 'Choose Options' ) )
	           ->set_options( array('administrative' => 'administrative','coastal' => 'coastal','commercial' => 'commercial','medical' => 'medical','residential' => 'residential',) )
        )),
    )
    )



    // Featured Projects
    ->add_tab( __('Featured Projects'), array(

        // Featured Projects
        Field::make( 'separator', 'jawda_separator_dfgdf1', __( 'Featured Projects Arabic' ) ),
        Field::make( 'multiselect', 'jawda_featured_projects_ar', __( 'Featured projects Arabic ( Choose 3 Projects )' ) )->add_options( 'get_my_projects_list' ),



        // Featured Projects
        Field::make( 'separator', 'jawda_separator_adzdcj11', __( 'Featured Projects English' ) ),
        Field::make( 'multiselect', 'jawda_featured_projects_en', __( 'Featured projects English ( Choose 3 Projects )' ) )->add_options( 'get_my_projects_list' ),

    )
    );



  // Codes Options
  Container::make( 'theme_options', __( 'Insert Codes' ) )
    ->set_page_file( 'jawda-codes-options' )
    ->set_page_parent( $basic_options_container ) // reference to a top level container
    ->add_fields( array(
      Field::make( 'textarea', 'jawda_header_script', __( 'Header Scripts' ) ),
      Field::make( 'textarea', 'jawda_body_script', __( 'Body Scripts' ) ),
      Field::make( 'textarea', 'jawda_footer_script', __( 'Footer Scripts' ) ),
      Field::make( 'textarea', 'jawda_thankyou_script', __( 'Thank You Script' ) ),
    )
  );



}


/* -----------------------------------------------------------------------------
// Pages List
----------------------------------------------------------------------------- */

function get_my_pages_list(){
  $return = [];
  $pages = get_pages();
  foreach ($pages as $page) {
    $return[$page->ID] = $page->post_title;
  }
  return $return;
}

function get_my_cities_list(){
  global $wpdb;
  $return = [];
  $table_name = $wpdb->prefix . 'jawda_cities';
  $cities = $wpdb->get_results( "SELECT id, name_ar FROM $table_name" );
  foreach ($cities as $city) {
    $return[$city->id] = $city->name_ar;
  }
  return $return;
}


function get_my_projects_list(){
  $return = [];
  $pages = get_posts(['post_type' => 'projects','post_status' => 'publish','numberposts' => -1]);
  foreach ($pages as $page) {
    $return[$page->ID] = $page->post_title;
  }
  return $return;
}

// get_my_properties_list
function get_my_properties_list(){
  $return = [];
  $pages = get_posts(['post_type' => 'property','post_status' => 'publish','numberposts' => -1]);
  foreach ($pages as $page) {
    $return[$page->ID] = $page->post_title;
  }
  return $return;
}

function get_my_areas_list(){
  $return = [];
  $terms = get_terms( 'projects_area', array('hide_empty' => false,) );
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}
function get_my_states_list(){
  $return = [];
  $terms = get_terms( 'property_state', array('hide_empty' => false,) );
  foreach ($terms as $term) {
    $return[$term->term_id] = $term->name;
  }
  return $return;
}

function get_my_types_list(){
  $return = [];
  $terms = get_terms( 'property_type', array('hide_empty' => false,) );
  foreach ((array)$terms as $term) {
    $id   = is_object($term) ? ($term->term_id ?? null) : ($term['term_id'] ?? null);
    $name = is_object($term) ? ($term->name ?? '') : ($term['name'] ?? '');
    if ($id) { $return[$id] = $name; }
  }
  return $return;
}

function get_my_projects_type_list(){
  $return = [];
  $terms = get_terms( 'projects_type', array('hide_empty' => false,) );
  foreach ((array)$terms as $term) {
    $id   = is_object($term) ? ($term->term_id ?? null) : ($term['term_id'] ?? null);
    $name = is_object($term) ? ($term->name ?? '') : ($term['name'] ?? '');
    if ($id) { $return[$id] = $name; }
  }
  return $return;
}
/* -----------------------------------------------------------------------------
# Option Helpers
----------------------------------------------------------------------------- */

// carbon_get_theme_option( 'jawda_addresses' )