#!/usr/bin/env python3
from __future__ import annotations

import re, sys, shutil
from pathlib import Path
from datetime import datetime

ROOT = Path.cwd()

META_BOX = ROOT / "app/functions/meta_box.php"
PM_METABOX = ROOT / "app/inc/admin/metaboxes/project-property-models.php"

def ts() -> str:
    return datetime.now().strftime("%Y%m%d-%H%M%S")

def die(msg: str, code: int = 1) -> None:
    print("ERROR:", msg, file=sys.stderr)
    sys.exit(code)

def backup(p: Path) -> Path:
    b = p.with_suffix(p.suffix + f".bak.{ts()}")
    shutil.copy2(p, b)
    return b

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(s, encoding="utf-8")

def remove_pm_requires_from_meta_box(src: str) -> tuple[str, bool]:
    """
    يشيل أي require_once للـ project-property-models.php
    ويشيل بلوك AJAX includes اللي اتضاف للـ PM لو موجود (أي بلوك فيه paths للـ property-types-ajax.php / property-models-ajax.php)
    """
    changed = False
    original = src

    # 1) Remove any require_once that references project-property-models.php
    pat_req = re.compile(r"^[ \t]*require_once\s+.*project-property-models\.php.*?;\s*$", re.MULTILINE)
    src2, n1 = pat_req.subn("", src)
    if n1:
        changed = True
        src = src2

    # 2) Remove any custom block we injected that includes PM AJAX endpoints
    #    (we remove any block containing BOTH of these filenames within ~2000 chars)
    pat_block = re.compile(
        r"(?:/\*.*?\*/\s*)?"
        r"(?s:.*?app/inc/lookups/admin/ajax/property-types-ajax\.php.*?app/inc/lookups/admin/ajax/property-models-ajax\.php.*?)",
        re.IGNORECASE
    )

    # safer: remove only inside PHP admin_include blocks; so we try to remove small include chunk lines
    pat_lines = re.compile(
        r"^[ \t]*(?:require_once|include_once)\s+.*app/inc/lookups/admin/ajax/(?:property-types-ajax|property-models-ajax)\.php.*?;\s*$",
        re.MULTILINE
    )
    src3, n2 = pat_lines.subn("", src)
    if n2:
        changed = True
        src = src3

    # 3) Clean excessive blank lines
    src = re.sub(r"\n{3,}", "\n\n", src).strip() + "\n"

    return src, changed and (src != original)

def add_kill_switch_to_pm_metabox(src: str) -> tuple[str, bool]:
    """
    لو الملف لسه بيتحمّل من أي مكان تاني، نضيف kill switch فوق.
    """
    if "AQARAND_DISABLE_PM_METABOX" in src:
        return src, False

    ins = (
        "<?php\n"
        "if (!defined('ABSPATH')) exit;\n\n"
        "// Kill switch: set define('AQARAND_DISABLE_PM_METABOX', true); in wp-config.php if needed.\n"
        "if (defined('AQARAND_DISABLE_PM_METABOX') && AQARAND_DISABLE_PM_METABOX) { return; }\n\n"
    )
    # replace first php tag block if exists
    if src.lstrip().startswith("<?php"):
        src2 = re.sub(r"^\s*<\?php\s*", ins, src, count=1, flags=re.DOTALL)
        return src2, True
    else:
        return ins + src, True

def quick_grep_admin_page_hints() -> None:
    """
    مجرد تلميحات لتست سريع: هل الـ metabox الجديد لسه referenced؟
    """
    hints = []
    if META_BOX.exists():
        s = read(META_BOX)
        if "project-property-models.php" in s:
            hints.append("meta_box.php still references project-property-models.php")
        if "property-types-ajax.php" in s or "property-models-ajax.php" in s:
            hints.append("meta_box.php still includes PM AJAX php files")

    print("\n[HINTS]")
    if hints:
        for h in hints:
            print(" -", h)
    else:
        print(" - Looks clean: no PM requires detected in meta_box.php")

def main() -> None:
    if not (ROOT / "app").exists():
        die("Run this from theme root (where app/ exists).")

    if not META_BOX.exists():
        die(f"Missing: {META_BOX}")

    print("[1] Patch meta_box.php to remove PM metabox + PM AJAX includes...")
    src = read(META_BOX)
    patched, changed = remove_pm_requires_from_meta_box(src)
    if changed:
        b = backup(META_BOX)
        write(META_BOX, patched)
        print("    [OK] Patched:", META_BOX)
        print("    [OK] Backup :", b)
    else:
        print("    [OK] meta_box.php already clean (no change).")

    # Optional kill switch in PM metabox file (in case something else loads it)
    if PM_METABOX.exists():
        print("\n[2] Add kill-switch guard to project-property-models.php (safe, optional)...")
        pms = read(PM_METABOX)
        pms2, ch2 = add_kill_switch_to_pm_metabox(pms)
        if ch2:
            b2 = backup(PM_METABOX)
            write(PM_METABOX, pms2)
            print("    [OK] Patched:", PM_METABOX)
            print("    [OK] Backup :", b2)
        else:
            print("    [OK] Kill switch already present (no change).")
    else:
        print("\n[2] PM metabox file not found (skip).")

    quick_grep_admin_page_hints()

    print("\nDONE ✅")
    print("\nNow run:")
    print("  php -l app/functions/meta_box.php")
    if PM_METABOX.exists():
        print("  php -l app/inc/admin/metaboxes/project-property-models.php")

    print("\nThen HARD refresh wp-admin Project edit page and test the container.")
    print("If you still see empty Project Details, open DevTools Console and tell me the first JS error line.")

if __name__ == "__main__":
    main()
