#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
from pathlib import Path
from datetime import datetime
import re

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

def build_set_html_payload() -> str:
    js = f"""<div style="display:none">
  <input type="hidden" name="aqarand_area_m2" id="aqarand_area_m2" value="" />
  <input type="hidden" name="aqarand_area_acres" id="aqarand_area_acres" value="" />
</div>

<script>(function(){{
  var FACTOR={FACTOR};

  function norm(s){{ return (s||"").replace(/\\s+/g," ").trim().toLowerCase(); }}

  function findFieldRowByLabel(targetText){{
    targetText = norm(targetText);
    var labels = Array.from(document.querySelectorAll("label, .carbon-field__label, .carbon-fields--field__label, th label, th, .field-label"));
    for (var i=0;i<labels.length;i++) {{
      var t = norm(labels[i].textContent || "");
      if(!t) continue;
      if(t.indexOf(targetText) !== -1) {{
        var row = labels[i].closest(".carbon-field") ||
                  labels[i].closest(".carbon-field-row") ||
                  labels[i].closest(".carbon-fields--field") ||
                  labels[i].closest(".carbon-container__field") ||
                  labels[i].closest("tr") ||
                  labels[i].parentElement;
        if(row) return row;
      }}
    }}
    return null;
  }}

  function getInputFromRow(row){{
    if(!row) return null;
    return row.querySelector("input[type=number], input[type=text]");
  }}

  function getSelectFromRow(row){{
    if(!row) return null;
    return row.querySelector("select");
  }}

  function toNum(v){{ v=(""+(v??"")).trim(); if(!v) return null; var n=parseFloat(v); return isNaN(n)?null:n; }}
  function round(n,dec){{ var p=Math.pow(10,dec); return Math.round(n*p)/p; }}

  function boot(){{
    var areaRow = findFieldRowByLabel("total area");
    var unitRow = findFieldRowByLabel("unit");
    var inp = getInputFromRow(areaRow);
    var sel = getSelectFromRow(unitRow);
    var hidM2=document.getElementById("aqarand_area_m2");
    var hidA=document.getElementById("aqarand_area_acres");

    if(!inp || !sel || !hidM2 || !hidA) return false;

    inp.setAttribute("data-aqarand-area","1");
    sel.setAttribute("data-aqarand-unit","1");

    function unitVal(){{
      var v = (sel.value || "").toLowerCase();
      if(v.indexOf("m2") !== -1 || v.indexOf("meter") !== -1) return "m2";
      if(v.indexOf("acres") !== -1 || v.indexOf("feddan") !== -1 || v.indexOf("فدان") !== -1) return "acres";
      if(v === "m2" || v === "acres") return v;
      return "acres";
    }}

    var lastUnit = unitVal();
    var lock=false;

    function setHiddenFromShown(){{
      if(lock) return;
      lock=true;
      var v=toNum(inp.value);
      var u=unitVal();
      if(v===null){{ hidM2.value=""; hidA.value=""; lock=false; return; }}
      if(u==="acres") {{
        hidA.value = round(v,4);
        hidM2.value = round(v*FACTOR,2);
      }} else {{
        hidM2.value = round(v,2);
        hidA.value = round(v/FACTOR,4);
      }}
      lock=false;
    }}

    function convertShown(oldU, newU){{
      if(lock) return;
      lock=true;
      var v=toNum(inp.value);
      if(v===null){{ lock=false; setHiddenFromShown(); return; }}
      if(oldU===newU) {{ lock=false; setHiddenFromShown(); return; }}
      if(oldU==="acres" && newU==="m2") {{
        inp.value = round(v*FACTOR,2);
      }} else if(oldU==="m2" && newU==="acres") {{
        inp.value = round(v/FACTOR,4);
      }}
      lock=false;
      setHiddenFromShown();
    }}

    inp.addEventListener("input", setHiddenFromShown);
    sel.addEventListener("change", function(){{
      var cur = unitVal();
      if(cur !== lastUnit) {{
        convertShown(lastUnit, cur);
        lastUnit = cur;
      }}
    }});

    var ticks = 0;
    var poll = setInterval(function(){{
      ticks++;
      if(!document.contains(sel)) {{
        unitRow = findFieldRowByLabel("unit");
        var s2 = getSelectFromRow(unitRow);
        if(s2) sel = s2;
      }}
      if(!document.contains(inp)) {{
        areaRow = findFieldRowByLabel("total area");
        var i2 = getInputFromRow(areaRow);
        if(i2) inp = i2;
      }}
      if(!inp || !sel) {{
        if(ticks>200) clearInterval(poll);
        return;
      }}
      var cur = unitVal();
      if(cur !== lastUnit) {{
        convertShown(lastUnit, cur);
        lastUnit = cur;
      }}
      if(ticks>1200) clearInterval(poll);
    }}, 200);

    try {{
      var obs = new MutationObserver(function(){{
        var cur = unitVal();
        if(cur !== lastUnit) {{
          convertShown(lastUnit, cur);
          lastUnit = cur;
        }}
      }});
      obs.observe(sel, {{attributes:true, childList:true, subtree:true}});
    }} catch(e) {{}}

    setHiddenFromShown();
    return true;
  }}

  var tries=0;
  var timer=setInterval(function(){{
    tries++;
    if(boot()) clearInterval(timer);
    if(tries>80) clearInterval(timer);
  }}, 250);

}})();</script>"""
    # escape for PHP single-quoted string
    js = js.replace("'", "\\'")
    return js

def patch_set_html_block(seg: str) -> str:
    # replace first occurrence of ->set_html('...') inside this segment (exactly single-quoted)
    marker = "->set_html('"
    i = seg.find(marker)
    if i < 0:
        raise SystemExit("ERROR: Could not find ->set_html(' in the targeted segment.")
    j = i + len(marker)

    # find the closing "')"
    # we need to scan for unescaped single quote that closes the PHP string, followed by )
    k = j
    esc = False
    while k < len(seg):
        ch = seg[k]
        if esc:
            esc = False
            k += 1
            continue
        if ch == "\\":
            esc = True
            k += 1
            continue
        if ch == "'":
            # check next chars for )
            if seg[k:k+2] == "')":
                end = k  # position of closing quote
                payload = build_set_html_payload()
                return seg[:j] + payload + seg[end:]
        k += 1

    raise SystemExit("ERROR: Could not find closing ') for set_html string (unexpected quoting).")

def main():
    src = read(TARGET)

    start = src.find("Field::make( 'html', 'jawda_project_total_area_js'")
    if start < 0:
        raise SystemExit("ERROR: Could not locate jawda_project_total_area_js field.")

    seg = src[start:start+30000]
    new_seg = patch_set_html_block(seg)

    b = backup(TARGET)
    new_src = src[:start] + new_seg + src[start+len(seg):]
    write(TARGET, new_src)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Applied safe splice replacement for set_html (no regex replacement escapes).")

if __name__ == "__main__":
    main()
