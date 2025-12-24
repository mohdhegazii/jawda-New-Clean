#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re
from pathlib import Path
from datetime import datetime

ROOT = Path(".").resolve()
TARGET = ROOT / "app/functions/meta_box.php"

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(read(p), encoding="utf-8")
    return b

def replace_set_html_with_hidden_only(src: str) -> str:
    # Replace ONLY the set_html content of jawda_project_total_area_js to hidden inputs only (no script)
    start = src.find("Field::make( 'html', 'jawda_project_total_area_js'")
    if start < 0:
        raise SystemExit("ERROR: Could not locate jawda_project_total_area_js field.")
    seg = src[start:start+40000]

    m = re.search(r"->set_html\('(.{20,}?)'\)\s*,", seg, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not parse set_html('...') block for jawda_project_total_area_js.")

    payload = """<div style="display:none">
  <input type="hidden" name="aqarand_area_m2" id="aqarand_area_m2" value="" />
  <input type="hidden" name="aqarand_area_acres" id="aqarand_area_acres" value="" />
</div>""".replace("'", "\\'")

    new_seg = seg[:m.start(1)] + payload + seg[m.end(1):]
    return src[:start] + new_seg + src[start+len(seg):]

def ensure_enqueue_hook(src: str) -> str:
    if "aqarand_admin_enqueue_projects_area_converter" in src:
        return src

    hook = r"""
/**
 * Admin: Projects area converter JS (Total area + Unit).
 * Ensures the JS runs reliably inside wp-admin (Carbon Fields UI may not execute inline scripts).
 */
if ( ! function_exists('aqarand_admin_enqueue_projects_area_converter') ) {
  function aqarand_admin_enqueue_projects_area_converter($hook) {
    if ( ! in_array($hook, array('post.php', 'post-new.php'), true) ) {
      return;
    }

    if ( function_exists('get_current_screen') ) {
      $screen = get_current_screen();
      if ( empty($screen) || empty($screen->post_type) || $screen->post_type !== 'projects' ) {
        return;
      }
    } else {
      return;
    }

    $handle = 'aqarand-projects-area-converter';
    $src = get_template_directory_uri() . '/assets/js/admin/projects-area.js';
    wp_register_script($handle, $src, array(), '1.0.0', true);
    wp_enqueue_script($handle);
  }
  add_action('admin_enqueue_scripts', 'aqarand_admin_enqueue_projects_area_converter', 20);
}
""".strip("\n") + "\n"

    return src.rstrip() + "\n\n" + hook

def main():
    src = read(TARGET)
    src2 = replace_set_html_with_hidden_only(src)
    src3 = ensure_enqueue_hook(src2)

    b = backup(TARGET)
    write(TARGET, src3)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] set_html now contains only hidden inputs; converter runs via enqueued admin JS.")

if __name__ == "__main__":
    main()
