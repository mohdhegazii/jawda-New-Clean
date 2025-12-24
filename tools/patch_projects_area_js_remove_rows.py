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

    if "removeUnitAndHtmlRows" in src:
        print("[SKIP] Removal logic already exists.")
        return

    # inject helper after qs() helper
    anchor = "function qs(sel, root) { return (root || document).querySelector(sel); }"
    pos = src.find(anchor)
    if pos < 0:
        raise SystemExit("ERROR: qs() helper not found.")

    inject = r"""

  /* === Remove unused Carbon rows (Unit label-only + cf-html hidden row) === */
  function removeUnitAndHtmlRows() {
    // 1) Remove Unit label-only row
    var unitLabel = document.querySelector('.cf-field.cf-select .cf-field__label');
    if (unitLabel && unitLabel.textContent.trim() === 'Unit') {
      var row = unitLabel.closest('.cf-field');
      if (row) row.remove();
    }

    // 2) Remove cf-html row that only contains hidden area inputs
    var htmlRows = document.querySelectorAll('.cf-field.cf-html');
    htmlRows.forEach(function(row){
      if (row.querySelector('#aqarand_area_m2') && row.querySelector('#aqarand_area_acres')) {
        row.remove();
      }
    });
  }
"""
    src = src[:pos + len(anchor)] + inject + src[pos + len(anchor):]

    # call remover inside boot() once fields are available
    if "removeUnitAndHtmlRows();" not in src:
        guard_pat = re.compile(
            r"(if\s*\(\s*!inp\s*\|\|\s*!sel\s*\|\|\s*!hidM2\s*\|\|\s*!hidA\s*\)\s*return\s+false\s*;\s*)",
            re.S
        )
        m = guard_pat.search(src)
        if not m:
            raise SystemExit("ERROR: boot() guard not found to hook remover.")
        src = src[:m.end(1)] + "\n\n    removeUnitAndHtmlRows();\n" + src[m.end(1):]

    b = backup(JS)
    write(JS, src)
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Unit label-only row + cf-html hidden row are REMOVED from DOM.")

if __name__ == "__main__":
    main()
