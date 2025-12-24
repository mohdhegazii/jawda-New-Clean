<?php
if (!defined('ABSPATH')) exit;

// Kill switch: set define('jawda_DISABLE_PM_METABOX', true); in wp-config.php if needed.
if (defined('jawda_DISABLE_PM_METABOX') && jawda_DISABLE_PM_METABOX) { return; }

/**
 * Project Metabox: Property Models (Category -> Property Type -> Sub Property)
 */

if (!defined('ABSPATH')) { exit; }

class Jawda_Project_Property_Models_Metabox {

    const NONCE_ACTION = 'jawda_save_project_property_models';
    const NONCE_NAME   = 'jawda_project_property_models_nonce';

    const META_CATS = '_jawda_pm_category_ids';
    const META_TYPE = '_jawda_pm_property_type_id';
    const META_SUB  = '_jawda_pm_sub_property_id';

    public static function boot() {
        add_action('add_meta_boxes', [__CLASS__, 'register_metabox']);
        add_action('save_post_projects', [__CLASS__, 'save'], 15, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin']);
    }

    public static function register_metabox() {
        add_meta_box(
            'jawda_project_property_models',
            __('Property Models', 'jawda'),
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
        <div class="jawda-pm-metabox" style="padding: 6px 0;">
            <table class="form-table" role="presentation">
                <tbody>

                <tr>
                    <th scope="row"><label for="jawda_pm_category_ids"><?php echo esc_html__('Category', 'jawda'); ?></label></th>
                    <td>
                        <select id="jawda_pm_category_ids" name="jawda_pm_category_ids[]" multiple="multiple" style="min-width: 320px; height: 120px;">
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
                    <th scope="row"><label for="jawda_pm_property_type_id"><?php echo esc_html__('Property Type', 'jawda'); ?></label></th>
                    <td>
                        <select id="jawda_pm_property_type_id" name="jawda_pm_property_type_id" style="min-width: 320px;">
                            <option value="0"><?php echo esc_html__('— Select —', 'jawda'); ?></option>
                            <?php if ($saved_type): ?>
                                <option value="<?php echo esc_attr($saved_type); ?>" selected="selected"><?php echo esc_html('#' . $saved_type); ?></option>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="jawda_pm_sub_property_id"><?php echo esc_html__('Sub Property', 'jawda'); ?></label></th>
                    <td>
                        <select id="jawda_pm_sub_property_id" name="jawda_pm_sub_property_id" style="min-width: 320px;">
                            <option value="0"><?php echo esc_html__('— Select —', 'jawda'); ?></option>
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
        
      const PM_NONCE = '<?php echo esc_js( wp_create_nonce('jawda_pm_ajax') ); ?>';
(function($){
            var catsSel = document.getElementById('jawda_pm_category_ids');
            var typeSel = document.getElementById('jawda_pm_property_type_id');
            var subSel  = document.getElementById('jawda_pm_sub_property_id');
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

                ajaxPost({ action: 'jawda_pm_get_sub_properties', property_type_id: typeId, nonce: PM_NONCE })
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

                ajaxPost({ action: 'jawda_pm_get_property_types', category_ids: catIds, nonce: PM_NONCE })
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
        if (isset($_POST['jawda_pm_category_ids'])) {
            $raw = (array) wp_unslash($_POST['jawda_pm_category_ids']);
            $cats = array_values(array_unique(array_filter(array_map('intval', $raw), function($v){ return $v > 0; })));
        }

        $type = isset($_POST['jawda_pm_property_type_id']) ? (int) $_POST['jawda_pm_property_type_id'] : 0;
        $sub  = isset($_POST['jawda_pm_sub_property_id']) ? (int) $_POST['jawda_pm_sub_property_id'] : 0;

        if (!empty($cats)) update_post_meta($post_id, self::META_CATS, $cats);
        else delete_post_meta($post_id, self::META_CATS);

        if ($type > 0) update_post_meta($post_id, self::META_TYPE, $type);
        else delete_post_meta($post_id, self::META_TYPE);

        if ($sub > 0) update_post_meta($post_id, self::META_SUB, $sub);
        else delete_post_meta($post_id, self::META_SUB);
    }
}

Jawda_Project_Property_Models_Metabox::boot();
