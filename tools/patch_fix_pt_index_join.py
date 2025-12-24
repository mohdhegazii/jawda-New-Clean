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

    # Replace the whole function block (safe, deterministic)
    pat = re.compile(
        r"if\s*\(\s*!\s*function_exists\(\s*'aqarand_projects_get_property_types_index'\s*\)\s*\)\s*\{\s*function\s+aqarand_projects_get_property_types_index\s*\(\s*\)\s*\{.*?\}\s*\}",
        re.S
    )
    m = pat.search(src)
    if not m:
        raise SystemExit("ERROR: Could not locate aqarand_projects_get_property_types_index() block to replace.")

    repl = r"""
if ( ! function_exists('aqarand_projects_get_property_types_index') ) {
  function aqarand_projects_get_property_types_index() {
    global $wpdb;

    $T_types = $wpdb->prefix . 'hegzz_property_types';
    $T_pivot = $wpdb->prefix . 'hegzz_property_type_categories';

    // Ensure tables exist
    if ( $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $T_types)) !== $T_types ) return array();
    if ( $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $T_pivot)) !== $T_pivot ) return array();

    // Types columns
    $tcols = $wpdb->get_col("DESCRIBE `$T_types`", 0);

    $name_ar = in_array('name_ar', $tcols, true) ? 'name_ar' : '';
    $name_en = in_array('name_en', $tcols, true) ? 'name_en' : (in_array('name',$tcols,true) ? 'name' : '');

    $has_active = in_array('is_active', $tcols, true);

    // Load types (active only if supported)
    $where = $has_active ? "WHERE is_active=1" : "";
    $order = in_array('sort_order', $tcols, true) ? "ORDER BY sort_order ASC, id ASC" : "ORDER BY id ASC";
    $types = $wpdb->get_results("SELECT * FROM `$T_types` $where $order", ARRAY_A);
    if ( empty($types) ) return array();

    // Pivot columns
    $pcols = $wpdb->get_col("DESCRIBE `$T_pivot`", 0);
    if ( !in_array('property_type_id',$pcols,true) || !in_array('category_id',$pcols,true) ) return array();

    $p_has_active = in_array('is_active', $pcols, true);
    $p_where = $p_has_active ? "WHERE is_active=1" : "";

    // Build map type_id -> [cat_ids...]
    $pivot_map = array();
    $piv = $wpdb->get_results("SELECT property_type_id, category_id FROM `$T_pivot` $p_where", ARRAY_A);
    foreach ( (array)$piv as $r ) {
      $pt = (int)($r['property_type_id'] ?? 0);
      $cat = (int)($r['category_id'] ?? 0);
      if (!$pt || !$cat) continue;
      if ( !isset($pivot_map[$pt]) ) $pivot_map[$pt] = array();
      $pivot_map[$pt][] = $cat;
    }
    foreach ( $pivot_map as $pt => $arr ) {
      $pivot_map[$pt] = array_values(array_unique(array_map('intval',$arr)));
    }

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

    $out = array();
    foreach ( $types as $t ) {
      $id = (int)($t['id'] ?? 0);
      if (!$id) continue;

      $ar = $name_ar ? ($t[$name_ar] ?? '') : '';
      $en = $name_en ? ($t[$name_en] ?? '') : '';

      $label = $is_ar ? ($ar ?: $en) : ($en ?: $ar);
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
    print("[OK] aqarand_projects_get_property_types_index() now reads pivot explicitly (property_type_id/category_id).")
