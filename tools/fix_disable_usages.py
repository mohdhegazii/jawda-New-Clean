#!/usr/bin/env python3
import re
import shutil
import subprocess
from datetime import datetime
from pathlib import Path

ROOT = Path("/Applications/XAMPP/xamppfiles/htdocs/aqarand/wp-content/themes/aqarand")
FILE = ROOT / "app/inc/lookups/admin/types-categories-page.php"

def backup_file(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    bak = p.with_suffix(p.suffix + f".bak.pyfix.usages.{ts}")
    shutil.copy2(p, bak)
    return bak

def lint_php(p: Path) -> tuple[bool, str]:
    r = subprocess.run(["php", "-l", str(p)], capture_output=True, text=True)
    out = (r.stdout + r.stderr).strip()
    return (r.returncode == 0, out)

def subn(pattern: str, repl: str, s: str, flags=0, desc=""):
    new_s, n = re.subn(pattern, repl, s, flags=flags)
    return new_s, n, desc

def main():
    if not FILE.exists():
        raise SystemExit(f"File not found: {FILE}")

    original = FILE.read_text(encoding="utf-8", errors="replace")
    bak = backup_file(FILE)

    s = original
    report = []

    # 1) Fix broken line that starts with "=" inside PHP block
    s, n, d = subn(
        r"(?m)^\s*=\s*Hegzz_Lookups_Service::get_all_categories\(\);\s*",
        "    $cats = Hegzz_Lookups_Service::get_all_categories();\n",
        s,
        desc="Fix broken '= Hegzz_Lookups_Service::get_all_categories()' line"
    )
    report.append((d, n))

    # 2) Remove usages list-table class block
    s, n, d = subn(
        r"\nclass\s+Hegzz_Usages_List_Table\b.*?\n(?=class\s+Hegzz_Property_Types_List_Table\b)",
        "\n",
        s,
        flags=re.S,
        desc="Remove class Hegzz_Usages_List_Table block"
    )
    report.append((d, n))

    # 3) Remove usages from entity_map and tabs labels
    s, n, d = subn(
        r"(?m)^\s*'usages'\s*=>\s*'usage'\s*,\s*(?:\r?\n)",
        "",
        s,
        desc="Remove entity_map: 'usages' => 'usage'"
    )
    report.append((d, n))

    s, n, d = subn(
        r"(?m)^\s*'usages'\s*=>\s*'Usages'\s*,\s*(?:\r?\n)",
        "",
        s,
        desc="Remove tabs label: 'usages' => 'Usages'"
    )
    report.append((d, n))

    # 4) Remove usages select from property-types form (echo block)
    s, n, d = subn(
        r"\n\s*echo\s*'<div class=\"form-field\"><label>Usages</label><select name=\"usage_ids\[\]\"[\s\S]*?<\/select><\/div>';\s*\n",
        "\n",
        s,
        flags=re.S,
        desc="Remove Usages select in form"
    )
    report.append((d, n))

    # 5) Remove $usages/$sel_usages lines if present
    s, n, d = subn(
        r"(?m)^\s*\$usages\s*=\s*Hegzz_Lookups_Service::get_all_usages\(\)\s*;\s*(?:\r?\n)",
        "",
        s,
        desc="Remove $usages = get_all_usages()"
    )
    report.append((d, n))

    s, n, d = subn(
        r"(?m)^\s*\$sel_usages\s*=\s*\$id\s*>\s*0\s*\?\s*Hegzz_Lookups_Service::get_usages_for_property_type\(\$id\)\s*:\s*\[\]\s*;\s*(?:\r?\n)",
        "",
        s,
        desc="Remove $sel_usages = get_usages_for_property_type(...)"
    )
    report.append((d, n))

    # 6) Force any POST usage_ids to empty array
    s, n, d = subn(
        r"(?m)^\s*\$usage_ids\s*=\s*isset\(\$_POST\[['\"]usage_ids['\"]\]\)[^;]*;\s*$",
        "            $usage_ids = [];",
        s,
        desc="POST: force $usage_ids = []"
    )
    report.append((d, n))

    # 7) Replace existing_usages fetch with []
    s, n, d = subn(
        r"(?m)^\s*\$existing_usages\s*=\s*Hegzz_Lookups_Service::get_usages_for_property_type\([^)]+\)\s*;\s*(?:\r?\n)",
        "            $existing_usages = [];\n",
        s,
        desc="Replace existing_usages fetch with []"
    )
    report.append((d, n))

    # 8) Neutralize any remaining get_usages_for_property_type(...) calls
    s, n, d = subn(
        r"Hegzz_Lookups_Service::get_usages_for_property_type\([^)]+\)",
        "[]",
        s,
        desc="Neutralize get_usages_for_property_type(...)"
    )
    report.append((d, n))

    # 9) Insert admin_init redirect hook once (to disable usages tab)
    if "DISABLE USAGES TAB (admin_init)" not in s:
        insert = (
            "\n// === DISABLE USAGES TAB (admin_init) 2025-12-21 ===\n"
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
        s2, n = re.subn(r"<\?php\s*", "<?php\n" + insert, s, count=1)
        s = s2
        report.append(("Insert admin_init redirect hook", n))
    else:
        report.append(("Insert admin_init redirect hook (already exists)", 0))

    FILE.write_text(s, encoding="utf-8")

    ok, lint_out = lint_php(FILE)

    print("=== Python Fix Report ===")
    print(f"File: {FILE}")
    print(f"Backup: {bak}")
    for desc, n in report:
        print(f"- {desc}: {n}")
    print("\n=== php -l ===")
    print(lint_out)

    if not ok:
        print("\nNOTE: php -l failed. Restore backup with:")
        print(f"cp -a '{bak}' '{FILE}'")
        raise SystemExit(1)

if __name__ == "__main__":
    main()
