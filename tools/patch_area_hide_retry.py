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

    # locate the set_html('<script>(function(){ ... })();</script>') for jawda_project_total_area_js
    # and inject a stronger hideRow + retry loop.
    m = re.search(r"Field::make\(\s*'html'\s*,\s*'jawda_project_total_area_js'\s*,\s*''\s*\)\s*->set_html\('(<script>\(function\(\)\{.*?\}</script>)'\)",
                  src, flags=re.S)
    if not m:
        # fallback: start at ->set_html('<script>(function(){
        m2 = re.search(r"Field::make\(\s*'html'\s*,\s*'jawda_project_total_area_js'\s*,\s*''\s*\)\s*\n\s*->set_html\('",
                       src, flags=re.S)
        if not m2:
            raise SystemExit("ERROR: Could not find jawda_project_total_area_js set_html start.")
        # We'll do a simpler string replacement inside the JS block
    # We'll replace the existing hideRow function with a stronger one + retry wrapper.

    # Stronger JS snippet to inject/replace
    # We replace:
    #   function hideRow(el){...}
    # with a version that:
    #   - tries multiple closest selectors
    #   - hides label/row wrappers
    # and we add a retry loop that runs a few times after load.
    def repl_hide(js: str) -> str:
        js2 = re.sub(
            r"function hideRow\(el\)\{\s*.*?\}\s*hideRow\(m2\);\s*hideRow\(acres\);\s*",
            r"""function hideRow(el){
    if(!el) return;
    // Try common Carbon wrappers
    var row = el.closest(".carbon-field") ||
              el.closest(".carbon-field-row") ||
              el.closest(".carbon-fields--field") ||
              el.closest(".carbon-container__field") ||
              el.closest("tr") ||
              el.closest(".postbox") ||
              null;
    if(row){ row.style.display="none"; return; }
    // Fallback: hide parent nodes
    var p = el.parentElement;
    for(var i=0;i<6 && p;i++){
      if(p.classList && (p.classList.contains("carbon-field") || p.classList.contains("carbon-field-row") || p.classList.contains("carbon-container__field"))){
        p.style.display="none"; return;
      }
      p = p.parentElement;
    }
    el.style.display="none";
  }
  // Retry hide because Carbon may render async
  (function retryHide(){
    var tries = 0;
    var t = setInterval(function(){
      tries++;
      hideRow(m2); hideRow(acres);
      if(tries >= 10){ clearInterval(t); }
    }, 250);
  })();
""",
            js, flags=re.S
        )
        return js2

    # Extract the set_html string content (between ->set_html(' and closing '))
    start = src.find("Field::make( 'html', 'jawda_project_total_area_js'")
    if start < 0:
        raise SystemExit("ERROR: Could not locate jawda_project_total_area_js field.")
    seg = src[start:start+12000]  # should contain the set_html block
    setm = re.search(r"->set_html\('(.{200,}?)'\)\s*,", seg, flags=re.S)
    if not setm:
        raise SystemExit("ERROR: Could not parse ->set_html('...') block for jawda_project_total_area_js (maybe quotes changed).")

    inner = setm.group(1)
    new_inner = repl_hide(inner)

    if inner == new_inner:
        # If pattern didn't match, inject retry hide right after var acres=...
        new_inner = re.sub(
            r"(var acres=q\(\"jawda_project_total_area_acres\"\);\s*if\(!inp\|\|!unit\|\|!m2\|\|!acres\) return;\s*)",
            r"""\1
  function hideRow(el){
    if(!el) return;
    var row = el.closest(".carbon-field") ||
              el.closest(".carbon-field-row") ||
              el.closest(".carbon-fields--field") ||
              el.closest(".carbon-container__field") ||
              el.closest("tr") || null;
    if(row){ row.style.display="none"; return; }
    el.style.display="none";
  }
  (function retryHide(){
    var tries = 0;
    var t = setInterval(function(){
      tries++;
      hideRow(m2); hideRow(acres);
      if(tries >= 10){ clearInterval(t); }
    }, 250);
  })();
""",
            inner, flags=re.S
        )

    # Replace in full file
    new_seg = seg[:setm.start(1)] + new_inner + seg[setm.end(1):]
    new_src = src[:start] + new_seg + src[start+len(seg):]

    b = backup(TARGET)
    write(TARGET, new_src)
    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Strong hideRow + retry injected (m2/acres should not appear).")

if __name__ == "__main__":
    main()
