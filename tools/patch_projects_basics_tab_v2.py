#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re
from pathlib import Path
from datetime import datetime

ROOT = Path(".").resolve()
TARGET = ROOT / "app/functions/meta_box.php"

# 1 فدان = 4200.83 m²
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

def detect_existing_developer_options_callback(src: str) -> str | None:
    pats = [
        r"Field::make\(\s*'select'\s*,\s*'[^']*(?:developer|dev)[^']*'\s*,.*?\)\s*->add_options\(\s*'([^']+)'\s*\)",
        r"->add_options\(\s*'([^']*(?:developer|developers|devs)[^']*)'\s*\)",
    ]
    for pat in pats:
        m = re.search(pat, src, flags=re.I|re.S)
        if m:
            return m.group(1)
    return None

def ensure_hegzz_developers_options_function(src: str, fn_name: str = "aqarand_get_hegzz_developers_options") -> tuple[str,str]:
    if re.search(rf"function\s+{re.escape(fn_name)}\s*\(", src):
        return src, fn_name

    block = r"""
/**
 * Get Developers options from Hegzz (Lookups / custom tables).
 * Tries known helpers/classes first, then safe DB probing if tables exist.
 */
if ( ! function_exists('aqarand_get_hegzz_developers_options') ) {
  function aqarand_get_hegzz_developers_options() {
    $opts = array();

    // 1) Prefer helper getters if present
    if ( function_exists('hegzz_get_lookup_options') ) {
      $try = hegzz_get_lookup_options('developer');
      if (is_array($try) && !empty($try)) return $try;
      $try = hegzz_get_lookup_options('developers');
      if (is_array($try) && !empty($try)) return $try;
    }
    if ( function_exists('aqarand_get_lookup_options') ) {
      $try = aqarand_get_lookup_options('developer');
      if (is_array($try) && !empty($try)) return $try;
      $try = aqarand_get_lookup_options('developers');
      if (is_array($try) && !empty($try)) return $try;
    }

    // 2) Try common class accessors
    if ( class_exists('Hegzz_Lookups') && method_exists('Hegzz_Lookups', 'options') ) {
      $try = Hegzz_Lookups::options('developer');
      if (is_array($try) && !empty($try)) return $try;
      $try = Hegzz_Lookups::options('developers');
      if (is_array($try) && !empty($try)) return $try;
    }

    // 3) DB probing
    global $wpdb;
    if ( empty($wpdb) ) return $opts;

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

      if ( preg_match('~_developers$~', $table) || preg_match('~_developer$~', $table) ) {
        $rows = $wpdb->get_results("SELECT * FROM `$table` ORDER BY id ASC", ARRAY_A);
        foreach ((array)$rows as $r) {
          $id = $r['id'] ?? $r['ID'] ?? null;
          if (!$id) continue;
          $name = $r['name_ar'] ?? $r['name_en'] ?? $r['name'] ?? $r['title'] ?? ('Developer #' . $id);
          $opts[(string)$id] = $name;
        }
        if (!empty($opts)) return $opts;
      }

      if ( preg_match('~_lookups$~', $table) ) {
        $rows = $wpdb->get_results(
          "SELECT * FROM `$table`
           WHERE (`type` IN ('developer','developers')
              OR `lookup_type` IN ('developer','developers')
              OR `group` IN ('developer','developers'))
           AND (`status` IS NULL OR `status`='active' OR `status`=1)
           ORDER BY `sort` ASC, `id` ASC",
          ARRAY_A
        );
        foreach ((array)$rows as $r) {
          $id = $r['id'] ?? $r['ID'] ?? null;
          if (!$id) continue;
          $name = $r['name_ar'] ?? $r['name_en'] ?? $r['name'] ?? $r['title'] ?? ('Developer #' . $id);
          $opts[(string)$id] = $name;
        }
        if (!empty($opts)) return $opts;
      }
    }

    return $opts;
  }
}
""".strip("\n") + "\n"

    return src.rstrip() + "\n\n" + block + "\n", fn_name

def build_basics_tab(dev_cb: str) -> str:
    """
    Field واحد محفوظ بالمتر² + Converter UI (فدان ⇄ متر²) يزامن نفس الحقل.
    """
    tpl = """
  ->add_tab( __( 'Basics data', 'aqarand' ), array(
    Field::make( 'separator', 'jawda_project_basics_sep', __( 'Basics data', 'aqarand' ) ),

    // Canonical stored value (m²). UI below provides feddan <-> m² converter and syncs this input.
    Field::make( 'text', 'jawda_project_total_area_m2', __( 'Total area (acres / m²)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_attribute( 'step', '0.01' )
      ->set_help_text( __( 'Use the converter below (Feddan ⇄ m²). Stored value is m².', 'aqarand' ) ),

    Field::make( 'html', 'jawda_project_total_area_converter_html', '' )
      ->set_html( '<div class="aqarand-area-converter" data-m2-field="jawda_project_total_area_m2" style="padding:12px;border:1px solid #e2e4e7;border-radius:8px;background:#fff;">'
        . '<div style="font-weight:600;margin-bottom:8px;">Area Converter (فدان ⇄ m²)</div>'
        . '<div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">'
          . '<label style="display:flex;flex-direction:column;gap:6px;">'
            . '<span>فدان</span>'
            . '<input type="number" step="0.0001" class="aqarand-area-feddan" style="min-width:220px;" />'
          . '</label>'
          . '<label style="display:flex;flex-direction:column;gap:6px;">'
            . '<span>m²</span>'
            . '<input type="number" step="0.01" class="aqarand-area-m2" style="min-width:220px;" />'
          . '</label>'
          . '<button type="button" class="button aqarand-area-clear">Clear</button>'
        . '</div>'
        . '<p style="margin:8px 0 0;color:#646970;">1 فدان = {factor} m²</p>'
      . '</div>'
      . '<script>(function(){{'
        . 'var wrap=document.currentScript&&document.currentScript.previousElementSibling;'
        . 'if(!wrap||!wrap.classList||!wrap.classList.contains("aqarand-area-converter")) return;'
        . 'var key=wrap.getAttribute("data-m2-field");'
        . 'var feddanInput=wrap.querySelector(".aqarand-area-feddan");'
        . 'var m2Input=wrap.querySelector(".aqarand-area-m2");'
        . 'var clearBtn=wrap.querySelector(".aqarand-area-clear");'
        . 'var FACTOR={factor};'
        . 'function findCarbonInput(){{'
          . 'var sel='
            . '"input[name*=\\"" + key + "\\"]"'
            . '+", input[id*=\\"" + key + "\\"]";'
          . 'return document.querySelector(sel);'
        . '}}'
        . 'var carbonInput=findCarbonInput();'
        . 'if(!carbonInput){{ return; }}'
        . 'carbonInput.style.display="none";'
        . 'function toNum(v){{'
          . 'if(v===null||v===undefined) return null;'
          . 'v=(""+v).trim();'
          . 'if(!v) return null;'
          . 'var n=parseFloat(v);'
          . 'return isNaN(n)?null:n;'
        . '}}'
        . 'function round(n,dec){{'
          . 'var p=Math.pow(10,dec);'
          . 'return Math.round(n*p)/p;'
        . '}}'
        . 'function syncFromStored(){{'
          . 'var m2=toNum(carbonInput.value);'
          . 'if(m2===null){{ feddanInput.value=""; m2Input.value=""; return; }}'
          . 'm2Input.value=round(m2,2);'
          . 'feddanInput.value=round(m2/FACTOR,4);'
        . '}}'
        . 'var lock=false;'
        . 'function setStoredM2(m2){{'
          . 'carbonInput.value=(m2===null?"":m2);'
          . 'carbonInput.dispatchEvent(new Event("input",{{bubbles:true}}));'
          . 'carbonInput.dispatchEvent(new Event("change",{{bubbles:true}}));'
        . '}}'
        . 'feddanInput.addEventListener("input", function(){{'
          . 'if(lock) return; lock=true;'
          . 'var f=toNum(feddanInput.value);'
          . 'if(f===null){{ m2Input.value=""; setStoredM2(null); lock=false; return; }}'
          . 'var m2=round(f*FACTOR,2);'
          . 'm2Input.value=m2;'
          . 'setStoredM2(m2);'
          . 'lock=false;'
        . '}});'
        . 'm2Input.addEventListener("input", function(){{'
          . 'if(lock) return; lock=true;'
          . 'var m2=toNum(m2Input.value);'
          . 'if(m2===null){{ feddanInput.value=""; setStoredM2(null); lock=false; return; }}'
          . 'var f=round(m2/FACTOR,4);'
          . 'feddanInput.value=f;'
          . 'setStoredM2(round(m2,2));'
          . 'lock=false;'
        . '}});'
        . 'clearBtn.addEventListener("click", function(){{'
          . 'feddanInput.value=""; m2Input.value=""; setStoredM2(null);'
        . '}});'
        . 'syncFromStored();'
      . '}})();</script>' ),

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
        # remove existing basics tab block by cutting from its start to next ->add_tab or end
        m = re.search(r"->add_tab\(\s*__\(\s*'Basics data'.*?\)\s*,", container_src, flags=re.I|re.S)
        if not m:
            raise SystemExit("ERROR: Basics data tab exists but cannot locate start safely.")

        start = m.start()

        # find next ->add_tab after this
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

    dev_cb = detect_existing_developer_options_callback(src)
    if not dev_cb:
        src, dev_cb = ensure_hegzz_developers_options_function(src, "aqarand_get_hegzz_developers_options")

    start, end = find_projects_container_span(src)
    container_src = src[start:end]

    new_tab = build_basics_tab(dev_cb)
    patched_container = replace_or_insert_basics_tab(container_src, new_tab)

    new_src = src[:start] + patched_container + src[end:]

    if not re.search(r"->add_tab\(\s*__\(\s*'Basics data'", new_src, flags=re.I):
        raise SystemExit("ERROR: Failed to ensure Basics data tab in output.")
    if "jawda_project_total_area_m2" not in new_src:
        raise SystemExit("ERROR: Failed to ensure area field key exists in output.")

    b = backup(TARGET)
    write(TARGET, new_src)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup :", b)
    print("[INFO] Developer options callback:", dev_cb)

if __name__ == "__main__":
    main()
