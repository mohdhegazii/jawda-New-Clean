<?php
/**
 * Seeder: Categories -> Property Types -> Sub Properties -> Property Models
 * - Upsert by slug
 * - Builds pivots:
 *   - {$wpdb->prefix}jawda_property_type_categories  (type <-> category)
 *   - {$wpdb->prefix}jawda_property_model_categories (model <-> category)
 *
 * Assumptions (based on your current implementation):
 * - Tables exist:
 *   - {$wpdb->prefix}jawda_categories
 *   - {$wpdb->prefix}jawda_property_types
 *   - {$wpdb->prefix}jawda_sub_properties
 *   - {$wpdb->prefix}jawda_property_models
 *   - pivots: jawda_property_type_categories, jawda_property_model_categories
 */

if (!defined('ABSPATH')) { exit; }

global $wpdb;

function jawda_seed_slug($s) {
  $s = trim((string)$s);
  $s = sanitize_title($s);
  return $s ?: sanitize_title(md5($s . microtime(true)));
}

function jawda_seed_now() {
  return current_time('mysql');
}

function jawda_seed_table($name) {
  global $wpdb;
  return $wpdb->prefix . $name;
}

function jawda_seed_find_id_by_slug($table, $slug) {
  global $wpdb;
  return (int) $wpdb->get_var(
    $wpdb->prepare("SELECT id FROM {$table} WHERE slug=%s LIMIT 1", $slug)
  );
}

/**
 * Generic Upsert for lookup tables.
 * Requires: slug unique or treated as unique in code.
 */
function jawda_seed_upsert($table, array $data) {
  global $wpdb;

  if (empty($data['slug'])) {
    $data['slug'] = jawda_seed_slug($data['name_en'] ?? ($data[ 'slug_ar' ] ?? 'item'));
  }
  $id = jawda_seed_find_id_by_slug($table, $data['slug']);

  // sanitize common fields
  if (isset($data['name_en'])) $data['name_en'] = sanitize_text_field($data['name_en']);
  if (isset($data[ 'slug_ar' ])) $data[ 'slug_ar' ] = sanitize_text_field($data[ 'slug_ar' ]);
  if (isset($data['icon']))    $data['icon']    = ($data['icon'] === null) ? null : sanitize_text_field($data['icon']);
  if (isset($data['is_active'])) $data['is_active'] = (int)!!$data['is_active'];
  if (isset($data['sort_order'])) $data['sort_order'] = (int)$data['sort_order'];

  if ($id > 0) {
    $wpdb->update($table, $data, ['id' => $id]);
    return $id;
  } else {
    $wpdb->insert($table, $data);
    return (int)$wpdb->insert_id;
  }
}

/**
 * Pivot upsert: ensures (a,b) exists once.
 */
function jawda_seed_pivot_attach($table, $a_col, $a_id, $b_col, $b_id) {
  global $wpdb;
  $a_id = (int)$a_id; $b_id = (int)$b_id;
  if ($a_id <= 0 || $b_id <= 0) return;

  $exists = (int)$wpdb->get_var(
    $wpdb->prepare("SELECT 1 FROM {$table} WHERE {$a_col}=%d AND {$b_col}=%d LIMIT 1", $a_id, $b_id)
  );
  if (!$exists) {
    $wpdb->insert($table, [ $a_col => $a_id, $b_col => $b_id ]);
  }
}

function jawda_seed_print($msg) {
  echo $msg . PHP_EOL;
}

function jawda_seed_run() {
  global $wpdb;

  // Tables
  $t_cats   = jawda_seed_table('jawda_categories');
  $t_types  = jawda_seed_table('jawda_property_types');
  $t_subs   = jawda_seed_table('jawda_sub_properties');
  $t_models = jawda_seed_table('jawda_property_models');

  $t_pivot_type_cat  = jawda_seed_table('jawda_property_type_categories');
  $t_pivot_model_cat = jawda_seed_table('jawda_property_model_categories');

  // 1) Categories
  $categories = [
    [ 'name_en' => 'Residential',        'slug_ar'  => 'سكني',           'slug' => 'residential', 'sort_order' => 1, 'is_active' => 1 ],
    [ 'name_en' => 'Commercial',         'slug_ar'  => 'تجاري',          'slug' => 'commercial',  'sort_order' => 2, 'is_active' => 1 ],
    [ 'name_en' => 'Vacation & Coastal', 'slug_ar'  => 'إجازات وساحلي',  'slug' => 'vacation-coastal','sort_order' => 3, 'is_active' => 1 ],
  ];

  $cat_ids = [];
  foreach ($categories as $c) {
    $id = jawda_seed_upsert($t_cats, $c);
    $cat_ids[$c['slug']] = $id;
    jawda_seed_print("Category: {$c['slug']} => #{$id}");
  }

  // 2) Property Types (high-level)
  // You can expand freely later.
  $types = [
    // Residential
    [ 'slug'=>'apartments', 'name_en'=>'Apartments',  'slug_ar' =>'شقق', 'cats'=>['residential'] ],
    [ 'slug'=>'villas',     'name_en'=>'Villas',      'slug_ar' =>'فيلات','cats'=>['residential'] ],
    [ 'slug'=>'special-residential', 'name_en'=>'Special Residential',  'slug_ar' =>'وحدات سكنية خاصة', 'cats'=>['residential'] ],

    // Vacation & Coastal
    [ 'slug'=>'chalets',    'name_en'=>'Chalets',     'slug_ar' =>'شاليهات', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'coastal-villas','name_en'=>'Coastal Villas', 'slug_ar' =>'فيلات ساحلية','cats'=>['vacation-coastal'] ],
    [ 'slug'=>'hotel-units','name_en'=>'Hotel Units', 'slug_ar' =>'وحدات فندقية','cats'=>['vacation-coastal'] ],

    // Commercial
    [ 'slug'=>'retail',     'name_en'=>'Retail',      'slug_ar' =>'محلات', 'cats'=>['commercial'] ],
    [ 'slug'=>'offices',    'name_en'=>'Offices',     'slug_ar' =>'مكاتب', 'cats'=>['commercial'] ],
    [ 'slug'=>'medical',    'name_en'=>'Medical',     'slug_ar' =>'طبي', 'cats'=>['commercial'] ],
  ];

  $type_ids = [];
  foreach ($types as $t) {
    $row = [
      'slug' => $t['slug'],
      'name_en' => $t['name_en'],
       'slug_ar'  => $t[ 'slug_ar' ],
      'is_active' => 1,
      'sort_order' => 0,
    ];
    $type_id = jawda_seed_upsert($t_types, $row);
    $type_ids[$t['slug']] = $type_id;

    // attach categories to type
    foreach ($t['cats'] as $cslug) {
      $cid = $cat_ids[$cslug] ?? 0;
      jawda_seed_pivot_attach($t_pivot_type_cat, 'property_type_id', $type_id, 'category_id', $cid);
    }

    jawda_seed_print("Property Type: {$t['slug']} => #{$type_id}");
  }

  // 3) Sub Properties (under property type)
  // We will store sub_properties and map them by (type_slug + sub_slug)
  $subs = [
    // Apartments
    [ 'type'=>'apartments','slug'=>'apartment','name_en'=>'Apartment', 'slug_ar' =>'شقة' ],
    [ 'type'=>'apartments','slug'=>'duplex','name_en'=>'Duplex', 'slug_ar' =>'دوبلكس' ],
    [ 'type'=>'apartments','slug'=>'penthouse','name_en'=>'Penthouse', 'slug_ar' =>'بنتهاوس' ],
    [ 'type'=>'apartments','slug'=>'loft','name_en'=>'Loft', 'slug_ar' =>'لوفت' ],
    [ 'type'=>'apartments','slug'=>'serviced-apartment','name_en'=>'Serviced Apartment', 'slug_ar' =>'شقق مخدومة' ],

    // Villas
    [ 'type'=>'villas','slug'=>'standalone','name_en'=>'Standalone Villa', 'slug_ar' =>'فيلا مستقلة' ],
    [ 'type'=>'villas','slug'=>'twin-house','name_en'=>'Twin House', 'slug_ar' =>'توين هاوس' ],
    [ 'type'=>'villas','slug'=>'townhouse','name_en'=>'Townhouse', 'slug_ar' =>'تاون هاوس' ],
    [ 'type'=>'villas','slug'=>'cluster','name_en'=>'Cluster Villa', 'slug_ar' =>'كلستر' ],

    // Vacation
    [ 'type'=>'chalets','slug'=>'chalet','name_en'=>'Chalet', 'slug_ar' =>'شاليه' ],
    [ 'type'=>'chalets','slug'=>'cabin','name_en'=>'Cabin', 'slug_ar' =>'كابين' ],
    [ 'type'=>'chalets','slug'=>'holiday-home','name_en'=>'Holiday Home', 'slug_ar' =>'بيت عطلات' ],

    [ 'type'=>'coastal-villas','slug'=>'beach-villa','name_en'=>'Beach Villa', 'slug_ar' =>'فيلا شاطئية' ],
    [ 'type'=>'coastal-villas','slug'=>'cabana','name_en'=>'Cabana', 'slug_ar' =>'كابانا' ],

    [ 'type'=>'hotel-units','slug'=>'hotel-room','name_en'=>'Hotel Room', 'slug_ar' =>'غرفة فندقية' ],
    [ 'type'=>'hotel-units','slug'=>'suite','name_en'=>'Suite', 'slug_ar' =>'جناح' ],

    // Commercial
    [ 'type'=>'retail','slug'=>'shop','name_en'=>'Shop', 'slug_ar' =>'محل' ],
    [ 'type'=>'retail','slug'=>'kiosk','name_en'=>'Kiosk', 'slug_ar' =>'كشك' ],
    [ 'type'=>'retail','slug'=>'restaurant','name_en'=>'Restaurant', 'slug_ar' =>'مطعم' ],

    [ 'type'=>'offices','slug'=>'admin-office','name_en'=>'Administrative Office', 'slug_ar' =>'مكتب إداري' ],
    [ 'type'=>'offices','slug'=>'corporate','name_en'=>'Corporate Office', 'slug_ar' =>'مكتب شركات' ],

    [ 'type'=>'medical','slug'=>'clinic','name_en'=>'Clinic', 'slug_ar' =>'عيادة' ],
    [ 'type'=>'medical','slug'=>'lab','name_en'=>'Lab', 'slug_ar' =>'معمل' ],
    [ 'type'=>'medical','slug'=>'medical-center','name_en'=>'Medical Center', 'slug_ar' =>'مركز طبي' ],
  ];

  $sub_ids = []; // key: type_slug|sub_slug => id
  foreach ($subs as $s) {
    $type_id = $type_ids[$s['type']] ?? 0;
    if ($type_id <= 0) continue;

    $row = [
      'slug' => $s['slug'],
      'name_en' => $s['name_en'],
       'slug_ar'  => $s[ 'slug_ar' ],
      'property_type_id' => $type_id,
      'is_active' => 1,
      'sort_order' => 0,
    ];

    $sub_id = jawda_seed_upsert($t_subs, $row);
    $k = $s['type'].'|'.$s['slug'];
    $sub_ids[$k] = $sub_id;
    jawda_seed_print("Sub Property: {$k} => #{$sub_id}");
  }

  /**
   * 4) Property Models
   * Rule:
   * - Bedrooms are meaningful only if category includes: residential OR vacation-coastal
   * - For commercial: bedrooms=0
   *
   * IMPORTANT:
   * - For Apartments/Villas/Chalets etc: we set property_type_id + sub_property_id
   * - Also attach categories pivot (model <-> category_ids[])
   */
  $models = [
    // Apartments -> Apartment
    [ 'slug'=>'studio', 'name_en'=>'Studio',  'slug_ar' =>'استوديو', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'1br', 'name_en'=>'1 Bedroom',  'slug_ar' =>'غرفة واحدة', 'bedrooms'=>1, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'2br', 'name_en'=>'2 Bedrooms',  'slug_ar' =>'غرفتين', 'bedrooms'=>2, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'3br', 'name_en'=>'3 Bedrooms',  'slug_ar' =>'3 غرف', 'bedrooms'=>3, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'4br', 'name_en'=>'4 Bedrooms',  'slug_ar' =>'4 غرف', 'bedrooms'=>4, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential'] ],

    [ 'slug'=>'gf-apartment', 'name_en'=>'Ground Floor Apartment',  'slug_ar' =>'دور أرضي', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'gf-garden', 'name_en'=>'Ground Floor with Garden',  'slug_ar' =>'أرضي بحديقة', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'typical-floor', 'name_en'=>'Typical Floor',  'slug_ar' =>'دور متكرر', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'apartment', 'cats'=>['residential'] ],

    // Apartments -> Duplex
    [ 'slug'=>'duplex', 'name_en'=>'Duplex',  'slug_ar' =>'دوبلكس', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'duplex', 'cats'=>['residential'] ],
    [ 'slug'=>'duplex-upper', 'name_en'=>'Duplex (Upper)',  'slug_ar' =>'دوبلكس علوي', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'duplex', 'cats'=>['residential'] ],
    [ 'slug'=>'duplex-gf', 'name_en'=>'Duplex (Ground + First)',  'slug_ar' =>'دوبلكس أرضي + أول', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'duplex', 'cats'=>['residential'] ],

    // Apartments -> Penthouse
    [ 'slug'=>'penthouse', 'name_en'=>'Penthouse',  'slug_ar' =>'بنتهاوس', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'penthouse', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'penthouse-roof', 'name_en'=>'Penthouse with Roof',  'slug_ar' =>'بنتهاوس برووف', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'penthouse', 'cats'=>['residential','vacation-coastal'] ],
    [ 'slug'=>'penthouse-terrace', 'name_en'=>'Penthouse with Terrace',  'slug_ar' =>'بنتهاوس بتراس', 'bedrooms'=>0, 'type'=>'apartments','sub'=>'penthouse', 'cats'=>['residential','vacation-coastal'] ],

    // Villas
    [ 'slug'=>'standalone-villa', 'name_en'=>'Standalone Villa',  'slug_ar' =>'فيلا مستقلة', 'bedrooms'=>0, 'type'=>'villas','sub'=>'standalone', 'cats'=>['residential'] ],
    [ 'slug'=>'twin-house', 'name_en'=>'Twin House',  'slug_ar' =>'توين هاوس', 'bedrooms'=>0, 'type'=>'villas','sub'=>'twin-house', 'cats'=>['residential'] ],
    [ 'slug'=>'townhouse-middle', 'name_en'=>'Townhouse (Middle)',  'slug_ar' =>'تاون هاوس (ميدل)', 'bedrooms'=>0, 'type'=>'villas','sub'=>'townhouse', 'cats'=>['residential'] ],
    [ 'slug'=>'townhouse-corner', 'name_en'=>'Townhouse (Corner)',  'slug_ar' =>'تاون هاوس (كورнер)', 'bedrooms'=>0, 'type'=>'villas','sub'=>'townhouse', 'cats'=>['residential'] ],

    // Vacation -> Chalet
    [ 'slug'=>'studio-chalet', 'name_en'=>'Studio Chalet',  'slug_ar' =>'شاليه استوديو', 'bedrooms'=>0, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'1br-chalet', 'name_en'=>'1 Bedroom Chalet',  'slug_ar' =>'شاليه غرفة', 'bedrooms'=>1, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'2br-chalet', 'name_en'=>'2 Bedrooms Chalet',  'slug_ar' =>'شاليه غرفتين', 'bedrooms'=>2, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'3br-chalet', 'name_en'=>'3 Bedrooms Chalet',  'slug_ar' =>'شاليه 3 غرف', 'bedrooms'=>3, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'chalet-garden', 'name_en'=>'Chalet with Garden',  'slug_ar' =>'شاليه بحديقة', 'bedrooms'=>0, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'chalet-roof', 'name_en'=>'Chalet with Roof',  'slug_ar' =>'شاليه برووف', 'bedrooms'=>0, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'chalet-sea-view', 'name_en'=>'Chalet Sea View',  'slug_ar' =>'شاليه فيو بحر', 'bedrooms'=>0, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'chalet-lagoon-view', 'name_en'=>'Chalet Lagoon View',  'slug_ar' =>'شاليه فيو لاجون', 'bedrooms'=>0, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],
    [ 'slug'=>'beachfront-chalet', 'name_en'=>'Beach Front Chalet',  'slug_ar' =>'شاليه صف أول', 'bedrooms'=>0, 'type'=>'chalets','sub'=>'chalet', 'cats'=>['vacation-coastal'] ],

    // Commercial -> Retail/Office/Medical (bedrooms forced 0)
    [ 'slug'=>'retail-shop', 'name_en'=>'Retail Shop',  'slug_ar' =>'محل', 'bedrooms'=>0, 'type'=>'retail','sub'=>'shop', 'cats'=>['commercial'] ],
    [ 'slug'=>'corner-shop', 'name_en'=>'Corner Shop',  'slug_ar' =>'محل ناصية', 'bedrooms'=>0, 'type'=>'retail','sub'=>'shop', 'cats'=>['commercial'] ],
    [ 'slug'=>'double-height-shop', 'name_en'=>'Double Height Shop',  'slug_ar' =>'محل دبل هايت', 'bedrooms'=>0, 'type'=>'retail','sub'=>'shop', 'cats'=>['commercial'] ],
    [ 'slug'=>'kiosk', 'name_en'=>'Kiosk',  'slug_ar' =>'كشك', 'bedrooms'=>0, 'type'=>'retail','sub'=>'kiosk', 'cats'=>['commercial'] ],
    [ 'slug'=>'restaurant', 'name_en'=>'Restaurant',  'slug_ar' =>'مطعم', 'bedrooms'=>0, 'type'=>'retail','sub'=>'restaurant', 'cats'=>['commercial'] ],

    [ 'slug'=>'admin-office', 'name_en'=>'Administrative Office',  'slug_ar' =>'مكتب إداري', 'bedrooms'=>0, 'type'=>'offices','sub'=>'admin-office', 'cats'=>['commercial'] ],
    [ 'slug'=>'corporate-office', 'name_en'=>'Corporate Office',  'slug_ar' =>'مكتب شركات', 'bedrooms'=>0, 'type'=>'offices','sub'=>'corporate', 'cats'=>['commercial'] ],

    [ 'slug'=>'medical-clinic', 'name_en'=>'Medical Clinic',  'slug_ar' =>'عيادة', 'bedrooms'=>0, 'type'=>'medical','sub'=>'clinic', 'cats'=>['commercial'] ],
    [ 'slug'=>'analysis-lab', 'name_en'=>'Analysis Lab',  'slug_ar' =>'معمل', 'bedrooms'=>0, 'type'=>'medical','sub'=>'lab', 'cats'=>['commercial'] ],
  ];

  $count = 0;
  foreach ($models as $m) {
    $type_id = $type_ids[$m['type']] ?? 0;
    $sub_id  = $sub_ids[$m['type'].'|'.$m['sub']] ?? 0;

    if ($type_id <= 0 || $sub_id <= 0) {
      jawda_seed_print("SKIP model {$m['slug']} (missing type/sub)");
      continue;
    }

    // Bedrooms rule: only meaningful if cats contain residential or vacation-coastal
    $allow_bedrooms = false;
    foreach ((array)$m['cats'] as $cslug) {
      if (in_array($cslug, ['residential','vacation-coastal'], true)) { $allow_bedrooms = true; break; }
    }
    $bedrooms = $allow_bedrooms ? (int)($m['bedrooms'] ?? 0) : 0;

    $row = [
      'slug' => $m['slug'],
      'name_en' => $m['name_en'],
       'slug_ar'  => $m[ 'slug_ar' ],
      'property_type_id' => (int)$type_id,
      'sub_property_id'  => (int)$sub_id,
      'bedrooms' => $bedrooms,
      'icon' => null,
      'is_active' => 1,
      'sort_order' => 0,
    ];

    $model_id = jawda_seed_upsert($t_models, $row);

    // attach categories to model
    foreach ((array)$m['cats'] as $cslug) {
      $cid = $cat_ids[$cslug] ?? 0;
      jawda_seed_pivot_attach($t_pivot_model_cat, 'property_model_id', $model_id, 'category_id', $cid);
    }

    $count++;
    jawda_seed_print("Model: {$m['slug']} => #{$model_id} (type={$m['type']} sub={$m['sub']} bedrooms={$bedrooms})");
  }

  jawda_seed_print("DONE. Seeded/updated models count: {$count}");
}

jawda_seed_run();
