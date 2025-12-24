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

    # 1) Inject wrapper-based layout helper (only affects the two rows)
    if "function wrapAreaUnitRows(" not in src:
        # put it after common helpers near top; easiest anchor: after qs() function
        anchor = "function qs(sel, root) { return (root || document).querySelector(sel); }"
        pos = src.find(anchor)
        if pos < 0:
            raise SystemExit("ERROR: Could not find qs() helper to anchor injection.")
        insert_at = pos + len(anchor)

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

  function wrapAreaUnitRows(areaEl, unitEl) {
    var rowA = closestRow(areaEl);
    var rowU = closestRow(unitEl);
    if (!rowA || !rowU) return;

    // avoid double-wrapping
    var existing = rowA.closest('.aqarand-areaunit-wrap') || rowU.closest('.aqarand-areaunit-wrap');
    if (existing) {
      // still enforce widths inside wrapper
      existing.style.display = 'flex';
      existing.style.flexWrap = 'nowrap';
      existing.style.gap = '10px';
      existing.style.alignItems = 'flex-start';

      rowA.style.flex = '0 0 80%';
      rowA.style.maxWidth = '80%';
      rowU.style.flex = '0 0 20%';
      rowU.style.maxWidth = '20%';
      rowA.style.display = 'block';
      rowU.style.display = 'block';
      return;
    }

    var parent = rowA.parentElement;
    if (!parent) return;

    // create wrapper just for the 2 rows
    var wrap = document.createElement('div');
    wrap.className = 'aqarand-areaunit-wrap';
    wrap.style.display = 'flex';
    wrap.style.flexWrap = 'nowrap';
    wrap.style.gap = '10px';
    wrap.style.alignItems = 'flex-start';

    // insert wrapper before rowA, then move rowA + rowU into it
    parent.insertBefore(wrap, rowA);
    wrap.appendChild(rowA);

    // if rowU was before rowA originally, moving rowA changed DOM; just append rowU now
    wrap.appendChild(rowU);

    // blockify the rows (works for TR or div-like rows)
    rowA.style.display = 'block';
    rowU.style.display = 'block';

    rowA.style.flex = '0 0 80%';
    rowA.style.maxWidth = '80%';

    rowU.style.flex = '0 0 20%';
    rowU.style.maxWidth = '20%';

    // controls full width inside columns
    var aCtrl = rowA.querySelector('input, select, textarea');
    if (aCtrl) { aCtrl.style.width = '100%'; aCtrl.style.maxWidth = '100%'; }

    var uCtrl = rowU.querySelector('select, input, textarea');
    if (uCtrl) { uCtrl.style.width = '100%'; uCtrl.style.maxWidth = '100%'; }
  }
"""
        src = src[:insert_at] + inject + src[insert_at:]

    # 2) Ensure boot() calls wrapAreaUnitRows(inp, sel) after inp/sel are ready
    # We add call after the boot guard, regardless of formatting.
    if "wrapAreaUnitRows(inp, sel);" not in src:
        guard_pat = re.compile(
            r"(if\s*\(\s*!inp\s*\|\|\s*!sel\s*\|\|\s*!hidM2\s*\|\|\s*!hidA\s*\)\s*return\s+false\s*;\s*)",
            re.S
        )
        m = guard_pat.search(src)
        if not m:
            raise SystemExit("ERROR: Could not locate boot() guard for inp/sel/hidM2/hidA.")
        src = src[:m.end(1)] + "\n\n    wrapAreaUnitRows(inp, sel);\n" + src[m.end(1):]

    # 3) Neutralize any previous parent-flex function calls if present
    # If a previous helper exists and is called, we keep it but it will no longer affect parent because we won't call it.
    src = src.replace("forceAreaUnitSameRow(inp, sel);", "// forceAreaUnitSameRow(inp, sel); // disabled (wrap only area+unit)")
    src = src.replace("applyRowLayoutClasses(inp, sel);", "// applyRowLayoutClasses(inp, sel); // disabled (wrap only area+unit)")

    b = backup(JS)
    write(JS, src)
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Now only Total area + Unit are wrapped on one line (80/20). Developer stays on its own row.")

if __name__ == "__main__":
    main()
