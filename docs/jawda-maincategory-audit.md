# Main Category & Property Types Audit (Projects)

## Current data model
- Meta keys in use:
  - `jawda_main_category_id` (single int); stored via Carbon Fields and manual meta updates.
  - `jawda_property_type_ids` (array serialized by `update_post_meta`).
- Lookup source: custom DB tables `{$wpdb->prefix}property_categories`, `{$wpdb->prefix}property_types`, and pivot `{$wpdb->prefix}property_type_category_relationships` accessed through `Jawda_Property_Taxonomy_Helper`.
- Helper class: `app/inc/categories/class-jawda-property-tax-helper.php` exposes placeholders, `get_main_categories()`, grouped property types, and `save_selection()` with single main-category enforcement.

## Admin surfaces
- Project meta box (`aqarand_render_project_category_fields` in `app/functions/meta_box.php`):
  - Single-select main category, multi-select property types filtered client-side by selected main category.
  - Save hook `aqarand_save_project_category_fields` calls `jawda_update_project_category_and_types` → helper `save_selection()`.
- Quick Edit / Bulk Edit (`app/inc/admin/quick-edit-locations.php`):
  - Provides main category + property type controls; values hydrated via localized data.
  - Saves via `jawda_update_project_category_and_types`.

## Frontend usage
- Templates read meta directly:
  - `app/templates/project/project_main.php` reads `jawda_property_type_ids` via `carbon_get_post_meta`.
  - `app/templates/project/project_header.php` and `app/templates/boxs/project_box.php` use `jawda_main_category_id`/`jawda_property_type_ids` when present.
- Helper `Jawda_Property_Taxonomy_Helper::get_saved_selection()` is not consistently used on frontend renders.

## Inconsistencies / gaps
- Data model assumes a single main category; business rules now allow multiple main categories driving property types.
- Frontend rendering bypasses helper service, risking label divergence.
- Validation only allows property types from one selected category (no multi-category filtering) and silently clears types if main category cleared.
- UI components for meta/quick/bulk edit are not shared; markup/styles diverge from helper expectations.

## Notes after unification work
- Canonical storage remains `jawda_main_category_id` (now array-capable) and `jawda_property_type_ids` (array).
- A new service (`Jawda_Project_Category_Service`) centralizes save/get logic for multi-category selections and type validation.
