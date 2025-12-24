<?php

/**
 * Global Function to render coordinate fields and Leaflet map
 * Based on original working version structure
 */
if (!function_exists('jawda_locations_render_coordinate_fields')) {
    function jawda_locations_render_coordinate_fields($args) {
        $lat_id = $args['lat_id'] ?? '';
        $lng_id = $args['lng_id'] ?? '';
        $lat_value = $args['lat_value'] ?? '';
        $lng_value = $args['lng_value'] ?? '';
        ?>
        <div class="coordinate-fields">
            <div class="form-field">
                <label>Latitude</label>
                <input type="text" name="latitude" id="<?php echo esc_attr($lat_id); ?>" value="<?php echo esc_attr($lat_value); ?>">
            </div>
            <div class="form-field">
                <label>Longitude</label>
                <input type="text" name="longitude" id="<?php echo esc_attr($lng_id); ?>" value="<?php echo esc_attr($lng_value); ?>">
            </div>
            <div class="form-field">
                <label>Map Preview</label>
                
                <div class="jawda-location-picker" data-lat-input="#<?php echo esc_attr($lat_id); ?>" data-lng-input="#<?php echo esc_attr($lng_id); ?>">
                    <div class="jawda-location-picker__map" id="locations-map" 
                         data-initial-lat="<?php echo $lat_value ? esc_attr($lat_value) : '30.0444'; ?>" 
                         data-initial-lng="<?php echo $lng_value ? esc_attr($lng_value) : '31.2357'; ?>" 
                         style="height: 400px; border: 1px solid #ccc; border-radius: 4px; position: relative;">
                    </div>

                    <p class="description">Click the map to populate the latitude and longitude fields.</p>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('jawda_locations_normalize_coordinate')) {
    function jawda_locations_normalize_coordinate($value) {
        return is_numeric($value) ? floatval($value) : '';
    }
}

class Jawda_Locations_Admin_Page {
    private $governorates_table;
    private $cities_table;
    private $districts_table;

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'on_load_page']);
    }

    public function register_menu() {
        add_submenu_page(
            'jawda-lookups',
            __('Locations', 'jawda'),
            __('Locations', 'jawda'),
            'manage_options',
            'jawda-lookups-locations',
            [$this, 'render_page']
        );
    }

    public function on_load_page() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'jawda-lookups-locations') return;
        
        require_once get_template_directory() . '/app/functions/jawda-locations-admin/governorates.php';
        require_once get_template_directory() . '/app/functions/jawda-locations-admin/cities.php';
        require_once get_template_directory() . '/app/functions/jawda-locations-admin/districts.php';

        $this->governorates_table = new Jawda_Governorates_List_Table();
        $this->cities_table = new Jawda_Cities_List_Table();
        $this->districts_table = new Jawda_Districts_List_Table();

        $this->governorates_table->process_actions();
        $this->cities_table->process_actions();
        $this->districts_table->process_actions();
    }

    public function render_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'governorates';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Jawda Locations Lookups', 'jawda'); ?></h1>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="?page=jawda-lookups-locations&tab=governorates" class="nav-tab <?php echo $active_tab === 'governorates' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Governorates', 'jawda'); ?>
                </a>
                <a href="?page=jawda-lookups-locations&tab=cities" class="nav-tab <?php echo $active_tab === 'cities' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Cities', 'jawda'); ?>
                </a>
                <a href="?page=jawda-lookups-locations&tab=districts" class="nav-tab <?php echo $active_tab === 'districts' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Districts', 'jawda'); ?>
                </a>
            </nav>
<style>.leaflet-tile-container { filter: none !important; } #locations-map { background: #eee !important; }</style>


            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'cities':
                        $this->cities_table->render_page();
                        break;
                    case 'districts':
                        $this->districts_table->render_page();
                        break;
                    default:
                        $this->governorates_table->render_page();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
}

// Instantiate the class
new Jawda_Locations_Admin_Page();

// Enqueue assets for the map
add_action('admin_enqueue_scripts', function($hook) {
    if (isset($_GET['page']) && $_GET['page'] === 'jawda-lookups-locations') {
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
        wp_enqueue_script('jawda-locations-map', get_template_directory_uri() . '/admin/locations-map.js', ['leaflet-js'], time(), true);
    }
});
