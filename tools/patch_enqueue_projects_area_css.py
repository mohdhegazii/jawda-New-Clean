#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
from pathlib import Path
from datetime import datetime
import re

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

def main():
    src = read(TARGET)

    # We already have aqarand_admin_enqueue_projects_area_converter(). We'll extend it to enqueue CSS too.
    func_pat = re.compile(r"(function\s+aqarand_admin_enqueue_projects_area_converter\s*\([^)]*\)\s*\{.*?\n\})", re.S)
    m = func_pat.search(src)
    if not m:
        raise SystemExit("ERROR: Could not find aqarand_admin_enqueue_projects_area_converter() in meta_box.php")

    block = m.group(1)
    if "projects-area.css" in block:
        print("[SKIP] CSS already enqueued.")
        return

    # Insert wp_enqueue_style right after wp_enqueue_script
    insert_pat = re.compile(r"(wp_enqueue_script\(\$handle\);\s*)", re.S)
    if not insert_pat.search(block):
        raise SystemExit("ERROR: Could not find wp_enqueue_script($handle) inside enqueue function to attach CSS.")

    css_lines = r"""
    $style_handle = 'aqarand-projects-area-converter-css';
    $style_src = get_template_directory_uri() . '/assets/css/admin/projects-area.css';
    wp_register_style($style_handle, $style_src, array(), '1.0.0');
    wp_enqueue_style($style_handle);
"""

    new_block = insert_pat.sub(r"\1" + css_lines, block, count=1)
    new_src = src[:m.start(1)] + new_block + src[m.end(1):]

    b = backup(TARGET)
    write(TARGET, new_src)
    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Enqueued admin CSS for 80/20 layout.")

if __name__ == "__main__":
    main()
