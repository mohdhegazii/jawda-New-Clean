#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations
import sys
import shutil
import datetime
from pathlib import Path

THEME_ROOT = Path.cwd()

META_BOX_PHP = THEME_ROOT / "app/functions/meta_box.php"
PM_METABOX_FILE = THEME_ROOT / "app/inc/admin/metaboxes/project-property-models.php"

INCLUDE_MARKER_BEGIN = "// === Aqarand: Project Property Models Metabox (AUTO) BEGIN ==="
INCLUDE_MARKER_END   = "// === Aqarand: Project Property Models Metabox (AUTO) END ==="

def ts() -> str:
    return datetime.datetime.now().strftime("%Y%m%d-%H%M%S")

def backup_file(p: Path) -> Path:
    if not p.exists():
        return p
    b = p.with_suffix(p.suffix + f".bak.{ts()}")
    shutil.copy2(p, b)
    return b

def ensure_parent_dir(p: Path) -> None:
    p.parent.mkdir(parents=True, exist_ok=True)

def write_text(p: Path, content: str) -> None:
    ensure_parent_dir(p)
    backup_file(p)
    p.write_text(content, encoding="utf-8")

def read_text(p: Path) -> str:
    return p.read_text(encoding="utf-8")

def upsert_block(src: str, begin: str, end: str, block: str) -> str:
    if begin in src and end in src:
        pre = src.split(begin, 1)[0]
        post = src.split(end, 1)[1]
        return (pre.rstrip("\n") + "\n\n" + block.rstrip("\n") + "\n\n" + post.lstrip("\n"))
    else:
        return src.rstrip("\n") + "\n\n" + block.rstrip("\n") + "\n"

def ensure_require_in_meta_box_php() -> None:
    if not META_BOX_PHP.exists():
        raise SystemExit(f"ERROR: Cannot find {META_BOX_PHP}")

    src = read_text(META_BOX_PHP)

    block = f"""{INCLUDE_MARKER_BEGIN}
if ( is_admin() ) {{
    $pm_metabox_file = get_template_directory() . '/app/inc/admin/metaboxes/project-property-models.php';
    if ( file_exists( $pm_metabox_file ) ) {{
        require_once $pm_metabox_file;
    }}
}}
{INCLUDE_MARKER_END}
"""
    new_src = upsert_block(src, INCLUDE_MARKER_BEGIN, INCLUDE_MARKER_END, block)
    if new_src != src:
        backup_file(META_BOX_PHP)
        META_BOX_PHP.write_text(new_src, encoding="utf-8")

def build_pm_metabox_php() -> str:
    return r"""<?php
/**
 * Project Metabox: Property Models (Category -> Property Type -> Sub Property)
 */

if (!defined('ABSPATH')) { exit; }

class Aqarand_Project_Property_Models_Metabox {

    const NONCE_ACTION = 'aqarand_save_project_property_models';
    const NONCE_NAME   = 'aqarand_project_property_models_nonce';

    const META_CATS = '_aqarand_pm_category_ids';
    const META_TYPE = '_aqarand_pm_property_type_id';
    const META_SUB  = '_aqarand_pm_sub_property_id';

    public static function boot() {
        add_action('add_meta_boxes', [__CLASS__, 'register_metabox']);
        add_action('save_post_projects', [__CLASS__, 'save'], 15, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin']);
    }

    public static function register_metabox() {
        add_meta_box(
            'aqarand_project_property_models',
            __('Property Models', 'aqarand'),
            [__CLASS__, 'render'],
            'projects',
            'normal',
            'default'
        );
    }

    public static function enqueue_admin($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'projects') return;
        wp_enqueue_script('jquery');
    }

    private static function get_saved($post_id) {
        $cats = get_post_meta($post_id, self::META_CATS, true);
        $type = (int) get_post_meta($post_id, self::META_TYPE, true);
        $sub  = (int) get_post_meta($post_id, self::META_SUB, true);

        if (!is_array($cats)) $cats = [];
        $cats = array_values(array_unique(array_map('intval', $cats)));
        return [$cats, $type, $sub];
    }

    public static function render($post) {
        if (!($post instanceof WP_Post)) return;

        list($saved_cats, $saved_type, $saved_sub) = self::get_saved($post->ID);

        $cat_options = [];
        if (taxonomy_exists('projects_category')) {
            $terms = get_terms([
                'taxonomy'   => 'projects_category',
                'hide_empty' => false,
            ]);
            if (!is_wp_error($terms)) {
                foreach ($terms as $t) {
                    $cat_options[(int)$t->term_id] = $t->name;
                }
            }
        }

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        ?>
        <div class="aqarand-pm-metabox" style="padding: 6px 0;">
            <table class="form-table" role="presentation">
                <tbody>

                <tr>
                    <th scope="row"><label for="hegzz_pm_category_ids"><?php echo esc_html__('Category', 'aqarand'); ?></label></th>
                    <td>
                        <select id="hegzz_pm_category_ids" name="aqarand_pm_category_ids[]" multiple="multiple" style="min-width: 320px; height: 120px;">
                            <?php if (!empty($cat_options)): ?>
                                <?php foreach ($cat_options as $id => $label): ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected(in_array((int)$id, $saved_cats, true)); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($saved_cats as $id): ?>
                                    <option value="<?php echo esc_attr((int)$id); ?>" selected="selected"><?php echo esc_html('#' . (int)$id); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="hegzz_pm_property_type_id"><?php echo esc_html__('Property Type', 'aqarand'); ?></label></th>
                    <td>
                        <select id="hegzz_pm_property_type_id" name="aqarand_pm_property_type_id" style="min-width: 320px;">
                            <option value="0"><?php echo esc_html__('— Select —', 'aqarand'); ?></option>
                            <?php if ($saved_type): ?>
                                <option value="<?php echo esc_attr($saved_type); ?>" selected="selected"><?php echo esc_html('#' . $saved_type); ?></option>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="hegzz_pm_sub_property_id"><?php echo esc_html__('Sub Property', 'aqarand'); ?></label></th>
                    <td>
                        <select id="hegzz_pm_sub_property_id" name="aqarand_pm_sub_property_id" style="min-width: 320px;">
                            <option value="0"><?php echo esc_html__('— Select —', 'aqarand'); ?></option>
                            <?php if ($saved_sub): ?>
                                <option value="<?php echo esc_attr($saved_sub); ?>" selected="selected"><?php echo esc_html('#' . $saved_sub); ?></option>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>

        <script>
        (function($){
            var catsSel = document.getElementById('hegzz_pm_category_ids');
            var typeSel = document.getElementById('hegzz_pm_property_type_id');
            var subSel  = document.getElementById('hegzz_pm_sub_property_id');
            if (!catsSel || !typeSel || !subSel) return;

            function getSelectedCategoryIds(){
                var out = [];
                for (var i=0; i<catsSel.options.length; i++){
                    var opt = catsSel.options[i];
                    if (opt.selected){
                        var v = parseInt(opt.value, 10);
                        if (!isNaN(v) && v>0) out.push(v);
                    }
                }
                return Array.from(new Set(out));
            }

            function clearSelect(sel, placeholder){
                while (sel.options.length) sel.remove(0);
                var opt = document.createElement('option');
                opt.value = '0';
                opt.textContent = placeholder || '— Select —';
                sel.appendChild(opt);
            }

            function addOptions(sel, items, selectedId){
                if (!items) return;
                if (Array.isArray(items)){
                    items.forEach(function(it){
                        if (!it) return;
                        var id = parseInt(it.id, 10);
                        var name = (it.name || it.label || it.title || ('#'+id));
                        if (!isNaN(id) && id>0){
                            var o = document.createElement('option');
                            o.value = String(id);
                            o.textContent = name;
                            if (selectedId && id === selectedId) o.selected = true;
                            sel.appendChild(o);
                        }
                    });
                } else if (typeof items === 'object') {
                    Object.keys(items).forEach(function(k){
                        var id = parseInt(k, 10);
                        if (!isNaN(id) && id>0){
                            var o = document.createElement('option');
                            o.value = String(id);
                            o.textContent = String(items[k] || ('#'+id));
                            if (selectedId && id === selectedId) o.selected = true;
                            sel.appendChild(o);
                        }
                    });
                }
            }

            function ajaxPost(data){
                return $.ajax({
                    url: (window.ajaxurl || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>'),
                    method: 'POST',
                    dataType: 'json',
                    data: data
                });
            }

            var initialSavedType = (function(){ var v = parseInt(typeSel.value, 10); return isNaN(v)?0:v; })();
            var initialSavedSub  = (function(){ var v = parseInt(subSel.value, 10);  return isNaN(v)?0:v; })();

            function loadSubs(keepSelected){
                var typeId = parseInt(typeSel.value, 10);
                clearSelect(subSel, '— Select —');
                if (isNaN(typeId) || typeId <= 0) return;

                ajaxPost({ action: 'hegzz_pm_get_sub_properties', property_type_id: typeId })
                .done(function(resp){
                    var payload = (resp && resp.success && resp.data) ? resp.data : resp;
                    var items = payload && (payload.items || payload.options) ? (payload.items || payload.options) : payload;
                    addOptions(subSel, items, keepSelected ? initialSavedSub : 0);
                });
            }

            function loadTypes(keepSelected){
                var catIds = getSelectedCategoryIds();
                clearSelect(typeSel, '— Select —');
                clearSelect(subSel, '— Select —');
                if (!catIds.length) return;

                ajaxPost({ action: 'hegzz_pm_get_property_types', category_ids: catIds })
                .done(function(resp){
                    var payload = (resp && resp.success && resp.data) ? resp.data : resp;
                    var items = payload && (payload.items || payload.options) ? (payload.items || payload.options) : payload;
                    addOptions(typeSel, items, keepSelected ? initialSavedType : 0);
                    if (keepSelected && initialSavedType) loadSubs(true);
                });
            }

            catsSel.addEventListener('change', function(){ initialSavedType = 0; initialSavedSub = 0; loadTypes(false); });
            typeSel.addEventListener('change', function(){ initialSavedSub = 0; loadSubs(false); });

            loadTypes(true);
        })(jQuery);
        </script>
        <?php
    }

    public static function save($post_id, $post) {
        if (!isset($_POST[self::NONCE_NAME])) return;
        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) return;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $cats = [];
        if (isset($_POST['aqarand_pm_category_ids'])) {
            $raw = (array) wp_unslash($_POST['aqarand_pm_category_ids']);
            $cats = array_values(array_unique(array_filter(array_map('intval', $raw), function($v){ return $v > 0; })));
        }

        $type = isset($_POST['aqarand_pm_property_type_id']) ? (int) $_POST['aqarand_pm_property_type_id'] : 0;
        $sub  = isset($_POST['aqarand_pm_sub_property_id']) ? (int) $_POST['aqarand_pm_sub_property_id'] : 0;

        if (!empty($cats)) update_post_meta($post_id, self::META_CATS, $cats);
        else delete_post_meta($post_id, self::META_CATS);

        if ($type > 0) update_post_meta($post_id, self::META_TYPE, $type);
        else delete_post_meta($post_id, self::META_TYPE);

        if ($sub > 0) update_post_meta($post_id, self::META_SUB, $sub);
        else delete_post_meta($post_id, self::META_SUB);
    }
}

Aqarand_Project_Property_Models_Metabox::boot();
"""

def main() -> None:
    if not (THEME_ROOT / "app").exists():
        print("ERROR: Run this script from the theme root (where 'app/' exists).", file=sys.stderr)
        sys.exit(1)

    write_text(PM_METABOX_FILE, build_pm_metabox_php())
    print(f"[OK] Wrote: {PM_METABOX_FILE}")

    ensure_require_in_meta_box_php()
    print(f"[OK] Ensured require_once in: {META_BOX_PHP}")

    print("\nDONE ✅")

if __name__ == "__main__":
    main()
