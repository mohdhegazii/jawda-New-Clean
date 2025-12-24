# Listings/Properties – Location & Categories Audit

## Post type
- Listings use the custom post type `property` defined in `app/functions/post_types.php`.

## Admin UI
- Carbon Fields meta container `Property Data` registers tabs for details, gallery, video, **Map**, and FAQ (`app/functions/meta_box.php`).
  - Map tab stores a Carbon map field in `jawda_map` with default coordinates.
  - The Property Details tab includes multiselect `jawda_project` linking listings to projects, the legacy text `jawda_location`, and other numeric/text fields.
  - Category fields are injected via `aqarand_render_listing_category_fields()` within the Property Details tab (main category + property types + inheritance checkbox).
- A custom “Location” meta box (`aqarand-project-location-placeholder`) is registered for `projects`, `catalogs`, **and `property`** posts, replacing legacy Carbon location fields. It renders cascading selects plus map + inherit checkbox for listings (`app/functions/meta_box.php`).
- Save logic for the location box routes property saves through `Jawda_Listing_Location_Service::save_location()`; projects/catalogs go through `Jawda_Location_Service::save_location_for_post()` (`app/functions/meta_box.php`).
- Category saving for listings is handled by `aqarand_save_listing_category_fields()` which calls `Jawda_Listing_Category_Service::save_selection()` with an inheritance flag (`app/functions/meta_box.php`).
- Quick Edit / Bulk Edit screens currently target **projects only** via `app/inc/admin/quick-edit-locations.php`; no listing (property) quick/bulk fields exist yet.

## Stored meta keys (listings)
- Project link: `jawda_project` (array; first value used as linked project).
- Location/inheritance:
  - `loc_governorate_id`, `loc_city_id`, `loc_district_id` (integers)
  - Map payload: `jawda_map` (array with `lat`, `lng`, `zoom`, `address`)
  - Inheritance flag: `jawda_inherit_project_location` (string `1`/`0`)
- Categories/types/inheritance:
  - Main category: `jawda_main_category_id` (single int)
  - Property types: `jawda_property_type_ids` (array of ints) plus legacy single `jawda_property_type_id`
  - Inheritance flag: `jawda_inherit_project_categories` (string `1`/`0`)
- Legacy location text: `jawda_location` remains on the Property Details tab and is used as a fallback on the frontend.

## Frontend usage
- Single listing helpers in `app/templates/properties/property_helper.php` read location via `Jawda_Listing_Location_Service::get_location()` (with `jawda_location` fallback) and categories via `Jawda_Listing_Category_Service::get_selection_with_labels()`. Map data is still read directly from `jawda_map` (Carbon meta).
- Listing cards in `app/templates/boxs/property_box.php` use the same services for location/category labels with fallback to `jawda_location`.
- Other templates (e.g., `page-properties.php`, archives) rely on these helpers/boxes for rendering.

## Observations / inconsistencies
- Carbon Map tab still stores `jawda_map` separately even though the unified Location meta box also writes `jawda_map`; duplication risk if only one of the UIs is used.
- Frontend map usage in `property_helper.php` pulls `jawda_map` directly instead of the unified location service’s map payload, so inheritance-aware coordinates are not guaranteed.
- Quick/Bulk edit parity is missing for listings; only projects have these unified controls.
- Legacy text field `jawda_location` persists as fallback; ensure service data is primary source going forward.
