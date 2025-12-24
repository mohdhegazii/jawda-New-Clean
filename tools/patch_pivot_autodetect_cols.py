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

    pat = re.compile(
        r"if\s*\(\s*!\s*function_exists\(\s*'aqarand_projects_get_property_types_index'\s*\)\s*\)\s*\{\s*function\s+aqarand_projects_get_property_types_index\s*\(\s*\)\s*\{.*?\}\s*\}",
        re.S
    )
    m = pat.search(src)
    if not m:
        raise SystemExit("ERROR: Could not locate aqarand_projects_get_property_types_index() block.")

    repl = r"""
if ( ! function_exists('aqarand_projects_get_property_types_index') ) {
  function aqarand_projects_get_property_types_index() {
    global $wpdb;

    $T_types = $wpdb->prefix . 'hegzz_property_types';
    $T_pivot = $wpdb->prefix . 'hegzz_property_type_categories';

    $exists_types = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $T_types) );
    if ( $exists_types !== $T_types ) return array();

    $cols = $wpdb->get_col("DESCRIBE `$T_types`", 0);

    $name_ar_col = in_array('name_ar',$cols,true) ? 'name_ar' : (in_array('ar_name',$cols,true) ? 'ar_name' : '');
    $name_en_col = in_array('name_en',$cols,true) ? 'name_en' : (in_array('name',$cols,true) ? 'name' : (in_array('title',$cols,true) ? 'title' : ''));

    $has_is_active = in_array('is_active',$cols,true);
    $where = $has_is_active ? "WHERE is_active=1" : "";
    $order = in_array('sort_order',$cols,true) ? "ORDER BY sort_order ASC, id ASC" : "ORDER BY id ASC";

    $rows = $wpdb->get_results("SELECT * FROM `$T_types` $where $order", ARRAY_A);
    if ( empty($rows) ) return array();

    // pivot map: detect column names dynamically
    $pivot_map = array();
    $exists_pivot = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $T_pivot) );
    if ( $exists_pivot === $T_pivot ) {
      $pcols = $wpdb->get_col("DESCRIBE `$T_pivot`", 0);

      $pt_col = '';
      foreach ( array('property_type_id','type_id','pt_id') as $c ) { if ( in_array($c,$pcols,true) ) { $pt_col = $c; break; } }

      $cat_col = '';
      foreach ( array('category_id','cat_id') as $c ) { if ( in_array($c,$pcols,true) ) { $cat_col = $c; break; } }

      if ( $pt_col && $cat_col ) {
        $piv = $wpdb->get_results("SELECT `$pt_col` AS pt, `$cat_col` AS cat FROM `$T_pivot`", ARRAY_A);
        foreach ( (array)$piv as $r ) {
          $pt = (int)($r['pt'] ?? 0);
          $cat = (int)($r['cat'] ?? 0);
          if (!$pt || !$cat) continue;
          if ( !isset($pivot_map[$pt]) ) $pivot_map[$pt] = array();
          $pivot_map[$pt][] = $cat;
        }
        foreach ($pivot_map as $pt => $arr) {
          $pivot_map[$pt] = array_values(array_unique(array_map('intval',$arr)));
        }
      }
    }

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

    $out = array();
    foreach ( $rows as $r ) {
      $id = isset($r['id']) ? (int)$r['id'] : 0;
      if ( ! $id ) continue;

      $name_ar = $name_ar_col ? ($r[$name_ar_col] ?? '') : '';
      $name_en = $name_en_col ? ($r[$name_en_col] ?? '') : '';

      $label = $is_ar ? ($name_ar ?: $name_en) : ($name_en ?: $name_ar);
      if ( empty($label) ) $label = 'ID ' . $id;

      $cats = $pivot_map[$id] ?? array();

      $out[(string)$id] = array(
        'label' => $label,
        'categories' => array_values(array_filter(array_unique(array_map('intval',$cats))))
      );
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
    print("[OK] Pivot columns autodetect added.")
