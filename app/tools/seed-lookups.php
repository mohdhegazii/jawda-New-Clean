<?php
/**
 * Seed Lookups from app/database/seed/lookups.seed.json into jawda lookup tables.
 *
 * Tables (from your DB):
 * - wp_jawda_categories
 * - wp_jawda_usages
 * - wp_jawda_property_types
 * - wp_jawda_sub_properties
 * - wp_jawda_property_models
 * Pivot:
 * - wp_jawda_property_type_categories
 * - wp_jawda_property_type_usages
 * - wp_jawda_property_model_categories
 *
 * Usage:
 *   php app/tools/seed-lookups.php --dry-run
 *   php app/tools/seed-lookups.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$dryRun = in_array('--dry-run', $argv, true);

$themeDir = realpath(__DIR__ . '/../..');
$seedFile = $themeDir . '/app/database/seed/lookups.seed.json';
if (!file_exists($seedFile)) {
  fwrite(STDERR, "❌ Seed file not found: $seedFile\n");
  exit(1);
}

// locate wp-load.php relative to theme
$wpLoad = null;
foreach ([
  $themeDir . '/../../../wp-load.php',
  $themeDir . '/../../../../wp-load.php',
] as $c) {
  $c = realpath($c);
  if ($c && file_exists($c)) { $wpLoad = $c; break; }
}
if (!$wpLoad) {
  fwrite(STDERR, "❌ Could not locate wp-load.php from theme directory.\n");
  exit(1);
}

require_once $wpLoad;
global $wpdb;

function println($s=''){ echo $s . PHP_EOL; }

$seed = json_decode(file_get_contents($seedFile), true);
if (!is_array($seed)) {
  fwrite(STDERR, "❌ Invalid JSON in $seedFile\n");
  exit(1);
}

$T = [
  'categories' => $wpdb->prefix . 'jawda_categories',
  'usages' => $wpdb->prefix . 'jawda_usages',
  'property_types' => $wpdb->prefix . 'jawda_property_types',
  'sub_properties' => $wpdb->prefix . 'jawda_sub_properties',
  'property_models' => $wpdb->prefix . 'jawda_property_models',
  'pt_categories' => $wpdb->prefix . 'jawda_property_type_categories',
  'pt_usages' => $wpdb->prefix . 'jawda_property_type_usages',
  'pm_categories' => $wpdb->prefix . 'jawda_property_model_categories',
];

println("=== Lookups Seeder (jawda) ===");
println("Seed: $seedFile");
println("WP DB: " . DB_NAME);
println("Dry-run: " . ($dryRun ? "YES" : "NO"));
println("");

/** helpers **/
function upsert_by_name_en($wpdb, $table, $name_en, array $row, $dryRun) {
  $existingId = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE name_en=%s LIMIT 1", $name_en));
  if ($dryRun) return [$existingId ? 'update' : 'insert', $existingId ? (int)$existingId : null, $row];

  if ($existingId) {
    $wpdb->update($table, $row, ['id' => $existingId]);
    return ['update', (int)$existingId, $row];
  } else {
    $wpdb->insert($table, $row);
    return ['insert', (int)$wpdb->insert_id, $row];
  }
}

function upsert_sub_property($wpdb, $table, $property_type_id, $name_en, array $row, $dryRun) {
  $existingId = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM `$table` WHERE property_type_id=%d AND name_en=%s LIMIT 1",
    $property_type_id, $name_en
  ));
  if ($dryRun) return [$existingId ? 'update' : 'insert', $existingId ? (int)$existingId : null, $row];

  if ($existingId) {
    $wpdb->update($table, $row, ['id' => $existingId]);
    return ['update', (int)$existingId, $row];
  } else {
    $wpdb->insert($table, $row);
    return ['insert', (int)$wpdb->insert_id, $row];
  }
}

function upsert_property_model_by_slug($wpdb, $table, $slug, array $row, $dryRun) {
  $existingId = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE slug=%s LIMIT 1", $slug));
  if ($dryRun) return [$existingId ? 'update' : 'insert', $existingId ? (int)$existingId : null, $row];

  if ($existingId) {
    $wpdb->update($table, $row, ['id' => $existingId]);
    return ['update', (int)$existingId, $row];
  } else {
    $wpdb->insert($table, $row);
    return ['insert', (int)$wpdb->insert_id, $row];
  }
}

function upsert_property_model_smart($wpdb, $table, $slug, $property_type_id, $sub_property_id, $bedrooms, $name_en, array $row, $dryRun) {
  $slug = is_string($slug) ? trim($slug) : '';
  if ($slug !== '') {
    return upsert_property_model_by_slug($wpdb, $table, $slug, $row, $dryRun);
  }

  // signature-based lookup (fallback when slug absent)
  $sql = "SELECT id FROM `$table` WHERE property_type_id=%d AND ";
  $params = [(int)$property_type_id];

  if (is_null($sub_property_id)) {
    $sql .= "sub_property_id IS NULL AND ";
  } else {
    $sql .= "sub_property_id=%d AND ";
    $params[] = (int)$sub_property_id;
  }

  $sql .= "bedrooms=%d AND name_en=%s LIMIT 1";
  $params[] = (int)$bedrooms;
  $params[] = (string)$name_en;

  // prepare with dynamic args
  $prepArgs = array_merge([$sql], $params);
  $prepared = call_user_func_array([$wpdb, 'prepare'], $prepArgs);
  $existingId = $wpdb->get_var($prepared);

  if ($dryRun) return [$existingId ? 'update' : 'insert', $existingId ? (int)$existingId : null, $row];

  if ($existingId) {
    $wpdb->update($table, $row, ['id' => $existingId]);
    return ['update', (int)$existingId, $row];
  } else {
    $wpdb->insert($table, $row);
    return ['insert', (int)$wpdb->insert_id, $row];
  }
}


function pivot_insert_ignore($wpdb, $table, array $row, array $whereUnique, $dryRun) {
  $whereParts = [];
  $vals = [];

  foreach ($whereUnique as $k => $v) {
    // treat numeric values as %d
    if (is_int($v) || (is_string($v) && ctype_digit($v))) {
      $whereParts[] = "`$k`=%d";
      $vals[] = (int)$v;
    } else {
      $whereParts[] = "`$k`=%s";
      $vals[] = (string)$v;
    }
  }

  $sql = "SELECT 1 FROM `$table` WHERE " . implode(" AND ", $whereParts) . " LIMIT 1";
  $prepArgs = array_merge([$sql], $vals);
  $prepared = call_user_func_array([$wpdb, "prepare"], $prepArgs);
  $exists = $wpdb->get_var($prepared);

  if ($exists) return ["skip", null, $row];
  if ($dryRun) return ["insert", null, $row];

  $wpdb->insert($table, $row);
  return ["insert", null, $row];
}

/**
 * Build slug->name_en maps from seed (since DB tables don't have slug for most lookups)
 */
$catSlugToName = [];
foreach (($seed['categories'] ?? []) as $c) $catSlugToName[$c['slug']] = $c['name_en'];

$usageSlugToName = [];
foreach (($seed['usages'] ?? []) as $u) $usageSlugToName[$u['slug']] = $u['name_en'];

$ptSlugToName = [];
foreach (($seed['property_types'] ?? []) as $p) $ptSlugToName[$p['slug']] = $p['name_en'];

$spSlugToName = [];
foreach (($seed['sub_properties'] ?? []) as $s) $spSlugToName[$s['slug']] = $s['name_en'];

/**
 * 1) Categories
 */
$categoryIdsBySlug = [];
$summary = [];

$summary['categories'] = ['insert'=>0,'update'=>0];
foreach (($seed['categories'] ?? []) as $item) {
  $row = [
    'name_en' => $item['name_en'],
     'slug_ar'  => $item[ 'slug_ar' ],
    'icon_class' => $item['icon_class'] ?? null,
    'sort_order' => (int)($item['sort_order'] ?? 0),
    'is_active' => !empty($item['is_active']) ? 1 : 0,
  ];
  [$op,$id,$__] = upsert_by_name_en($wpdb, $T['categories'], $item['name_en'], $row, $dryRun);
  if ($op === 'insert') $summary['categories']['insert']++;
  if ($op === 'update') $summary['categories']['update']++;

  // get id even in dry-run (lookup)
  $realId = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$T['categories']}` WHERE name_en=%s LIMIT 1", $item['name_en']));
  if ($realId) $categoryIdsBySlug[$item['slug']] = (int)$realId;
}

/**
 * 2) Usages
 */
$usageIdsBySlug = [];
$summary['usages'] = ['insert'=>0,'update'=>0];
foreach (($seed['usages'] ?? []) as $item) {
  $row = [
    'name_en' => $item['name_en'],
     'slug_ar'  => $item[ 'slug_ar' ],
    'icon_class' => $item['icon_class'] ?? null,
    'sort_order' => (int)($item['sort_order'] ?? 0),
    'is_active' => !empty($item['is_active']) ? 1 : 0,
  ];
  [$op,$id,$__] = upsert_by_name_en($wpdb, $T['usages'], $item['name_en'], $row, $dryRun);
  if ($op === 'insert') $summary['usages']['insert']++;
  if ($op === 'update') $summary['usages']['update']++;

  $realId = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$T['usages']}` WHERE name_en=%s LIMIT 1", $item['name_en']));
  if ($realId) $usageIdsBySlug[$item['slug']] = (int)$realId;
}

/**
 * 3) Property Types + pivots
 */
$propertyTypeIdsBySlug = [];
$summary['property_types'] = ['insert'=>0,'update'=>0];
$summary['property_type_categories'] = ['insert'=>0,'skip'=>0];
$summary['property_type_usages'] = ['insert'=>0,'skip'=>0];

foreach (($seed['property_types'] ?? []) as $item) {
  $row = [
    'name_en' => $item['name_en'],
     'slug_ar'  => $item[ 'slug_ar' ],
    'icon_class' => $item['icon_class'] ?? null,
    'sort_order' => (int)($item['sort_order'] ?? 0),
    'is_active' => !empty($item['is_active']) ? 1 : 0,
  ];
  [$op,$id,$__] = upsert_by_name_en($wpdb, $T['property_types'], $item['name_en'], $row, $dryRun);
  if ($op === 'insert') $summary['property_types']['insert']++;
  if ($op === 'update') $summary['property_types']['update']++;

  $ptId = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$T['property_types']}` WHERE name_en=%s LIMIT 1", $item['name_en']));
  if ($ptId) $propertyTypeIdsBySlug[$item['slug']] = $ptId;

  // pivot: categories
  foreach (($item['categories'] ?? []) as $catSlug) {
    if (empty($categoryIdsBySlug[$catSlug])) continue;
    $res = pivot_insert_ignore(
      $wpdb,
      $T['pt_categories'],
      [
        'property_type_id' => $ptId,
        'category_id' => (int)$categoryIdsBySlug[$catSlug],
        'sort_order' => (int)($item['sort_order'] ?? 0),
        'is_active' => 1,
      ],
      ['property_type_id'=>$ptId, 'category_id'=>(int)$categoryIdsBySlug[$catSlug]],
      $dryRun
    );
    if ($res[0] === 'insert') $summary['property_type_categories']['insert']++;
    if ($res[0] === 'skip') $summary['property_type_categories']['skip']++;
  }

  // pivot: usages
  foreach (($item['usages'] ?? []) as $usageSlug) {
    if (empty($usageIdsBySlug[$usageSlug])) continue;
    $res = pivot_insert_ignore(
      $wpdb,
      $T['pt_usages'],
      [
        'property_type_id' => $ptId,
        'usage_id' => (int)$usageIdsBySlug[$usageSlug],
      ],
      ['property_type_id'=>$ptId, 'usage_id'=>(int)$usageIdsBySlug[$usageSlug]],
      $dryRun
    );
    if ($res[0] === 'insert') $summary['property_type_usages']['insert']++;
    if ($res[0] === 'skip') $summary['property_type_usages']['skip']++;
  }
}

/**
 * 4) Sub Properties
 */
$subPropertyIdsBySlug = [];
$summary['sub_properties'] = ['insert'=>0,'update'=>0];

foreach (($seed['sub_properties'] ?? []) as $item) {
  $parentSlug = $item['parent_property_type_slug'] ?? '';
  $ptId = $propertyTypeIdsBySlug[$parentSlug] ?? null;
  if (!$ptId) continue;

  $row = [
    'property_type_id' => (int)$ptId,
    'name_en' => $item['name_en'],
     'slug_ar'  => $item[ 'slug_ar' ],
    'icon_class' => $item['icon_class'] ?? null,
    'sort_order' => (int)($item['sort_order'] ?? 0),
    'is_active' => !empty($item['is_active']) ? 1 : 0,
  ];

  [$op,$id,$__] = upsert_sub_property($wpdb, $T['sub_properties'], (int)$ptId, $item['name_en'], $row, $dryRun);
  if ($op === 'insert') $summary['sub_properties']['insert']++;
  if ($op === 'update') $summary['sub_properties']['update']++;

  $spId = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT id FROM `{$T['sub_properties']}` WHERE property_type_id=%d AND name_en=%s LIMIT 1",
    (int)$ptId, $item['name_en']
  ));
  if ($spId) $subPropertyIdsBySlug[$item['slug']] = $spId;
}

/**
 * 5) Property Models + pivot categories
 */
$summary['property_models'] = ['insert'=>0,'update'=>0];
$summary['property_model_categories'] = ['insert'=>0,'skip'=>0];

foreach (($seed['property_models'] ?? []) as $item) {
  $ptSlug = $item['property_type_slug'] ?? '';
  $ptId = $propertyTypeIdsBySlug[$ptSlug] ?? null;
  if (!$ptId) continue;

  $spId = null;
  if (!empty($item['sub_property_slug'])) {
    $spId = $subPropertyIdsBySlug[$item['sub_property_slug']] ?? null;
  }

  $slug = $item['slug'] ?? sanitize_title($item['name_en'] ?? '');

  // NOTE: property_models table has column "icon" (not icon_class)
  $row = [
    'name_en' => $item['name_en'],
     'slug_ar'  => $item[ 'slug_ar' ],
    'slug' => $slug,
    'property_type_id' => (int)$ptId,
    'sub_property_id' => $spId ? (int)$spId : null,
    'bedrooms' => (int)($item['bedrooms'] ?? 0),
    'icon' => $item['icon_class'] ?? 'bi bi-house-door',
    'is_active' => !empty($item['is_active']) ? 1 : 0,
  ];

  [$op,$pmId,$__] = upsert_property_model_smart($wpdb, $T['property_models'], $slug, (int)$ptId, $spId ? (int)$spId : null, (int)($item['bedrooms'] ?? 0), (string)$item['name_en'], $row, $dryRun);
if ($op === 'insert') $summary['property_models']['insert']++;
  if ($op === 'update') $summary['property_models']['update']++;

  $realPmId = $pmId ? (int)$pmId : (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$T['property_models']}` WHERE slug=%s LIMIT 1", $slug));
if (!$realPmId) continue;

  foreach (($item['category_slugs'] ?? []) as $catSlug) {
    if (empty($categoryIdsBySlug[$catSlug])) continue;
    $res = pivot_insert_ignore(
      $wpdb,
      $T['pm_categories'],
      [
        'property_model_id' => $realPmId,
        'category_id' => (int)$categoryIdsBySlug[$catSlug],
      ],
      ['property_model_id'=>$realPmId, 'category_id'=>(int)$categoryIdsBySlug[$catSlug]],
      $dryRun
    );
    if ($res[0] === 'insert') $summary['property_model_categories']['insert']++;
    if ($res[0] === 'skip') $summary['property_model_categories']['skip']++;
  }
}

println("=== Summary ===");
foreach ($summary as $k => $v) {
  if (isset($v['update'])) {
    println(" - $k: insert={$v['insert']} update={$v['update']}");
  } else {
    println(" - $k: insert={$v['insert']} skip={$v['skip']}");
  }
}
println("");
println($dryRun ? "✅ Dry-run completed (no DB changes)." : "✅ Seeding completed (DB updated).");
