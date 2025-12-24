#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re
from pathlib import Path
from datetime import datetime

ROOT = Path(".").resolve()
TARGET = ROOT / "app/functions/meta_box.php"
FACTOR = 4200.83

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(read(p), encoding="utf-8")
    return b

def build_set_html_replacement() -> str:
    js = f"""<div style="display:none">
  <input type="hidden" name="aqarand_area_m2" id="aqarand_area_m2" value="" />
  <input type="hidden" name="aqarand_area_acres" id="aqarand_area_acres" value="" />
</div>
<script>(function(){{
  var FACTOR={FACTOR};

  function q(namePart){{
    var sel="input[name*=\\\"" + namePart + "\\\"], select[name*=\\\"" + namePart + "\\\"], input[id*=\\\"" + namePart + "\\\"], select[id*=\\\"" + namePart + "\\\"]";
    return document.querySelector(sel);
  }}

  function toNum(v){{ v=(""+(v??"")).trim(); if(!v) return null; var n=parseFloat(v); return isNaN(n)?null:n; }}
  function round(n,dec){{ var p=Math.pow(10,dec); return Math.round(n*p)/p; }}

  function initOnce(){{
    var inp=q("jawda_project_total_area_value");
    var unit=q("jawda_project_total_area_unit");
    var hidM2=document.getElementById("aqarand_area_m2");
    var hidA=document.getElementById("aqarand_area_acres");
    if(!inp||!unit||!hidM2||!hidA) return false;

    // store lastUnit so we can convert based on old->new
    var lastUnit = unit.value || "acres";

    function setHiddenFromShown(){{
      var v=toNum(inp.value);
      var u=unit.value||"acres";
      if(v===null){{ hidM2.value=""; hidA.value=""; return; }}
      if(u==="acres") {{
        hidA.value = round(v,4);
        hidM2.value = round(v*FACTOR,2);
      }} else {{
        hidM2.value = round(v,2);
        hidA.value = round(v/FACTOR,4);
      }}
    }}

    function convertShown(oldU, newU){{
      var v=toNum(inp.value);
      if(v===null){{ setHiddenFromShown(); return; }}
      if(oldU===newU) {{ setHiddenFromShown(); return; }}

      // old -> new conversion on the SAME displayed number
      if(oldU==="acres" && newU==="m2") {{
        inp.value = round(v*FACTOR,2);
      }} else if(oldU==="m2" && newU==="acres") {{
        inp.value = round(v/FACTOR,4);
      }}
      setHiddenFromShown();
    }}

    // 1) update hidden while typing
    inp.addEventListener("input", function(){{
      setHiddenFromShown();
    }});

    // 2) also listen to native change (works sometimes)
    unit.addEventListener("change", function(){{
      var newU = unit.value || "acres";
      convertShown(lastUnit, newU);
      lastUnit = newU;
    }});

    // 3) watcher/poller (works always even with Select2/DOM swaps)
    var tries = 0;
    var t = setInterval(function(){{
      tries++;
      // unit element might be replaced by Carbon; re-fetch if lost
      if(!document.contains(unit)) {{
        unit = q("jawda_project_total_area_unit");
        if(unit) lastUnit = unit.value || lastUnit;
      }}
      if(!document.contains(inp)) {{
        inp = q("jawda_project_total_area_value");
      }}
      if(!inp || !unit) {{
        if(tries>50) clearInterval(t);
        return;
      }}

      var cur = unit.value || "acres";
      if(cur !== lastUnit) {{
        convertShown(lastUnit, cur);
        lastUnit = cur;
      }}
      if(tries>200) clearInterval(t); // stop after ~40s
    }}, 200);

    // initial hidden sync
    setHiddenFromShown();
    return true;
  }}

  // retry init because Carbon renders fields async
  var attempts = 0;
  var boot = setInterval(function(){{
    attempts++;
    if(initOnce()) {{ clearInterval(boot); }}
    if(attempts > 60) {{ clearInterval(boot); }}
  }}, 250);
}})();</script>"""
    js = js.replace("'", "\\'")
    return f"->set_html('{js}')"

def main():
    src = read(TARGET)

    # locate the jawda_project_total_area_js set_html block and replace it entirely
    start = src.find("Field::make( 'html', 'jawda_project_total_area_js'")
    if start < 0:
        raise SystemExit("ERROR: Could not locate jawda_project_total_area_js field.")

    seg = src[start:start+20000]
    m = re.search(r"->set_html\('(.{200,}?)'\)\s*,", seg, flags=re.S)
    if not m:
        raise SystemExit("ERROR: Could not parse ->set_html('...') block (quotes changed?).")

    new_seg = seg[:m.start()-len("->set_html('")]  # not safe; do targeted replace instead

    # safer: replace only the ->set_html('...') portion
    replaced = re.sub(
        r"->set_html\('(.{200,}?)'\)",
        build_set_html_replacement(),
        seg,
        count=1,
        flags=re.S
    )

    if replaced == seg:
        raise SystemExit("ERROR: Replacement did not apply (pattern mismatch).")

    new_src = src[:start] + replaced + src[start+len(seg):]

    b = backup(TARGET)
    write(TARGET, new_src)
    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Unit watcher added: switching acres<->m2 now converts the shown value.")

if __name__ == "__main__":
    main()
