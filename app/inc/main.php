<?php
/**
 * Main loader for modular features located in the /app/inc/ directory.
 * This file includes all the necessary components for new functionalities.
 *
 * @package Jawda
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// --- Dependent Dropdowns Feature ---
// This feature adds AJAX-powered dependent dropdowns (Governorate -> City -> District)
// to the project post editor screen. The dropdown markup is provided by a custom
// meta box (see app/functions/meta_box.php).

// 1. Configuration: Defines constants for taxonomies and meta keys.
require_once __DIR__ . "/helpers/terms.php";
require_once __DIR__ . '/helpers/capabilities.php';
require_once __DIR__ . '/admin/cf-dependent-config.php';

// --- Locations API ---
// Centralized functions for fetching location data. Must be loaded before dependent files.
require_once __DIR__ . '/locations/api.php';
require_once __DIR__ . '/locations/class-jawda-location-service.php';
require_once __DIR__ . '/locations/class-jawda-listing-location-service.php';
require_once __DIR__ . '/locations/project-location.php';

// 2. AJAX Handlers: Sets up endpoints to fetch cities and projects dynamically.
require_once __DIR__ . '/admin/cf-dependent-ajax.php';

// 3. Quick & Bulk Edit: Adds location fields to the project list screen.
require_once __DIR__ . '/admin/quick-edit-locations.php';

// --- Categories System ---
// This feature adds a new 2-level classification system: Main Categories and Property Types.
// require_once __DIR__ . '/categories/main.php'; // REMOVED (Legacy)

// --- Project Features Lookups ---
// Provides bilingual feature lookups with media support.
require_once __DIR__ . '/project-features/main.php';

// --- Unified Lookups (Segments / Main Cats / Property Types) ---
require_once __DIR__ . '/lookups/main.php';

// --- Unit Lookups System ---
// Manages flat lookups for unit details (status, finishing, amenities, etc.).
require_once __DIR__ . '/units/main.php';

// --- Developers Engine ---
// New standalone developers system (custom table + routing + admin UI).
require_once __DIR__ . '/developers/main.php';

// --- Jawda Locations Admin Page ---
// The new, unified admin page for managing locations under "Jawda Lookups".
require_once __DIR__ . '/locations/admin/main-page.php';

// --- Catalog SEO Overrides for dynamic catalogs ---
require_once __DIR__ . '/catalogs/main.php';
