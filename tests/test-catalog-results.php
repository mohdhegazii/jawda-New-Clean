<?php

// This test can't be run directly and is intended for a WP Core test suite.
// It requires the WP testing framework to be bootstrapped.
// Example command: phpunit --group catalog

/**
 * @group catalog
 */
class Catalog_Results_Test extends WP_UnitTestCase {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    register_post_type('projects', [
        'public' => true,
        'label'  => 'Projects',
        'taxonomies' => ['projects_area', 'projects_type'],
    ]);
    register_post_type('property', [
        'public' => true,
        'label'  => 'Properties',
        'taxonomies' => ['property_city', 'property_type'],
    ]);
    register_post_type('catalog', [
        'public' => true,
        'label'  => 'Catalog',
    ]);
  }

  public function test_unified_catalog_query_returns_all_results() {
    // Create a catalog post to hold the filter criteria.
    $catalog_id = self::factory()->post->create(['post_type' => 'catalog']);

    // Define filter criteria for projects.
    update_post_meta($catalog_id, 'jawda_catalog_type', 1); // Project type
    update_post_meta($catalog_id, 'jawda_project_price_from', 100000);
    update_post_meta($catalog_id, 'jawda_project_price_to', 500000);

    // Create 8 project posts that match the price filter.
    for ($i = 0; $i < 8; $i++) {
      $p = self::factory()->post->create([
        'post_type'   => 'projects',
        'post_status' => 'publish',
        'post_title'  => 'Project-' . $i,
      ]);
      // Set a price that is within the filter range.
      update_post_meta($p, 'jawda_price', 200000 + $i);
    }

    // Create 7 property posts. These should not be included in the project query.
    for ($i = 0; $i < 7; $i++) {
      self::factory()->post->create([
        'post_type'   => 'property',
        'post_status' => 'publish',
        'post_title'  => 'Property-' . $i,
      ]);
    }

    // Include the function file to make it available for the test.
    require_once dirname( __DIR__ ) . '/app/templates/catalogs/main.php';

    // Get the query arguments using the refactored function.
    $args = get_unified_catalog_args($catalog_id);

    // Execute the query.
    $q = new WP_Query($args);

    // Before the fix, this query might have returned 0 or an incorrect count.
    // After the fix, it should return exactly the 8 projects we created.
    $this->assertEquals(8, (int) $q->post_count, 'Unified query should return all matching projects.');

    // Change catalog to filter for properties.
    update_post_meta($catalog_id, 'jawda_catalog_type', 2); // Property type

    $args_props = get_unified_catalog_args($catalog_id);
    $q_props = new WP_Query($args_props);

    // The query should now return the 7 properties.
    $this->assertEquals(7, (int) $q_props->post_count, 'Unified query should return all matching properties.');
  }

  public static function tearDownAfterClass(): void {
    parent::tearDownAfterClass();
    // Clean up post types.
    unregister_post_type('projects');
    unregister_post_type('property');
    unregister_post_type('catalog');
  }
}
