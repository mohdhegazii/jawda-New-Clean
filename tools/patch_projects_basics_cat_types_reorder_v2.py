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

def slice_basics_tab(src: str) -> tuple[int,int,str]:
    # Start at Basics data tab
    m = re.search(r"->add_tab\(\s*__\(\s*'Basics data'\s*,\s*'aqarand'\s*\)\s*,\s*array\s*\(", src, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not locate Basics data tab start.")
    start = m.start()

    # End at next ->add_tab( after this one
    m2 = re.search(r"->add_tab\(\s*__\(", src[m.end():], flags=re.S)
    end = (m.end() + m2.start()) if m2 else len(src)
    seg = src[start:end]
    return start, end, seg

def ensure_php_helpers(src: str) -> str:
    if "function aqarand_projects_get_categories_options" in src and "function aqarand_projects_get_property_types_index" in src:
        return src

    helpers = r"""
/**
 * Projects Basics: Categories + Property Types lookups (custom tables, no taxonomies).
 * Reads directly from DB with fallbacks (column/table names may vary).
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

    $cols = $wpdb->get_col("DESCRIBE `$table`", 0);
    $has_status = in_array('status', $cols, true);
    $has_sort   = in_array('sort', $cols, true) || in_array('sort_order', $cols, true);
    $sort_col   = in_array('sort', $cols, true) ? 'sort' : (in_array('sort_order', $cols, true) ? 'sort_order' : '');

    $where = $has_status ? "WHERE status='active' OR status='Active' OR status=1" : "";
    $order = ($has_sort && $sort_col) ? "ORDER BY `$sort_col` ASC, id ASC" : "ORDER BY id ASC";

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
    $order = ($has_sort && $sort_col) ? "ORDER BY `$sort_col` ASC, id ASC" : "ORDER BY id ASC";

    $rows = $wpdb->get_results("SELECT * FROM `$table` $where $order", ARRAY_A);
    if ( empty($rows) ) return array();

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

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

      if ( is_numeric($cats_raw) ) {
        $cats = array((int)$cats_raw);
      } else if ( is_string($cats_raw) && $cats_raw !== '' ) {
        $trim = trim($cats_raw);
        if ( strlen($trim) && ($trim[0] === '[' || $trim[0] === '{') ) {
          $decoded = json_decode($trim, true);
          if ( is_array($decoded) ) {
            if ( isset($decoded['ids']) && is_array($decoded['ids']) ) $cats = array_map('intval', $decoded['ids']);
            else $cats = array_map('intval', array_values($decoded));
          }
        } else {
          $cats = array_map('intval', preg_split('/\s*,\s*/', $trim));
        }
      }

      $cats = array_values(array_filter(array_unique($cats)));
      $out[(string)$id] = array('label' => $label, 'categories' => $cats);
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
    if "AQARAND_PROJECTS_LOOKUPS" in src:
        return src

    m = re.search(r"function\s+aqarand_admin_enqueue_projects_area_converter\s*\(\s*\$hook\s*\)\s*\{.*?\n\}", src, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not find aqarand_admin_enqueue_projects_area_converter()")

    block = m.group(0)
    if "wp_enqueue_script($handle);" not in block:
        raise SystemExit("ERROR: wp_enqueue_script($handle) not found in enqueue function.")

    inject = r"""
    // Localize lookups for dependent dropdowns (Categories -> Property Types)
    $cats = function_exists('aqarand_projects_get_categories_options') ? aqarand_projects_get_categories_options() : array();
    $types_index = function_exists('aqarand_projects_get_property_types_index') ? aqarand_projects_get_property_types_index() : array();

    wp_localize_script($handle, 'AQARAND_PROJECTS_LOOKUPS', array(
      'categories' => $cats,
      'property_types' => $types_index
    ));
"""
    block2 = block.replace("wp_enqueue_script($handle);", inject + "\n    wp_enqueue_script($handle);", 1)
    return src[:m.start()] + block2 + src[m.end():]

def insert_fields_after_separator(seg: str) -> str:
    if "_hegzz_project_category_id" in seg and "_hegzz_project_property_type_ids" in seg:
        return seg

    cat_field = r"""
    Field::make( 'select', '_hegzz_project_category_id', __( 'Category', 'aqarand' ) )
      ->add_options( 'aqarand_projects_get_categories_options' )
      ->set_help_text( __( 'Choose the main category for this project.', 'aqarand' ) ),
""".strip("\n")

    types_field = r"""
    Field::make( 'multiselect', '_hegzz_project_property_type_ids', __( 'Property Types', 'aqarand' ) )
      ->add_options( 'aqarand_projects_get_property_types_options_all' )
      ->set_help_text( __( 'Choose property types. List is filtered by the selected category instantly.', 'aqarand' ) ),
""".strip("\n")

    # find separator line in Basics tab
    m = re.search(r"Field::make\(\s*'separator'\s*,\s*'jawda_project_basics_sep'.*?\)\s*(?:->.*?\)\s*)*,", seg, flags=re.S)
    if not m:
        # fallback: insert after "array(" opening
        mm = re.search(r"array\s*\(\s*", seg, flags=re.S)
        if not mm:
            raise SystemExit("ERROR: Could not find array( in Basics tab seg.")
        ins = mm.end()
        return seg[:ins] + "\n\n    " + cat_field + "\n\n    " + types_field + seg[ins:]

    ins = m.end()
    return seg[:ins] + "\n\n    " + cat_field + "\n\n    " + types_field + seg[ins:]

def move_developer_before_area(seg: str) -> str:
    # locate developer field block
    dev = re.search(r"(Field::make\(\s*'select'\s*,\s*'_hegzz_project_developer_id'.*?\)\s*(?:->.*?\)\s*)*,\s*)", seg, flags=re.S)
    area = re.search(r"(Field::make\(\s*'text'\s*,\s*'jawda_project_total_area_value'.*?\)\s*(?:->.*?\)\s*)*,\s*)", seg, flags=re.S)
    if not dev or not area:
        return seg

    dev_block = dev.group(1)
    # if dev already before area, do nothing
    if dev.start() < area.start():
        return seg

    # remove dev block then insert before area block
    seg2 = seg[:dev.start()] + seg[dev.end():]
    area2 = re.search(r"(Field::make\(\s*'text'\s*,\s*'jawda_project_total_area_value'.*?\)\s*(?:->.*?\)\s*)*,\s*)", seg2, flags=re.S)
    if not area2:
        return seg  # fallback, no change
    return seg2[:area2.start()] + dev_block + "\n    " + seg2[area2.start():]

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

    function getSelected() {
      return Array.from(typesSel.selectedOptions || []).map(function(o){ return o.value; });
    }

    function rebuild(catId) {
      var selected = new Set(getSelected());
      var options = [];

      Object.keys(typesIndex).forEach(function(id){
        var row = typesIndex[id] || {};
        var label = row.label || id;
        var cats = row.categories || [];
        var ok = !catId || cats.indexOf(parseInt(catId,10)) !== -1;
        if (ok) options.push({id:id, label:label});
      });

      options.sort(function(a,b){ return (a.label||'').localeCompare(b.label||''); });

      typesSel.innerHTML = '';
      options.forEach(function(o){
        var opt = document.createElement('option');
        opt.value = o.id;
        opt.textContent = o.label;
        if (selected.has(o.id)) opt.selected = true;
        typesSel.appendChild(opt);
      });

      try { if (window.jQuery) window.jQuery(typesSel).trigger('change'); } catch(e){}
    }

    rebuild(catsSel.value);
    catsSel.addEventListener('change', function(){ rebuild(catsSel.value); });

    return true;
  }

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

    # Patch Basics tab segment
    s, e, seg = slice_basics_tab(php_src)
    seg2 = insert_fields_after_separator(seg)
    seg3 = move_developer_before_area(seg2)
    php_src = php_src[:s] + seg3 + php_src[e:]

    php_src = ensure_php_helpers(php_src)
    php_src = ensure_localize_in_enqueue(php_src)
    js_src  = ensure_js_dependent_logic(js_src)

    b1 = backup(PHP)
    b2 = backup(JS)
    write(PHP, php_src)
    write(JS, js_src)

    print("[OK] Patched:", PHP)
    print("[OK] Backup :", b1)
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b2)
    print("[INFO] Basics data: Category + Property Types inserted; Developer moved before Total area.")

if __name__ == "__main__":
    main()
