# Views, Orientation, and Facade Lookup Implementation Plan

## 1. Unit View Lookup
- Create a lookup table (or extend existing featured lookup) with bilingual columns `view_ar`, `view_en`.
- Seed dropdown values:
  - على شارع رئيسي / Main Road View
  - على شارع داخلي / Internal Street View
  - على ميدان / Square View
  - على محور / طريق سريع / Highway / Main Axis View
  - على مدخل المشروع / Project Entrance View
  - على حدائق / لاندسكيب / Gardens / Landscape View
  - على بارك مركزي / Central Park View
  - على بحيرة صناعية / Artificial Lake View
  - على حمام السباحة / Pool View
  - على النادي / الكلوب هاوس / Clubhouse / Club View
  - على منطقة أطفال / Kids Area View
  - على منطقة تجارية (محلات) / Retail / Commercial Strip View
  - على الجراج / الباركينج / Parking View
  - على فناء داخلي (كورتيارد) / Courtyard / Internal Garden View
  - على واجهات مباني أخرى / Other Buildings View
  - على سور المشروع / Compound Wall View
  - على مدرسة / جامعة / School / University View
  - على منطقة خدمية (محطة، خدمات) / Service Area View
  - فيو مفتوح / بانوراما / Open / Panoramic View
  - بدون فيو مميز (منور / حارة خلفية) / Light Well / Back Alley View
- Support optional grouping metadata (`view_group` with values Excellent / Good / Standard / Weak) for analytics.

## 2. Orientation and Facade Lookups
### 2.1 Orientation
- Store bilingual `orientation_ar`, `orientation_en` values:
  - بحري (شمالي) / North (Bahary)
  - قبلي (جنوبي) / South (Qebly)
  - شرقي / East
  - غربي / West
  - بحري شرقي / North-East (Bahary Sharqy)
  - بحري غربي / North-West (Bahary Gharby)
  - قبلي شرقي / South-East (Qebly Sharqy)
  - قبلي غربي / South-West (Qebly Gharby)
  - متعددة الاتجاهات / Multiple Orientations

### 2.2 Facade Count / Position
- Store bilingual `facades_ar`, `facades_en` values:
  - واجهة واحدة / Single Facade
  - واجهتين ناصية / Corner Unit (Two Facades)
  - ثلاث واجهات / Three Facades (Head of Block)
  - أربع واجهات / Detached (Four Facades)
  - بدون جيران جانبيين / No Side Neighbors
  - بدون جار علوي / No Upper Neighbor (Top Floor)
  - بدون جار سفلي / No Lower Neighbor (Ground / Over Podium)
  - بدون جيران (مستقلة بالكامل) / Fully Detached / No Neighbors

### 2.3 Marketing Labels
- Create a marketing label lookup (e.g. `marketing_orientation_label`) with bilingual copy prebuilt from orientation + facade combinations.
- Example entries:
  - واجهة واحدة بحري / Single Bahary Facade
  - واجهة واحدة قبلي / Single Qebly Facade
  - واجهة واحدة شرقي / Single East Facade
  - واجهة واحدة غربي / Single West Facade
  - ناصية بحري شرقي / Corner Bahary-East (Two Facades)
  - ناصية بحري غربي / Corner Bahary-West (Two Facades)
  - ناصية قبلي شرقي / Corner Qebly-East
  - ناصية قبلي غربي / Corner Qebly-West
  - ثلاث واجهات / Three-Facade Unit
  - بدون جيران / Fully Detached / No Neighbors

## 3. Data Modeling Guidance
- **Storage:** Keep orientation and facades separate for analytics. Use numeric or enum columns for counts and flags (`facades_count`, `position_type`, `has_side_neighbors`, etc.).
- **Frontend:** Compose marketing labels dynamically by combining selected orientation and facade values or fetch from the marketing lookup.
- **Admin UI:** Provide Carbon Fields dropdowns or multi-selects sourced from the respective lookups. Consider adding helper columns for grouping and sort order.
- **Analytics:** Use normalized lookups to aggregate pricing by orientation group, facade count, and view group.

## 4. Integration Steps
1. Extend the lookup module schema to support new categories (`view`, `orientation`, `facade`, `marketing_orientation`).
2. Seed the provided bilingual datasets with IDs and optional grouping metadata.
3. Update project/unit meta boxes to reference the lookups (dropdowns for view, orientation, facades; optional multi-select for marketing labels).
4. Add frontend helpers to render combined labels (e.g., "شقة ناصية بحري شرقي على لاندسكيب").
5. Update migration scripts to backfill new fields from existing meta if available.
6. Provide export/import utilities or WP-CLI commands for maintaining the lookup datasets.
