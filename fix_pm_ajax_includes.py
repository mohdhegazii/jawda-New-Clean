#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import sys
import shutil
import datetime
from pathlib import Path

THEME_ROOT = Path.cwd()

META_BOX_PHP = THEME_ROOT / "app/functions/meta_box.php"

AJAX_TYPES = THEME_ROOT / "app/inc/lookups/admin/ajax/property-types-ajax.php"
AJAX_SUBS  = THEME_ROOT / "app/inc/lookups/admin/ajax/property-models-ajax.php"

MARK_BEGIN = "// === Aqarand: PM AJAX Endpoints (AUTO) BEGIN ==="
MARK_END   = "// === Aqarand: PM AJAX Endpoints (AUTO) END ==="

def ts() -> str:
    return datetime.datetime.now().strftime("%Y%m%d-%H%M%S")

def backup_file(p: Path) -> Path:
    b = p.with_suffix(p.suffix + f".bak.{ts()}")
    shutil.copy2(p, b)
    return b

def read_text(p: Path) -> str:
    return p.read_text(encoding="utf-8")

def write_text(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def upsert_block(src: str, begin: str, end: str, block: str) -> str:
    if begin in src and end in src:
        pre = src.split(begin, 1)[0]
        post = src.split(end, 1)[1]
        return pre.rstrip("\n") + "\n\n" + block.rstrip("\n") + "\n\n" + post.lstrip("\n")
    return src.rstrip("\n") + "\n\n" + block.rstrip("\n") + "\n"

def main() -> None:
    # sanity
    if not (THEME_ROOT / "app").exists():
        print("ERROR: Run this from theme root (where app/ exists).", file=sys.stderr)
        sys.exit(1)

    missing = []
    if not META_BOX_PHP.exists():
        missing.append(str(META_BOX_PHP))
    if not AJAX_TYPES.exists():
        missing.append(str(AJAX_TYPES))
    if not AJAX_SUBS.exists():
        missing.append(str(AJAX_SUBS))

    if missing:
        print("ERROR: Missing required file(s):", file=sys.stderr)
        for m in missing:
            print(" -", m, file=sys.stderr)
        sys.exit(1)

    src = read_text(META_BOX_PHP)

    # robust require in admin
    block = f"""{MARK_BEGIN}
if ( is_admin() ) {{
    // Ensure PM AJAX endpoints are registered on ALL admin screens (including Project edit).
    $pm_ajax_types = get_template_directory() . '/app/inc/lookups/admin/ajax/property-types-ajax.php';
    $pm_ajax_subs  = get_template_directory() . '/app/inc/lookups/admin/ajax/property-models-ajax.php';

    if ( file_exists($pm_ajax_types) ) {{
        require_once $pm_ajax_types;
    }}
    if ( file_exists($pm_ajax_subs) ) {{
        require_once $pm_ajax_subs;
    }}
}}
{MARK_END}
"""

    new_src = upsert_block(src, MARK_BEGIN, MARK_END, block)

    if new_src == src:
        print("[OK] meta_box.php already contains PM AJAX endpoints block (no changes).")
        return

    bak = backup_file(META_BOX_PHP)
    write_text(META_BOX_PHP, new_src)

    print(f"[OK] Patched: {META_BOX_PHP}")
    print(f"[OK] Backup : {bak}")
    print("\nNext:")
    print("1) php -l app/functions/meta_box.php")
    print("2) Open Project edit screen and test the selects again.")
    print("3) If still not loading, check Network -> admin-ajax.php response.")

if __name__ == "__main__":
    main()
