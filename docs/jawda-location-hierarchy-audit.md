# Location Hierarchy Audit (Projects & Listings)

## Current Data Model
- Meta keys in use for both projects and listings:
  - `loc_governorate_id`
  - `loc_city_id`
  - `loc_district_id`
  - `jawda_map` (array: `lat`, `lng`, `zoom`, `address`)
- Listings also store inheritance toggle and project linkage:
  - `jawda_inherit_project_location`
  - `jawda_project` (linked project IDs)
- Services:
  - `Jawda_Location_Service` reads/writes the canonical `loc_*` keys and `jawda_map`, with coordinate normalization and fallbacks.
  - `Jawda_Listing_Location_Service` wraps the core service, handling inheritance from the linked project when allowed.

## Admin UI Usage
- **Meta box (`app/functions/meta_box.php`):**
  - Custom HTML renders governorate, city, district selects plus a Leaflet map via `aqarand_locations_render_coordinate_fields`.
  - Uses `jawda_get_all_governorates` / `jawda_get_cities_by_governorate` / `jawda_get_districts_by_city` for options.
  - Optional inheritance checkbox for listings tied to a project.
- **Quick Edit / Bulk Edit (`app/inc/admin/quick-edit-locations.php`):**
  - Inline forms render bare selects without map support.
  - Saving calls `Jawda_Location_Service::save_location_for_post()` for projects and `Jawda_Listing_Location_Service::save_location()` for listings, with `sync_map_from_location` to back-fill coordinates.
  - Hidden row data injects stored IDs for JS hydration.
- **Admin JS:**
  - `admin/cf-dependent-selects.js` drives cascading selects + map focus for the meta box.
  - `app/inc/admin/js/quick-edit-locations.js` duplicates dependent dropdown logic for list-table inline edits (no map integration).

## Front-end Usage
- No dedicated add-property/add-project form in the theme; only search/catalog templates read stored location.
- `assets/js/frontend-locations.js` provides a basic governorate → city → district cascade for any front-end selects using `jawda-*-frontend` classes; no map integration.

## Front-end Display
- Listing cards and helpers (`app/templates/boxs/property_box.php`, `app/templates/properties/property_helper.php`) call `Jawda_Listing_Location_Service::get_location()` to resolve labels and map payloads with Carbon fallbacks.
- Project templates still reference `loc_*` meta directly in some places (e.g., breadcrumbs), outside the unified service.

## Inconsistencies / Legacy Paths
- UI/JS duplication: meta box uses `admin/cf-dependent-selects.js` with map support; quick/bulk edit and front-end rely on separate JS without map behavior.
- Quick/Bulk edit lacks the map + consistent styling used in the meta box.
- Front-end cascade script does not align with the admin component classes or the Leaflet widget.
- Some templates still query `get_post_meta()` directly instead of the location service, especially for projects.
