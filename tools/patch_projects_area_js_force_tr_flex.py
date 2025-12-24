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

    if "forceAreaUnitSameRow" in src:
        print("[SKIP] forceAreaUnitSameRow already present.")
        return

    # Inject helper functions near top (after FACTOR declaration)
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

    // Prefer table section parent if rows are <tr>
    var parent = commonParent(rowA, rowU) || rowA.parentElement;
    if (!parent) return;

    // If TR layout, make parent flex and rows block so they can sit side-by-side
    // This is admin-only and scoped to this screen, safe enough.
    parent.style.display = 'flex';
    parent.style.flexWrap = 'nowrap';
    parent.style.gap = '10px';
    parent.style.alignItems = 'flex-start';

    rowA.style.display = 'block';
    rowU.style.display = 'block';

    rowA.style.flex = '0 0 80%';
    rowA.style.maxWidth = '80%';

    rowU.style.flex = '0 0 20%';
    rowU.style.maxWidth = '20%';

    // Make controls fill available space
    var aInp = rowA.querySelector('input, select, textarea');
    if (aInp) { aInp.style.width = '100%'; aInp.style.maxWidth = '100%'; }

    var uSel = rowU.querySelector('select, input');
    if (uSel) { uSel.style.width = '100%'; uSel.style.maxWidth = '100%'; }
  }
""".rstrip() + "\n\n"

    src2 = src[:m.end()] + inject + src[m.end():]

    # Call forceAreaUnitSameRow inside boot() after we have inp/sel/hiddens
    guard = r"if \(!inp \|\| !sel \|\| !hidM2 \|\| !hidA\) return false;"
    if guard not in src2:
        raise SystemExit("ERROR: Could not find boot() guard line; file changed unexpectedly.")

    src3 = src2.replace(guard, guard + "\n\n    forceAreaUnitSameRow(inp, sel);", 1)

    b = backup(JS)
    write(JS, src3)
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Forced TR-flex layout 80/20 for Total area + Unit.")

if __name__ == "__main__":
    main()
