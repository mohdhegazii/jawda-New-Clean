#!/usr/bin/env python3
from pathlib import Path
from datetime import datetime
import re

JS = Path("assets/js/admin/projects-area.js")

def backup(p: Path):
    b = p.with_suffix(p.suffix + f".bak.{datetime.now().strftime('%Y%m%d-%H%M%S')}")
    b.write_text(p.read_text(encoding="utf-8"), encoding="utf-8")
    return b

INJECT_FN = r"""
// === UI: Put Total area + Unit in one row (80/20) + hide Unit label/row + hide HTML row ===
function moveUnitIntoAreaRow(inp, sel){
  try{
    if(!inp || !sel) return;

    var rowA = inp.closest('.carbon-field, .carbon-field-row, .carbon-fields--field, tr') || inp.parentElement;
    var rowU = sel.closest('.carbon-field, .carbon-field-row, .carbon-fields--field, tr') || sel.parentElement;
    if(!rowA || !rowU) return;

    // area body
    var bodyA = rowA.querySelector('.carbon-field__body, .cf-field__body, td') || rowA;

    // build wrapper for 80/20
    var wrap = bodyA.querySelector('.aqarand-area-unit-inline');
    if(!wrap){
      wrap = document.createElement('div');
      wrap.className = 'aqarand-area-unit-inline';
      wrap.style.display = 'flex';
      wrap.style.gap = '10px';
      wrap.style.alignItems = 'flex-end';
      wrap.style.width = '100%';
      // move existing controls into wrap (only control area, not labels)
      // keep bodyA clean
      while(bodyA.firstChild){ wrap.appendChild(bodyA.firstChild); }
      bodyA.appendChild(wrap);
    }

    // ensure input takes 80%
    inp.style.flex = '1 1 80%';
    inp.style.width = '100%';
    inp.style.maxWidth = '100%';

    // move select control from Unit row into wrap
    var selControl = sel.closest('.carbon-field__control, .cf-field__body, td, div') || sel.parentElement;
    if(selControl && selControl.parentElement !== wrap){
      // style select 20%
      sel.style.flex = '0 0 20%';
      sel.style.width = '100%';
      sel.style.maxWidth = '100%';
      wrap.appendChild(selControl);
    }

    // hide the Unit row completely (label + line)
    rowU.style.display = 'none';

    // hide the HTML field row (jawda_project_total_area_js)
    // try multiple selectors because Carbon markup differs by version
    var jsField =
      document.querySelector('[data-carbon-field-id="jawda_project_total_area_js"]') ||
      document.querySelector('[name="jawda_project_total_area_js"]') ||
      document.getElementById('jawda_project_total_area_js');

    if(jsField){
      var jsRow = jsField.closest('.carbon-field, .carbon-field-row, .carbon-fields--field, tr');
      if(jsRow) jsRow.style.display = 'none';
    }
  }catch(e){}
}
"""

def main():
    src = JS.read_text(encoding="utf-8")
    if "function moveUnitIntoAreaRow" in src:
        print("[SKIP] moveUnitIntoAreaRow already exists.")
        return

    b = backup(JS)

    # inject function before the final boot wiring OR at end
    # We'll append safely.
    src2 = src.rstrip() + "\n\n" + INJECT_FN.strip() + "\n"

    # find a good call site: after the guard line in boot:
    # if (!inp || !sel || !hidM2 || !hidA) return false;
    pat = re.compile(r"(if\s*\(\s*!inp\s*\|\|\s*!sel\s*\|\|\s*!hidM2\s*\|\|\s*!hidA\s*\)\s*return\s+false\s*;\s*)")
    m = pat.search(src2)
    if not m:
        raise SystemExit("ERROR: Could not find boot() guard (inp/sel/hidM2/hidA).")

    insert_call = m.group(1) + "\n    moveUnitIntoAreaRow(inp, sel);\n"
    src3 = pat.sub(insert_call, src2, count=1)

    JS.write_text(src3, encoding="utf-8")
    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Total area + Unit inline 80/20 applied, Unit label/row hidden, HTML row hidden.")

if __name__ == "__main__":
    main()
