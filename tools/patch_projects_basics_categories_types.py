#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
from pathlib import Path
from datetime import datetime
import re

ROOT = Path(".").resolve()
PHP = ROOT / "app/functions/meta_box.php"
JS  = ROOT / "assets/js/admin/projects-area.js"

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(read(p), encoding="utf-8")
    return b

def find_basics_tab_block(src: str) -> tuple[int,int,str]:
    # locate Basics data tab array(...) and return full tab fields block
    m = re.search(r"->add_tab\(\s*__\(\s*'Basics data'\s*,\s*'aqarand'\s*\)\s*,\s*array\s*\(", src, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not find Basics data tab.")
    start = m.end()

    # find matching closing for array( ... )
    i = start
    depth = 1
    while i < len(src):
        if src[i:i+5] == "array(":
            depth += 1
            i += 5
            continue
        if src[i] == "(":
            i += 1
            continue
        if src[i] == ")":
            depth -= 1
            i += 1
            if depth == 0:
                end = i
                block = src[start:end-1]  # inside array(...)
                return start, end-1, block
            continue
        i += 1
    raise SystemExit("ERROR: Could not parse Basics data array() block end.")

def extract_dev_field(block: str) -> str|None:
    # find developer field by meta key
    m = re.search(r"(Field::make\(\s*'select'\s*,\s*'_hegzz_project_developer_id'.*?\)\s*(?:->.*?\)\s*)*[,;])", block, flags=re.S)
    return m.group(1).strip() if m else None

def extract_area_fields(block: str) -> str:
    # keep area value/unit/js fields as-is (we already use them)
    keys = [
        "jawda_project_total_area_value",
        "jawda_project_total_area_unit",
        "jawda_project_total_area_js",
        # hidden stored fields may still exist in PHP but removed from DOM; keep them if present
        "jawda_project_total_area_m2",
        "jawda_project_total_area_acres",
    ]
    parts = []
    for k in keys:
        m = re.search(rf"(Field::make\(\s*'[^']+'\s*,\s*'{re.escape(k)}'.*?\)\s*(?:->.*?\)\s*)*[,;])", block, flags=re.S)
        if m:
            parts.append(m.group(1).strip())
    if not parts:
        raise SystemExit("ERROR: Could not find area fields in Basics block.")
    # de-dup while preserving order
    seen=set(); out=[]
    for p in parts:
        if p not in seen:
            seen.add(p); out.append(p)
    return "\n\n    ".join(out)

def build_category_types_fields() -> str:
    return r"""
    Field::make( 'select', '_hegzz_project_category_id', __( 'Category', 'aqarand' ) )
      ->add_options( 'aqarand_projects_get_categories_options' )
      ->set_help_text( __( 'Choose the main category for this project.', 'aqarand' ) ),

    Field::make( 'multiselect', '_hegzz_project_property_type_ids', __( 'Property Types', 'aqarand' ) )
      ->add_options( 'aqarand_projects_get_property_types_options_all' )
      ->set_help_text( __( 'Choose property types. List is filtered by the selected category instantly.', 'aqarand' ) ),
""".strip("\n")

def ensure_php_helpers(src: str) -> str:
    if "function aqarand_projects_get_categories_options" in src and "function aqarand_projects_get_property_types_options_all" in src:
        return src

    helpers = r"""
/**
 * Projects Basics: Categories + Property Types lookups (custom tables, no taxonomies).
 * We read directly from DB with fallbacks because column names may vary.
 */
if ( ! function_exists('aqarand_projects_get_categories_options') ) {
  function aqarand_projects_get_categories_options() {
    global $wpdb;

    $table_candidates = array(
      $wpdb->prefix . 'hegzz_categories',
      $wpdb->prefix . 'hegzz_property_categories',
      $wpdb->prefix . 'hegzz_categories_lookup',
    );

    $table = '';
    foreach ( $table_candidates as $t ) {
      $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $t) );
      if ( $exists === $t ) { $table = $t; break; }
    }
    if ( empty($table) ) return array();

    // prefer active + sorted columns if present
    $cols = $wpdb->get_col("DESCRIBE `$table`", 0);
    $has_status = in_array('status', $cols, true);
    $has_sort   = in_array('sort', $cols, true) || in_array('sort_order', $cols, true);
    $sort_col   = in_array('sort', $cols, true) ? 'sort' : (in_array('sort_order', $cols, true) ? 'sort_order' : '');

    $where = $has_status ? "WHERE status='active' OR status='Active' OR status=1" : "";
    $order = $has_sort && $sort_col ? "ORDER BY `$sort_col` ASC, id ASC" : "ORDER BY id ASC";

    $rows = $wpdb->get_results("SELECT * FROM `$table` $where $order", ARRAY_A);
    if ( empty($rows) ) return array();

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

    $out = array();
    foreach ( $rows as $r ) {
      $id = isset($r['id']) ? (int)$r['id'] : 0;
      if ( ! $id ) continue;

      $name_ar = $r['name_ar'] ?? ($r['ar_name'] ?? ($r['title_ar'] ?? ''));
      $name_en = $r['name_en'] ?? ($r['en_name'] ?? ($r['title_en'] ?? ($r['name'] ?? '')));

      $label = $is_ar ? ($name_ar ?: $name_en) : ($name_en ?: $name_ar);
      if ( empty($label) ) $label = 'ID ' . $id;

      $out[(string)$id] = $label;
    }
    return $out;
  }
}

if ( ! function_exists('aqarand_projects_get_property_types_index') ) {
  function aqarand_projects_get_property_types_index() {
    global $wpdb;

    $table_candidates = array(
      $wpdb->prefix . 'hegzz_property_types',
      $wpdb->prefix . 'hegzz_types',
      $wpdb->prefix . 'hegzz_property_types_lookup',
    );

    $table = '';
    foreach ( $table_candidates as $t ) {
      $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $t) );
      if ( $exists === $t ) { $table = $t; break; }
    }
    if ( empty($table) ) return array();

    $cols = $wpdb->get_col("DESCRIBE `$table`", 0);
    $has_status = in_array('status', $cols, true);
    $has_sort   = in_array('sort', $cols, true) || in_array('sort_order', $cols, true);
    $sort_col   = in_array('sort', $cols, true) ? 'sort' : (in_array('sort_order', $cols, true) ? 'sort_order' : '');

    $where = $has_status ? "WHERE status='active' OR status='Active' OR status=1" : "";
    $order = $has_sort && $sort_col ? "ORDER BY `$sort_col` ASC, id ASC" : "ORDER BY id ASC";

    $rows = $wpdb->get_results("SELECT * FROM `$table` $where $order", ARRAY_A);
    if ( empty($rows) ) return array();

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

    // detect category relation column name
    $cat_cols = array('category_ids','category_id','categories','category','cat_ids','cat_id');
    $cat_col = '';
    foreach($cat_cols as $c){ if(in_array($c,$cols,true)){ $cat_col=$c; break; } }

    $out = array();
    foreach ( $rows as $r ) {
      $id = isset($r['id']) ? (int)$r['id'] : 0;
      if ( ! $id ) continue;

      $name_ar = $r['name_ar'] ?? ($r['ar_name'] ?? ($r['title_ar'] ?? ''));
      $name_en = $r['name_en'] ?? ($r['en_name'] ?? ($r['title_en'] ?? ($r['name'] ?? '')));

      $label = $is_ar ? ($name_ar ?: $name_en) : ($name_en ?: $name_ar);
      if ( empty($label) ) $label = 'ID ' . $id;

      $cats_raw = $cat_col ? ($r[$cat_col] ?? '') : '';
      $cats = array();

      // parse category ids: could be json array, comma string, int
      if ( is_numeric($cats_raw) ) {
        $cats = array((int)$cats_raw);
      } else if ( is_string($cats_raw) && $cats_raw !== '' ) {
        $trim = trim($cats_raw);
        if ( strlen($trim) && ($trim[0] === '[' || $trim[0] === '{') ) {
          $decoded = json_decode($trim, true);
          if ( is_array($decoded) ) {
            // accept [1,2] or {"ids":[1,2]}
            if ( isset($decoded['ids']) && is_array($decoded['ids']) ) $cats = array_map('intval', $decoded['ids']);
            else $cats = array_map('intval', array_values($decoded));
          }
        } else {
          $cats = array_map('intval', preg_split('/\s*,\s*/', $trim));
        }
      }

      $cats = array_values(array_filter(array_unique($cats)));
      $out[(string)$id] = array(
        'label' => $label,
        'categories' => $cats,
      );
    }

    return $out;
  }
}

if ( ! function_exists('aqarand_projects_get_property_types_options_all') ) {
  function aqarand_projects_get_property_types_options_all() {
    $index = aqarand_projects_get_property_types_index();
    $out = array();
    foreach ( $index as $id => $row ) {
      $out[(string)$id] = is_array($row) ? ($row['label'] ?? $id) : (string)$row;
    }
    return $out;
  }
}
""".strip("\n") + "\n"
    return src.rstrip() + "\n\n" + helpers

def ensure_localize_in_enqueue(src: str) -> str:
    # extend existing admin enqueue function to localize categories/types data
    if "AQARAND_PROJECTS_LOOKUPS" in src:
        return src

    # find enqueue function block
    m = re.search(r"function\s+aqarand_admin_enqueue_projects_area_converter\s*\(\s*\$hook\s*\)\s*\{.*?\n\}", src, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not find aqarand_admin_enqueue_projects_area_converter() in meta_box.php")

    block = m.group(0)
    # insert wp_localize_script just before wp_enqueue_script($handle);
    if "wp_enqueue_script($handle);" not in block:
        raise SystemExit("ERROR: Could not find wp_enqueue_script(\$handle) inside enqueue function.")

    inject = r"""
    // Localize lookups for dependent dropdowns (Categories -> Property Types)
    $cats = function_exists('aqarand_projects_get_categories_options') ? aqarand_projects_get_categories_options() : array();
    $types_index = function_exists('aqarand_projects_get_property_types_index') ? aqarand_projects_get_property_types_index() : array();

    wp_localize_script($handle, 'AQARAND_PROJECTS_LOOKUPS', array(
      'categories' => $cats,          // {id: label}
      'property_types' => $types_index // {id: {label, categories:[...]}}
    ));
"""
    block2 = block.replace("wp_enqueue_script($handle);", inject + "\n    wp_enqueue_script($handle);", 1)
    return src[:m.start()] + block2 + src[m.end():]

def patch_basics_tab(src: str) -> str:
    start, end, block = find_basics_tab_block(src)

    # keep separator
    sep = re.search(r"(Field::make\(\s*'separator'\s*,\s*'jawda_project_basics_sep'.*?\)\s*(?:->.*?\)\s*)*[,;])", block, flags=re.S)
    sep_line = sep.group(1).strip() if sep else "Field::make( 'separator', 'jawda_project_basics_sep', __( 'Basics data', 'aqarand' ) ),"

    dev = extract_dev_field(block)
    if not dev:
        # if developer field not present yet, add a placeholder that will rely on existing callback in file (common key)
        dev = r"""Field::make( 'select', '_hegzz_project_developer_id', __( 'Developer*', 'aqarand' ) )
      ->add_options( 'aqarand_get_developers_list' )
      ->set_help_text( __( 'Select the project developer (hegzz developer).', 'aqarand' ) ),"""

    area = extract_area_fields(block)

    # Remove any existing category/type fields if present (avoid duplicates)
    cleaned = block
    cleaned = re.sub(r"Field::make\(\s*'select'\s*,\s*'_hegzz_project_category_id'.*?\)\s*(?:->.*?\)\s*)*[,;]\s*", "", cleaned, flags=re.S)
    cleaned = re.sub(r"Field::make\(\s*'multiselect'\s*,\s*'_hegzz_project_property_type_ids'.*?\)\s*(?:->.*?\)\s*)*[,;]\s*", "", cleaned, flags=re.S)

    # Build new block in required order:
    # separator -> category -> types -> developer -> area fields -> rest (other fields if any)
    # We'll keep other trailing fields that are not the ones we reassembled.
    # Remove existing dev + area fields from cleaned before appending rest.
    cleaned2 = cleaned
    cleaned2 = re.sub(r"(Field::make\(\s*'select'\s*,\s*'_hegzz_project_developer_id'.*?\)\s*(?:->.*?\)\s*)*[,;]\s*)", "", cleaned2, flags=re.S)
    for k in ["jawda_project_total_area_value","jawda_project_total_area_unit","jawda_project_total_area_js","jawda_project_total_area_m2","jawda_project_total_area_acres"]:
        cleaned2 = re.sub(rf"(Field::make\(\s*'[^']+'\s*,\s*'{re.escape(k)}'.*?\)\s*(?:->.*?\)\s*)*[,;]\s*)", "", cleaned2, flags=re.S)

    rest = cleaned2.strip()
    if rest:
        rest = "\n\n    " + rest.strip()

    new_block = f"""
    {sep_line}

    {build_category_types_fields()}

    {dev}

    {area}{rest}
""".strip("\n")

    return src[:start] + new_block + src[end:]

def ensure_js_dependent_logic(js: str) -> str:
    if "bootCategoryTypesDependent" in js:
        return js

    addon = r"""

  /* === Basics data: dependent dropdowns (Category -> Property Types) === */
  function bootCategoryTypesDependent() {
    var catsSel = qs('select[name*="_hegzz_project_category_id"], select[id*="_hegzz_project_category_id"]');
    var typesSel = qs('select[multiple][name*="_hegzz_project_property_type_ids"], select[multiple][id*="_hegzz_project_property_type_ids"]');

    if (!catsSel || !typesSel) return false;

    var data = (window.AQARAND_PROJECTS_LOOKUPS || {});
    var typesIndex = data.property_types || {};

    function currentSelectedTypes() {
      return Array.from(typesSel.selectedOptions || []).map(function(o){ return o.value; });
    }

    function rebuildTypesOptionsForCategory(catId) {
      var selected = new Set(currentSelectedTypes());
      var options = [];

      Object.keys(typesIndex).forEach(function(id){
        var row = typesIndex[id];
        var label = (row && row.label) ? row.label : id;
        var cats = (row && row.categories) ? row.categories : [];
        var ok = !catId || cats.indexOf(parseInt(catId,10)) !== -1;
        if (ok) options.push({id:id, label:label});
      });

      // sort by label
      options.sort(function(a,b){ return (a.label||'').localeCompare(b.label||''); });

      // rebuild select
      typesSel.innerHTML = '';
      options.forEach(function(o){
        var opt = document.createElement('option');
        opt.value = o.id;
        opt.textContent = o.label;
        if (selected.has(o.id)) opt.selected = true;
        typesSel.appendChild(opt);
      });

      // trigger change for select2/jQuery if present
      try {
        if (window.jQuery) window.jQuery(typesSel).trigger('change');
      } catch(e){}
    }

    // initial
    rebuildTypesOptionsForCategory(catsSel.value);

    catsSel.addEventListener('change', function(){
      rebuildTypesOptionsForCategory(catsSel.value);
    });

    return true;
  }

"""
    # inject near end of boot() retry loop (safe: before final retry timer)
    # We'll call it inside the existing boot() when inputs exist. Easiest: add call in the main retry timer after boot() success.
    # If not possible, call independent retry here.

    # append + add its own retry
    addon += r"""
  // retry because Carbon renders async
  (function(){
    var tries=0;
    var t=setInterval(function(){
      tries++;
      if (bootCategoryTypesDependent()) clearInterval(t);
      if (tries>80) clearInterval(t);
    }, 250);
  })();
"""

    return js.rstrip() + "\n" + addon + "\n"

def main():
    php_src = read(PHP)
    js_src  = read(JS)

    php_src = patch_basics_tab(php_src)
    php_src = ensure_php_helpers(php_src)
    php_src = ensure_localize_in_enqueue(php_src)

    js_src  = ensure_js_dependent_logic(js_src)

    b1 = backup(PHP)
    b2 = backup(JS)
    write(PHP, php_src)
    write(JS, js_src)

    print("[OK] Patched PHP:", PHP)
    print("[OK] Backup PHP :", b1)
    print("[OK] Patched JS :", JS)
    print("[OK] Backup JS  :", b2)
    print("[INFO] Basics data order now: Category -> Property Types -> Developer* -> Total area")

if __name__ == "__main__":
    main()
