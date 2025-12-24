#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re
import sys
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

def find_projects_container_span(src: str) -> tuple[int,int]:
    """
    Find the exact container chain for projects:
      Container::make('post_meta','Project Details')->where('post_type','=','projects')...
    Return (start_index_of_Container::make, end_index_of_chain_statement)
    end index is heuristic: end of the Container chain (until semicolon after add_tab chain)
    """
    # match the container declaration start
    m = re.search(r"Container::make\(\s*'post_meta'\s*,\s*'Project Details'\s*\)\s*"
                  r"->where\(\s*'post_type'\s*,\s*'='\s*,\s*'projects'\s*\)",
                  src, flags=re.I)
    if not m:
        raise SystemExit("ERROR: Could not find Projects container (Project Details + post_type=projects) in meta_box.php")

    start = m.start()

    # Find statement end: from start forward, first semicolon that ends the chain.
    # We'll walk char-by-char counting parentheses/brackets to avoid hitting semicolons inside arrays/strings.
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

        if not in_dquote and ch == "'" :
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

    raise SystemExit("ERROR: Could not determine end of Projects container statement (semicolon not found).")

def detect_developer_options_callback(src: str) -> str | None:
    """
    Try to discover an existing options callback used for developer selects.
    Examples:
      Field::make('select','...developer...','...')->add_options('some_callback')
    """
    patterns = [
        r"Field::make\(\s*'select'\s*,\s*'[^']*developer[^']*'\s*,.*?\)\s*->add_options\(\s*'([^']+)'\s*\)",
        r"->add_options\(\s*'([^']+developers[^']*)'\s*\)",
    ]
    for pat in patterns:
        m = re.search(pat, src, flags=re.I|re.S)
        if m:
            return m.group(1)
    return None

def ensure_developer_options_function(src: str, fn_name: str = "aqarand_get_developers_lookup_options") -> tuple[str, str]:
    """
    Ensure a function exists to provide developer options.
    If not exists, inject at end of file (before closing php if any).
    Returns (updated_src, function_name_to_use)
    """
    if re.search(rf"function\s+{re.escape(fn_name)}\s*\(", src):
        return src, fn_name

    # Inject a safe fallback function. Uses CPT 'developers' if exists.
    # (Later you can wire it to your custom lookups table easily.)
    block = r"""
/**
 * Developers lookup options (fallback).
 * If you already have a custom lookups table for developers, replace the internals here later.
 */
if ( ! function_exists('aqarand_get_developers_lookup_options') ) {
  function aqarand_get_developers_lookup_options() {
    $opts = array();

    // Prefer CPT "developers" if available
    if ( function_exists('post_type_exists') && post_type_exists('developers') ) {
      $posts = get_posts(array(
        'post_type'      => 'developers',
        'post_status'    => 'publish',
        'numberposts'    => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'suppress_filters' => false,
      ));
      foreach ((array)$posts as $p) {
        $opts[(string)$p->ID] = get_the_title($p->ID);
      }
      return $opts;
    }

    // If no CPT, return empty array (safe)
    return $opts;
  }
}
""".strip("\n") + "\n"

    # Append near end
    # Keep trailing whitespace/newline tidy
    new_src = src.rstrip() + "\n\n" + block + "\n"
    return new_src, fn_name

def already_has_basics_tab(container_src: str) -> bool:
    return bool(re.search(r"->add_tab\(\s*__\(\s*'Basics data'", container_src, flags=re.I))

def insert_basics_tab_first(container_src: str, dev_options_cb: str) -> str:
    """
    Insert ->add_tab('Basics data'...) BEFORE first ->add_tab(...) in the projects container.
    """
    # Find first add_tab in this container chain
    m = re.search(r"(\s*->add_tab\s*\()", container_src, flags=re.I)
    if not m:
        raise SystemExit("ERROR: No ->add_tab found inside Projects container chain. Unexpected structure.")

    insert_at = m.start(1)

    # Build fields (acres + m2 + developer required)
    # Use number inputs via set_attribute('type','number') for nice UX
    basics_tab = f"""
  ->add_tab( __( 'Basics data', 'aqarand' ), array(
    Field::make( 'separator', 'jawda_project_basics_sep', __( 'Basics data', 'aqarand' ) ),

    Field::make( 'text', 'jawda_project_total_area_acres', __( 'Total area (acres)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_help_text( __( 'Enter the total land area in acres (numeric).', 'aqarand' ) ),

    Field::make( 'text', 'jawda_project_total_area_m2', __( 'Total area (m²)', 'aqarand' ) )
      ->set_attribute( 'type', 'number' )
      ->set_help_text( __( 'Enter the total land area in square meters (numeric).', 'aqarand' ) ),

    Field::make( 'select', '_hegzz_project_developer_id', __( 'Developer', 'aqarand' ) )
      ->add_options( '{dev_options_cb}' )
      ->set_required( true )
      ->set_help_text( __( 'Select the project developer.', 'aqarand' ) ),
  ) )
""".rstrip() + "\n"

    return container_src[:insert_at] + basics_tab + container_src[insert_at:]

def main():
    if not TARGET.exists():
        raise SystemExit(f"ERROR: target file not found: {TARGET}")

    src = read(TARGET)

    # Find projects container span
    start, end = find_projects_container_span(src)
    container_src = src[start:end]

    if already_has_basics_tab(container_src):
        print("[SKIP] Basics data tab already exists in Projects container. Nothing to do.")
        return

    # detect callback
    cb = detect_developer_options_callback(src)
    if not cb:
        # ensure fallback function exists and use it
        src, cb = ensure_developer_options_function(src, "aqarand_get_developers_lookup_options")
        # re-slice because src changed (append) but container span indices still valid for earlier region
        # safe to recompute span
        start, end = find_projects_container_span(src)
        container_src = src[start:end]

    # insert tab first
    patched_container = insert_basics_tab_first(container_src, cb)

    # replace back
    new_src = src[:start] + patched_container + src[end:]

    # Safety check: ensure it now contains Basics data tab
    if not re.search(r"->add_tab\(\s*__\(\s*'Basics data'", new_src, flags=re.I):
        raise SystemExit("ERROR: Patch did not insert Basics data tab as expected.")

    # Backup + write
    b = backup(TARGET)
    write(TARGET, new_src)

    print("[OK] Patched:", TARGET)
    print("[OK] Backup  :", b)
    print("[INFO] Developer options callback used:", cb)

if __name__ == "__main__":
    main()
