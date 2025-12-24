#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import re, sys, shutil, time
from pathlib import Path
from dataclasses import dataclass
from typing import List, Tuple

ROOT = Path.cwd().resolve()
STAMP = time.strftime("%Y%m%d-%H%M%S")
MARKER = "HEGZZ_PROPERTY_MODELS_LOOKUP__INSTALLED"

FILES = {
    "db_update": ROOT / "app/inc/lookups/db-update.php",
    "service": ROOT / "app/inc/lookups/class-hegzz-lookups-service.php",
    "api": ROOT / "app/inc/lookups/api.php",
    "admin": ROOT / "app/inc/lookups/admin/types-categories-page.php",
    "verify": ROOT / "tools/verify_lookups.php",
}

@dataclass
class Change:
    path: Path
    backup: Path

def die(msg: str, code: int = 1):
    print(f"\n[ERROR] {msg}\n", file=sys.stderr)
    sys.exit(code)

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace").replace("\r\n", "\n").replace("\r", "\n")

def write(p: Path, s: str):
    p.write_text(s, encoding="utf-8")

def backup_file(p: Path) -> Change:
    b = p.with_suffix(p.suffix + f".bak.{STAMP}")
    shutil.copy2(p, b)
    return Change(path=p, backup=b)

def restore(changes: List[Change]):
    for c in changes[::-1]:
        try:
            shutil.copy2(c.backup, c.path)
        except Exception as e:
            print(f"[WARN] restore failed {c.path}: {e}", file=sys.stderr)

def ensure_root():
    if not (ROOT / "style.css").exists():
        die("Not in theme root (style.css not found). cd into theme root and rerun.")
    missing = [k for k,p in FILES.items() if not p.exists()]
    if missing:
        die("Missing required files: " + ", ".join(missing))

def run_php_lint(paths: List[Path]):
    import subprocess
    for p in paths:
        if p.suffix.lower() != ".php":
            continue
        r = subprocess.run(["php", "-l", str(p)], capture_output=True, text=True)
        if r.returncode != 0:
            raise RuntimeError(f"PHP lint failed for {p}:\n{r.stdout}\n{r.stderr}")

# ---------------- SNIPPETS ----------------
DB_HELPER = r"""
/* === HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
// %s
function hegzz_lookups_install_property_models_tables($wpdb) {
    $charset_collate = $wpdb->get_charset_collate();
    $t_models = $wpdb->prefix . 'hegzz_property_models';
    $t_pm_cats = $wpdb->prefix . 'hegzz_property_model_categories';

    $sql1 = "CREATE TABLE IF NOT EXISTS {$t_models} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name_ar VARCHAR(190) NOT NULL DEFAULT '',
        name_en VARCHAR(190) NOT NULL DEFAULT '',
        slug VARCHAR(190) NOT NULL,
        property_type_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        bedrooms TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
        icon VARCHAR(190) NULL DEFAULT NULL,
        is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NULL DEFAULT NULL,
        updated_at DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY property_type_id (property_type_id),
        KEY bedrooms (bedrooms),
        KEY is_active (is_active)
    ) {$charset_collate};";

    $sql2 = "CREATE TABLE IF NOT EXISTS {$t_pm_cats} (
        property_model_id BIGINT(20) UNSIGNED NOT NULL,
        category_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY (property_model_id, category_id),
        KEY category_id (category_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql1);
    dbDelta($sql2);
}
/* === END HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
""".strip("\n") % MARKER

DB_CALL = r"""
    // Property Models tables (AUTO)
    if (function_exists('hegzz_lookups_install_property_models_tables')) { hegzz_lookups_install_property_models_tables($wpdb); }
""".strip("\n")

SERVICE_BLOCK = r"""
    /* === HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */

    public function get_property_models($args = []) {
        global $wpdb;
        $t_models = $wpdb->prefix . 'hegzz_property_models';
        $t_pm_cats = $wpdb->prefix . 'hegzz_property_model_categories';

        $defaults = [
            'is_active' => null,
            'search' => '',
            'limit' => 500,
            'offset' => 0,
        ];
        $args = array_merge($defaults, is_array($args) ? $args : []);

        $where = "1=1";
        $params = [];

        if ($args['is_active'] !== null) {
            $where .= " AND is_active = %d";
            $params[] = (int)$args['is_active'];
        }
        if (!empty($args['search'])) {
            $where .= " AND (name_ar LIKE %s OR name_en LIKE %s OR slug LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $limit = max(1, min(2000, (int)$args['limit']));
        $offset = max(0, (int)$args['offset']);

        $sql = "SELECT * FROM {$t_models} WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        if (!empty($rows)) {
            $ids = array_map(fn($r) => (int)$r['id'], $rows);
            $ids_in = implode(',', array_fill(0, count($ids), '%d'));
            $q = $wpdb->prepare("SELECT property_model_id, category_id FROM {$t_pm_cats} WHERE property_model_id IN ({$ids_in})", ...$ids);
            $pairs = $wpdb->get_results($q, ARRAY_A);
            $map = [];
            foreach ($pairs as $p) {
                $mid = (int)$p['property_model_id'];
                $map[$mid][] = (int)$p['category_id'];
            }
            foreach ($rows as &$r) {
                $r['category_ids'] = $map[(int)$r['id']] ?? [];
            }
        }

        return $rows ?: [];
    }

    public function upsert_property_model($data) {
        global $wpdb;
        $t_models = $wpdb->prefix . 'hegzz_property_models';

        $data = is_array($data) ? $data : [];
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        $name_ar = isset($data['name_ar']) ? sanitize_text_field($data['name_ar']) : '';
        $name_en = isset($data['name_en']) ? sanitize_text_field($data['name_en']) : '';
        $slug = isset($data['slug']) ? sanitize_title($data['slug']) : '';
        $property_type_id = isset($data['property_type_id']) ? (int)$data['property_type_id'] : 0;
        $bedrooms = isset($data['bedrooms']) ? (int)$data['bedrooms'] : 0;
        $icon = isset($data['icon']) && $data['icon'] !== '' ? sanitize_text_field($data['icon']) : null;
        $is_active = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

        if ($slug === '') {
            $slug = sanitize_title($name_en ?: $name_ar ?: ('model-' . time()));
        }

        $now = current_time('mysql');

        $row = [
            'name_ar' => $name_ar,
            'name_en' => $name_en,
            'slug' => $slug,
            'property_type_id' => $property_type_id,
            'bedrooms' => max(0, $bedrooms),
            'icon' => $icon,
            'is_active' => $is_active,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $wpdb->update($t_models, $row, ['id' => $id]);
            return $id;
        }

        $row['created_at'] = $now;
        $wpdb->insert($t_models, $row);
        return (int)$wpdb->insert_id;
    }

    public function delete_property_model($id) {
        global $wpdb;
        $id = (int)$id;
        if ($id <= 0) return false;

        $t_models = $wpdb->prefix . 'hegzz_property_models';
        $t_pm_cats = $wpdb->prefix . 'hegzz_property_model_categories';

        $wpdb->delete($t_pm_cats, ['property_model_id' => $id]);
        $wpdb->delete($t_models, ['id' => $id]);
        return true;
    }

    public function set_property_model_categories($model_id, $category_ids) {
        global $wpdb;
        $model_id = (int)$model_id;
        $t_pm_cats = $wpdb->prefix . 'hegzz_property_model_categories';

        $category_ids = is_array($category_ids) ? $category_ids : [];
        $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids), fn($v)=>$v>0)));

        $wpdb->delete($t_pm_cats, ['property_model_id' => $model_id]);

        foreach ($category_ids as $cid) {
            $wpdb->insert($t_pm_cats, [
                'property_model_id' => $model_id,
                'category_id' => $cid,
            ]);
        }
        return true;
    }

    /* === END HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
""".strip("\n")

API_BLOCK = r"""
/* === HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
add_action('wp_ajax_hegzz_pm_types_for_categories', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    global $wpdb;
    $cat_ids = isset($_POST['category_ids']) ? (array) $_POST['category_ids'] : [];
    $cat_ids = array_values(array_unique(array_filter(array_map('intval', $cat_ids), fn($v)=>$v>0)));

    if (empty($cat_ids)) {
        wp_send_json_success(['items' => []]);
    }

    $cache_group = 'hegzz_lookups';
    $cache_key = 'pm_types_for_cats_' . md5(implode(',', $cat_ids));
    $cached = wp_cache_get($cache_key, $cache_group);
    if ($cached !== false) {
        wp_send_json_success(['items' => $cached]);
    }

    $t_types = $wpdb->prefix . 'hegzz_property_types';
    $t_ptc = $wpdb->prefix . 'hegzz_property_type_categories';

    $in = implode(',', array_fill(0, count($cat_ids), '%d'));
    $sql = "
        SELECT DISTINCT t.id, t.name_ar, t.name_en, t.slug
        FROM {$t_types} t
        INNER JOIN {$t_ptc} ptc ON ptc.property_type_id = t.id
        WHERE ptc.category_id IN ({$in})
        ORDER BY t.id DESC
    ";
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$cat_ids), ARRAY_A);
    $rows = $rows ?: [];

    wp_cache_set($cache_key, $rows, $cache_group, 3600);
    wp_send_json_success(['items' => $rows]);
});
/* === END HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
""".strip("\n")

# verify injection lines (NO semicolons inside array literal!)
VERIFY_ITEMS = [
    "$wpdb->prefix . 'hegzz_property_models'",
    "$wpdb->prefix . 'hegzz_property_model_categories'",
]

ADMIN_HANDLER_MIN = r"""
/* === HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
if ($tab === 'property-models') {
    // Minimal first version: CRUD via service, categories multi, type id manual (AJAX endpoint prepared)
    if (isset($_POST['hegzz_pm_action']) && $_POST['hegzz_pm_action'] === 'save') {
        if (!current_user_can('manage_options')) { wp_die('forbidden'); }
        check_admin_referer('hegzz_pm_save');

        $service = hegzz_get_lookups_service();

        $model_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $category_ids = isset($_POST['category_ids']) ? (array)$_POST['category_ids'] : [];
        $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids), fn($v)=>$v>0)));

        $property_type_id = isset($_POST['property_type_id']) ? (int)$_POST['property_type_id'] : 0;

        $data = [
            'id' => $model_id,
            'name_ar' => isset($_POST['name_ar']) ? wp_unslash($_POST['name_ar']) : '',
            'name_en' => isset($_POST['name_en']) ? wp_unslash($_POST['name_en']) : '',
            'slug' => isset($_POST['slug']) ? wp_unslash($_POST['slug']) : '',
            'property_type_id' => $property_type_id,
            'bedrooms' => isset($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 0,
            'icon' => isset($_POST['icon']) ? wp_unslash($_POST['icon']) : '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Validate type belongs to selected categories (server-side guard)
        if (!empty($category_ids) && $property_type_id > 0) {
            global $wpdb;
            $t_ptc = $wpdb->prefix . 'hegzz_property_type_categories';
            $in = implode(',', array_fill(0, count($category_ids), '%d'));
            $sql = "SELECT COUNT(*) FROM {$t_ptc} WHERE property_type_id = %d AND category_id IN ({$in})";
            $count = $wpdb->get_var($wpdb->prepare($sql, $property_type_id, ...$category_ids));
            if ((int)$count <= 0) {
                $data['property_type_id'] = 0;
            }
        }

        $new_id = $service->upsert_property_model($data);
        if ($new_id) { $service->set_property_model_categories($new_id, $category_ids); }

        wp_cache_flush();
        wp_safe_redirect(add_query_arg(['tab' => 'property-models', 'updated' => 1], menu_page_url('hegzz-lookups', false)));
        exit;
    }

    if (isset($_GET['hegzz_pm_delete']) && (int)$_GET['hegzz_pm_delete'] > 0) {
        if (!current_user_can('manage_options')) { wp_die('forbidden'); }
        check_admin_referer('hegzz_pm_delete');

        $service = hegzz_get_lookups_service();
        $service->delete_property_model((int)$_GET['hegzz_pm_delete']);
        wp_cache_flush();

        wp_safe_redirect(add_query_arg(['tab' => 'property-models', 'deleted' => 1], menu_page_url('hegzz-lookups', false)));
        exit;
    }

    $service = hegzz_get_lookups_service();
    $models = $service->get_property_models(['limit' => 500]);

    global $wpdb;
    $cats = $wpdb->get_results("SELECT id, name_ar, name_en, slug FROM {$wpdb->prefix}hegzz_categories ORDER BY id DESC", ARRAY_A);
    ?>
    <div class="wrap">
      <h1>Property Models</h1>

      <?php if (isset($_GET['updated'])): ?><div class="notice notice-success"><p>Saved.</p></div><?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?><div class="notice notice-success"><p>Deleted.</p></div><?php endif; ?>

      <h2>Add / Update Model</h2>
      <form method="post">
        <?php wp_nonce_field('hegzz_pm_save'); ?>
        <input type="hidden" name="hegzz_pm_action" value="save" />

        <table class="form-table">
          <tr><th><label>ID (edit)</label></th><td><input type="number" name="id" value="" min="0" /></td></tr>
          <tr><th><label>Name AR</label></th><td><input type="text" name="name_ar" class="regular-text" required /></td></tr>
          <tr><th><label>Name EN</label></th><td><input type="text" name="name_en" class="regular-text" /></td></tr>
          <tr><th><label>Slug</label></th><td><input type="text" name="slug" class="regular-text" placeholder="auto if empty" /></td></tr>
          <tr>
            <th><label>Categories (multi)</label></th>
            <td>
              <select name="category_ids[]" multiple size="6" style="min-width:320px">
                <?php foreach ($cats as $c): ?>
                  <option value="<?php echo esc_attr($c['id']); ?>"><?php echo esc_html(($c['name_en'] ?: $c['name_ar']) . ' (#' . $c['id'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr><th><label>Property Type ID</label></th><td><input type="number" name="property_type_id" value="0" min="0" /></td></tr>
          <tr><th><label>Bedrooms</label></th><td><input type="number" name="bedrooms" value="0" min="0" max="50" /></td></tr>
          <tr><th><label>Icon (optional)</label></th><td><input type="text" name="icon" class="regular-text" /></td></tr>
          <tr><th><label>Active</label></th><td><label><input type="checkbox" name="is_active" checked /> Active</label></td></tr>
        </table>

        <?php submit_button('Save Model'); ?>
      </form>

      <hr />

      <h2>Existing Models</h2>
      <table class="widefat striped">
        <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Type ID</th><th>Bedrooms</th><th>Categories</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($models as $m): ?>
          <tr>
            <td><?php echo (int)$m['id']; ?></td>
            <td><?php echo esc_html(($m['name_en'] ?: $m['name_ar'])); ?></td>
            <td><?php echo esc_html($m['slug']); ?></td>
            <td><?php echo (int)$m['property_type_id']; ?></td>
            <td><?php echo (int)$m['bedrooms']; ?></td>
            <td><?php echo esc_html(implode(',', $m['category_ids'] ?? [])); ?></td>
            <td><?php echo ((int)$m['is_active'] ? 'Yes' : 'No'); ?></td>
            <td>
              <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['tab'=>'property-models','hegzz_pm_delete'=>(int)$m['id']], menu_page_url('hegzz-lookups', false)), 'hegzz_pm_delete')); ?>" onclick="return confirm('Delete?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    return;
}
/* === END HEGZZ PROPERTY MODELS LOOKUP (AUTO) === */
""".strip("\n")

# --------------- patch helpers ---------------
def insert_before_last(s: str, insert: str) -> str:
    i = s.rfind("}")
    if i == -1:
        return s + "\n" + insert + "\n"
    return s[:i] + "\n\n" + insert + "\n\n" + s[i:]

def patch_db_update(s: str) -> str:
    if "hegzz_property_models" in s or MARKER in s:
        return s
    # add helper function near end (before ?> if exists)
    helper = DB_HELPER
    if "?>" in s:
        s = s.replace("?>", helper + "\n?>")
    else:
        s = s.rstrip() + "\n\n" + helper + "\n"
    # add call after last dbDelta occurrence
    lines = s.splitlines()
    idx = -1
    for i,l in enumerate(lines):
        if "dbDelta(" in l:
            idx = i
    if idx == -1:
        die("db-update.php: couldn't find dbDelta() to hook property models install.")
    lines.insert(idx+1, DB_CALL)
    return "\n".join(lines) + "\n"

def patch_service(s: str) -> str:
    if "upsert_property_model" in s:
        return s
    return insert_before_last(s, SERVICE_BLOCK)

def patch_api(s: str) -> str:
    if "hegzz_pm_types_for_categories" in s:
        return s
    # add after last wp_ajax line if exists else append
    lines = s.splitlines()
    idx = -1
    for i,l in enumerate(lines):
        if "add_action('wp_ajax" in l or 'add_action("wp_ajax' in l:
            idx = i
    if idx != -1:
        lines.insert(idx+1, API_BLOCK)
        return "\n".join(lines) + "\n"
    return s.rstrip() + "\n\n" + API_BLOCK + "\n"

def patch_admin(s: str) -> str:
    if "property-models" in s:
        return s

    # Add tab entry after sub-properties
    tab_entry = "        'property-models' => 'Property Models',"
    injected = False

    patterns = [
        r"(\n\s*['\"]sub-properties['\"]\s*=>\s*[^,\n]+,\s*)",
        r"(\n\s*['\"]sub_properties['\"]\s*=>\s*[^,\n]+,\s*)",
    ]
    for pat in patterns:
        s2, n = re.subn(pat, r"\1\n" + tab_entry + "\n", s, count=1, flags=re.I)
        if n == 1:
            s = s2
            injected = True
            break

    if not injected:
        die("admin/types-categories-page.php: couldn't locate tabs array to add property-models after sub-properties.")

    # Inject handler near end (safe)
    return insert_before_last(s, ADMIN_HANDLER_MIN)

def patch_verify(s: str) -> str:
    if "hegzz_property_models" in s:
        return s

    # Remove any previous broken injection patterns (safety)
    s = re.sub(r".*hegzz_property_models.*\n", "", s)
    s = re.sub(r".*hegzz_property_model_categories.*\n", "", s)

    # Strategy A: $tables = [ ... ];
    m = re.search(r"(\$tables\s*=\s*\[\s*)([\s\S]*?)(\n\s*\]\s*;)", s)
    if m:
        head, body, tail = m.group(1), m.group(2), m.group(3)
        # ensure body ends with comma if non-empty meaningful
        body_stripped = body.rstrip()
        if body_stripped and not body_stripped.rstrip().endswith(","):
            body_stripped = body_stripped + ","
        add = "\n    " + ",\n    ".join(VERIFY_ITEMS) + ",\n"
        new_block = head + body_stripped + add + tail
        s = s[:m.start()] + new_block + s[m.end():]
        return s

    # Strategy B: $tables = array( ... );
    m = re.search(r"(\$tables\s*=\s*array\s*\(\s*)([\s\S]*?)(\n\s*\)\s*;)", s, flags=re.I)
    if m:
        head, body, tail = m.group(1), m.group(2), m.group(3)
        body_stripped = body.rstrip()
        if body_stripped and not body_stripped.rstrip().endswith(","):
            body_stripped = body_stripped + ","
        add = "\n    " + ",\n    ".join(VERIFY_ITEMS) + ",\n"
        new_block = head + body_stripped + add + tail
        s = s[:m.start()] + new_block + s[m.end():]
        return s

    # Strategy C: $tables[] = ...
    if "$tables[]" in s:
        lines = s.splitlines()
        idx = -1
        for i,l in enumerate(lines):
            if "$tables[]" in l:
                idx = i
        ins = [
            f"$tables[] = {VERIFY_ITEMS[0]};",
            f"$tables[] = {VERIFY_ITEMS[1]};",
        ]
        lines[idx+1:idx+1] = ins
        return "\n".join(lines) + "\n"

    die("tools/verify_lookups.php: couldn't find $tables definition to safely insert new tables.")
    return s

def main():
    ensure_root()

    changes: List[Change] = []
    try:
        for p in FILES.values():
            changes.append(backup_file(p))

        write(FILES["db_update"], patch_db_update(read(FILES["db_update"])))
        write(FILES["service"], patch_service(read(FILES["service"])))
        write(FILES["api"], patch_api(read(FILES["api"])))
        write(FILES["admin"], patch_admin(read(FILES["admin"])))
        write(FILES["verify"], patch_verify(read(FILES["verify"])))

        run_php_lint(list(FILES.values()))

    except Exception as e:
        print(f"\n[FAIL] {e}", file=sys.stderr)
        print("[ROLLBACK] restoring backups...", file=sys.stderr)
        restore(changes)
        print("[ROLLBACK] done.", file=sys.stderr)
        sys.exit(2)

    print("\n[SUCCESS] Property Models lookup installed.")
    print("Backups:")
    for c in changes:
        print(" -", c.backup.name)

    print("\nNext:")
    print("  php tools/verify_lookups.php || true")

if __name__ == "__main__":
    main()
