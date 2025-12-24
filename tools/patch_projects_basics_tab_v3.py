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
        raise SystemExit("ERROR: Could not find Projects container: Container::make('post_meta','Project Details')->where(post_type=projects)")

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

def ensure_hegzz_developers_options_function(src: str, fn_name: str = "aqarand_get_hegzz_developers_options") -> tuple[str,str]:
    if re.search(rf"function\s+{re.escape(fn_name)}\s*\(", src):
        return src, fn_name

    block = r"""
/**
 * Developers options from Hegzz lookups/custom tables.
 * Returns: [id => name]
 *
 * NOTE: We try to be strict but flexible: detect columns dynamically and only query tables that exist.
 */
if ( ! function_exists('aqarand_get_hegzz_developers_options') ) {
  function aqarand_get_hegzz_developers_options() {
    $opts = array();
    global $wpdb;
    if ( empty($wpdb) ) return $opts;

    // Helper: choose best name column
    $pick_name = function($row) {
      foreach (array('name_ar','name_en','name','title_ar','title_en','title','label_ar','label_en','label') as $k) {
        if (isset($row[$k]) && $row[$k] !== '') return $row[$k];
      }
      return null;
    };

    // Candidate tables (expand safely)
    $candidates = array(
      $wpdb->prefix . 'hegzz_developers',
      $wpdb->prefix . 'hegzz_developer',
      $wpdb->prefix . 'hegzz_lookup_developers',
      $wpdb->prefix . 'hegzz_lookups',
      $wpdb->prefix . 'aqarand_lookups',
    );

    foreach ($candidates as $table) {
      $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $table) );
      if ( $exists !== $table ) continue;

      // Get columns for heuristics
      $cols = $wpdb->get_col("SHOW COLUMNS FROM `$table`", 0);
      $colset = array_flip((array)$cols);

      // Case A: dedicated developers table
      if ( preg_match('~_developers$~', $table) || preg_match('~_developer$~', $table) ) {
        $idcol = isset($colset['id']) ? 'id' : (isset($colset['ID']) ? 'ID' : null);
        if (!$idcol) continue;

        $sql = "SELECT * FROM `$table`";
        // active/status filters if exist
        if (isset($colset['status'])) $sql .= " WHERE (`status`='active' OR `status`=1 OR `status` IS NULL)";
        $sql .= " ORDER BY `$idcol` ASC";

        $rows = $wpdb->get_results($sql, ARRAY_A);
        foreach ((array)$rows as $r) {
          $id = $r[$idcol] ?? null;
          if (!$id) continue;
          $name = $pick_name($r);
          if (!$name) $name = 'Developer #' . $id;
          $opts[(string)$id] = $name;
        }
        if (!empty($opts)) return $opts;
        continue;
      }

      // Case B: lookups mega table
      if ( preg_match('~_lookups$~', $table) ) {
        $idcol = isset($colset['id']) ? 'id' : (isset($colset['ID']) ? 'ID' : null);
        if (!$idcol) continue;

        // Determine type column name
        $typecol = null;
        foreach (array('type','lookup_type','group','lookup_group','kind','slug') as $tc) {
          if (isset($colset[$tc])) { $typecol = $tc; break; }
        }
        if (!$typecol) continue;

        // Try values for developers group
        $types = array('developer','developers','hegzz_developer','hegzz_developers');

        $in = "'" . implode("','", array_map('esc_sql', $types)) . "'";
        $sql = "SELECT * FROM `$table` WHERE `$typecol` IN ($in)";

        // Status filters if exist
        if (isset($colset['status'])) $sql .= " AND (`status`='active' OR `status`=1 OR `status` IS NULL)";
        if (isset($colset['is_active'])) $sql .= " AND (`is_active`=1 OR `is_active` IS NULL)";

        // sort/order if exist
        if (isset($colset['sort'])) $sql .= " ORDER BY `sort` ASC, `$idcol` ASC";
        else $sql .= " ORDER BY `$idcol` ASC";

        $rows = $wpdb->get_results($sql, ARRAY_A);
        foreach ((array)$rows as $r) {
          $id = $r[$idcol] ?? null;
          if (!$id) continue;
          $name = $pick_name($r);
          if (!$name) $name = 'Developer #' . $id;
          $opts[(string)$id] = $name;
        }
        if (!empty($opts)) return $opts;
        continue;
      }
    }

    return $opts;
  }
}
""".strip("\n") + "\n"

    return src.rstrip() + "\n\n" + block + "\n", fn_name

def build_basics_tab(dev_cb: str) -> str:
    """
    المطلوب:
    - input رقم واحد + dropdown unit (acres|m2)
    - التحويل يتم أوتوماتيك بدون refresh
    - عند تغيير dropdown: الرقم يتغير فورًا
    - تخزين: value + unit + computed acres/m2
    """
    tpl = """
  ->add_tab( __( 'Basics data', 'aqarand' ), array(
    Field::make( 'separator', 'jawda_project_basics_sep', __( 'Basics data', 'aqarand' ) ),

    // User-facing value + unit
    Field::make( 'text', 'jawda_project_total_area_value', __( 'Total area', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.0001' )
      ->set_help_text( __( 'Enter area then choose unit. Conversion happens instantly and saves both values.', 'aqarand' ) ),

    Field::make( 'select', 'jawda_project_total_area_unit', __( 'Unit', 'aqarand' ) )
      ->add_options( array(
        'acres' => __( 'Feddan (فدان)', 'aqarand' ),
        'm2'    => __( 'Square meter (m²)', 'aqarand' ),
      ) )
      ->set_default_value( 'acres' ),

    // Stored computed fields (hidden by JS)
    Field::make( 'text', 'jawda_project_total_area_m2', __( 'Total area (m²)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.01' ),

    Field::make( 'text', 'jawda_project_total_area_acres', __( 'Total area (acres)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.0001' ),

    // Live converter UI (same input + dropdown, shows computed other value)
    Field::make( 'html', 'jawda_project_total_area_converter_ui', '' )
      ->set_html(
        '<div class="aqarand-area-oneinput" data-factor="{factor}" style="margin-top:8px;padding:12px;border:1px solid #e2e4e7;border-radius:8px;background:#fff;">'
          . '<div style="font-weight:600;margin-bottom:8px;">Live Conversion</div>'
          . '<div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">'
            . '<div style="color:#646970;">'
              . '<div><strong>Computed:</strong> <span class="aqarand-area-computed">—</span></div>'
              . '<div style="margin-top:4px;">1 فدان = {factor} m²</div>'
            . '</div>'
          . '</div>'
        . '</div>'
        . '<script>(function(){{'
          . 'var box=document.currentScript&&document.currentScript.previousElementSibling;'
          . 'if(!box||!box.classList||!box.classList.contains("aqarand-area-oneinput")) return;'
          . 'var FACTOR=parseFloat(box.getAttribute("data-factor")||"{factor}");'
          . 'function q(namePart){{'
            . 'var sel="input[name*=\\\""+namePart+"\\\"], select[name*=\\\""+namePart+"\\\"], input[id*=\\\""+namePart+"\\\"], select[id*=\\\""+namePart+"\\\"]";'
            . 'return document.querySelector(sel);'
          . '}}'
          . 'var inp=q("jawda_project_total_area_value");'
          . 'var unit=q("jawda_project_total_area_unit");'
          . 'var m2=q("jawda_project_total_area_m2");'
          . 'var acres=q("jawda_project_total_area_acres");'
          . 'var computed=box.querySelector(".aqarand-area-computed");'
          . 'if(!inp||!unit||!m2||!acres) return;'

          // hide stored fields (keep in DOM to save)
          . 'm2.style.display="none";'
          . 'acres.style.display="none";'

          . 'function toNum(v){{'
            . 'v=(""+(v??"")).trim();'
            . 'if(!v) return null;'
            . 'var n=parseFloat(v);'
            . 'return isNaN(n)?null:n;'
          . '}}'
          . 'function round(n,dec){{ var p=Math.pow(10,dec); return Math.round(n*p)/p; }}'
          . 'function emit(el){{'
            . 'el.dispatchEvent(new Event("input",{{bubbles:true}}));'
            . 'el.dispatchEvent(new Event("change",{{bubbles:true}}));'
          . '}}'

          . 'var lock=false;'

          . 'function setComputedFromValue(){{'
            . 'if(lock) return; lock=true;'
            . 'var v=toNum(inp.value);'
            . 'var u=unit.value||"acres";'
            . 'if(v===null){{ m2.value=""; acres.value=""; computed.textContent="—"; emit(m2); emit(acres); lock=false; return; }}'
            . 'if(u==="acres"){{'
              . 'var m2v=round(v*FACTOR,2);'
              . 'm2.value=m2v; acres.value=round(v,4);'
              . 'computed.textContent = m2v + " m²";'
            . '}} else {{'
              . 'var acresv=round(v/FACTOR,4);'
              . 'm2.value=round(v,2); acres.value=acresv;'
              . 'computed.textContent = acresv + " فدان";'
            . '}}'
            . 'emit(m2); emit(acres);'
            . 'lock=false;'
          . '}}'

          . 'function convertValueOnUnitChange(){{'
            . 'if(lock) return; lock=true;'
            . 'var v=toNum(inp.value);'
            . 'var u=unit.value||"acres";'
            . 'if(v===null){{ setComputedFromValue(); lock=false; return; }}'
            . 'if(u==="acres"){{'
              // unit became acres -> current input is m2, convert to acres
              . 'var newV=round(v/FACTOR,4);'
              . 'inp.value=newV;'
            . '}} else {{'
              // unit became m2 -> current input is acres, convert to m2
              . 'var newV=round(v*FACTOR,2);'
              . 'inp.value=newV;'
            . '}}'
            . 'emit(inp);'
            . 'setComputedFromValue();'
            . 'lock=false;'
          . '}}'

          . 'inp.addEventListener("input", setComputedFromValue);'
          . 'unit.addEventListener("change", convertValueOnUnitChange);'

          // init from stored if present (prefer unit+value, else fallback to m2/acres)
          . 'function init(){{'
            . 'var u=unit.value||"acres";'
            . 'var v=toNum(inp.value);'
            . 'if(v===null){{'
              . 'if(u==="acres"){{'
                . 'var a=toNum(acres.value); if(a!==null) inp.value=round(a,4);'
              . '}} else {{'
                . 'var mm=toNum(m2.value); if(mm!==null) inp.value=round(mm,2);'
              . '}}'
            . '}}'
            . 'setComputedFromValue();'
          . '}}'
          . 'init();'
        . '}})();</script>'
      ),

    Field::make( 'select', '_hegzz_project_developer_id', __( 'Developer', 'aqarand' ) )
      ->add_options( '{dev_cb}' )
      ->set_required( true )
      ->set_help_text( __( 'Select the project developer (hegzz developer).', 'aqarand' ) ),
  ) )
""".rstrip() + "\n"
    return tpl.format(factor=FEDDAN_TO_M2, dev_cb=dev_cb)

def has_basics_tab(container_src: str) -> bool:
    return bool(re.search(r"->add_tab\(\s*__\(\s*'Basics data'", container_src, flags=re.I))

def replace_or_insert_basics_tab(container_src: str, new_tab_block: str) -> str:
    if has_basics_tab(container_src):
        m = re.search(r"->add_tab\(\s*__\(\s*'Basics data'.*?\)\s*,", container_src, flags=re.I|re.S)
        if not m:
            raise SystemExit("ERROR: Basics data tab exists but cannot locate start safely.")
        start = m.start()
        nxt = re.search(r"\n\s*->add_tab\s*\(", container_src[m.end():], flags=re.I)
        end = m.end() + (nxt.start() if nxt else 0)
        return container_src[:start] + new_tab_block + container_src[end:]

    first_tab = re.search(r"(\s*->add_tab\s*\()", container_src, flags=re.I)
    if not first_tab:
        raise SystemExit("ERROR: No ->add_tab found in projects container.")
    insert_at = first_tab.start(1)
    return container_src[:insert_at] + new_tab_block + container_src[insert_at:]

def main():
    if not TARGET.exists():
        raise SystemExit(f"ERROR: target not found: {TARGET}")

    src = read(TARGET)

    # Ensure developer options function exists and use it explicitly (حتى لو كان فيه callback تاني)
    src, dev_cb = ensure_hegzz_developers_options_function(src, "aqarand_get_hegzz_developers_options")

    start, end = find_projects_container_span(src)
    container_src = src[start:end]

    new_tab = build_basics_tab(dev_cb)
    patched_container = replace_or_insert_basics_tab(container_src, new_tab)

    new_src = src[:start] + patched_container + src[end:]

    # sanity
    if "jawda_project_total_area_value" not in new_src:
        raise SystemExit("ERROR: Area value field missing after patch.")
    if "_hegzz_project_developer_id" not in new_src:
        raise SystemExit("ERROR: Developer field missing after patch.")

    b = backup(TARGET)
    write(TARGET, new_src)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[INFO] Using developer options callback:", dev_cb)

if __name__ == "__main__":
    main()
