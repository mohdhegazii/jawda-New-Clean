#!/usr/bin/env python3
from __future__ import annotations

import os, re, sys, shutil
from pathlib import Path
from datetime import datetime

THEME_ROOT = Path(os.getcwd()).resolve()

# الملفات "الأساسية" اللي غالباً لعبنا فيها أثناء شغل الكونتينرز/الميتابوكس/اللوكوپس
KEY_FILES = [
    Path("app/functions/meta_box.php"),
    Path("app/inc/admin/metaboxes/project-property-models.php"),
    Path("app/inc/lookups/admin/types-categories-page.php"),
    Path("app/inc/lookups/admin/ajax/property-types-ajax.php"),
    Path("app/inc/lookups/admin/ajax/property-models-ajax.php"),
]

# كلمات نعتبرها "علامات" إن الفورم/الكونتينر موجود (تقدر تزودها)
SIGNALS = [
    "Container::make(",                 # Carbon Fields containers
    "carbon_fields_register_fields",
    "add_meta_box(",
    "Property Models",
    "hegzz_pm_get_property_types",
    "hegzz_pm_get_sub_properties",
    "Types & Categories",
]

BACKUP_PAT = re.compile(r"\.(bak|broken)\.", re.I)

def eprint(*a): print(*a, file=sys.stderr)

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(s, encoding="utf-8")

def ts() -> str:
    return datetime.now().strftime("%Y%m%d-%H%M%S")

def find_backups_for(target: Path) -> list[Path]:
    """
    يرجع كل الباكابس لنفس الملف:
    meta_box.php.bak.2025...
    types-categories-page.php.broken.2025...
    """
    t = (THEME_ROOT / target)
    if not t.exists():
        return []
    # أي ملف يبدأ بنفس الاسم ويحتوي .bak. أو .broken.
    parent = t.parent
    base = t.name
    out = []
    for p in parent.iterdir():
        if not p.is_file():
            continue
        if p.name.startswith(base) and BACKUP_PAT.search(p.name):
            out.append(p)
    # الأحدث للأقدم حسب وقت التعديل
    out.sort(key=lambda x: x.stat().st_mtime, reverse=True)
    return out

def backup_current(target: Path) -> Path:
    src = THEME_ROOT / target
    b = src.with_name(src.name + f".rollback_snapshot.{ts()}")
    if src.exists():
        shutil.copy2(src, b)
    return b

def restore(target: Path, backup_file: Path) -> None:
    dst = THEME_ROOT / target
    if not backup_file.exists():
        raise FileNotFoundError(str(backup_file))
    dst.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(backup_file, dst)

def check_signals(paths: list[Path]) -> dict[str, int]:
    counts = {s: 0 for s in SIGNALS}
    for rel in paths:
        p = THEME_ROOT / rel
        if not p.exists():
            continue
        txt = read(p)
        for s in SIGNALS:
            if s in txt:
                counts[s] += 1
    return counts

def print_check(counts: dict[str, int]) -> None:
    print("\n[CHECK] Signals found across key files:")
    for k, v in counts.items():
        print(f"  - {k}: {v}")

def main() -> None:
    if not (THEME_ROOT / "app").exists():
        eprint("ERROR: Run from theme root (where app/ exists).")
        sys.exit(1)

    # جمع backups لكل key file
    all_candidates: dict[str, list[Path]] = {}
    for rel in KEY_FILES:
        bks = find_backups_for(rel)
        all_candidates[str(rel)] = bks

    print("Theme root:", THEME_ROOT)
    print("\nKey files:")
    for rel in KEY_FILES:
        print(" -", rel)

    print("\nBackups found:")
    for rel, bks in all_candidates.items():
        print(f"\n== {rel} ==")
        if not bks:
            print("  (none)")
            continue
        for i, p in enumerate(bks[:15], start=1):
            print(f"  {i:02d}) {p.name}")

    print("\nHow rollback will work:")
    print(" - We roll back ALL key files together using the SAME index (1 = newest backup per file).")
    print(" - If a file has no backup at that index, it is skipped for that step.")
    print("\nCommands:")
    print("  python3 rollback_backups.py apply 1")
    print("  python3 rollback_backups.py apply 2")
    print("  python3 rollback_backups.py apply 3")
    print("  ... (keep going until the form returns)")

    if len(sys.argv) < 3:
        print("\n(No action taken. Use apply N)")
        return

    action = sys.argv[1].strip().lower()
    idx = int(sys.argv[2].strip())
    if action != "apply" or idx < 1:
        eprint("Usage: python3 rollback_backups.py apply N")
        sys.exit(2)

    print(f"\n[APPLY] Rolling back to backup index #{idx} (1=newest)...")

    # snapshot current files first
    snap_dir = THEME_ROOT / f".rollback_snapshots_{ts()}"
    snap_dir.mkdir(parents=True, exist_ok=True)

    applied = 0
    for rel in KEY_FILES:
        rel_str = str(rel)
        backups = all_candidates.get(rel_str, [])
        if len(backups) < idx:
            print(f" - SKIP {rel_str} (no backup #{idx})")
            continue

        # snapshot current into snap dir
        cur = THEME_ROOT / rel
        if cur.exists():
            shutil.copy2(cur, snap_dir / cur.name)
        bk = backups[idx - 1]
        restore(rel, bk)
        print(f" - RESTORED {rel_str} <= {bk.name}")
        applied += 1

    print(f"\n[OK] Applied restores: {applied}")
    print(f"[OK] Snapshot of previous current files saved in: {snap_dir}")

    counts = check_signals(KEY_FILES)
    print_check(counts)

    print("\nNext:")
    print("1) افتح wp-admin وجرّب صفحة الفورم/الكونتينر.")
    print("2) لو لسه مش شغال: جرّب النسخة اللي بعدها:")
    print(f"   python3 rollback_backups.py apply {idx+1}")

if __name__ == "__main__":
    main()
