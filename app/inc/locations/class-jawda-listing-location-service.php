<?php
/**
 * Location service wrapper for listings (property CPT).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Listing_Location_Service {
    const META_INHERIT_LOCATION = 'jawda_inherit_project_location';
    const META_PROJECT_LINK     = 'jawda_project';

    /**
     * Returns the first linked project ID for a listing.
     */
    public static function get_linked_project_id($post_id) {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return 0;
        }

        $projects = [];
        if (function_exists('carbon_get_post_meta')) {
            $projects = (array) carbon_get_post_meta($post_id, self::META_PROJECT_LINK);
        }

        if (!$projects) {
            $projects = (array) get_post_meta($post_id, self::META_PROJECT_LINK, true);
        }

        $projects = array_values(array_filter(array_map('absint', $projects)));

        return $projects ? (int) $projects[0] : 0;
    }

    /**
     * Determines if the listing should inherit the project location.
     * Now strictly enforced if a project is linked.
     */
    public static function should_inherit_location($post_id) {
        $post_id    = absint($post_id);
        $project_id = self::get_linked_project_id($post_id);

        if ($project_id) {
            // Mandate inheritance if linked to a project
            return true;
        }

        return false;
    }

    /**
     * Returns the effective location for a listing, honoring project inheritance when enabled.
     */
    public static function get_location($post_id, $include_names = true) {
        $post_id    = absint($post_id);
        $project_id = self::get_linked_project_id($post_id);

        $inherit = self::should_inherit_location($post_id);

        if ($inherit && $project_id) {
            return Jawda_Location_Service::get_location_for_post($project_id, $include_names);
        }

        return Jawda_Location_Service::get_location_for_post($post_id, $include_names);
    }

    /**
     * Saves listing location or inheritance flag.
     */
    public static function save_location($post_id, array $data) {
        $post_id    = absint($post_id);
        $project_id = self::get_linked_project_id($post_id);

        if ($post_id <= 0) {
            return;
        }

        // FORCE Inheritance if project is linked
        $inherit = ($project_id > 0);
        update_post_meta($post_id, self::META_INHERIT_LOCATION, $inherit ? '1' : '0');

        if ($inherit && $project_id) {
            // Retrieve Project Location Data to MIRROR it to the Property.
            // This ensures standard WP filtering works on the Property post too.
            $project_location = Jawda_Location_Service::get_location_for_post($project_id, false);

            // Prepare data array for saving to Property
            $mirror_data = [
                'governorate_id'       => $project_location['ids']['governorate'],
                'city_id'              => $project_location['ids']['city'],
                'district_id'          => $project_location['ids']['district'],
                'map'                  => $project_location['map'],
                'overwrite_map'        => true, // Force overwrite with project map
                'sync_map_from_location' => false, // Map is already explicit from project
            ];

            // Save mirrored data to Property
            Jawda_Location_Service::save_location_for_post($post_id, $mirror_data);
            return;
        }

        // Delegate to main service (which handles validation)
        Jawda_Location_Service::save_location_for_post($post_id, [
            'governorate_id'       => $data['governorate_id'] ?? $data['loc_governorate_id'] ?? 0,
            'city_id'              => $data['city_id'] ?? $data['loc_city_id'] ?? 0,
            'district_id'          => $data['district_id'] ?? $data['loc_district_id'] ?? 0,
            'map'                  => $data['map'] ?? null,
            'overwrite_map'        => !empty($data['overwrite_map']),
            'sync_map_from_location' => !empty($data['sync_map_from_location']),
        ]);
    }
}
