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
            esc = False; i += 1; continue
        if ch == "\\":
            esc = True; i += 1; continue
        if not in_dquote and ch == "'":
            in_squote = not in_squote; i += 1; continue
        if not in_squote and ch == '"':
            in_dquote = not in_dquote; i += 1; continue
        if in_squote or in_dquote:
            i += 1; continue
        if ch == "(":
            depth_paren += 1
        elif ch == ")":
            depth_paren = max(0, depth_paren - 1)
        elif ch == "[":
            depth_brack += 1
        elif ch == "]":
            depth_brack = max(0, depth_brack - 1)
        elif ch == ";" and depth_paren == 0 and depth_brack == 0:
            return start, i + 1
        i += 1
    raise SystemExit("ERROR: Could not find end of Projects container.")

def get_dev_cb(src: str) -> str:
    if re.search(r"function\s+aqarand_get_hegzz_developers_options\s*\(", src):
        return "aqarand_get_hegzz_developers_options"
    return "aqarand_get_hegzz_developers_options"

def build_basics_tab_final(dev_cb: str) -> str:
    # NOTE: No m2/acres Carbon fields at all. Only value+unit + html script.
    js = f"""<div style="display:none">
  <input type="hidden" name="aqarand_area_m2" id="aqarand_area_m2" value="" />
  <input type="hidden" name="aqarand_area_acres" id="aqarand_area_acres" value="" />
</div>
<script>(function(){{
  var FACTOR={FEDDAN_TO_M2};

  function q(namePart){{
    var sel="input[name*=\\\"" + namePart + "\\\"], select[name*=\\\"" + namePart + "\\\"], input[id*=\\\"" + namePart + "\\\"], select[id*=\\\"" + namePart + "\\\"]";
    return document.querySelector(sel);
  }}

  var inp=q("jawda_project_total_area_value");
  var unit=q("jawda_project_total_area_unit");
  var hidM2=document.getElementById("aqarand_area_m2");
  var hidA=document.getElementById("aqarand_area_acres");
  if(!inp||!unit||!hidM2||!hidA) return;

  function toNum(v){{ v=(""+(v??"")).trim(); if(!v) return null; var n=parseFloat(v); return isNaN(n)?null:n; }}
  function round(n,dec){{ var p=Math.pow(10,dec); return Math.round(n*p)/p; }}

  var lock=false;

  function setHidden(){{
    if(lock) return; lock=true;
    var v=toNum(inp.value);
    var u=unit.value||"acres";
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

  function convertShownOnUnitChange(){{
    if(lock) return; lock=true;
    var v=toNum(inp.value);
    var u=unit.value||"acres";
    if(v===null){{ setHidden(); lock=false; return; }}
    if(u==="acres") {{
      inp.value = round(v/FACTOR,4);
    }} else {{
      inp.value = round(v*FACTOR,2);
    }}
    lock=false;
    setHidden();
  }}

  inp.addEventListener("input", setHidden);
  unit.addEventListener("change", convertShownOnUnitChange);

  setHidden();
}})();</script>"""
    js = js.replace("'", "\\'")

    tab = f"""
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

    Field::make( 'html', 'jawda_project_total_area_js', '' )
      ->set_html('{js}'),

    Field::make( 'select', '_hegzz_project_developer_id', __( 'Developer', 'aqarand' ) )
      ->add_options( '{dev_cb}' )
      ->set_required( true )
      ->set_help_text( __( 'Select the project developer (hegzz developer).', 'aqarand' ) ),
  ) )
""".rstrip() + "\n"
    return tab

def replace_basics_tab(container_src: str, new_tab_block: str) -> str:
    if not re.search(r"->add_tab\(\s*__\(\s*'Basics data'", container_src, flags=re.I):
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

def ensure_save_hook(src: str) -> str:
    if re.search(r"function\s+aqarand_save_project_area_meta\s*\(", src):
        return src

    hook = r"""
/**
 * Save computed project area values coming from admin UI hidden inputs.
 * Stores:
 * - jawda_project_total_area_m2
 * - jawda_project_total_area_acres
 */
if ( ! function_exists('aqarand_save_project_area_meta') ) {
  function aqarand_save_project_area_meta($post_id) {
    // only projects
    if (get_post_type($post_id) !== 'projects') return;

    // autosave/revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    // capabilities
    if (!current_user_can('edit_post', $post_id)) return;

    // Read hidden inputs
    $m2    = isset($_POST['aqarand_area_m2']) ? sanitize_text_field(wp_unslash($_POST['aqarand_area_m2'])) : '';
    $acres = isset($_POST['aqarand_area_acres']) ? sanitize_text_field(wp_unslash($_POST['aqarand_area_acres'])) : '';

    // Normalize to numeric (allow decimals)
    $m2f = ($m2 !== '') ? floatval($m2) : 0;
    $af  = ($acres !== '') ? floatval($acres) : 0;

    // Save only if meaningful; else delete
    if ($m2 !== '' && $m2f > 0) update_post_meta($post_id, 'jawda_project_total_area_m2', $m2f);
    else delete_post_meta($post_id, 'jawda_project_total_area_m2');

    if ($acres !== '' && $af > 0) update_post_meta($post_id, 'jawda_project_total_area_acres', $af);
    else delete_post_meta($post_id, 'jawda_project_total_area_acres');
  }

  add_action('save_post', 'aqarand_save_project_area_meta', 20);
}
""".strip("\n") + "\n"

    return src.rstrip() + "\n\n" + hook

def main():
    src = read(TARGET)

    start, end = find_projects_container_span(src)
    container_src = src[start:end]

    dev_cb = get_dev_cb(src)
    new_tab = build_basics_tab_final(dev_cb)
    patched_container = replace_basics_tab(container_src, new_tab)
    new_src = src[:start] + patched_container + src[end:]

    new_src = ensure_save_hook(new_src)

    b = backup(TARGET)
    write(TARGET, new_src)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[OK] Final approach applied: UI has ONLY Total area + Unit. Computed values saved via save_post hook.")

if __name__ == "__main__":
    main()
