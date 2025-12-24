#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import sys, shutil, datetime, re
from pathlib import Path

THEME_ROOT = Path.cwd()

AJAX_TYPES = THEME_ROOT / "app/inc/lookups/admin/ajax/property-types-ajax.php"
AJAX_SUBS  = THEME_ROOT / "app/inc/lookups/admin/ajax/property-models-ajax.php"
PM_METABOX = THEME_ROOT / "app/inc/admin/metaboxes/project-property-models.php"

def ts() -> str:
    return datetime.datetime.now().strftime("%Y%m%d-%H%M%S")

def backup(p: Path) -> Path:
    b = p.with_suffix(p.suffix + f".bak.{ts()}")
    shutil.copy2(p, b)
    return b

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def must_exist(p: Path) -> None:
    if not p.exists():
        print(f"ERROR: Missing file: {p}", file=sys.stderr)
        sys.exit(1)

def patch_ajax_caps(src: str) -> tuple[str, bool]:
    """
    Replace manage_options with edit_posts (more appropriate for post editing screens).
    Keep behavior same otherwise.
    """
    changed = False
    # Replace only the specific can check
    pat = r"current_user_can\(\s*'manage_options'\s*\)"
    if re.search(pat, src):
        src2 = re.sub(pat, "current_user_can('edit_posts')", src)
        changed = (src2 != src)
        src = src2
    return src, changed

def ensure_metabox_nonce(src: str) -> tuple[str, bool]:
    """
    Ensure metabox JS sends nonce key exactly: nonce: <wp_create_nonce('hegzz_pm_ajax')>
    We inject:
      const PM_NONCE = '...';
    and ensure ajaxPost always includes it.
    """
    changed = False

    # 1) Ensure we have PM_NONCE constant inside the <script> block
    # We search for "var ajaxurl" or "const ajaxurl" style, and inject right after it.
    nonce_php = "const PM_NONCE = '<?php echo esc_js( wp_create_nonce('hegzz_pm_ajax') ); ?>';"

    if "PM_NONCE" not in src:
        # Try insert after ajaxurl definition (most common pattern)
        m = re.search(r"(var|const)\s+ajaxurl\s*=\s*[^;]+;\s*", src)
        if m:
            insert_at = m.end()
            src = src[:insert_at] + "\n      " + nonce_php + "\n" + src[insert_at:]
            changed = True
        else:
            # fallback: insert near the top of script by finding "<script"
            m2 = re.search(r"<script[^>]*>\s*", src)
            if m2:
                insert_at = m2.end()
                src = src[:insert_at] + "\n      " + nonce_php + "\n" + src[insert_at:]
                changed = True

    # 2) Ensure ajaxPost merges nonce into payload
    # Look for function ajaxPost(...) { ... body ... }
    # We'll try to patch a common pattern: data = data || {}; or payload = ...
    if "nonce:" not in src:
        # Replace occurrences of "var payload = data || {};" or "const payload = data || {};"
        pat_payload = r"(\b(var|const)\s+payload\s*=\s*)(data\s*\|\|\s*\{\s*\})\s*;"
        if re.search(pat_payload, src):
            src2 = re.sub(
                pat_payload,
                r"\1Object.assign({}, \3, { nonce: PM_NONCE });",
                src
            )
            if src2 != src:
                src = src2
                changed = True
        else:
            # Another common: "data = data || {};" then later used in fetch/body
            # We'll add: data = Object.assign({}, data, { nonce: PM_NONCE });
            pat_data_init = r"\bdata\s*=\s*data\s*\|\|\s*\{\s*\}\s*;"
            if re.search(pat_data_init, src):
                src2 = re.sub(
                    pat_data_init,
                    "data = data || {};\n        data = Object.assign({}, data, { nonce: PM_NONCE });",
                    src,
                    count=1
                )
                if src2 != src:
                    src = src2
                    changed = True

    # 3) As a final safety: patch the two calls to ajaxPost to include nonce if they build literal objects.
    # (Won't harm if ajaxPost already adds nonce)
    src2 = re.sub(
        r"ajaxPost\(\s*\{\s*action:\s*'hegzz_pm_get_sub_properties'\s*,\s*property_type_id:\s*typeId\s*\}\s*\)",
        "ajaxPost({ action: 'hegzz_pm_get_sub_properties', property_type_id: typeId, nonce: PM_NONCE })",
        src
    )
    if src2 != src:
        src = src2
        changed = True

    src3 = re.sub(
        r"ajaxPost\(\s*\{\s*action:\s*'hegzz_pm_get_property_types'\s*,\s*category_ids:\s*catIds\s*\}\s*\)",
        "ajaxPost({ action: 'hegzz_pm_get_property_types', category_ids: catIds, nonce: PM_NONCE })",
        src
    )
    if src3 != src:
        src = src3
        changed = True

    return src, changed

def main() -> None:
    if not (THEME_ROOT / "app").exists():
        print("ERROR: Run from theme root (where app/ exists).", file=sys.stderr)
        sys.exit(1)

    for p in [AJAX_TYPES, AJAX_SUBS, PM_METABOX]:
        must_exist(p)

    # Patch ajax caps
    for p in [AJAX_TYPES, AJAX_SUBS]:
        original = read(p)
        patched, changed = patch_ajax_caps(original)
        if changed:
            b = backup(p)
            write(p, patched)
            print(f"[OK] Patched caps: {p}")
            print(f"     Backup: {b}")
        else:
            print(f"[OK] Caps already fine (no change): {p}")

    # Patch metabox nonce
    original = read(PM_METABOX)
    patched, changed = ensure_metabox_nonce(original)
    if changed:
        b = backup(PM_METABOX)
        write(PM_METABOX, patched)
        print(f"[OK] Patched metabox nonce: {PM_METABOX}")
        print(f"     Backup: {b}")
    else:
        print(f"[OK] Metabox nonce already present (no change): {PM_METABOX}")

    print("\nDONE ✅")
    print("Run:")
    print("  php -l app/inc/admin/metaboxes/project-property-models.php")
    print("  php -l app/inc/lookups/admin/ajax/property-types-ajax.php")
    print("  php -l app/inc/lookups/admin/ajax/property-models-ajax.php")
    print("\nThen open Project edit screen and test Network calls to admin-ajax.php.")

if __name__ == "__main__":
    main()
