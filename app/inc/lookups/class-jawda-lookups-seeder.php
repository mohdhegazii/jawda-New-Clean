<?php
/**
 * Seeder class for the Jawda Lookups module.
 *
 * Handles resetting and seeding of all lookup tables.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Lookups_Seeder {

    /**
     * @var array List of lookup tables to be reset.
     */
    private $lookup_tables = [
        'jawda_categories',
        'jawda_subcategories',
        'jawda_property_types',
        'jawda_property_type_subcategory',
        'jawda_aliases',
    ];

    /**
     * Truncates all Jawda Lookup tables.
     */
    public function reset_lookups() {
        global $wpdb;

        echo '<h2>Resetting Jawda Lookups Data...</h2>';

        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($this->lookup_tables as $table_name) {
            $table = $wpdb->prefix . $table_name;
            $wpdb->query("TRUNCATE TABLE `$table`");
            echo "<p>Truncated table: `$table`</p>";
        }

        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

        echo '<p style="color: green;">All lookup tables have been cleared.</p>';
    }

    /**
     * Seeds the database with a default set of lookups.
     */
    public function seed_default_data() {
        echo '<h2>Seeding Default Lookups Data...</h2>';

        $category_ids = $this->seed_categories();
        $subcategory_ids = $this->seed_subcategories($category_ids);
        $this->seed_property_types_and_aliases($subcategory_ids);
        $this->seed_market_types();

        echo '<p style="color: green;">Seeding complete.</p>';
    }

    private function seed_market_types() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jawda_unit_lookups';
        $group_key = 'market_type';

        // Check if market types are already seeded
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE group_key = %s", $group_key));
        if ($count > 0) {
            echo '<p>Market types already seeded. Skipping.</p>';
            return;
        }

        echo '<h3>Seeding Market Types...</h3>';

        $market_types = [
            [
                'slug'     => 'from-developer',
                'label_ar' => 'من المطور (أول سكن)',
                'label_en' => 'From Developer (Primary Market)',
            ],
            [
                'slug'     => 'from-owner',
                'label_ar' => 'من مالك العقار (ريسيل)',
                'label_en' => 'From Owner (Resale Market)',
            ],
        ];

        foreach ($market_types as $type) {
            $result = Jawda_Unit_Lookups_Service::create_lookup($group_key, [
                'slug'     => $type['slug'],
                'label_ar' => $type['label_ar'],
                'label_en' => $type['label_en'],
            ]);

            if ($result && !is_wp_error($result)) {
                echo "<p>Seeded Market Type: {$type['label_en']}</p>";
            } else {
                echo "<p style='color: red;'>Failed to seed Market Type: {$type['label_en']}</p>";
            }
        }
    }

    private function seed_categories() {
        echo '<h3>Seeding Categories...</h3>';

        $categories = [
            'residential' => [ 'slug_ar'  => 'سكني', 'name_en' => 'Residential'],
            'commercial' => [ 'slug_ar'  => 'تجاري', 'name_en' => 'Commercial'],
        ];

        $category_ids = [];
        foreach ($categories as $code => $names) {
            $cat_id = Jawda_Category_Lookups_Service::create_category([
                 'slug_ar'  => $names[ 'slug_ar' ],
                'name_en' => $names['name_en'],
                'sort_order' => 0,
            ]);
            if ($cat_id && !is_wp_error($cat_id)) {
                $category_ids[$code] = $cat_id;
                echo "<p>Seeded Category: {$names['name_en']} (ID: $cat_id)</p>";
            }
        }

        return $category_ids;
    }

    private function seed_subcategories($category_ids) {
        echo '<h3>Seeding Sub-Categories...</h3>';

        $subcategories = [
            [ 'slug_ar'  => 'شقق', 'name_en' => 'Flats', 'category_code' => 'residential'],
            [ 'slug_ar'  => 'فيلات', 'name_en' => 'Villas', 'category_code' => 'residential'],
            [ 'slug_ar'  => 'تجاري', 'name_en' => 'Commercial', 'category_code' => 'commercial'],
        ];

        $sub_ids = [];
        foreach ($subcategories as $sub) {
            if (empty($category_ids[$sub['category_code']])) {
                continue;
            }
            $sid = Jawda_Category_Lookups_Service::create_subcategory([
                'category_id' => $category_ids[$sub['category_code']],
                 'slug_ar'  => $sub[ 'slug_ar' ],
                'name_en' => $sub['name_en'],
            ]);
            if ($sid && !is_wp_error($sid)) {
                $sub_ids[$sub['name_en']] = $sid;
                echo "<p>Seeded Sub-Category: {$sub['name_en']} (ID: $sid)</p>";
            }
        }

        return $sub_ids;
    }

    private function seed_property_types_and_aliases($subcategory_ids) {
        echo '<h3>Seeding Property Types & Aliases...</h3>';

        $property_types = [
            [ 'slug_ar'  => 'شقة', 'name_en' => 'Apartment', 'sub_keys' => ['Flats']],
            [ 'slug_ar'  => 'فيلا', 'name_en' => 'Villa', 'sub_keys' => ['Villas']],
            [ 'slug_ar'  => 'مكتب', 'name_en' => 'Office', 'sub_keys' => ['Commercial']],
        ];
foreach ($property_types as $type_data) {
            $pt_id = Jawda_Category_Lookups_Service::create_property_type([
                 'slug_ar'  => $type_data[ 'slug_ar' ],
                'name_en' => $type_data['name_en'],
            ], array_values(array_intersect_key($subcategory_ids, array_flip($type_data['sub_keys']))));

            if ($pt_id && !is_wp_error($pt_id)) {
                echo "<p>Seeded Property Type: {$type_data['name_en']} (ID: $pt_id)</p>";

Jawda_Category_Lookups_Service::create_alias([
                        'property_type_id' => $pt_id, 'slug_ar'  => $type_data[ 'slug_ar' ],
                        'name_en' => $type_data['name_en'],
                    ]);
}
        }
    }

    private function get_first_project_id() {
        $project = get_posts([
            'post_type' => 'projects',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);
        return !empty($project) ? (int) $project[0] : 0;
    }
}
