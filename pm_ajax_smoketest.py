#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import re
import sys
import urllib.parse
import urllib.request
from typing import Dict, Tuple

BASE_URL = os.environ.get("BASE_URL", "http://localhost/aqarand").rstrip("/")
AJAX_URL = f"{BASE_URL}/wp-admin/admin-ajax.php"

def http_get(url: str, cookie: str) -> Tuple[int, str]:
    req = urllib.request.Request(url, method="GET")
    req.add_header("User-Agent", "pm-smoketest/1.0")
    if cookie:
        req.add_header("Cookie", cookie)
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return r.status, r.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace") if e.fp else ""
        return e.code, body

def http_post(url: str, data: Dict[str, str], cookie: str) -> Tuple[int, str]:
    payload = urllib.parse.urlencode(data, doseq=True).encode("utf-8")
    req = urllib.request.Request(url, data=payload, method="POST")
    req.add_header("Content-Type", "application/x-www-form-urlencoded; charset=utf-8")
    req.add_header("User-Agent", "pm-smoketest/1.0")
    if cookie:
        req.add_header("Cookie", cookie)
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return r.status, r.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace") if e.fp else ""
        return e.code, body

def pretty_json(s: str) -> str:
    try:
        return json.dumps(json.loads(s), ensure_ascii=False, indent=2)
    except Exception:
        return s

def extract_pm_nonce(html: str) -> str:
    """
    Extract nonce for PM AJAX calls.
    Supports:
      1) Hidden input id/name: hegzz_pm_ajax_nonce
      2) JS assignment: PM_NONCE = "..."
    """
    # Hidden input: id="hegzz_pm_ajax_nonce" value="...."
    patterns = [
        r"id=['\"]hegzz_pm_ajax_nonce['\"][^>]*value=['\"]([^'\"]+)['\"]",
        r"name=['\"]hegzz_pm_ajax_nonce['\"][^>]*value=['\"]([^'\"]+)['\"]",
    ]
    for pat in patterns:
        m = re.search(pat, html, flags=re.IGNORECASE)
        if m:
            return m.group(1).strip()

    # JS: PM_NONCE = "...."
    js_patterns = [
        r"PM_NONCE\s*=\s*['\"]([^'\"]+)['\"]",
        r"window\.PM_NONCE\s*=\s*['\"]([^'\"]+)['\"]",
        r"var\s+PM_NONCE\s*=\s*['\"]([^'\"]+)['\"]",
        r"const\s+PM_NONCE\s*=\s*['\"]([^'\"]+)['\"]",
        r"let\s+PM_NONCE\s*=\s*['\"]([^'\"]+)['\"]",
    ]
    for pat in js_patterns:
        m = re.search(pat, html)
        if m:
            return m.group(1).strip()

    return ""

def main() -> None:
    cookie = os.environ.get("WP_COOKIE", "").strip()
    post_id = os.environ.get("POST_ID", "").strip()
    cat_ids = os.environ.get("CAT_IDS", "").strip()

    if not post_id:
        print("ERROR: POST_ID env var is required. Example: POST_ID=123 CAT_IDS=1,2 python3 pm_ajax_smoketest.py", file=sys.stderr)
        sys.exit(1)

    edit_url = f"{BASE_URL}/wp-admin/post.php?post={post_id}&action=edit"
    print("[1] GET Project edit page to extract nonce:")
    print(f"    {edit_url}")
    st, html = http_get(edit_url, cookie)
    print(f"    Status: {st}")

    nonce = extract_pm_nonce(html)
    if not nonce:
        print("\nERROR: Nonce not found in page HTML.")
        print("Checklist:")
        print(" - Ensure WP_COOKIE is REAL (copied from wp-admin request headers).")
        print(" - Ensure the PM metabox is loaded and prints hidden nonce input OR PM_NONCE JS.")
        print(" - Try: curl -sS -H \"Cookie: $WP_COOKIE\" \"<edit_url>\" | rg -n \"hegzz_pm_ajax_nonce|PM_NONCE|Property Models\"")
        sys.exit(2)

    print(f"\n[OK] Nonce: {nonce}")

    # Build category ids
    if not cat_ids:
        cat_ids = "1"
    cat_list = [c.strip() for c in cat_ids.split(",") if c.strip()]

    # [2] types
    print("\n[2] POST admin-ajax.php: hegzz_pm_get_property_types")
    data = [("action", "hegzz_pm_get_property_types"), ("nonce", nonce)]
    # send category_ids as repeated keys to match (array) $_POST['category_ids']
    for c in cat_list:
        data.append(("category_ids[]", c))

    st2, body2 = http_post(AJAX_URL, dict(data), cookie)  # dict keeps last; so we do manual:
    # Fix: urllib.parse.urlencode with doseq needs list values
    data_dict = {"action": "hegzz_pm_get_property_types", "nonce": nonce, "category_ids": cat_list}
    st2, body2 = http_post(AJAX_URL, data_dict, cookie)
    print(f"    Status: {st2}")
    print("    Body:")
    print(pretty_json(body2))

if __name__ == "__main__":
    main()
