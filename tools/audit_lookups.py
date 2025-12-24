#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Audit WordPress theme lookups architecture:
- Find where lookups/tabs/admin pages are defined
- Detect CPT/taxonomy registrations
- Detect custom DB tables / CREATE TABLE usage
- Detect caching (transients/wp_cache)
- Detect AJAX endpoints for dependent dropdowns/lookups
Outputs a structured report (human-readable) to stdout.
"""

from __future__ import annotations
import os, re, sys
from pathlib import Path
from dataclasses import dataclass
from typing import Iterable, List, Dict, Tuple

ROOT = Path(os.getcwd()).resolve()

EXCLUDE_DIRS = {
    ".git", "node_modules", "vendor",
    "app/vendor",
    "uploads", "cache",
}

# If your theme keeps vendor in app/vendor, we still exclude the big ones above.

TARGET_KEYWORDS = [
    # Tab names you mentioned
    "Categories", "Property Types", "Sub-Properties", "UsagesAliases",
    # likely internal naming variants
    "usage_alias", "usages_alias", "usagesaliases", "usagealiases",
    "sub_properties", "sub-properties", "subproperty", "sub_property",
    "property_type", "property types", "category", "categories",
    "lookup", "lookups",
    # your brand prefix guesses
    "aqarand", "jawda",
]

PHP_PATTERNS = {
    "admin_menus": re.compile(r"\badd_(menu_page|submenu_page)\s*\(", re.I),
    "admin_tabs": re.compile(r"\b(tab|tabs)\b|\bnav-tab\b", re.I),
    "register_cpt": re.compile(r"\bregister_post_type\s*\(", re.I),
    "register_tax": re.compile(r"\bregister_taxonomy\s*\(", re.I),
    "ajax_actions": re.compile(r"\badd_action\s*\(\s*['\"]wp_ajax(_nopriv)?_", re.I),
    "create_table": re.compile(r"\bCREATE\s+TABLE\b", re.I),
    "wpdb_prefix": re.compile(r"\$wpdb->prefix|\$wpdb->base_prefix", re.I),
    "transients": re.compile(r"\b(set|get|delete)_transient\s*\(", re.I),
    "wp_cache": re.compile(r"\bwp_cache_(set|get|delete|add|incr|decr|flush)\s*\(", re.I),
    "carbon_fields": re.compile(r"\bcarbon_get_(theme_option|post_meta|term_meta)\s*\(", re.I),
    "rest_routes": re.compile(r"\bregister_rest_route\s*\(", re.I),
}

LOOKUP_HINT_PATTERNS = [
    re.compile(r"\b(lookup|lookups)\b", re.I),
    re.compile(r"\b(category|categories)\b", re.I),
    re.compile(r"\b(property[_\s-]?type|types)\b", re.I),
    re.compile(r"\b(sub[_\s-]?properties|sub[_\s-]?property)\b", re.I),
    re.compile(r"\b(usage[_\s-]?alias|usagesaliases|usages_alias)\b", re.I),
]

def should_skip_dir(path: Path) -> bool:
    try:
        rel = path.relative_to(ROOT)
    except Exception:
        rel = path
    parts = set(rel.parts)
    return any(d in parts for d in EXCLUDE_DIRS)


def iter_files(root: Path) -> Iterable[Path]:
    for p in root.rglob("*"):
        if p.is_dir():
            continue
        if should_skip_dir(p):
            continue
        if p.suffix.lower() in {".php", ".js", ".ts", ".css", ".scss", ".json", ".md"}:
            yield p

def read_text(p: Path) -> str:
    try:
        return p.read_text(encoding="utf-8", errors="replace")
    except Exception:
        try:
            return p.read_text(errors="replace")
        except Exception:
            return ""

@dataclass
class Hit:
    file: str
    lineno: int
    kind: str
    line: str

def find_pattern_hits(text: str, pattern: re.Pattern, kind: str, file: Path) -> List[Hit]:
    hits: List[Hit] = []
    lines = text.splitlines()
    for i, line in enumerate(lines, start=1):
        if pattern.search(line):
            hits.append(Hit(str(file.relative_to(ROOT)), i, kind, line.strip()))
    return hits

def find_keyword_context(text: str, keywords: List[str], file: Path) -> List[Hit]:
    hits: List[Hit] = []
    lines = text.splitlines()
    # Create a combined regex for keywords (escaped, case-insensitive)
    kw = [re.escape(k) for k in keywords if k.strip()]
    if not kw:
        return hits
    rx = re.compile(r"(" + "|".join(kw) + r")", re.I)
    for i, line in enumerate(lines, start=1):
        if rx.search(line):
            hits.append(Hit(str(file.relative_to(ROOT)), i, "keyword", line.strip()))
    return hits

def summarize_hits(hits: List[Hit]) -> Dict[str, List[Hit]]:
    grouped: Dict[str, List[Hit]] = {}
    for h in hits:
        grouped.setdefault(h.kind, []).append(h)
    return grouped

def print_section(title: str):
    print("\n" + "="*80)
    print(title)
    print("="*80)

def print_hits(kind: str, hits: List[Hit], limit_per_kind: int = 200):
    if not hits:
        print(f"- {kind}: (no matches)")
        return
    print(f"- {kind}: {len(hits)} match(es)")
    shown = 0
    last_file = None
    for h in hits:
        if shown >= limit_per_kind:
            print(f"  ... truncated (showing first {limit_per_kind})")
            break
        if h.file != last_file:
            print(f"\n  {h.file}")
            last_file = h.file
        print(f"    L{h.lineno}: {h.line}")
        shown += 1

def main():
    if (ROOT / "style.css").exists() is False and (ROOT / "functions.php").exists() is False:
        # still allow running, but warn
        pass

    all_hits: List[Hit] = []
    per_file_flags: Dict[str, Dict[str, int]] = {}

    files = list(iter_files(ROOT))
    if not files:
        print("No files found. Are you in the theme root?")
        sys.exit(1)

    for f in files:
        t = read_text(f)
        if not t.strip():
            continue

        flags: Dict[str, int] = {}

        # Pattern hits
        for k, rx in PHP_PATTERNS.items():
            hh = find_pattern_hits(t, rx, k, f)
            if hh:
                all_hits.extend(hh)
                flags[k] = flags.get(k, 0) + len(hh)

        # Keyword context hits (broader, may be noisy)
        kk = find_keyword_context(t, TARGET_KEYWORDS, f)
        if kk:
            all_hits.extend(kk)
            flags["keyword"] = flags.get("keyword", 0) + len(kk)

        # Lookup hint density (to find core modules)
        hint_count = 0
        for hrx in LOOKUP_HINT_PATTERNS:
            hint_count += len(hrx.findall(t))
        if hint_count:
            flags["lookup_hints"] = hint_count

        if flags:
            per_file_flags[str(f.relative_to(ROOT))] = flags

    # Rank files by "lookup_hints" then total signals
    ranked = sorted(
        per_file_flags.items(),
        key=lambda kv: (kv[1].get("lookup_hints", 0), sum(kv[1].values())),
        reverse=True
    )

    grouped = summarize_hits(all_hits)

    print_section("LOOKUPS ARCHITECTURE AUDIT (Theme)")
    print(f"Theme root: {ROOT}")
    print(f"Scanned files: {len(files)}")
    print(f"Files with signals: {len(per_file_flags)}")

    print_section("TOP SUSPICIOUS / RELEVANT FILES (by lookup signal)")
    for i, (fname, flags) in enumerate(ranked[:40], start=1):
        score = flags.get("lookup_hints", 0)
        total = sum(flags.values())
        print(f"{i:02d}. {fname} | lookup_hints={score} | total_signals={total} | {flags}")

    # Key sections
    print_section("ADMIN MENUS / TABS")
    print_hits("admin_menus", grouped.get("admin_menus", []))
    print_hits("admin_tabs", grouped.get("admin_tabs", []))

    print_section("CPT / TAXONOMY REGISTRATION")
    print_hits("register_cpt", grouped.get("register_cpt", []))
    print_hits("register_tax", grouped.get("register_tax", []))

    print_section("AJAX / REST ENDPOINTS")
    print_hits("ajax_actions", grouped.get("ajax_actions", []))
    print_hits("rest_routes", grouped.get("rest_routes", []))

    print_section("DATABASE (custom tables / wpdb usage)")
    print_hits("create_table", grouped.get("create_table", []))
    print_hits("wpdb_prefix", grouped.get("wpdb_prefix", []))

    print_section("CACHING (transients / wp_cache)")
    print_hits("transients", grouped.get("transients", []))
    print_hits("wp_cache", grouped.get("wp_cache", []))

    print_section("CARBON FIELDS (if used for lookups/options)")
    print_hits("carbon_fields", grouped.get("carbon_fields", []))

    print_section("RAW KEYWORD CONTEXT (the 4 tabs + lookup words)")
    # reduce noise by showing fewer
    print_hits("keyword", grouped.get("keyword", []), limit_per_kind=250)

    print("\nDONE.")

if __name__ == "__main__":
    main()
