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

    # 1) Ensure helper exists (inject once after FACTOR line)
    if "function forceAreaUnitSameRow(" not in src:
        m = re.search(r"var\s+FACTOR\s*=\s*4200\.83\s*;\s*\n", src)
        if not m:
            raise SystemExit("ERROR: Could not find FACTOR line to insert helpers after.")

        inject = r"""
  function closestRow(el) {
    if (!el) return null;
    return el.closest('tr') ||
           el.closest('.carbon-field') ||
           el.closest('.carbon-field-row') ||
           el.closest('.carbon-fields--field') ||
           el.closest('.carbon-container__field') ||
           el.parentElement;
  }

  function commonParent(a, b) {
    if (!a || !b) return null;
    var p = a.parentElement;
    while (p) {
      if (p.contains(b)) return p;
      p = p.parentElement;
    }
    return null;
  }

  function forceAreaUnitSameRow(areaEl, unitEl) {
    var rowA = closestRow(areaEl);
    var rowU = closestRow(unitEl);
    if (!rowA || !rowU) return;

    var parent = commonParent(rowA, rowU) || rowA.parentElement;
    if (!parent) return;

    // Make parent flex (works even if it's TBODY / DIV)
    parent.style.display = 'flex';
    parent.style.flexWrap = 'nowrap';
    parent.style.gap = '10px';
    parent.style.alignItems = 'flex-start';

    // TR can't be flex items unless blockified
    rowA.style.display = 'block';
    rowU.style.display = 'block';

    rowA.style.flex = '0 0 80%';
    rowA.style.maxWidth = '80%';

    rowU.style.flex = '0 0 20%';
    rowU.style.maxWidth = '20%';

    // Controls full width
    var aCtrl = rowA.querySelector('input, select, textarea');
    if (aCtrl) { aCtrl.style.width = '100%'; aCtrl.style.maxWidth = '100%'; }

    var uCtrl = rowU.querySelector('select, input, textarea');
    if (uCtrl) { uCtrl.style.width = '100%'; uCtrl.style.maxWidth = '100%'; }
  }

""".lstrip("\n")

        src = src[:m.end()] + inject + src[m.end():]

    # 2) Inject call after the boot() guard if not already there
    if "forceAreaUnitSameRow(inp, sel);" not in src:
        # Match any formatting: if (!inp || !sel || !hidM2 || !hidA) return false;
        guard_pat = re.compile(
            r"(if\s*\(\s*!inp\s*\|\|\s*!sel\s*\|\|\s*!hidM2\s*\|\|\s*!hidA\s*\)\s*return\s+false\s*;\s*)",
            re.S
        )
        m = guard_pat.search(src)
        if not m:
            raise SystemExit("ERROR: Could not locate boot() guard for inp/sel/hidM2/hidA to inject after.")
        insert = m.group(1) + "\n\n    forceAreaUnitSameRow(inp, sel);\n"
        src = src[:m.start(1)] + insert + src[m.end(1):]

    b = backup(JS)
    write(JS, src)
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Injected forceAreaUnitSameRow() + call (80/20 same-row enforced).")

if __name__ == "__main__":
    main()
