#!/usr/bin/env python3
import re
from pathlib import Path
from datetime import datetime

TARGET = Path("app/functions/meta_box.php")

def backup(p: Path):
    b = p.with_suffix(p.suffix + f".bak.{datetime.now().strftime('%Y%m%d-%H%M%S')}")
    b.write_text(p.read_text(encoding="utf-8"), encoding="utf-8")
    return b

def main():
    src = TARGET.read_text(encoding="utf-8")

    # locate function block
    m = re.search(r"function\s+aqarand_projects_get_property_types_index\s*\(\)\s*\{", src)
    if not m:
        raise SystemExit("ERROR: function aqarand_projects_get_property_types_index() not found")

    start = m.start()
    # find matching closing brace of this function
    i = m.end()
    depth = 0
    in_str = None
    esc = False
    while i < len(src):
        ch = src[i]
        if in_str:
            if esc:
                esc = False
            elif ch == "\\":
                esc = True
            elif ch == in_str:
                in_str = None
        else:
            if ch in ("'", '"'):
                in_str = ch
            elif ch == "{":
                depth += 1
            elif ch == "}":
                if depth == 0:
                    end = i + 1
                    break
                depth -= 1
        i += 1
    else:
        raise SystemExit("ERROR: could not find end of function body")

    repl = r"""
  function aqarand_projects_get_property_types_index() {
    global $wpdb;

    $t_types = $wpdb->prefix . 'hegzz_property_types';
    $t_pivot = $wpdb->prefix . 'hegzz_property_type_categories';

    // ensure base tables exist
    $ex1 = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t_types));
    if ($ex1 !== $t_types) return array();

    // read types
    $cols = $wpdb->get_col("DESCRIBE `$t_types`", 0);
    $has_active = in_array('is_active', $cols, true);
    $has_sort   = in_array('sort_order', $cols, true) || in_array('sort', $cols, true);
    $sort_col   = in_array('sort_order', $cols, true) ? 'sort_order' : (in_array('sort', $cols, true) ? 'sort' : '');

    $where = $has_active ? "WHERE is_active=1" : "";
    $order = ($has_sort && $sort_col) ? "ORDER BY `$sort_col` ASC, id ASC" : "ORDER BY id ASC";

    $rows = $wpdb->get_results("SELECT * FROM `$t_types` $where $order", ARRAY_A);
    if (empty($rows)) return array();

    // read pivot (if exists)
    $pivot_map = array(); // pt_id => [cat_id, cat_id...]
    $ex2 = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t_pivot));
    if ($ex2 === $t_pivot) {
      $pcols = $wpdb->get_col("DESCRIBE `$t_pivot`", 0);
      $pt_col  = in_array('property_type_id', $pcols, true) ? 'property_type_id' : (in_array('type_id', $pcols, true) ? 'type_id' : '');
      $cat_col = in_array('category_id', $pcols, true) ? 'category_id' : (in_array('cat_id', $pcols, true) ? 'cat_id' : '');
      $p_active = in_array('is_active', $pcols, true);

      if ($pt_col && $cat_col) {
        $pw = $p_active ? "WHERE is_active=1" : "";
        $prows = $wpdb->get_results("SELECT `$pt_col` as pt_id, `$cat_col` as cat_id FROM `$t_pivot` $pw", ARRAY_A);
        foreach ($prows as $pr) {
          $pt = (int)($pr['pt_id'] ?? 0);
          $ct = (int)($pr['cat_id'] ?? 0);
          if (!$pt || !$ct) continue;
          if (!isset($pivot_map[$pt])) $pivot_map[$pt] = array();
          $pivot_map[$pt][] = $ct;
        }
      }
    }

    $is_ar = function_exists('aqarand_is_arabic_locale') ? aqarand_is_arabic_locale() : false;

    $out = array();
    foreach ($rows as $r) {
      $id = isset($r['id']) ? (int)$r['id'] : 0;
      if (!$id) continue;

      $name_ar = $r['name_ar'] ?? '';
      $name_en = $r['name_en'] ?? ($r['name'] ?? '');

      $label = $is_ar ? ($name_ar ?: $name_en) : ($name_en ?: $name_ar);
      if (empty($label)) $label = 'ID ' . $id;

      $cats = $pivot_map[$id] ?? array();
      $cats = array_values(array_filter(array_unique(array_map('intval', $cats))));

      $out[(string)$id] = array(
        'label' => $label,
        'categories' => $cats,
      );
    }

    return $out;
  }
""".strip("\n")

    new_src = src[:start] + repl + src[end:]
    b = backup(TARGET)
    TARGET.write_text(new_src, encoding="utf-8")
    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)

if __name__ == "__main__":
    main()
