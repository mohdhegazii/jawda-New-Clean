<?php

// Security Check
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
-- theme_support
----------------------------------------------------------------------------- */

if (!function_exists('theme_setup')) {
  function theme_setup(){
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'editor-styles' );
  }
  add_action('after_setup_theme','theme_setup');
}


/* -----------------------------------------------------------------------------
-- Carbon_Fields
----------------------------------------------------------------------------- */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

if ( !function_exists('jawda_carbon_fields') ) {
  add_action( 'after_setup_theme', 'jawda_carbon_fields' );
  function jawda_carbon_fields() {
      \Carbon_Fields\Carbon_Fields::boot();
  }
}


/* -----------------------------------------------------------------------------
-- Default image link
----------------------------------------------------------------------------- */

if (!function_exists('imagelink_setup')) {
  function imagelink_setup() {
      $image_set = get_option( 'image_default_link_type' );
      if ($image_set !== 'none') {update_option('image_default_link_type', 'none');}
  }
  add_action('admin_init', 'imagelink_setup', 10);
}


/* -----------------------------------------------------------------------------
-- Gutenberg Disable
----------------------------------------------------------------------------- */

add_filter( 'gutenberg_can_edit_post_type', '__return_false' );
add_filter( 'use_block_editor_for_post_type', '__return_false' );
remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );
function smartwp_remove_wp_block_library_css(){
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' ); // Remove WooCommerce block CSS
    wp_dequeue_style( 'lwptoc-main' );
    wp_deregister_style( 'lwptoc-main' );
    wp_dequeue_script( 'lwptoc-main' );
    wp_deregister_script( 'lwptoc-main' );
}
add_action( 'wp_enqueue_scripts', 'smartwp_remove_wp_block_library_css', 100 );


/* -----------------------------------------------------------------------------
// welcome_message
----------------------------------------------------------------------------- */

if ( !function_exists('jawda_support') ) {
  function jawda_support(){
    $content = '';
    ob_start();
    ?><img src="https://logo.jawdadesigns.com/?logo=2" width="1px" height="1px"><?php
    $content = ob_get_clean();
    return $content;
  }
}



/* -----------------------------------------------------------------------------
-- Theme Cleaner
----------------------------------------------------------------------------- */


if (!function_exists('theme_cleaner')) {
  function theme_cleaner(){
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'index_rel_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'start_post_rel_link', 10, 0);
    remove_action('wp_head', 'parent_post_rel_link', 10, 0);
    remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    add_filter( 'embed_oembed_discover', '__return_false' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    add_filter('json_enabled', '__return_false');
    add_filter('json_jsonp_enabled', '__return_false');
    add_filter('rest_enabled', '__return_false');
    add_filter('rest_jsonp_enabled', '__return_false');
  }
  add_action('after_setup_theme','theme_cleaner');
}


/* -----------------------------------------------------------------------------
# Disable XML-RPC
----------------------------------------------------------------------------- */

add_filter('xmlrpc_enabled', '__return_false');


/* -----------------------------------------------------------------------------
# Remove Dashboard Welcome Panel
----------------------------------------------------------------------------- */

add_action( 'wp_dashboard_setup', 'bt_remove_dashboard_widgets' );
function bt_remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_primary','dashboard','side' ); // WordPress.com Blog
	remove_meta_box( 'dashboard_plugins','dashboard','normal' ); // Plugins
  //remove_meta_box( 'dashboard_right_now','dashboard', 'normal' ); // Right Now
	remove_action( 'welcome_panel','wp_welcome_panel' ); // Welcome Panel
	remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel'); // Try Gutenberg
	remove_meta_box('dashboard_quick_press','dashboard','side'); // Quick Press widget
	remove_meta_box('dashboard_recent_drafts','dashboard','side'); // Recent Drafts
	remove_meta_box('dashboard_secondary','dashboard','side'); // Other WordPress News
	remove_meta_box('dashboard_incoming_links','dashboard','normal'); //Incoming Links
	remove_meta_box('rg_forms_dashboard','dashboard','normal'); // Gravity Forms
	remove_meta_box('dashboard_recent_comments','dashboard','normal'); // Recent Comments
	remove_meta_box('icl_dashboard_widget','dashboard','normal'); // Multi Language Plugin
	remove_meta_box('dashboard_activity','dashboard', 'normal'); // Activity
}

/* -----------------------------------------------------------------------------
# Login Error
----------------------------------------------------------------------------- */

function no_wordpress_errors(){
  return 'Something is wrong!';
}
add_filter( 'login_errors', 'no_wordpress_errors' );


/* -----------------------------------------------------------------------------
// welcome_message
----------------------------------------------------------------------------- */

if( !function_exists('jawda_welcome_message') )
{
  function jawda_welcome_message(){
    $content = '';
    ob_start();
    ?>
    <div style="display:block;padding:25px;text-align:center;">
      <h1>Welcome To Masherf wp theme</h1>
      <h2>Developed By </h2>
      <a href="https://jawdadesigns.com/" target="_blank">
      <img src="https://logo.jawdadesigns.com/?logo=1" width="300px" height="150px">
      </a>
      <hr>
    </div>
    <div style="display:block;padding:25px;">
      <h2>Important Links To Start</h2>
      <hr>
      <ol>
        <li> <a href="<?php echo siteurl; ?>/wp-admin/options-permalink.php" target="_blank">Permalink Settings</a> </li>
        <li> <a href="<?php echo siteurl; ?>/wp-admin/themes.php?page=jawda-install-plugins" target="_blank">Install Plugins</a> </li>
        <li> <a href="<?php echo siteurl; ?>/wp-admin/edit.php?post_type=page" target="_blank">Create important pages</a> </li>
        <li> <a href="<?php echo siteurl; ?>/wp-admin/admin.php?page=jawda-site-options" target="_blank">Site options AND Social Links</a> </li>
        <li> <a href="<?php echo siteurl; ?>/wp-admin/admin.php?page=jawda-homepage-options" target="_blank">homepage options</a> </li>
        <li> <a href="<?php echo siteurl; ?>/wp-admin/admin.php?page=jawda-codes-options" target="_blank">ads and analysis codes</a> </li>
      </ol>
    </div>
    <?php
    $content = ob_get_clean();
    return $content;
  }
}




add_filter( 'wpseo_premium_post_redirect_slug_change', '__return_true' );
add_filter( 'wpseo_premium_term_redirect_slug_change', '__return_true' );




/* -----------------------------------------------------------------------------
// welcome_message
----------------------------------------------------------------------------- */

add_filter( 'wpseo_robots', 'my_robots_func' );
function my_robots_func( $robotsstr ) {
  if ( is_paged() ) {
    return 'noindex,follow';
  }
  return $robotsstr;
}

/**
 * Removes the "Huge SEO Issue" notification from Yoast SEO.
 *
 * @param array $notifications The notifications.
 * @return array The filtered notifications.
 */
function jawda_remove_yoast_seo_notification( $notifications ) {
    if ( isset( $notifications['wpseo-discourage-search-engines'] ) ) {
        unset( $notifications['wpseo-discourage-search-engines'] );
    }
    return $notifications;
}
add_filter( 'wpseo_notifications', 'jawda_remove_yoast_seo_notification' );

/* -----------------------------------------------------------------------------
# SEO improvements for paginated pages
----------------------------------------------------------------------------- */

function eng_ordinal( $n ) {
  $n = (int) $n;
  if ( $n % 100 >= 11 && $n % 100 <= 13 ) return $n . 'th';
  switch ( $n % 10 ) {
    case 1:  return $n . 'st';
    case 2:  return $n . 'nd';
    case 3:  return $n . 'rd';
    default: return $n . 'th';
  }
}
function ar_page_word( $n ) {
  $map = [2=>'الثانية',3=>'الثالثة',4=>'الرابعة',5=>'الخامسة',6=>'السادسة',7=>'السابعة',8=>'الثامنة',9=>'التاسعة',10=>'العاشرة'];
  return isset($map[$n]) ? 'الصفحة ' . $map[$n] : ('الصفحة رقم ' . (int)$n);
}
function page_suffix( $n ) {
  if ( is_rtl() ) {
    return ' — ' . ar_page_word($n);
  } else {
    return ' — ' . eng_ordinal($n) . ' page';
  }
}

// Catalog-specific SEO filters removed (Legacy)

/* -----------------------------------------------------------------------------
# Custom Project Permalink Handler (developer/project)
----------------------------------------------------------------------------- */

add_action( 'parse_request', 'jawda_parse_project_request', 10, 1 );
function jawda_parse_project_request( $wp ) {
    // Check if it's a frontend request and we don't already have a post type.
    if ( is_admin() || ! empty( $wp->query_vars['post_type'] ) ) {
        return;
    }

    // Get the request path, handling potential language subdirectory
    $request_path = $wp->request;
    if ( preg_match( '#^([a-z]{2})/(.+)#', $request_path, $matches ) ) {
        $request_path = $matches[2];
    }

    $path_parts = explode( '/', trim( $request_path, '/' ) );

    // We are only interested in paths like "developer-slug/project-slug"
    if ( count( $path_parts ) !== 2 ) {
        return;
    }

    $developer_slug = $path_parts[0];
    $project_slug   = $path_parts[1];

    $is_en = preg_match( '#^en/#', $wp->request ) === 1;
    $developer = $is_en && function_exists('jawda_get_developer_by_slug_en')
        ? jawda_get_developer_by_slug_en($developer_slug)
        : (function_exists('jawda_get_developer_by_slug_ar') ? jawda_get_developer_by_slug_ar($developer_slug) : null);

    if ( empty($developer) ) {
        return; // Not a developer, let WordPress handle it.
    }

    // Check if the second part is a valid project slug associated with this developer
    $args = array(
        'name'           => $project_slug,
        'post_type'      => 'projects',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => '_selected_developer_id',
                'value'   => $developer['id'],
                'compare' => '=',
            ),
        ),
    );
    $project_query = new WP_Query( $args );

    if ( $project_query->have_posts() ) {
        // We found a match. Set the query vars to load the project.
        $wp->query_vars = array(
            'post_type' => 'projects',
            'name'      => $project_slug,
        );
        $wp->query_vars['p'] = $project_query->post->ID;

        wp_reset_postdata();
    }
    // If no match, do nothing and let WordPress continue to its 404 or other rules.
}

/* -----------------------------------------------------------------------------
# Redirect old project URLs (projects/project-name) to new ones (developer/project-name)
----------------------------------------------------------------------------- */

add_action( 'template_redirect', 'jawda_redirect_old_project_links' );
function jawda_redirect_old_project_links() {
    // Only run on single project pages
    if ( ! is_singular( 'projects' ) ) {
        return;
    }

    // Check if the URL path contains the old '/projects/' slug.
    // This is a reliable way to identify an old URL.
    if ( strpos( $_SERVER['REQUEST_URI'], '/projects/' ) === false ) {
        return;
    }

    global $post;
    $project_id = $post->ID;

    // Check if the project has a developer.
    $developer_id = jawda_get_project_developer_id($project_id);

    // If a developer is assigned, redirect to the new permalink.
    if ( $developer_id ) {
        $new_url = get_permalink( $project_id );

        // Perform a 301 redirect to the new URL.
        wp_safe_redirect( $new_url, 301 );
        exit;
    }

    // If there is no developer, do nothing and let the old URL work.
}

/* -----------------------------------------------------------------------------
# Admin notice for projects without a developer
----------------------------------------------------------------------------- */

add_action( 'admin_notices', 'jawda_projects_without_developer_notice' );
function jawda_projects_without_developer_notice() {
    // Show notice only to editors and administrators
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        return;
    }

    // Check if the user has snoozed the notice in the last 24 hours
    $snooze_time = get_user_meta( get_current_user_id(), 'jawda_snooze_developer_notice', true );
    if ( $snooze_time && ( time() - $snooze_time < DAY_IN_SECONDS ) ) {
        return;
    }

    // Get the cached list of projects
    $projects_without_developer = get_transient( 'jawda_projects_without_developer' );

    // If the cache is empty, run the query and set the cache
    if ( false === $projects_without_developer ) {
        $args = array(
            'post_type'      => 'projects',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_selected_developer_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'fields' => 'ids', // Optimize by only getting post IDs
        );
        $projects_query = new WP_Query( $args );
        $projects_without_developer = $projects_query->posts;

        // Cache the result for 10 minutes
        set_transient( 'jawda_projects_without_developer', $projects_without_developer, 10 * MINUTE_IN_SECONDS );
    }

    // If there are no projects without a developer, do nothing
    if ( empty( $projects_without_developer ) ) {
        return;
    }

    // Display the admin notice
    ?>
    <div class="notice notice-warning jawda-developer-notice">
        <p><strong><?php _e( 'Action Required: Projects Missing Developers', 'jawda' ); ?></strong></p>
        <p><?php _e( 'The following projects do not have a developer assigned. This will prevent their URLs from being redirected to the new SEO-friendly structure. Please edit each project and assign a developer.', 'jawda' ); ?></p>
        <ul id="jawda-missing-developer-list">
            <?php
            $project_count = count($projects_without_developer);
            $i = 0;
            foreach ( $projects_without_developer as $project_id ) :
                $i++;
                $li_style = ($i > 3) ? 'style="display: none;"' : '';
            ?>
                <li <?php echo $li_style; ?>>
                    <a href="<?php echo esc_url( get_edit_post_link( $project_id ) ); ?>">
                        <?php echo esc_html( get_the_title( $project_id ) ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ( $project_count > 3 ) : ?>
        <p><button id="jawda-toggle-projects" class="button" data-show-text="<?php _e('إظهار المزيد', 'jawda'); ?>" data-hide-text="<?php _e('إظهار أقل', 'jawda'); ?>"><?php _e('إظهار المزيد', 'jawda'); ?></button></p>
        <?php endif; ?>
        <p><a href="#" class="button jawda-snooze-notice"><?php _e( 'Snooze for 24 hours', 'jawda' ); ?></a></p>
    </div>
    <?php
}

// AJAX handler for snoozing the notice
add_action( 'wp_ajax_jawda_snooze_developer_notice', 'jawda_snooze_developer_notice_ajax' );
function jawda_snooze_developer_notice_ajax() {
    if ( ! check_ajax_referer( 'jawda_snooze_nonce', 'security', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
    }

    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
    }

    update_user_meta( get_current_user_id(), 'jawda_snooze_developer_notice', time() );
    wp_send_json_success( 'Notice snoozed.' );
}

// Enqueue script for the snooze button
add_action( 'admin_enqueue_scripts', 'jawda_snooze_notice_script' );
function jawda_snooze_notice_script() {
    $nonce = wp_create_nonce( 'jawda_snooze_nonce' );
    $script = "
        jQuery(document).on('click', '.jawda-snooze-notice', function(e) {
            e.preventDefault();
            var notice = jQuery(this).closest('.jawda-developer-notice');

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'jawda_snooze_developer_notice',
                    security: '{$nonce}'
                },
                success: function(response) {
                    if (response.success) {
                        notice.fadeOut('slow', function() {
                            notice.remove();
                        });
                    }
                }
            });
        });

        jQuery(document).on('click', '#jawda-toggle-projects', function(e) {
            e.preventDefault();
            var button = jQuery(this);
            var list = jQuery('#jawda-missing-developer-list');
            var hiddenItems = list.find('li:nth-child(n+4)');

            hiddenItems.slideToggle();

            if (button.text() === button.data('show-text')) {
                button.text(button.data('hide-text'));
            } else {
                button.text(button.data('show-text'));
            }
        });
    ";
    wp_add_inline_script( 'jquery', $script );
}
