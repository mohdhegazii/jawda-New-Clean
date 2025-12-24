#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
from pathlib import Path
from datetime import datetime
import re

ROOT = Path(".").resolve()
PHP = ROOT / "app/functions/meta_box.php"

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(read(p), encoding="utf-8")
    return b

def main():
    src = read(PHP)

    # Replace only the function aqarand_projects_get_property_types_index with a more robust one
    pat = re.compile(r"if\s*\(\s*!\s*function_exists\(\s*'aqarand_projects_get_property_types_index'\s*\)\s*\)\s*\{\s*function\s+aqarand_projects_get_property_types_index\s*\(\s*\)\s*\{.*?\}\s*\}", re.S)
    m = pat.search(src)
    if not m:
        raise SystemExit("ERROR: Could not locate existing aqarand_projects_get_property_types_index() block to replace.")

    repl = r"""
if ( ! function_exists('aqarand_projects_get_property_types_index') ) {
  function aqarand_projects_get_property_types_index() {
    global $wpdb;

    // Prefer existing tables in DB (we try in order)
    $table_candidates = array(
      $wpdb->prefix . 'hegzz_property_types',
      $wpdb->prefix . 'hegzz_types',
      $wpdb->prefix . 'hegzz_property_types_lookup',
      $wpdb->prefix . 'hegzz_lookup_property_types',
    );

    $table = '';
    foreach ( $table_candidates as $t ) {
      $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $t) );
      if ( $exists === $t ) { $table = $t; break; }
    }
    if ( empty($table) ) return array();

    $cols = $wpdb->get_col("DESCRIBE `$table`", 0);

    // detect name columns
    $name_ar_col = '';
    foreach ( array('name_ar','ar_name','title_ar') as $c ) { if ( in_array($c,$cols,true) ) { $name_ar_col=$c; break; } }
    $name_en_col = '';
    foreach ( array('name_en','en_name','title_en','name','title') as $c ) { if ( in_array($c,$cols,true) ) { $name_en_col=$c; break; } }

    // detect category relation column (optional)
    $cat_col = '';
    foreach ( array('category_ids','categories','category_id','category','cat_ids','cat_id') as $c ) {
      if ( in_array($c,$cols,true) ) { $cat_col=$c; break; }
    }

    // filters
    $has_status = in_array('status', $cols, true);
    $where = $has_status ? "WHERE status='active' OR status='Active' OR status=1" : "";
    $order = in_array('sort',$cols,true) ? "ORDER BY `sort` ASC, id ASC" : (in_array('sort_order',$cols,true) ? "ORDER BY `sort_order` ASC, id ASC" : "ORDER BY id ASC");

    $rows = $wpdb->get_results("SELECT * FROM `$table` $where $order", ARRAY_A);
    if ( empty($rows) ) return array();

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

    $out = array();
    foreach ( $rows as $r ) {
      $id = isset($r['id']) ? (int)$r['id'] : 0;
      if ( ! $id ) continue;

      $name_ar = $name_ar_col ? ($r[$name_ar_col] ?? '') : '';
      $name_en = $name_en_col ? ($r[$name_en_col] ?? '') : '';

      $label = $is_ar ? ($name_ar ?: $name_en) : ($name_en ?: $name_ar);
      if ( empty($label) ) $label = 'ID ' . $id;

      $cats = array();
      if ( $cat_col ) {
        $cats_raw = $r[$cat_col] ?? '';
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
      }

      $cats = array_values(array_filter(array_unique($cats)));
      $out[(string)$id] = array('label' => $label, 'categories' => $cats);
    }

    return $out;
  }
}
""".strip("\n")

    new_src = src[:m.start()] + repl + src[m.end():]
    b = backup(PHP)
    write(PHP, new_src)
    print("[OK] Patched:", PHP)
    print("[OK] Backup :", b)
    print("[OK] Replaced property types reader with robust DB introspection.")
