#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re
from pathlib import Path
from datetime import datetime

ROOT = Path(".").resolve()
TARGET = ROOT / "app/functions/meta_box.php"

FEDDAN_TO_M2 = 4200.83

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(read(p), encoding="utf-8")
    return b

def build_set_html_fixed() -> str:
    # PHP single-quoted string; avoid any PHP concatenation.
    js = f"""<script>(function(){{
  var FACTOR={FEDDAN_TO_M2};
  function q(namePart){{
    var sel="input[name*=\\\"" + namePart + "\\\"], select[name*=\\\"" + namePart + "\\\"], input[id*=\\\"" + namePart + "\\\"], select[id*=\\\"" + namePart + "\\\"]";
    return document.querySelector(sel);
  }}
  var inp=q("jawda_project_total_area_value");
  var unit=q("jawda_project_total_area_unit");
  var m2=q("jawda_project_total_area_m2");
  var acres=q("jawda_project_total_area_acres");
  if(!inp||!unit||!m2||!acres) return;

  function hideRow(el){{
    if(!el) return;
    var row = el.closest(".carbon-field");
    if(row) row.style.display="none";
    else el.style.display="none";
  }}
  hideRow(m2);
  hideRow(acres);

  function toNum(v){{ v=(""+(v??"")).trim(); if(!v) return null; var n=parseFloat(v); return isNaN(n)?null:n; }}
  function round(n,dec){{ var p=Math.pow(10,dec); return Math.round(n*p)/p; }}
  function emit(el){{ el.dispatchEvent(new Event("input",{{bubbles:true}})); el.dispatchEvent(new Event("change",{{bubbles:true}})); }}

  var lock=false;

  function setComputed(){{
    if(lock) return; lock=true;
    var v=toNum(inp.value);
    var u=unit.value||"acres";
    if(v===null){{ m2.value=""; acres.value=""; emit(m2); emit(acres); lock=false; return; }}
    if(u==="acres"){{
      acres.value=round(v,4);
      m2.value=round(v*FACTOR,2);
    }} else {{
      m2.value=round(v,2);
      acres.value=round(v/FACTOR,4);
    }}
    emit(m2); emit(acres);
    lock=false;
  }}

  function convertShownOnUnitChange(){{
    if(lock) return; lock=true;
    var v=toNum(inp.value);
    var u=unit.value||"acres";
    if(v===null){{ setComputed(); lock=false; return; }}
    if(u==="acres") {{
      inp.value=round(v/FACTOR,4);
    }} else {{
      inp.value=round(v*FACTOR,2);
    }}
    emit(inp);
    setComputed();
    lock=false;
  }}

  inp.addEventListener("input", setComputed);
  unit.addEventListener("change", convertShownOnUnitChange);

  (function init(){{
    var v=toNum(inp.value);
    var u=unit.value||"acres";
    if(v===null){{
      if(u==="acres"){{ var a=toNum(acres.value); if(a!==null) inp.value=round(a,4); }}
      else{{ var mm=toNum(m2.value); if(mm!==null) inp.value=round(mm,2); }}
    }}
    setComputed();
  }})();
}})();</script>"""
    # Escape single quotes for PHP single-quoted string (none expected, but safe)
    js = js.replace("'", "\\'")
    return f"->set_html('{js}')"

def main():
    src = read(TARGET)

    # Find the Field::make('html','jawda_project_total_area_js'...) block and replace its ->set_html(...) content
    # Match from Field::make('html', 'jawda_project_total_area_js' ...) to the following ->set_html( ... ),
    pat = re.compile(
        r"(Field::make\(\s*'html'\s*,\s*'jawda_project_total_area_js'\s*,\s*''\s*\)\s*)"
        r"->set_html\((?:.|\n)*?\)\s*,",
        flags=re.S
    )
    m = pat.search(src)
    if not m:
        raise SystemExit("ERROR: Could not locate jawda_project_total_area_js set_html block to fix.")

    fixed = m.group(1) + build_set_html_fixed() + ","
    new_src = src[:m.start()] + fixed + src[m.end():]

    b = backup(TARGET)
    write(TARGET, new_src)
    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Fixed PHP runtime fatal: removed string '+' concatenation inside set_html().")

if __name__ == "__main__":
    main()
