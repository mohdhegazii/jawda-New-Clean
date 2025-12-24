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

def find_projects_container_span(src: str) -> tuple[int,int]:
    m = re.search(
        r"Container::make\(\s*'post_meta'\s*,\s*'Project Details'\s*\)\s*"
        r"->where\(\s*'post_type'\s*,\s*'='\s*,\s*'projects'\s*\)",
        src, flags=re.I
    )
    if not m:
        raise SystemExit("ERROR: Could not find Projects container (Project Details + post_type=projects).")

    start = m.start()

    i = start
    depth_paren = 0
    depth_brack = 0
    in_squote = False
    in_dquote = False
    esc = False

    while i < len(src):
        ch = src[i]
        if esc:
            esc = False
            i += 1
            continue
        if ch == "\\":
            esc = True
            i += 1
            continue

        if not in_dquote and ch == "'":
            in_squote = not in_squote
            i += 1
            continue
        if not in_squote and ch == '"':
            in_dquote = not in_dquote
            i += 1
            continue

        if in_squote or in_dquote:
            i += 1
            continue

        if ch == "(":
            depth_paren += 1
        elif ch == ")":
            depth_paren = max(0, depth_paren - 1)
        elif ch == "[":
            depth_brack += 1
        elif ch == "]":
            depth_brack = max(0, depth_brack - 1)
        elif ch == ";" and depth_paren == 0 and depth_brack == 0:
            end = i + 1
            return start, end

        i += 1

    raise SystemExit("ERROR: Could not determine end of Projects container (missing semicolon).")

def get_dev_cb(src: str) -> str:
    if re.search(r"function\s+aqarand_get_hegzz_developers_options\s*\(", src):
        return "aqarand_get_hegzz_developers_options"
    m = re.search(r"Field::make\(\s*'select'\s*,\s*'[^']*(?:developer|dev)[^']*'.*?\)->add_options\(\s*'([^']+)'\s*\)", src, flags=re.I|re.S)
    if m:
        return m.group(1)
    return "aqarand_get_hegzz_developers_options"

def build_basics_tab_clean(dev_cb: str) -> str:
    tpl = r"""
  ->add_tab( __( 'Basics data', 'aqarand' ), array(
    Field::make( 'separator', 'jawda_project_basics_sep', __( 'Basics data', 'aqarand' ) ),

    Field::make( 'text', 'jawda_project_total_area_value', __( 'Total area', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.0001' )
      ->set_help_text( __( 'Enter area then choose unit. Conversion saves both values.', 'aqarand' ) ),

    Field::make( 'select', 'jawda_project_total_area_unit', __( 'Unit', 'aqarand' ) )
      ->add_options( array(
        'acres' => __( 'Feddan (فدان)', 'aqarand' ),
        'm2'    => __( 'Square meter (m²)', 'aqarand' ),
      ) )
      ->set_default_value( 'acres' ),

    // Stored computed values (hidden via JS)
    Field::make( 'text', 'jawda_project_total_area_m2', __( 'Total area (m²)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.01' ),

    Field::make( 'text', 'jawda_project_total_area_acres', __( 'Total area (acres)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.0001' ),

    // JS: hide storage fields rows + do conversion + unit switch converts shown value instantly
    Field::make( 'html', 'jawda_project_total_area_js', '' )
      ->set_html('<script>(function(){'
        + 'var FACTOR=__FACTOR__;'
        + 'function q(namePart){'
          + 'var sel="input[name*=\\\""+namePart+"\\\"], select[name*=\\\""+namePart+"\\\"], input[id*=\\\""+namePart+"\\\"], select[id*=\\\""+namePart+"\\\"]";'
          + 'return document.querySelector(sel);'
        + '}'
        + 'var inp=q("jawda_project_total_area_value");'
        + 'var unit=q("jawda_project_total_area_unit");'
        + 'var m2=q("jawda_project_total_area_m2");'
        + 'var acres=q("jawda_project_total_area_acres");'
        + 'if(!inp||!unit||!m2||!acres) return;'

        // hide whole carbon field rows if possible
        + 'function hideRow(el){'
          + 'if(!el) return;'
          + 'var row=el.closest(".carbon-field");'
          + 'if(row) row.style.display="none";'
          + 'else el.style.display="none";'
        + '}'
        + 'hideRow(m2);'
        + 'hideRow(acres);'

        + 'function toNum(v){ v=(""+(v??"")).trim(); if(!v) return null; var n=parseFloat(v); return isNaN(n)?null:n; }'
        + 'function round(n,dec){ var p=Math.pow(10,dec); return Math.round(n*p)/p; }'
        + 'function emit(el){ el.dispatchEvent(new Event("input",{bubbles:true})); el.dispatchEvent(new Event("change",{bubbles:true})); }'

        + 'var lock=false;'
        + 'function setComputed(){'
          + 'if(lock) return; lock=true;'
          + 'var v=toNum(inp.value);'
          + 'var u=unit.value||"acres";'
          + 'if(v===null){ m2.value=""; acres.value=""; emit(m2); emit(acres); lock=false; return; }'
          + 'if(u==="acres"){'
            + 'acres.value=round(v,4);'
            + 'm2.value=round(v*FACTOR,2);'
          + '}else{'
            + 'm2.value=round(v,2);'
            + 'acres.value=round(v/FACTOR,4);'
          + '}'
          + 'emit(m2); emit(acres);'
          + 'lock=false;'
        + '}'

        + 'function convertShownOnUnitChange(){'
          + 'if(lock) return; lock=true;'
          + 'var v=toNum(inp.value);'
          + 'var u=unit.value||"acres";'
          + 'if(v===null){ setComputed(); lock=false; return; }'
          + 'if(u==="acres"){'
            + 'inp.value=round(v/FACTOR,4);'
          + '}else{'
            + 'inp.value=round(v*FACTOR,2);'
          + '}'
          + 'emit(inp);'
          + 'setComputed();'
          + 'lock=false;'
        + '}'

        + 'inp.addEventListener("input", setComputed);'
        + 'unit.addEventListener("change", convertShownOnUnitChange);'

        + '(function init(){'
          + 'var v=toNum(inp.value);'
          + 'var u=unit.value||"acres";'
          + 'if(v===null){'
            + 'if(u==="acres"){ var a=toNum(acres.value); if(a!==null) inp.value=round(a,4); }'
            + 'else{ var mm=toNum(m2.value); if(mm!==null) inp.value=round(mm,2); }'
          + '}'
          + 'setComputed();'
        + '})();'
      + '})();</script>'),

    Field::make( 'select', '_hegzz_project_developer_id', __( 'Developer', 'aqarand' ) )
      ->add_options( '__DEV_CB__' )
      ->set_required( true )
      ->set_help_text( __( 'Select the project developer (hegzz developer).', 'aqarand' ) ),
  ) )
""".strip("\n") + "\n"

    return (tpl
            .replace("__FACTOR__", str(FEDDAN_TO_M2))
            .replace("__DEV_CB__", dev_cb))

def has_basics_tab(container_src: str) -> bool:
    return bool(re.search(r"->add_tab\(\s*__\(\s*'Basics data'", container_src, flags=re.I))

def replace_basics_tab(container_src: str, new_tab_block: str) -> str:
    if not has_basics_tab(container_src):
        first_tab = re.search(r"(\s*->add_tab\s*\()", container_src, flags=re.I)
        if not first_tab:
            raise SystemExit("ERROR: No ->add_tab found in projects container.")
        insert_at = first_tab.start(1)
        return container_src[:insert_at] + new_tab_block + container_src[insert_at:]

    m = re.search(r"->add_tab\(\s*__\(\s*'Basics data'.*?\)\s*,", container_src, flags=re.I|re.S)
    if not m:
        raise SystemExit("ERROR: Basics data tab exists but cannot locate start safely.")
    start = m.start()
    nxt = re.search(r"\n\s*->add_tab\s*\(", container_src[m.end():], flags=re.I)
    end = m.end() + (nxt.start() if nxt else 0)
    return container_src[:start] + new_tab_block + container_src[end:]

def main():
    src = read(TARGET)

    start, end = find_projects_container_span(src)
    container_src = src[start:end]

    dev_cb = get_dev_cb(src)
    new_tab = build_basics_tab_clean(dev_cb)

    patched_container = replace_basics_tab(container_src, new_tab)
    new_src = src[:start] + patched_container + src[end:]

    b = backup(TARGET)
    write(TARGET, new_src)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[INFO] Removed extra UI. Only Total area + Unit remain; m2/acres stored fields hidden by row.")

if __name__ == "__main__":
    main()
