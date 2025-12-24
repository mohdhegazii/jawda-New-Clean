#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re
from pathlib import Path
from datetime import datetime

ROOT = Path(".").resolve()
TARGET = ROOT / "app/functions/meta_box.php"

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
    src = read(TARGET)

    # find the JS block inside jawda_project_total_area_js set_html('...') and inject label-based hiding
    start = src.find("Field::make( 'html', 'jawda_project_total_area_js'")
    if start < 0:
        raise SystemExit("ERROR: Could not locate jawda_project_total_area_js field.")

    seg = src[start:start+14000]
    m = re.search(r"->set_html\('(.{200,}?)'\)\s*,", seg, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not parse set_html string.")

    inner = m.group(1)

    if "hideByLabelText" in inner:
        print("[SKIP] label-based hide already present.")
        return

    inject = r"""
  function hideByLabelText(txt){
    var labels = Array.from(document.querySelectorAll("label, .carbon-field__label, .carbon-field-row label, th, .field-label"));
    labels.forEach(function(l){
      var t = (l.textContent||"").trim();
      if(t === txt){
        var row = l.closest(".carbon-field") ||
                  l.closest(".carbon-field-row") ||
                  l.closest(".carbon-fields--field") ||
                  l.closest(".carbon-container__field") ||
                  l.closest("tr") ||
                  l.parentElement;
        if(row) row.style.display="none";
      }
    });
  }
  (function retryHideLabels(){
    var tries = 0;
    var t = setInterval(function(){
      tries++;
      hideByLabelText("Total area (m²)");
      hideByLabelText("Total area (acres)");
      if(tries >= 10) clearInterval(t);
    }, 250);
  })();
""".strip("\n")

    # put it after the first retryHide() or after hideRow() if exists
    if "retryHide" in inner:
        # inject after the first occurrence of retryHide closure
        inner2 = re.sub(r"\}\)\(\);\s*", lambda mm: mm.group(0) + inject, inner, count=1, flags=re.S)
    else:
        inner2 = inner + inject

    new_seg = seg[:m.start(1)] + inner2 + seg[m.end(1):]
    new_src = src[:start] + new_seg + src[start+len(seg):]

    b = backup(TARGET)
    write(TARGET, new_src)
    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Added label-text based hiding for Total area (m²)/(acres).")

if __name__ == "__main__":
    main()
