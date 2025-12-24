#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Analyze WordPress theme code to locate the edit screen implementation for a given post type (projects),
especially Carbon Fields containers/tabs/fields, and lookup developer integration.

Outputs:
- tools/_reports/projects_edit_screen_report.md
- tools/_reports/projects_edit_screen_hits.json

Usage:
  python3 tools/analyze_projects_edit_screen.py --root . --post-type projects
Optional:
  --needle "Total area"
  --verbose
"""

from __future__ import annotations
import argparse
import json
import os
import re
import sys
from dataclasses import dataclass, asdict
from pathlib import Path
from typing import List, Dict, Tuple, Optional

# ---------- Config ----------
PHP_EXTS = {".php", ".inc"}
SKIP_DIRS = {
    "node_modules", "vendor", ".git", ".idea", ".vscode",
    "cache", "uploads", "wp-admin", "wp-includes"
}

CARBON_HINTS = [
    r"\bcarbon_fields_register_fields\b",
    r"\bContainer::make\s*\(",
    r"\bField::make\s*\(",
    r"->add_fields\s*\(",
    r"->add_tab\s*\(",
    r"carbon_get_post_meta\s*\(",
    r"carbon_set_post_meta\s*\(",
]

PROJECTS_HINTS = [
    r"post_type['\"]\s*=>\s*['\"]projects['\"]",
    r"\bprojects\b",
    r"\bproject\b",
    r"\bProject\b",
]

LOOKUP_HINTS = [
    r"\blookups?\b",
    r"\bdeveloper\b",
    r"\bdevelopers?\b",
    r"\btypes[-_ ]categories\b",
    r"\bhegzz\b",
    r"\baqarand\b",
]

AREA_HINTS = [
    r"\barea\b",
    r"\btotal\s*area\b",
    r"\bacres?\b",
    r"\bm2\b",
    r"m²",
    r"\bsqm\b",
    r"\bsquare\s*meters?\b",
]

TAB_HINTS = [
    r"->add_tab\s*\(",
    r"\btab\b",
    r"nav-tab",
    r"tabs?",
    r"metabox",
]

# ---------- Data model ----------
@dataclass
class Hit:
    file: str
    line: int
    col: int
    kind: str
    pattern: str
    excerpt: str

def iter_files(root: Path) -> List[Path]:
    out: List[Path] = []
    for dirpath, dirnames, filenames in os.walk(root):
        # prune
        parts = set(Path(dirpath).parts)
        if parts & SKIP_DIRS:
            continue
        # also prune nested skip dirs
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]

        for fn in filenames:
            p = Path(dirpath) / fn
            if p.suffix.lower() in PHP_EXTS or fn.lower().endswith(".php"):
                out.append(p)
    return out

def read_text(p: Path) -> str:
    try:
        return p.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return p.read_text(errors="replace")

def find_hits(text: str, patterns: List[str], kind: str, file: Path) -> List[Hit]:
    hits: List[Hit] = []
    lines = text.splitlines()
    for pat in patterns:
        rx = re.compile(pat, re.IGNORECASE)
        for i, line in enumerate(lines, start=1):
            m = rx.search(line)
            if not m:
                continue
            col = (m.start() + 1)
            excerpt = line.strip()
            hits.append(Hit(
                file=str(file),
                line=i,
                col=col,
                kind=kind,
                pattern=pat,
                excerpt=excerpt[:300],
            ))
    return hits

def context_block(text: str, line_no: int, radius: int = 10) -> str:
    lines = text.splitlines()
    start = max(1, line_no - radius)
    end = min(len(lines), line_no + radius)
    block = []
    for i in range(start, end + 1):
        prefix = ">>" if i == line_no else "  "
        block.append(f"{prefix} {i:5d} | {lines[i-1]}")
    return "\n".join(block)

def score_file(text: str, post_type: str) -> int:
    score = 0
    # carbon clues
    for pat in CARBON_HINTS:
        if re.search(pat, text, flags=re.IGNORECASE):
            score += 5
    # projects clues
    if re.search(rf"post_type['\"]\s*=>\s*['\"]{re.escape(post_type)}['\"]", text, flags=re.IGNORECASE):
        score += 20
    if re.search(rf"\b{re.escape(post_type)}\b", text, flags=re.IGNORECASE):
        score += 8
    if re.search(r"\bContainer::make\s*\(\s*['\"]post_meta['\"]", text, flags=re.IGNORECASE):
        score += 10
    if re.search(r"->where\s*\(\s*['\"]post_type['\"]", text, flags=re.IGNORECASE):
        score += 8
    if re.search(r"->add_tab\s*\(", text, flags=re.IGNORECASE):
        score += 10
    if re.search(r"\bdeveloper\b", text, flags=re.IGNORECASE):
        score += 4
    if re.search(r"\barea\b|\bacre\b|m²|\bm2\b", text, flags=re.IGNORECASE):
        score += 3
    return score

def extract_carbon_containers(text: str) -> List[Tuple[str, int]]:
    """
    Heuristic: find 'Container::make(' positions with line numbers.
    """
    lines = text.splitlines()
    out: List[Tuple[str, int]] = []
    rx = re.compile(r"Container::make\s*\(", re.IGNORECASE)
    for i, line in enumerate(lines, start=1):
        if rx.search(line):
            out.append((line.strip()[:200], i))
    return out

def extract_tabs_near(text: str, around_line: int, window: int = 200) -> List[Tuple[int, str]]:
    """
    Extract lines containing ->add_tab(...) in a window around a line.
    """
    lines = text.splitlines()
    start = max(1, around_line - window)
    end = min(len(lines), around_line + window)
    rx = re.compile(r"->add_tab\s*\(", re.IGNORECASE)
    out = []
    for i in range(start, end + 1):
        if rx.search(lines[i-1]):
            out.append((i, lines[i-1].strip()[:260]))
    return out

def extract_fields_near(text: str, around_line: int, window: int = 250) -> List[Tuple[int, str]]:
    lines = text.splitlines()
    start = max(1, around_line - window)
    end = min(len(lines), around_line + window)
    rx = re.compile(r"Field::make\s*\(", re.IGNORECASE)
    out = []
    for i in range(start, end + 1):
        if rx.search(lines[i-1]):
            out.append((i, lines[i-1].strip()[:260]))
    return out

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--root", default=".", help="theme root")
    ap.add_argument("--post-type", default="projects")
    ap.add_argument("--needle", default="", help="extra search needle")
    ap.add_argument("--verbose", action="store_true")
    args = ap.parse_args()

    root = Path(args.root).resolve()
    post_type = args.post_type.strip()

    files = iter_files(root)
    if not files:
        print("No PHP files found.", file=sys.stderr)
        sys.exit(1)

    all_hits: List[Hit] = []
    ranked: List[Tuple[int, Path]] = []

    # Scan pass 1: rank files
    for f in files:
        txt = read_text(f)
        sc = score_file(txt, post_type)
        if sc > 0:
            ranked.append((sc, f))

    ranked.sort(key=lambda x: (-x[0], str(x[1])))

    # Pick top candidates for deeper scanning
    top = ranked[:60]  # plenty
    if args.verbose:
        print("Top candidates:")
        for sc, f in top[:20]:
            print(f"{sc:3d}  {f}")

    # Scan pass 2: collect hits
    needle_patterns = []
    if args.needle.strip():
        safe = re.escape(args.needle.strip())
        needle_patterns = [safe]

    for sc, f in top:
        txt = read_text(f)
        all_hits += find_hits(txt, CARBON_HINTS, "carbon_hint", f)
        all_hits += find_hits(txt, [rf"post_type['\"]\s*=>\s*['\"]{re.escape(post_type)}['\"]"], "post_type_exact", f)
        all_hits += find_hits(txt, PROJECTS_HINTS, "projects_hint", f)
        all_hits += find_hits(txt, LOOKUP_HINTS, "lookup_hint", f)
        all_hits += find_hits(txt, AREA_HINTS, "area_hint", f)
        all_hits += find_hits(txt, TAB_HINTS, "tab_hint", f)
        if needle_patterns:
            all_hits += find_hits(txt, needle_patterns, "needle", f)

    # De-duplicate hits by (file,line,pattern)
    dedup: Dict[Tuple[str,int,str], Hit] = {}
    for h in all_hits:
        key = (h.file, h.line, h.pattern)
        if key not in dedup:
            dedup[key] = h
    hits = list(dedup.values())
    hits.sort(key=lambda h: (h.file, h.line, h.col))

    # Group hits by file
    by_file: Dict[str, List[Hit]] = {}
    for h in hits:
        by_file.setdefault(h.file, []).append(h)

    # Build report
    report_lines: List[str] = []
    report_lines.append(f"# Projects Edit Screen Analysis Report")
    report_lines.append("")
    report_lines.append(f"- Root: `{root}`")
    report_lines.append(f"- Target post_type: `{post_type}`")
    report_lines.append("")
    report_lines.append("## Top ranked files (likelihood of being the Projects edit screen / Carbon container)")
    report_lines.append("")
    for sc, f in ranked[:30]:
        report_lines.append(f"- **{sc:3d}** `{f}`")
    report_lines.append("")
    report_lines.append("## Detailed hits (grouped by file)")
    report_lines.append("")

    # For each relevant file, show: containers found, tabs near containers, and context for most important hits.
    for sc, f in ranked[:30]:
        fp = str(f)
        txt = read_text(f)
        if fp not in by_file:
            continue

        report_lines.append(f"### `{fp}`  (score={sc})")
        report_lines.append("")
        containers = extract_carbon_containers(txt)
        if containers:
            report_lines.append("**Carbon Containers found:**")
            for line_txt, ln in containers[:20]:
                report_lines.append(f"- line {ln}: `{line_txt}`")
            report_lines.append("")
        else:
            report_lines.append("_No obvious `Container::make(` found in this file._")
            report_lines.append("")

        # Find tabs around each container
        if containers:
            report_lines.append("**Tabs near containers (->add_tab):**")
            any_tabs = False
            for _, ln in containers[:10]:
                tabs = extract_tabs_near(txt, ln, window=220)
                if tabs:
                    any_tabs = True
                    report_lines.append(f"- Around container line {ln}:")
                    for tln, ttxt in tabs[:30]:
                        report_lines.append(f"  - line {tln}: `{ttxt}`")
            if not any_tabs:
                report_lines.append("- (none found near containers)")
            report_lines.append("")

        # Show a curated subset of hits for this file
        report_lines.append("**Key hits (first 80):**")
        for h in by_file[fp][:80]:
            report_lines.append(f"- line {h.line}: [{h.kind}] `{h.excerpt}`")
        report_lines.append("")

        # Context blocks for the most relevant patterns
        # prefer exact post_type, add_tab, Field::make and developer/area hints
        interesting = [h for h in by_file[fp] if h.kind in ("post_type_exact","tab_hint","area_hint","lookup_hint","carbon_hint")]
        if interesting:
            report_lines.append("**Context blocks (around most relevant hits):**")
            # pick up to 6 unique lines
            seen_lines = set()
            picked = []
            for h in interesting:
                if h.line in seen_lines:
                    continue
                seen_lines.add(h.line)
                picked.append(h)
                if len(picked) >= 6:
                    break
            for h in picked:
                report_lines.append(f"\n<details><summary>Context around line {h.line} ({h.kind})</summary>\n\n```php\n{context_block(txt, h.line, radius=14)}\n```\n\n</details>\n")
        report_lines.append("\n---\n")

    # Persist outputs
    out_md = root / "tools/_reports/projects_edit_screen_report.md"
    out_json = root / "tools/_reports/projects_edit_screen_hits.json"
    out_md.write_text("\n".join(report_lines), encoding="utf-8")
    out_json.write_text(json.dumps([asdict(h) for h in hits], ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"[OK] Wrote report: {out_md}")
    print(f"[OK] Wrote hits json: {out_json}")

    # Also print quick “actionable suspects”
    print("\n=== Actionable suspects (files that contain Container::make + projects + add_tab) ===")
    for sc, f in ranked[:80]:
        txt = read_text(f)
        if re.search(r"Container::make\s*\(", txt, flags=re.IGNORECASE) and \
           re.search(rf"\b{re.escape(post_type)}\b", txt, flags=re.IGNORECASE) and \
           re.search(r"->add_tab\s*\(", txt, flags=re.IGNORECASE):
            print(f"{sc:3d}  {f}")

    print("\n=== Next step ===")
    print("Open the report and send me:")
    print("1) The top 1-2 files that clearly define the projects container/tabs")
    print("2) The current tabs order (names) if found")
    print("Then I will generate the final python-based patcher that adds: Basics data tab + Total area + Developer lookup.")

if __name__ == "__main__":
    main()
