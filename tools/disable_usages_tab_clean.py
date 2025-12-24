#!/usr/bin/env python3
from __future__ import annotations

from pathlib import Path
import re
import subprocess
from datetime import datetime

ROOT = Path(__file__).resolve().parents[1]
FILE = ROOT / "app/inc/lookups/admin/types-categories-page.php"

def run(cmd: list[str]) -> tuple[int, str]:
    p = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
    return p.returncode, p.stdout

def lint_php(path: Path) -> tuple[bool, str]:
    code, out = run(["php", "-l", str(path)])
    return code == 0, out.strip()

def subn(s: str, pattern: str, repl: str, flags: int = 0, count: int = 0):
    new_s, n = re.subn(pattern, repl, s, count=count, flags=flags)
    return new_s, n

def main() -> None:
    if not FILE.exists():
        raise SystemExit(f"ERROR: file not found: {FILE}")

    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    bak = FILE.with_name(FILE.name + f".bak.clean-disable-usages.{ts}")
    bak.write_text(FILE.read_text(encoding="utf-8"), encoding="utf-8")

    s = FILE.read_text(encoding="utf-8")
    report: list[tuple[str, int]] = []

    # 0) تنظيف أي خراب قديم: أسطر بتبدأ بـ "=" أو "[];"
    s2, n = subn(s, r"(?m)^\s*=\s*", "")
    if n: report.append(("Strip stray leading '='", n)); s = s2
    s2, n = subn(s, r"(?m)^\s*\[\]\s*;\s*(?:\r?\n)", "")
    if n: report.append(("Remove lone [] lines", n)); s = s2

    # 1) Remove Usages list table class (if exists)
    s2, n = subn(
        s,
        r"(?s)\nclass\s+Hegzz_Usages_List_Table\b.*?\n(?=class\s+Hegzz_Property_Types_List_Table\b)",
        "\n",
    )
    if n: report.append(("Remove class Hegzz_Usages_List_Table block", n)); s = s2

    # 2) Remove entity_map mapping usages => usage
    s2, n = subn(s, r"(?m)^\s*'usages'\s*=>\s*'usage'\s*,\s*(?:\r?\n)", "")
    if n: report.append(("Remove entity_map: 'usages' => 'usage'", n)); s = s2

    # 3) Remove tabs label usages => Usages (if present)
    s2, n = subn(s, r"(?m)^\s*'usages'\s*=>\s*'Usages'\s*,\s*(?:\r?\n)", "")
    if n: report.append(("Remove tabs label: 'usages' => 'Usages'", n)); s = s2

    # 4) Remove usages select in form for property-types (if any remnants)
    s2, n = subn(
        s,
        r"(?s)\n\s*echo\s*'<div class=\"form-field\"><label>Usages</label><select name=\"usage_ids\[\]\".*?<\/select><\/div>';\s*\n",
        "\n",
    )
    if n: report.append(("Remove Usages select in form", n)); s = s2

    # 5) Remove any get_all_usages / sel usages lines (form area)
    s2, n = subn(s, r"(?m)^\s*\$usages\s*=\s*Hegzz_Lookups_Service::get_all_usages\(\);\s*(?:\r?\n)", "")
    if n: report.append(("Remove $usages = get_all_usages()", n)); s = s2
    s2, n = subn(s, r"(?m)^\s*\$sel_usages\s*=.*get_usages_for_property_type\(\$id\).*;\s*(?:\r?\n)", "")
    if n: report.append(("Remove $sel_usages = get_usages_for_property_type($id)", n)); s = s2

    # 6) POST handler: force usage_ids = [] safely
    # Replace any assignment to $usage_ids from POST
    s2, n = subn(
        s,
        r"(?m)^\s*\$usage_ids\s*=\s*isset\(\$_POST\['usage_ids'\][^;]*;\s*$",
        "            $usage_ids = [];",
    )
    if n: report.append(("POST: normalize $usage_ids assignment", n)); s = s2

    # If no $usage_ids assignment exists near $cat_ids in POST property-types block, inject it.
    # Look for the property-types POST block cat_ids line
    m = re.search(
        r"(?s)(\}\s*elseif\s*\(\s*\$tab\s*===\s*'property-types'\s*\)\s*\{\s*.*?\n\s*\$cat_ids\s*=.*?;\s*)",
        s
    )
    if m:
        start = m.end(1)
        window = s[start:start+400]
        if not re.search(r"\$usage_ids\s*=", window):
            s = s[:start] + "            $usage_ids = [];\n" + s[start:]
            report.append(("POST: inserted $usage_ids = [] after $cat_ids", 1))

    # 7) Neutralize any remaining get_usages_for_property_type(...) calls
    s2, n = subn(s, r"get_usages_for_property_type\([^\)]*\)", "[]")
    if n: report.append(("Neutralize get_usages_for_property_type(...)", n)); s = s2

    # 8) Ensure admin_init redirect hook exists (so direct URL tab=usages never opens)
    if "DISABLE USAGES TAB (admin_init)" not in s:
        hook = (
            "// === DISABLE USAGES TAB (admin_init) 2025-12-21 ===\n"
            "add_action('admin_init', function () {\n"
            "  if (!is_admin()) return;\n"
            "  $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';\n"
            "  $tab  = isset($_GET['tab'])  ? sanitize_text_field($_GET['tab'])  : '';\n"
            "  if ($page === 'hegzz-lookups-types' && $tab === 'usages') {\n"
            "    wp_safe_redirect(admin_url('admin.php?page=hegzz-lookups-types&tab=property-types'));\n"
            "    exit;\n"
            "  }\n"
            "});\n"
            "// === END DISABLE USAGES TAB ===\n\n"
        )
        s2, n = subn(s, r"<\?php\s*", "<?php\n\n" + hook, count=1)
        if n: report.append(("Insert admin_init redirect hook", n)); s = s2

    FILE.write_text(s, encoding="utf-8")

    ok, lint = lint_php(FILE)

    print("=== Clean Disable Usages Report ===")
    print(f"File:   {FILE}")
    print(f"Backup: {bak}")
    for desc, n in report:
        print(f"- {desc}: {n}")
    print("\n=== php -l ===")
    print(lint)

    if not ok:
        print("\nNOTE: php -l failed. Restore backup with:")
        print(f"cp -a '{bak}' '{FILE}'")
        raise SystemExit(1)

    # Quick verification grep
    checks = [
        r"tab=usages",
        r"Hegzz_Usages_List_Table",
        r"get_all_usages",
        r"get_usages_for_property_type",
        r'name="usage_ids\[\]"',
    ]
    print("\n=== Verification (should be empty) ===")
    any_hit = False
    for pat in checks:
        code, out = run(["rg", "-n", pat, str(FILE)])
        if out.strip():
            any_hit = True
            print(f"\n--- HIT: {pat} ---\n{out.strip()}")
    if not any_hit:
        print("OK: usages fully removed/disabled")

if __name__ == "__main__":
    main()
