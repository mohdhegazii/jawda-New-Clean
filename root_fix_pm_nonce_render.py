#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re, sys
from pathlib import Path
from datetime import datetime

THEME_ROOT = Path.cwd()
PM_METABOX = THEME_ROOT / "app/inc/admin/metaboxes/project-property-models.php"
SMOKETEST  = THEME_ROOT / "pm_ajax_smoketest.py"

def die(msg: str) -> None:
    print("ERROR:", msg, file=sys.stderr)
    sys.exit(1)

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(p.read_text(encoding="utf-8", errors="ignore"), encoding="utf-8")
    return b

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="ignore")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def ensure_nonce_field_in_metabox(src: str) -> tuple[str, bool]:
    """
    Ensure wp_nonce_field('hegzz_pm_ajax','hegzz_pm_ajax_nonce') exists inside render().
    We'll inject it near the top of render output.
    """
    if "hegzz_pm_ajax_nonce" in src and "wp_nonce_field('hegzz_pm_ajax'" in src:
        return src, False

    # Try to locate render() method and first echo/print area.
    # We'll insert right after opening of render() and before HTML.
    m = re.search(r"(function\s+render\s*\(\s*\$post\s*\)\s*\{\s*)", src)
    if not m:
        # maybe it's public static function render($post)
        m = re.search(r"(public\s+static\s+function\s+render\s*\(\s*\$post\s*\)\s*\{\s*)", src)
    if not m:
        return src, False

    insert = (
        "    // Nonce for PM AJAX (read by JS + verified by check_ajax_referer)\n"
        "    echo '<input type=\"hidden\" id=\"hegzz_pm_ajax_nonce\" name=\"hegzz_pm_ajax_nonce\" value=\"' . esc_attr( wp_create_nonce('hegzz_pm_ajax') ) . '\">';\n"
    )
    out = src[:m.end(1)] + insert + src[m.end(1):]
    return out, True

def force_js_nonce_read_from_hidden(src: str) -> tuple[str, bool]:
    """
    Replace/ensure JS uses nonce from hidden input:
      const nonce = (document.getElementById('hegzz_pm_ajax_nonce')||{}).value || '';
    and passes nonce in ajaxPost.
    """
    changed = False

    # If code had PM_NONCE assignment from php echo, neutralize it to read hidden input
    # Common patterns:
    #   const PM_NONCE = '...';
    #   var PM_NONCE = '...';
    out = re.sub(
        r"(const|var)\s+PM_NONCE\s*=\s*'[^']*'\s*;",
        r"const PM_NONCE = (document.getElementById('hegzz_pm_ajax_nonce')||{}).value || '';",
        src
    )
    if out != src:
        changed = True
        src = out

    # If no PM_NONCE at all, ensure we define it before ajaxPost usage
    if "PM_NONCE" not in src:
        # inject near the beginning of the <script> block if exists
        m = re.search(r"(<script[^>]*>\s*)", src)
        if m:
            inject = "const PM_NONCE = (document.getElementById('hegzz_pm_ajax_nonce')||{}).value || '';\n"
            src = src[:m.end(1)] + inject + src[m.end(1):]
            changed = True

    # Ensure ajaxPost appends nonce if missing
    # look for function ajaxPost(...) { ... body.append('action', ...) ... }
    # We'll add a safe body.append('nonce', PM_NONCE) if it's not there.
    if "body.append('nonce'" not in src and "body.append(\"nonce\"" not in src:
        m = re.search(r"(function\s+ajaxPost\s*\([^\)]*\)\s*\{.*?body\.append\(\s*['\"]action['\"]\s*,\s*data\.action\s*\)\s*;)", src, re.S)
        if m:
            add = "\n            body.append('nonce', PM_NONCE);\n"
            src = src[:m.end(1)] + add + src[m.end(1):]
            changed = True

    return src, changed

def patch_smoketest(src: str) -> tuple[str, bool]:
    """
    Update nonce extraction:
    - First try PM_NONCE assignment
    - If not found, extract from hidden input:
        id="hegzz_pm_ajax_nonce" value="..."
    """
    if "hegzz_pm_ajax_nonce" in src and "extract_nonce" in src and "id=\"hegzz_pm_ajax_nonce\"" in src:
        return src, False

    # Replace extract_nonce function entirely (simple + robust)
    pattern = r"def\s+extract_nonce\(html: str\)\s*->\s*str:\s*.*?return\s+\"\"\s*"
    repl = r'''def extract_nonce(html: str) -> str:
    # 1) try PM_NONCE assignment
    m = re.search(r"PM_NONCE\\s*=\\s*'([^']+)'", html)
    if m:
        return m.group(1).strip()

    # 2) fallback: hidden input printed by metabox
    m2 = re.search(r'id=["\\\']hegzz_pm_ajax_nonce["\\\'][^>]*value=["\\\']([^"\\\']+)["\\\']', html)
    if m2:
        return m2.group(1).strip()

    return ""
'''
    out, n = re.subn(pattern, repl, src, flags=re.S)
    if n == 0:
        return src, False
    return out, True

def main() -> None:
    if not (THEME_ROOT / "app").exists():
        die("Run from theme root (where app/ exists).")

    if not PM_METABOX.exists():
        die(f"Metabox file not found: {PM_METABOX}")

    # 1) Patch metabox: nonce field + JS reading
    metabox_src = read(PM_METABOX)
    metabox_src2, ch1 = ensure_nonce_field_in_metabox(metabox_src)
    metabox_src3, ch2 = force_js_nonce_read_from_hidden(metabox_src2)

    if ch1 or ch2:
        b = backup(PM_METABOX)
        write(PM_METABOX, metabox_src3)
        print(f"[OK] Patched metabox: {PM_METABOX}")
        print(f"     Backup: {b}")
    else:
        print(f"[OK] Metabox already patched (no change): {PM_METABOX}")

    # 2) Patch smoketest if exists
    if SMOKETEST.exists():
        st_src = read(SMOKETEST)
        st_src2, chs = patch_smoketest(st_src)
        if chs:
            b2 = backup(SMOKETEST)
            write(SMOKETEST, st_src2)
            print(f"[OK] Patched smoketest nonce extractor: {SMOKETEST}")
            print(f"     Backup: {b2}")
        else:
            print(f"[OK] Smoketest already supports hidden nonce (no change): {SMOKETEST}")
    else:
        print(f"[SKIP] Smoketest not found at: {SMOKETEST}")

    print("\nNow run:")
    print("  php -l app/inc/admin/metaboxes/project-property-models.php")
    print("  # then open project edit screen and run smoketest again")

if __name__ == "__main__":
    main()
