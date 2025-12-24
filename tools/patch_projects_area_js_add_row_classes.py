#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
from pathlib import Path
from datetime import datetime
import re

ROOT = Path(".").resolve()
JS = ROOT / "assets/js/admin/projects-area.js"

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
    src = read(JS)

    if "aqarand-area-row" in src and "aqarand-unit-row" in src:
        print("[SKIP] Row classes already present.")
        return

    # Insert helper after normalizeUnit() function
    marker = "function normalizeUnit"
    i = src.find(marker)
    if i < 0:
        raise SystemExit("ERROR: Could not find normalizeUnit() in JS file.")

    # Find end of normalizeUnit function block (naive: first '}\n\n' after it)
    m = re.search(r"function normalizeUnit\([^\)]*\)\s*\{.*?\}\s*\n", src[i:], flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not parse normalizeUnit() block.")

    insert_at = i + m.end()

    helper = r"""
  function closestCarbonField(el) {
    if (!el) return null;
    return el.closest('.carbon-field') ||
           el.closest('.carbon-field-row') ||
           el.closest('.carbon-fields--field') ||
           el.closest('.carbon-container__field') ||
           el.closest('tr') ||
           el.parentElement;
  }

  function applyRowLayoutClasses(inp, sel) {
    var rowA = closestCarbonField(inp);
    var rowU = closestCarbonField(sel);
    if (rowA) rowA.classList.add('aqarand-area-row');
    if (rowU) rowU.classList.add('aqarand-unit-row');

    // also tag a common parent to behave as flex container
    var parent = null;
    if (rowA && rowA.parentElement) parent = rowA.parentElement;
    if (rowU && rowU.parentElement) parent = parent || rowU.parentElement;
    if (parent) parent.classList.add('aqarand-area-unit-wrap');
  }
""".rstrip() + "\n\n"

    src2 = src[:insert_at] + helper + src[insert_at:]

    # Now call applyRowLayoutClasses after we obtain inp/sel in boot()
    # Find in boot() after: if (!inp || !sel || !hidM2 || !hidA) return false;
    pat = re.compile(r"(if\s*\(\s*!inp\s*\|\|\s*!sel\s*\|\|\s*!hidM2\s*\|\|\s*!hidA\s*\)\s*return\s+false\s*;\s*)")
    if not pat.search(src2):
        raise SystemExit("ERROR: Could not find boot() guard for inp/sel/hid fields.")
    src3 = pat.sub(r"\1\n    applyRowLayoutClasses(inp, sel);\n", src2, count=1)

    b = backup(JS)
    write(JS, src3)
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Added row classes + wrap class for 80/20 layout.")
