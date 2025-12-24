<?php
/**
 * Admin page for managing the 4-level Jawda Lookups system and Aliases.
 */

if (!defined('ABSPATH')) exit;


if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

function jawda_lookups_enqueue_admin_assets($hook) {
    if ($hook === 'toplevel_page_jawda-lookups' || (isset($_GET['page']) && $_GET['page'] === 'jawda-lookups-types')) {
        wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css', [], '1.11.3');
    }
}
add_action('admin_enqueue_scripts', 'jawda_lookups_enqueue_admin_assets');

// -- LIST TABLE CLASSES --
class Jawda_Base_Lookup_List_Table extends WP_List_Table {
    protected $tab = '';

    function get_bulk_actions() {
        return [
            'activate' => __('Activate', 'jawda'),
            'deactivate' => __('Deactivate', 'jawda'),
            'delete' => __('Delete', 'jawda'),
        ];
    }

    protected function render_status($item) {
        return !empty($item['is_active']) ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>';
    }

    function column_cb($item) { return sprintf('<input type="checkbox" name="ids[]" value="%s" />', $item['id']); }
    function column_icon_class($item) { return !empty($item['icon_class']) ? '<i class="' . esc_attr($item['icon_class']) . '"></i>' : '—'; }
    function column_sort_order($item) { return isset($item['sort_order']) ? intval($item['sort_order']) : 0; }
    function column_is_active($item) { return $this->render_status($item); }
}

class Jawda_Categories_List_Table extends Jawda_Base_Lookup_List_Table {
    protected $tab = 'categories';
    function __construct() { parent::__construct(['singular' => 'Category', 'plural' => 'Categories', 'ajax' => false]); }
    function get_columns() { return ['cb' => '<input type="checkbox" />', 'icon_class' => __('Icon', 'jawda'), 'name_en' => 'Name (EN)',  'slug_ar'  => 'Name (AR)', 'sort_order' => 'Sort', 'is_active' => 'Status']; }
    function prepare_items() { $this->_column_headers = [$this->get_columns(), [], []]; $this->items = Jawda_Lookups_Service::get_all_categories(); }
    function column_default($item, $column_name) { return isset($item[$column_name]) ? esc_html($item[$column_name]) : ''; }
    function column_name_en($item) {
        $actions = [
            'edit' => sprintf('<a href="?page=jawda-lookups-types&tab=categories&action=edit&id=%s">Edit</a>', $item['id']),
            'quick_edit' => sprintf('<a href="?page=jawda-lookups-types&tab=categories&action=quick-edit&id=%s">Quick Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=jawda-lookups-types&tab=categories&action=delete&id=%s&_wpnonce=%s">Delete</a>', $item['id'], wp_create_nonce('jawda_delete_lookup')),
        ];
        return sprintf('<strong>%s</strong>%s', esc_html($item['name_en']), $this->row_actions($actions));
    }
}

class Jawda_Usages_List_Table extends Jawda_Base_Lookup_List_Table {
    protected $tab = 'usages';
    function __construct() { parent::__construct(['singular' => 'Usage', 'plural' => 'Usages', 'ajax' => false]); }
    function get_columns() { return ['cb' => '<input type="checkbox" />', 'icon_class' => __('Icon', 'jawda'), 'name_en' => 'Name (EN)',  'slug_ar'  => 'Name (AR)', 'sort_order' => 'Sort', 'is_active' => 'Status']; }
    function prepare_items() { $this->_column_headers = [$this->get_columns(), [], []]; $this->items = Jawda_Lookups_Service::get_all_usages(); }
    function column_default($item, $column_name) { return isset($item[$column_name]) ? esc_html($item[$column_name]) : ''; }
    function column_name_en($item) {
        $actions = [
            'edit' => sprintf('<a href="?page=jawda-lookups-types&tab=usages&action=edit&id=%s">Edit</a>', $item['id']),
            'quick_edit' => sprintf('<a href="?page=jawda-lookups-types&tab=usages&action=quick-edit&id=%s">Quick Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=jawda-lookups-types&tab=usages&action=delete&id=%s&_wpnonce=%s">Delete</a>', $item['id'], wp_create_nonce('jawda_delete_lookup')),
        ];
        return sprintf('<strong>%s</strong>%s', esc_html($item['name_en']), $this->row_actions($actions));
    }
}

class Jawda_Property_Types_List_Table extends Jawda_Base_Lookup_List_Table {
    protected $tab = 'property-types';
    private $categories_map = [];
    private $usages_map = [];
    private $relations_by_type = ['categories' => [], 'usages' => []];

    function __construct() { parent::__construct(['singular' => 'Property Type', 'plural' => 'Property Types', 'ajax' => false]); }
    function get_columns() { return ['cb' => '<input type="checkbox" />', 'icon_class' => __('Icon', 'jawda'), 'name_en' => 'Name (EN)',  'slug_ar'  => 'Name (AR)', 'categories' => 'Categories', 'usages' => 'Usages', 'sort_order' => 'Sort', 'is_active' => 'Status']; }

    function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = Jawda_Lookups_Service::get_all_property_types();

        $this->categories_map = wp_list_pluck(Jawda_Lookups_Service::get_all_categories(['is_active' => 1]), 'name_en', 'id');
        $this->usages_map = wp_list_pluck(Jawda_Lookups_Service::get_all_usages(['is_active' => 1]), 'name_en', 'id');

        $category_relations = Jawda_Lookups_Service::get_all_property_type_category_relations();
        foreach ($category_relations as $rel) {
            $this->relations_by_type['categories'][$rel->property_type_id][] = $rel->category_id;
        }

        $usage_relations = Jawda_Lookups_Service::get_all_property_type_usage_relations();
        foreach ($usage_relations as $rel) {
            $this->relations_by_type['usages'][$rel->property_type_id][] = $rel->usage_id;
        }
    }

    function column_default($item, $column_name) { return isset($item[$column_name]) ? esc_html($item[$column_name]) : ''; }
    function column_name_en($item) {
        $actions = [
            'edit' => sprintf('<a href="?page=jawda-lookups-types&tab=property-types&action=edit&id=%s">Edit</a>', $item['id']),
            'quick_edit' => sprintf('<a href="?page=jawda-lookups-types&tab=property-types&action=quick-edit&id=%s">Quick Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=jawda-lookups-types&tab=property-types&action=delete&id=%s&_wpnonce=%s">Delete</a>', $item['id'], wp_create_nonce('jawda_delete_lookup')),
        ];
        return sprintf('<strong>%s</strong>%s', esc_html($item['name_en']), $this->row_actions($actions));
    }

    function column_categories($item) {
        $cat_ids = $this->relations_by_type['categories'][$item['id']] ?? [];
        if (empty($cat_ids)) return '—';
        $names = array_intersect_key($this->categories_map, array_flip($cat_ids));
        return implode(', ', array_map('esc_html', $names));
    }

    function column_usages($item) {
        $usage_ids = $this->relations_by_type['usages'][$item['id']] ?? [];
        if (empty($usage_ids)) return '—';
        $names = array_intersect_key($this->usages_map, array_flip($usage_ids));
        return implode(', ', array_map('esc_html', $names));
    }
}

class Jawda_Sub_Properties_List_Table extends Jawda_Base_Lookup_List_Table {
    protected $tab = 'sub-properties';
    private $property_types_map = [];
    function __construct() { parent::__construct(['singular' => 'Sub-Property', 'plural' => 'Sub-Properties', 'ajax' => false]); }
    function get_columns() { return ['cb' => '<input type="checkbox" />', 'icon_class' => __('Icon', 'jawda'), 'name_en' => 'Name (EN)',  'slug_ar'  => 'Name (AR)', 'parent_type' => 'Parent Property Type', 'sort_order' => 'Sort', 'is_active' => 'Status']; }
    function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = Jawda_Lookups_Service::get_all_sub_properties();
        $this->property_types_map = wp_list_pluck(Jawda_Lookups_Service::get_all_property_types(['is_active' => 1]), 'name_en', 'id');
    }
    function column_default($item, $column_name) { return isset($item[$column_name]) ? esc_html($item[$column_name]) : ''; }
    function column_name_en($item) {
        $actions = [
            'edit' => sprintf('<a href="?page=jawda-lookups-types&tab=sub-properties&action=edit&id=%s">Edit</a>', $item['id']),
            'quick_edit' => sprintf('<a href="?page=jawda-lookups-types&tab=sub-properties&action=quick-edit&id=%s">Quick Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=jawda-lookups-types&tab=sub-properties&action=delete&id=%s&_wpnonce=%s">Delete</a>', $item['id'], wp_create_nonce('jawda_delete_lookup')),
        ];
        return sprintf('<strong>%s</strong>%s', esc_html($item['name_en']), $this->row_actions($actions));
    }
    function column_parent_type($item) { return isset($item['property_type_id']) ? esc_html($this->property_types_map[$item['property_type_id']] ?? 'N/A') : 'N/A'; }
}

class Jawda_Aliases_List_Table extends WP_List_Table {
    function __construct() { parent::__construct(['singular' => 'Alias', 'plural' => 'Aliases', 'ajax' => false]); }
    function get_columns() { return ['cb' => '<input type="checkbox" />', 'name_en' => 'Name (EN)',  'slug_ar'  => 'Name (AR)', 'sub_property' => 'Sub-Property', 'project' => 'Project']; }
    function get_bulk_actions() { return ['delete' => __('Delete', 'jawda')]; }
    function prepare_items() { $this->_column_headers = [$this->get_columns(), [], []]; $this->items = Jawda_Lookups_Service::get_all_aliases(['is_deleted' => 0]); }
    function column_default($item, $column_name) { return esc_html($item[$column_name]); }
    function column_cb($item) { return sprintf('<input type="checkbox" name="ids[]" value="%s" />', $item['id']); }
    function column_name_en($item) {
        $actions = [
            'edit' => sprintf('<a href="?page=jawda-lookups-types&tab=aliases&action=edit&id=%s">Edit</a>', $item['id']),
            'quick_edit' => sprintf('<a href="?page=jawda-lookups-types&tab=aliases&action=quick-edit&id=%s">Quick Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=jawda-lookups-types&tab=aliases&action=delete&id=%s&_wpnonce=%s">Delete</a>', $item['id'], wp_create_nonce('jawda_delete_lookup'))
        ];
        return sprintf('<strong>%s</strong>%s', esc_html($item['name_en']), $this->row_actions($actions));
    }
    function column_sub_property($item) { $sub = Jawda_Lookups_Service::get_sub_property($item['sub_property_id']); return $sub ? esc_html($sub['name_en']) : 'N/A'; }}

// --- PAGE RENDERING & FORM HANDLING ---

function jawda_lookups_get_entity_map() {
    return [
        'categories' => 'category',
        'property-types' => 'property_type',
        'sub-properties' => 'sub_property',
        
        'property-models' => 'property_model',
'usages' => 'usage',
        'aliases' => 'alias',
    ];
}

/* === AUTO: Property Models List Table (RESTORE TABLE) === */
if (!class_exists('Jawda_Property_Models_List_Table') && class_exists('Jawda_Base_Lookup_List_Table')) {

    class Jawda_Property_Models_List_Table extends Jawda_Base_Lookup_List_Table {

        public function get_columns() {
            return [
                'cb' => '<input type="checkbox" />',
                'id' => 'ID',
                'name_en' => 'Name (EN)',
                 'slug_ar'  => 'Name (AR)',
                'bedrooms' => 'Bedrooms',
                'is_active' => 'Status',
            ];
        }

        protected function get_sortable_columns() {
            return [
                'id' => ['id', false],
                'name_en' => ['name_en', false],
            ];
        }

        public function column_cb($item) {
            $id = isset($item['id']) ? (int)$item['id'] : 0;
            return sprintf('<input type="checkbox" name="bulk_ids[]" value="%d" />', $id);
        }

        public function column_name_en($item) {
            $id = isset($item['id']) ? (int)$item['id'] : 0;

            $edit = add_query_arg([
                'page' => 'jawda-lookups-types',
                'tab' => 'property-models',
                'action' => 'edit',
                'id' => $id,
            ], admin_url('admin.php'));

            $delete = add_query_arg([
                'page' => 'jawda-lookups-types',
                'tab' => 'property-models',
                'action' => 'delete',
                'id' => $id,
                '_wpnonce' => wp_create_nonce('jawda_delete_lookup'),
            ], admin_url('admin.php'));

            $actions = [
                'edit' => sprintf('<a href="%s">Edit</a>', esc_url($edit)),
                'delete' => sprintf('<a href="%s" onclick="return confirm(\'Delete?\')">Delete</a>', esc_url($delete)),
            ];

            $val = isset($item['name_en']) ? $item['name_en'] : '';
            return sprintf('%s %s', esc_html($val), $this->row_actions($actions));
        }

        public function column_id($item) {
            return isset($item['id']) ? (int)$item['id'] : '';
        }

        public function column_name_ar($item) {
            return isset($item[ 'slug_ar' ]) ? esc_html($item[ 'slug_ar' ]) : '';
        }

        public function column_bedrooms($item) {
            return isset($item['bedrooms']) ? (int)$item['bedrooms'] : 0;
        }

        public function column_is_active($item) {
            $v = isset($item['is_active']) ? (int)$item['is_active'] : 0;
            return $v ? 'Active' : 'Inactive';
        }

        public function prepare_items() {
            global $wpdb;

            $per_page = 20;
            $paged = $this->get_pagenum();
            $offset = ($paged - 1) * $per_page;

            $search    = isset($_GET['pm_search']) ? sanitize_text_field(wp_unslash($_GET['pm_search'])) : '';
            $is_active = (isset($_GET['pm_active']) && $_GET['pm_active'] !== '') ? (int)$_GET['pm_active'] : null;
            $bedrooms  = (isset($_GET['pm_bedrooms']) && $_GET['pm_bedrooms'] !== '') ? (int)$_GET['pm_bedrooms'] : null;

            $cat_id = isset($_GET['pm_category_id']) ? (int)$_GET['pm_category_id'] : 0;
            $pt_id  = isset($_GET['pm_property_type_id']) ? (int)$_GET['pm_property_type_id'] : 0;
            $sp_id  = isset($_GET['pm_sub_property_id']) ? (int)$_GET['pm_sub_property_id'] : 0;

            $t_models = $wpdb->prefix . 'jawda_property_models';
            $t_pmc    = $wpdb->prefix . 'jawda_property_model_categories';

            $where = "1=1";
            $params = [];

            if ($is_active !== null) { $where .= " AND m.is_active=%d"; $params[] = (int)$is_active; }

            if ($search !== '') {
                $where .= " AND (m.name_ar LIKE %s OR m.name_en LIKE %s OR m.slug LIKE %s)";
                $like = '%' . $wpdb->esc_like($search) . '%';
                $params[] = $like; $params[] = $like; $params[] = $like;
            }

            if ($bedrooms !== null) { $where .= " AND m.bedrooms=%d"; $params[] = (int)$bedrooms; }
            if ($pt_id > 0) { $where .= " AND m.property_type_id=%d"; $params[] = (int)$pt_id; }
            if ($sp_id > 0) { $where .= " AND m.sub_property_id=%d"; $params[] = (int)$sp_id; }

            $join = "";
            if ($cat_id > 0) {
                $join = " INNER JOIN {$t_pmc} pmc ON pmc.property_model_id=m.id ";
                $where .= " AND pmc.category_id=%d";
                $params[] = (int)$cat_id;
            }

            // Count
            $sqlc = "SELECT COUNT(DISTINCT m.id) FROM {$t_models} m {$join} WHERE {$where}";
            $total_items = $params ? (int)$wpdb->get_var($wpdb->prepare($sqlc, ...$params)) : (int)$wpdb->get_var($sqlc);

            // Page items
            $sql = "SELECT DISTINCT m.* FROM {$t_models} m {$join} WHERE {$where} ORDER BY m.id DESC LIMIT %d OFFSET %d";
            $params2 = array_merge($params, [$per_page, $offset]);
            $items = $wpdb->get_results($wpdb->prepare($sql, ...$params2), ARRAY_A);

            // Attach categories to each item (for rendering)
            if (!empty($items)) {
                $ids = array_map(function($r){ return (int)$r['id']; }, $items);
                $place = implode(',', array_fill(0, count($ids), '%d'));
                $q = $wpdb->prepare("SELECT property_model_id, category_id FROM {$t_pmc} WHERE property_model_id IN ({$place})", ...$ids);
                $pairs = $wpdb->get_results($q, ARRAY_A);
                $map = [];
                foreach ($pairs as $p2) {
                    $mid = (int)$p2['property_model_id'];
                    if (!isset($map[$mid])) $map[$mid] = [];
                    $map[$mid][] = (int)$p2['category_id'];
                }
                foreach ($items as &$it) {
                    $it['category_ids'] = $map[(int)$it['id']] ?? [];
                }
                unset($it);
            }

            $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
            $this->items = is_array($items) ? $items : [];

            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => max(1, (int)ceil($total_items / $per_page)),
            ]);
        }

    

        public function extra_tablenav($which) {
            if ($which !== 'top') return;

            global $wpdb;

            $f_cat = isset($_GET['pm_category_id']) ? (int)$_GET['pm_category_id'] : 0;
            $f_pt  = isset($_GET['pm_property_type_id']) ? (int)$_GET['pm_property_type_id'] : 0;
            $f_sp  = isset($_GET['pm_sub_property_id']) ? (int)$_GET['pm_sub_property_id'] : 0;

            $f_search = isset($_GET['pm_search']) ? sanitize_text_field(wp_unslash($_GET['pm_search'])) : '';
            $f_active = (isset($_GET['pm_active']) && $_GET['pm_active'] !== '') ? (int)$_GET['pm_active'] : null;
            $f_bedrooms = (isset($_GET['pm_bedrooms']) && $_GET['pm_bedrooms'] !== '') ? (int)$_GET['pm_bedrooms'] : null;

            $cats = $wpdb->get_results("SELECT id, name_en, name_ar FROM {$wpdb->prefix}jawda_categories WHERE is_active=1 ORDER BY sort_order ASC, id ASC", ARRAY_A);
            $pts  = $wpdb->get_results("SELECT id, name_en, name_ar FROM {$wpdb->prefix}jawda_property_types WHERE is_active=1 ORDER BY sort_order ASC, id ASC", ARRAY_A);

            if ($f_pt > 0) {
                $subs = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, name_en, name_ar FROM {$wpdb->prefix}jawda_sub_properties WHERE is_active=1 AND property_type_id=%d ORDER BY sort_order ASC, id ASC",
                    $f_pt
                ), ARRAY_A);
            } else {
                $subs = $wpdb->get_results("SELECT id, name_en, name_ar FROM {$wpdb->prefix}jawda_sub_properties WHERE is_active=1 ORDER BY sort_order ASC, id ASC", ARRAY_A);
            }

            ?>
            <div class="alignleft actions" style="padding:8px 0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text" name="pm_search" value="<?php echo esc_attr($f_search); ?>" placeholder="Search (name/slug)..." class="regular-text" style="max-width:260px;" />

                <select name="pm_category_id">
                    <option value="0">Category (All)</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php selected($f_cat, (int)$c['id']); ?>>
                            <?php echo esc_html(($c['name_en'] ?: $c[ 'slug_ar' ])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="pm_property_type_id">
                    <option value="0">Property Type (All)</option>
                    <?php foreach ($pts as $pt): ?>
                        <option value="<?php echo (int)$pt['id']; ?>" <?php selected($f_pt, (int)$pt['id']); ?>>
                            <?php echo esc_html(($pt['name_en'] ?: $pt[ 'slug_ar' ])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="pm_sub_property_id">
                    <option value="0">Sub-Property (All)</option>
                    <?php foreach ($subs as $sp): ?>
                        <option value="<?php echo (int)$sp['id']; ?>" <?php selected($f_sp, (int)$sp['id']); ?>>
                            <?php echo esc_html(($sp['name_en'] ?: $sp[ 'slug_ar' ])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="pm_bedrooms">
                    <option value="">Bedrooms (All)</option>
                    <?php foreach ([0,1,2,3,4,5] as $b): ?>
                      <option value="<?php echo esc_attr($b); ?>" <?php selected((string)$f_bedrooms, (string)$b); ?>>
                        <?php echo esc_html($b); ?>
                      </option>
                    <?php endforeach; ?>
                </select>

                <select name="pm_active">
                    <option value="">Status (All)</option>
                    <option value="1" <?php selected((string)$f_active, "1"); ?>>Active</option>
                    <option value="0" <?php selected((string)$f_active, "0"); ?>>Inactive</option>
                </select>

                <?php submit_button(__('Filter'), 'secondary', 'filter_action', false); ?>
                <a class="button" href="<?php echo esc_url(remove_query_arg(['pm_search','pm_active','pm_bedrooms','pm_category_id','pm_property_type_id','pm_sub_property_id','paged'])); ?>">Reset</a>
            </div>
            <?php
        }

}
}



function jawda_lookups_types_categories_page() {
    $tabs = [
        'categories' => 'Categories',
        'property-types' => 'Property Types',
        'sub-properties' => 'Sub-Properties',
        'property-models' => 'Property Models',
        'usages' => 'Usages',
        'aliases' => 'Aliases'
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && (empty($_GET['tab']) || !isset($tabs[$_GET['tab']]))) {
        jawda_lookups_safe_redirect(jawda_lookups_redirect_url('categories'));
    }

    $current_tab = jawda_lookups_get_tab_from_request($tabs);
    jawda_lookups_handle_form_actions($current_tab);

    $error = get_transient('jawda_lookup_error');
    $success = get_transient('jawda_lookup_success');
    if ($error) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        delete_transient('jawda_lookup_error');
    }
    if ($success) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success) . '</p></div>';
        delete_transient('jawda_lookup_success');
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Types & Categories</h1>
        <hr class="wp-header-end">
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $key => $name) { echo '<a href="?page=jawda-lookups-types&tab='.$key.'" class="nav-tab'.($current_tab===$key?' nav-tab-active':'').'">'.$name.'</a>'; } ?>
        </h2>
        <div id="col-container" class="wp-clearfix" style="margin-top: 20px;">
            <div id="col-left"><div class="col-wrap"><?php jawda_lookups_render_form($current_tab); ?></div></div>
            <div id="col-right"><div class="col-wrap"><?php jawda_lookups_render_quick_edit($current_tab); jawda_lookups_render_list_table($current_tab); ?></div></div>
        </div>
    </div>
    <?php
}

function jawda_lookups_get_tab_from_request($tabs) {
    if (!empty($_REQUEST['tab']) && isset($tabs[$_REQUEST['tab']])) {
        return sanitize_text_field($_REQUEST['tab']);
    }
    return 'categories';
}

function jawda_lookups_render_list_table($tab) {
    $class_key = str_replace('-', '_', $tab);
    $table_class = 'Jawda_' . str_replace(' ', '_', ucwords(str_replace('_', ' ', $class_key))) . '_List_Table';
    if (class_exists($table_class)) {
        $list_table = new $table_class();
        $list_table->prepare_items();
        echo '<form method="post">';
        wp_nonce_field('jawda_lookup_bulk_action', 'jawda_lookup_bulk_nonce');
        echo '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />';
        $list_table->display();
        echo '</form>';
    }
}

function jawda_lookups_render_form($tab) {
    /* === PM OVERRIDE: render form === */
    if ($tab === 'property-models') {
        jawda_pm_render_form();
        return;
    }


    $action = isset($_GET['action']) && $_GET['action'] == 'edit' ? 'edit' : 'add';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $entity_map = jawda_lookups_get_entity_map();
    $singular_entity = $entity_map[$tab] ?? '';
    $item = $id > 0 ? call_user_func(['Jawda_Lookups_Service', 'get_' . $singular_entity], $id) : null;
    ?>
    <div class="form-wrap">
        <h2><?php echo $action === 'edit' ? 'Edit' : 'Add New'; ?></h2>
        <form method="post" action="?page=jawda-lookups-types&tab=<?php echo $tab; ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'add'; ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <?php wp_nonce_field('jawda_save_lookup'); ?>

            <div class="form-field"><label for="name_en">Name (EN)</label><input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($item['name_en'] ?? ''); ?>" required></div>
            <div class="form-field"><label for= 'slug_ar' >Name (AR)</label><input type="text" name= 'slug_ar'  id= 'slug_ar'  value="<?php echo esc_attr($item[ 'slug_ar' ] ?? ''); ?>" required></div>
            <div class="form-field"><label for="icon_class">Bootstrap Icon Class</label><input type="text" name="icon_class" id="icon_class" value="<?php echo esc_attr($item['icon_class'] ?? ''); ?>" placeholder="bi bi-house-door"></div>
            <div class="form-field"><label for="sort_order">Sort Order</label><input type="number" name="sort_order" id="sort_order" value="<?php echo esc_attr($item['sort_order'] ?? 0); ?>" min="0"></div>
            <div class="form-field"><label><input type="checkbox" name="is_active" value="1" <?php checked($item['is_active'] ?? 1, 1); ?>> <?php _e('Active', 'jawda'); ?></label></div>

            <?php if ($tab === 'property-types'):
                $cats = Jawda_Lookups_Service::get_all_categories(); $usages = Jawda_Lookups_Service::get_all_usages();
                $sel_cats = $id > 0 ? Jawda_Lookups_Service::get_categories_for_property_type($id) : [];
                $sel_usages = $id > 0 ? Jawda_Lookups_Service::get_usages_for_property_type($id) : [];
                echo '<div class="form-field"><label>Categories</label><select name="category_ids[]" multiple style="width:100%;height:100px;">';
                foreach($cats as $cat) echo '<option value="'.$cat['id'].'" '.selected(in_array($cat['id'], $sel_cats), true, false).'>'.$cat['name_en'].'</option>';
                echo '</select></div>';
                echo '<div class="form-field"><label>Usages</label><select name="usage_ids[]" multiple style="width:100%;height:100px;">';
                foreach($usages as $u) echo '<option value="'.$u['id'].'" '.selected(in_array($u['id'], $sel_usages), true, false).'>'.$u['name_en'].'</option>';
                echo '</select></div>';
            endif; ?>

            <?php if ($tab === 'sub-properties'):
                $types = Jawda_Lookups_Service::get_all_property_types();
                echo '<div class="form-field"><label>Parent Property Type</label><select name="property_type_id" required>';
                foreach($types as $t) echo '<option value="'.$t['id'].'" '.selected($item['property_type_id'] ?? 0, $t['id'], false).'>'.$t['name_en'].'</option>';
                echo '</select></div>';
                echo '<p class="description">Inherits categories & usages from its parent Property Type.</p>';
            endif; ?>

            <?php if ($tab === 'aliases'):
                $subs = Jawda_Lookups_Service::get_all_sub_properties(['is_active' => null]);
                $projects = get_posts(['post_type' => 'projects', 'posts_per_page' => -1]);
                echo '<div class="form-field"><label>Sub-Property</label><select name="sub_property_id" required>';
                foreach($subs as $s) echo '<option value="'.$s['id'].'" '.selected($item['sub_property_id'] ?? 0, $s['id'], false).'>'.$s['name_en'].'</option>';
                echo '</select></div>';
                echo '';
            endif; ?>

            <?php submit_button($action === 'edit' ? 'Update' : 'Add'); ?>
        </form>
    </div>
    <?php
}

function jawda_lookups_render_quick_edit($tab) {
    if (!isset($_GET['action']) || $_GET['action'] !== 'quick-edit' || empty($_GET['id'])) {
        return;
    }
    $entity_map = jawda_lookups_get_entity_map();
    $singular_entity = $entity_map[$tab] ?? '';
    $item = call_user_func(['Jawda_Lookups_Service', 'get_' . $singular_entity], (int) $_GET['id']);
    if (!$item) return;
    ?>
    <div class="form-wrap">
        <h2><?php _e('Quick Edit', 'jawda'); ?></h2>
        <form method="post" action="?page=jawda-lookups-types&tab=<?php echo esc_attr($tab); ?>">
            <?php wp_nonce_field('jawda_quick_edit_lookup'); ?>
            <input type="hidden" name="quick_edit" value="1">
            <input type="hidden" name="id" value="<?php echo esc_attr($item['id']); ?>">
            <?php if ($tab === 'aliases'): ?>
                <?php $subs = Jawda_Lookups_Service::get_all_sub_properties(['is_active' => null]); $projects = get_posts(['post_type' => 'projects', 'posts_per_page' => -1]); ?>
                <div class="form-field"><label for="qe_alias_name_en">Name (EN)</label><input type="text" name="name_en" id="qe_alias_name_en" value="<?php echo esc_attr($item['name_en']); ?>" required></div>
                <div class="form-field"><label for="qe_alias_name_ar">Name (AR)</label><input type="text" name= 'slug_ar'  id="qe_alias_name_ar" value="<?php echo esc_attr($item[ 'slug_ar' ]); ?>" required></div>
                <div class="form-field"><label>Sub-Property</label><select name="sub_property_id" required><?php foreach($subs as $s) { echo '<option value="'.$s['id'].'" '.selected($item['sub_property_id'], $s['id'], false).'>'.$s['name_en'].'</option>'; } ?></select></div><?php else: ?>
                <?php if ($tab === 'sub-properties'): ?><input type="hidden" name="property_type_id" value="<?php echo esc_attr($item['property_type_id']); ?>"><?php endif; ?>
                <div class="form-field"><label for="qe_name_en">Name (EN)</label><input type="text" name="name_en" id="qe_name_en" value="<?php echo esc_attr($item['name_en']); ?>" required></div>
                <div class="form-field"><label for="qe_name_ar">Name (AR)</label><input type="text" name= 'slug_ar'  id="qe_name_ar" value="<?php echo esc_attr($item[ 'slug_ar' ]); ?>" required></div>
                <div class="form-field"><label for="qe_icon">Bootstrap Icon Class</label><input type="text" name="icon_class" id="qe_icon" value="<?php echo esc_attr($item['icon_class']); ?>"></div>
                <div class="form-field"><label for="qe_sort_order">Sort Order</label><input type="number" name="sort_order" id="qe_sort_order" value="<?php echo esc_attr($item['sort_order'] ?? 0); ?>" min="0"></div>
                <div class="form-field"><label><input type="checkbox" name="is_active" value="1" <?php checked($item['is_active'], 1); ?>> <?php _e('Active', 'jawda'); ?></label></div>
            <?php endif; ?>
            <?php submit_button(__('Save Quick Edit', 'jawda')); ?>
        </form>
    </div>
    <?php
}

function jawda_lookups_handle_form_actions($tab) {
    /* === PM OVERRIDE: handle actions === */
    if ($tab === 'property-models') {
        jawda_pm_handle_actions();
        return;
    }


    jawda_lookups_handle_bulk_actions($tab);
    $redirect_url = jawda_lookups_redirect_url($tab);
    $entity_map = jawda_lookups_get_entity_map();
    $singular_entity = $entity_map[$tab] ?? '';

    // Handle Delete Action
    if (isset($_GET['action'], $_GET['id'], $_GET['_wpnonce']) && $_GET['action'] === 'delete') {
        if (wp_verify_nonce($_GET['_wpnonce'], 'jawda_delete_lookup')) {
            $result = call_user_func(['Jawda_Lookups_Service', 'delete_' . $singular_entity], (int)$_GET['id']);
            jawda_lookups_flush_cache();
            if ($result) {
                set_transient('jawda_lookup_success', 'Item deleted successfully.', 30);
            } else {
                set_transient('jawda_lookup_error', 'Failed to delete item.', 30);
            }
        } else {
            set_transient('jawda_lookup_error', 'Invalid security token.', 30);
        }
        jawda_lookups_safe_redirect($redirect_url);
    }

    // Quick edit
    if (isset($_POST['quick_edit']) && wp_verify_nonce($_POST['_wpnonce'], 'jawda_quick_edit_lookup')) {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

/* === PM SAVE: bedrooms guard by categories (AUTO) === */
            // Force bedrooms=0 unless selected categories include: سكني or إجازات وساحلي
            $allow_bedrooms = false;
            $allowed_ar = ['سكني', 'إجازات وساحلي'];

            if (!empty($cat_ids) && is_array($cats)) {
                foreach ($cats as $__c) {
                    $cid = (int)($__c['id'] ?? 0);
                    if ($cid <= 0 || !in_array($cid, $cat_ids, true)) continue;
                    $ar = trim((string)($__c[ 'slug_ar' ] ?? ''));
                    if (in_array($ar, $allowed_ar, true)) { $allow_bedrooms = true; break; }
                }
            }

            if (!$allow_bedrooms) {
                $_POST['bedrooms'] = 0;
            }

        $data = [
            'name_en' => sanitize_text_field($_POST['name_en']),
             'slug_ar'  => sanitize_text_field($_POST[ 'slug_ar' ]),
            'icon_class' => sanitize_text_field($_POST['icon_class'] ?? ''),
            'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

            // PM SAVE bedrooms guard (AUTO)
            if (isset($allow_bedrooms) && !$allow_bedrooms) { $data['bedrooms'] = 0; }

        if ($tab === 'aliases') {
            $alias_data = [
                'name_en' => sanitize_text_field($_POST['name_en']),
                 'slug_ar'  => sanitize_text_field($_POST[ 'slug_ar' ]),
                'sub_property_id' => isset($_POST['sub_property_id']) ? (int) $_POST['sub_property_id'] : 0,
                ];
            $result = Jawda_Lookups_Service::update_alias($id, $alias_data);
        } elseif ($tab === 'property-types') {
            $existing_categories = Jawda_Lookups_Service::get_categories_for_property_type($id);
            $existing_usages = Jawda_Lookups_Service::get_usages_for_property_type($id);
            $result = Jawda_Lookups_Service::update_property_type($id, $data, $existing_categories, $existing_usages);
        } else {
            if ($tab === 'sub-properties') {
                $data['property_type_id'] = isset($_POST['property_type_id']) ? (int) $_POST['property_type_id'] : 0;
            }
            $result = call_user_func(['Jawda_Lookups_Service', 'update_' . $singular_entity], $id, $data);
        }
        jawda_lookups_flush_cache();
        if ($result && !is_wp_error($result)) {
            set_transient('jawda_lookup_success', 'Item updated successfully.', 30);
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : 'An unknown error occurred.';
            set_transient('jawda_lookup_error', $error_message, 30);
        }
        jawda_lookups_safe_redirect($redirect_url);
    }

    // Handle Add/Update Actions
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'], $_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'jawda_save_lookup')) return;

    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $data = [
        'name_en' => sanitize_text_field($_POST['name_en']),
         'slug_ar'  => sanitize_text_field($_POST[ 'slug_ar' ]),
        'icon_class' => sanitize_text_field($_POST['icon_class']),
        'sort_order' => isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    $result = false;

    switch ($tab) {
        case 'property-types':
            $cat_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : [];
            $usage_ids = isset($_POST['usage_ids']) ? array_map('intval', $_POST['usage_ids']) : [];
            if ($action === 'add') $result = Jawda_Lookups_Service::create_property_type($data, $cat_ids, $usage_ids);

            // === PM SAVE bedrooms guard (AUTO) ===
            $allow_bedrooms = false;
            if (!empty($cat_ids) && is_array($cats)) {
                foreach ($cats as $__c) {
                    $cid = (int)($__c['id'] ?? 0);
                    if ($cid <= 0 || !in_array($cid, $cat_ids, true)) continue;

                    $slug = strtolower((string)($__c['slug'] ?? ''));
                    $en   = strtolower((string)($__c['name_en'] ?? ''));
                    $ar   = (string)($__c[ 'slug_ar' ] ?? '');

                    if (in_array($slug, ['residential','housing','vacation','vacations','holiday','holidays','coastal','seaside','north-coast'], true)
                        || strpos($en, 'residen') !== false
                        || strpos($en, 'vacat')   !== false
                        || strpos($en, 'holiday') !== false
                        || strpos($en, 'coast')   !== false
                        || strpos($en, 'seaside') !== false
                        || (function_exists('mb_strpos') && (mb_strpos($ar, 'سكن') !== false
                            || mb_strpos($ar, 'إجاز') !== false
                            || mb_strpos($ar, 'اجاز') !== false
                            || mb_strpos($ar, 'ساحل') !== false
                            || mb_strpos($ar, 'ساحلي') !== false))
                    ) { $allow_bedrooms = true; break; }
                }
            }

            elseif ($id > 0) $result = Jawda_Lookups_Service::update_property_type($id, $data, $cat_ids, $usage_ids);
            break;
        case 'sub-properties':
            $parent_id = (int)$_POST['property_type_id'];
            $data['property_type_id'] = $parent_id;
            if ($action === 'add') $result = Jawda_Lookups_Service::create_sub_property($data);
            elseif ($id > 0) $result = Jawda_Lookups_Service::update_sub_property($id, $data);
            break;
        case 'aliases':
            $alias_data = ['name_en' => $data['name_en'],  'slug_ar'  => $data[ 'slug_ar' ], 'sub_property_id' => (int)$_POST['sub_property_id']];
            if ($action === 'add') $result = Jawda_Lookups_Service::create_alias($alias_data);
            elseif ($id > 0) $result = Jawda_Lookups_Service::update_alias($id, $alias_data);
            break;
        default:
            if ($action === 'add') $result = call_user_func(['Jawda_Lookups_Service', 'create_' . $singular_entity], $data);
            elseif ($id > 0) $result = call_user_func(['Jawda_Lookups_Service', 'update_' . $singular_entity], $id, $data);
            break;
    }

    if ($result && !is_wp_error($result)) {
        jawda_lookups_flush_cache();
        $message = $action === 'add' ? 'Item added successfully.' : 'Item updated successfully.';
        set_transient('jawda_lookup_success', $message, 30);
    } else {
        $error_message = is_wp_error($result) ? $result->get_error_message() : 'An unknown error occurred.';
        set_transient('jawda_lookup_error', $error_message, 30);
    }
    jawda_lookups_safe_redirect($redirect_url);
}

function jawda_lookups_handle_bulk_actions($tab) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['jawda_lookup_bulk_nonce']) || !wp_verify_nonce($_POST['jawda_lookup_bulk_nonce'], 'jawda_lookup_bulk_action')) {
        return;
    }
    $action = $_POST['action'] !== '-1' ? $_POST['action'] : ($_POST['action2'] ?? '-1');
    if (!in_array($action, ['activate', 'deactivate', 'delete'], true)) {
        return;
    }
    $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];
    if (empty($ids)) return;

    if ($tab === 'aliases' && $action === 'delete') {
        foreach ($ids as $id) {
            Jawda_Lookups_Service::delete_alias($id);
        }
        jawda_lookups_flush_cache();
        set_transient('jawda_lookup_success', 'Bulk action applied.', 30);
        jawda_lookups_safe_redirect(jawda_lookups_redirect_url($tab));
    }

    $method_map = [
        'categories' => 'set_category_active_state',
        'property-types' => 'set_property_type_active_state',
        'sub-properties' => 'set_sub_property_active_state',
        'usages' => 'set_usage_active_state',
    ];

    if (!isset($method_map[$tab])) return;
    $state = ($action === 'activate') ? 1 : 0;
    call_user_func(['Jawda_Lookups_Service', $method_map[$tab]], $ids, $state);
    jawda_lookups_flush_cache();
    set_transient('jawda_lookup_success', 'Bulk action applied.', 30);
    jawda_lookups_safe_redirect(jawda_lookups_redirect_url($tab));
}

function jawda_lookups_redirect_url($tab) {
    return add_query_arg(
        [
            'page' => 'jawda-lookups-types',
            'tab'  => $tab,
        ],
        admin_url('admin.php')
    );
}

function jawda_lookups_safe_redirect($url) {
    $redirected = wp_safe_redirect($url);

    if ($redirected === false) {
        $redirected = wp_redirect($url);
    }

    // If headers were sent (or redirect functions returned a string without sending headers), fall back to HTML/JS redirect to
    // avoid leaving the user on a blank screen.
    if (headers_sent()) {
        echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">';
        echo '<script>window.location.href = ' . wp_json_encode($url) . ';</script>';
        return;
    }

    if ($redirected) {
        exit;
    }


/* === jawda PROPERTY MODELS LOOKUP (AUTO) === */
if ($tab === 'property-models') {
    // Minimal first version: CRUD via service, categories multi, type id manual (AJAX endpoint prepared)
    if (isset($_POST['jawda_pm_action']) && $_POST['jawda_pm_action'] === 'save') {
        if (!current_user_can('manage_options')) { wp_die('forbidden'); }
        check_admin_referer('jawda_pm_save');

        $service = jawda_get_lookups_service();

        $model_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $category_ids = isset($_POST['category_ids']) ? (array)$_POST['category_ids'] : [];
        $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids), fn($v)=>$v>0)));

        $property_type_id = isset($_POST['property_type_id']) ? (int)$_POST['property_type_id'] : 0;

        $data = [
            'id' => $model_id,
             'slug_ar'  => isset($_POST[ 'slug_ar' ]) ? wp_unslash($_POST[ 'slug_ar' ]) : '',
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
            $t_ptc = $wpdb->prefix . 'jawda_property_type_categories';
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
        wp_safe_redirect(add_query_arg(['tab' => 'property-models', 'updated' => 1], menu_page_url('jawda-lookups', false)));
        exit;
    }

    if (isset($_GET['jawda_pm_delete']) && (int)$_GET['jawda_pm_delete'] > 0) {
        if (!current_user_can('manage_options')) { wp_die('forbidden'); }
        check_admin_referer('jawda_pm_delete');

        $service = jawda_get_lookups_service();
        $service->delete_property_model((int)$_GET['jawda_pm_delete']);
        wp_cache_flush();

        wp_safe_redirect(add_query_arg(['tab' => 'property-models', 'deleted' => 1], menu_page_url('jawda-lookups', false)));
        exit;
    }

    $service = jawda_get_lookups_service();
    
    // === Property Models: pagination + filters ===
    $per_page = 20;
    $paged = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
    $offset = ($paged - 1) * $per_page;

    $filter_search = isset($_GET['pm_search']) ? sanitize_text_field(wp_unslash($_GET['pm_search'])) : '';
    $filter_bedrooms = (isset($_GET['pm_bedrooms']) && $_GET['pm_bedrooms'] !== '') ? (int)$_GET['pm_bedrooms'] : null;
    $filter_active = (isset($_GET['pm_active']) && $_GET['pm_active'] !== '') ? (int)$_GET['pm_active'] : null;
    $filter_pt = (isset($_GET['pm_property_type_id']) && $_GET['pm_property_type_id'] !== '') ? (int)$_GET['pm_property_type_id'] : null;
    $filter_sp = (isset($_GET['pm_sub_property_id']) && $_GET['pm_sub_property_id'] !== '') ? (int)$_GET['pm_sub_property_id'] : null;

    $pm_args = [
        'limit' => $per_page,
        'offset' => $offset,
        'search' => $filter_search,
    ];
    if ($filter_bedrooms !== null) $pm_args['bedrooms'] = $filter_bedrooms;
    if ($filter_active !== null) $pm_args['is_active'] = $filter_active;
    if ($filter_pt !== null) $pm_args['property_type_id'] = $filter_pt;
    if ($filter_sp !== null) $pm_args['sub_property_id'] = $filter_sp;

    $total_models = method_exists($service, 'count_property_models') ? $service->count_property_models($pm_args) : 0;
    $models = $service->get_property_models($pm_args);

    $total_pages = $total_models > 0 ? (int)ceil($total_models / $per_page) : 1;


    global $wpdb;
    $cats = $wpdb->get_results("SELECT id, name_ar, name_en, slug FROM {$wpdb->prefix}jawda_categories ORDER BY id DESC", ARRAY_A);
    ?>
    <div class="wrap">
      <h1>Property Models</h1>

      <?php if (isset($_GET['updated'])): ?><div class="notice notice-success"><p>Saved.</p></div><?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?><div class="notice notice-success"><p>Deleted.</p></div><?php endif; ?>

      <h2>Add / Update Model</h2>
      <form method="post">
        <?php wp_nonce_field('jawda_pm_save'); ?>
        <input type="hidden" name="jawda_pm_action" value="save" />

        
    <?php
      // --- Filters UI (Property Models) ---
      $base_url = remove_query_arg(['paged']);
    ?>
    <form method="get" style="margin:10px 0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
      <input type="hidden" name="tab" value="property-models">

      <input type="text" name="pm_search" value="<?php echo esc_attr($filter_search); ?>" placeholder="Search (name/slug)..." class="regular-text" />

      <select name="pm_bedrooms">
        <option value="">Bedrooms (All)</option>
        <?php foreach ([0,1,2,3,4,5] as $b): ?>
          <option value="<?php echo esc_attr($b); ?>" <?php selected((string)$filter_bedrooms, (string)$b); ?>>
            <?php echo esc_html($b); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="pm_active">
        <option value="">Status (All)</option>
        <option value="1" <?php selected((string)$filter_active, "1"); ?>>Active</option>
        <option value="0" <?php selected((string)$filter_active, "0"); ?>>Inactive</option>
      </select>

      <input type="number" name="pm_property_type_id" value="<?php echo esc_attr($filter_pt ?? ''); ?>" placeholder="Property Type ID" style="width:160px;" />
      <input type="number" name="pm_sub_property_id" value="<?php echo esc_attr($filter_sp ?? ''); ?>" placeholder="Sub-Property ID" style="width:160px;" />

      <button class="button button-primary" type="submit">Filter</button>
      <a class="button" href="<?php echo esc_url(remove_query_arg(['pm_search','pm_bedrooms','pm_active','pm_property_type_id','pm_sub_property_id','paged'])); ?>">Reset</a>
    </form>

<table class="form-table">
          <tr><th><label>ID (edit)</label></th><td><input type="number" name="id" value="" min="0" /></td></tr>
          <tr><th><label>Name AR</label></th><td><input type="text" name= 'slug_ar'  class="regular-text" required /></td></tr>
          <tr><th><label>Name EN</label></th><td><input type="text" name="name_en" class="regular-text" /></td></tr>
          <tr><th><label>Slug</label></th><td><input type="text" name="slug" class="regular-text" placeholder="auto if empty" /></td></tr>
          <tr>
            <th><label>Categories (multi)</label></th>
            <td>
              <select name="category_ids[]" multiple size="6" style="min-width:320px">
                <?php foreach ($cats as $c): ?>
                  <option value="<?php echo esc_attr($c['id']); ?>"><?php echo esc_html(($c['name_en'] ?: $c[ 'slug_ar' ]) . ' (#' . $c['id'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr><th><label>Property Type ID</label></th><td><input type="number" name="property_type_id" value="0" min="0" /></td></tr>
          <tr><th><label>Bedrooms</label></th><td><input type="number" name="bedrooms" value="0" min="0" max="50" /></td></tr>
          <tr><th><label>Icon (optional)</label></th><td><input type="text" name="icon" class="regular-text" /></td></tr>
          <tr><th><label>Active</label></th><td><label><input type="checkbox" name="is_active" checked /> Active</label></td></tr>
        </table>

    <?php if ($total_pages > 1): ?>
      <div class="tablenav">
        <div class="tablenav-pages" style="margin:12px 0;">
          <?php
            echo paginate_links([
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'prev_text' => '&laquo;',
              'next_text' => '&raquo;',
              'total' => $total_pages,
              'current' => $paged,
              'type' => 'plain',
            ]);
          ?>
        </div>
      </div>
    <?php endif; ?>



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
            <td><?php echo esc_html(($m['name_en'] ?: $m[ 'slug_ar' ])); ?></td>
            <td><?php echo esc_html($m['slug']); ?></td>
            <td><?php echo (int)$m['property_type_id']; ?></td>
            <td><?php echo (int)$m['bedrooms']; ?></td>
            <td><?php echo esc_html(implode(',', $m['category_ids'] ?? [])); ?></td>
            <td><?php echo ((int)$m['is_active'] ? 'Yes' : 'No'); ?></td>
            <td>
              <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['tab'=>'property-models','jawda_pm_delete'=>(int)$m['id']], menu_page_url('jawda-lookups', false)), 'jawda_pm_delete')); ?>" onclick="return confirm('Delete?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    return;
}
/* === END jawda PROPERTY MODELS LOOKUP (AUTO) === */

}


/* === PM JS: 2-level loader Categories -> Property Types -> Sub-Properties (AUTO) === */
add_action('admin_footer', function () {
    if (!is_admin()) return;
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    $tab  = isset($_GET['tab'])  ? sanitize_text_field($_GET['tab'])  : '';
    if ($page !== 'jawda-lookups-types' || $tab !== 'property-models') return;
    ?>
    <script>
    (function(){
      var catsSel = document.getElementById('jawda_pm_category_ids');
      var typeSel = document.getElementById('jawda_pm_property_type_id');
      var subSel  = document.getElementById('jawda_pm_sub_property_id');
      var nonceEl = document.getElementById('jawda_pm_ajax_nonce');

      var preType = document.getElementById('jawda_pm_property_type_id_prefill');
      var preSub  = document.getElementById('jawda_pm_sub_property_id_prefill');

      // === PM JS bedrooms toggle (AUTO) ===
      var bedWrap = document.getElementById('jawda_pm_bedrooms_wrap');
      var bedInput = document.getElementById('bedrooms');

      function shouldShowBedrooms(){
        // check selected category option labels for Arabic/English keywords
        try {
          for (var i=0;i<catsSel.options.length;i++){
            var o = catsSel.options[i];
            if (!o.selected) continue;
            var t = (o.textContent || '').toLowerCase();
            if (t.indexOf('residen') !== -1 || t.indexOf('vacat') !== -1 || t.indexOf('holiday') !== -1 || t.indexOf('coast') !== -1 || t.indexOf('seaside') !== -1) return true;
            if (t.indexOf('سكن') !== -1 || t.indexOf('إجاز') !== -1 || t.indexOf('اجاز') !== -1 || t.indexOf('ساحل') !== -1 || t.indexOf('ساحلي') !== -1) return true;
          }
        } catch(e){}
        return false;
      }

      function syncBedroomsVisibility(){
        if (!bedWrap) return;
        var ok = shouldShowBedrooms();
        bedWrap.style.display = ok ? '' : 'none';
        if (!ok && bedInput) bedInput.value = '0';
      }


      if (!catsSel || !typeSel || !subSel || !nonceEl) return;

      function selectedValues(select){
        var out = [];
        for (var i=0;i<select.options.length;i++){
          var o = select.options[i];
          if (o.selected) out.push(parseInt(o.value,10));
        }
        return out.filter(function(v){ return v>0; });
      }

      function setOptions(selectEl, items, selectedId){
        selectEl.innerHTML = '<option value="0">— Select —</option>';
        (items||[]).forEach(function(it){
          var id = parseInt(it.id,10);
          var name = (it.name_en || '') + ' / ' + (it.name_ar || '');
          var opt = document.createElement('option');
          opt.value = id;
          opt.textContent = name;
          if (selectedId && id === selectedId) opt.selected = true;
          selectEl.appendChild(opt);
        });
      }

      function post(action, extra){
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', nonceEl.value);
        if (extra) {
          Object.keys(extra).forEach(function(k){
            var v = extra[k];
            if (Array.isArray(v)) v.forEach(function(x){ fd.append(k+'[]', x); });
            else fd.append(k, v);
          });
        }
        return fetch(ajaxurl, { method:'POST', credentials:'same-origin', body: fd })
          .then(function(r){ return r.json(); });
      }

      function loadTypes(){
        var catIds = selectedValues(catsSel);

        // reset downstream
        setOptions(typeSel, [], 0);
        setOptions(subSel, [], 0);
        if (preSub) preSub.value = '0';

        if (!catIds.length) return Promise.resolve();

        return post('jawda_pm_get_property_types', { category_ids: catIds })
          .then(function(j){
            var sel = preType ? parseInt(preType.value||'0',10) : 0;
            var items = (j && j.success && j.data && j.data.items) ? j.data.items : [];
            setOptions(typeSel, items, sel);
            // if prefill exists and still valid, auto-load subs
            var currentType = parseInt(typeSel.value||'0',10);
            if (currentType > 0) return loadSubs(currentType);
          })
          .catch(function(){
            setOptions(typeSel, [], 0);
            setOptions(subSel, [], 0);
          });
      }

      function loadSubs(typeId){
        setOptions(subSel, [], 0);
        if (preSub) preSub.value = preSub.value || '0';

        if (!typeId || typeId <= 0) return Promise.resolve();

        return post('jawda_pm_get_sub_properties', { property_type_id: typeId })
          .then(function(j){
            var sel = preSub ? parseInt(preSub.value||'0',10) : 0;
            var items = (j && j.success && j.data && j.data.items) ? j.data.items : [];
            setOptions(subSel, items, sel);
          })
          .catch(function(){ setOptions(subSel, [], 0); });
      }

      catsSel.addEventListener('change', function(){
        syncBedroomsVisibility();
        if (preType) preType.value = '0';
        if (preSub) preSub.value = '0';
        loadTypes();
      });

      typeSel.addEventListener('change', function(){
        if (preSub) preSub.value = '0';
        var typeId = parseInt(typeSel.value||'0',10);
        loadSubs(typeId);
      });

      // initial load (edit/add)
      loadTypes();
    })();
    </script>
    <?php
});
/* === PM OVERRIDE IMPLEMENTATION (AUTO) === */

function jawda_pm_handle_actions() {
    if (!current_user_can('manage_options')) { return; }

    global $wpdb;
    $service = function_exists('jawda_get_lookups_service') ? jawda_get_lookups_service() : new Jawda_Lookups_Service();

    // DELETE
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
        $id = (int) $_GET['id'];
        if ($id > 0) {
            check_admin_referer('jawda_delete_lookup');
            if (method_exists($service, 'delete_property_model')) {
                $service->delete_property_model($id);
            } else {
                // fallback delete direct
                $t = $wpdb->prefix . 'jawda_property_models';
                $wpdb->delete($t, ['id' => $id], ['%d']);
            }
            if (function_exists('jawda_lookups_flush_cache')) { jawda_lookups_flush_cache(); }
            set_transient('jawda_lookup_success', 'Property Model deleted.', 30);
        }
        wp_safe_redirect(add_query_arg(['page'=>'jawda-lookups-types','tab'=>'property-models'], admin_url('admin.php')));
        exit;
    }

    // SAVE (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name_en'], $_POST[ 'slug_ar' ])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'jawda_save_lookup')) {
            set_transient('jawda_lookup_error', 'Invalid security token.', 30);
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $cat_ids = isset($_POST['category_ids']) ? (array) $_POST['category_ids'] : [];
        $cat_ids = array_values(array_unique(array_filter(array_map('intval', $cat_ids), function($v){ return $v > 0; })));

        $sub_property_id = isset($_POST['sub_property_id']) ? (int) $_POST['sub_property_id'] : 0;

        // derive property_type_id from sub_property_id
        $property_type_id = 0;
        if ($sub_property_id > 0) {
            $t_sub = $wpdb->prefix . 'jawda_sub_properties';
            $property_type_id = (int) $wpdb->get_var($wpdb->prepare("SELECT property_type_id FROM {$t_sub} WHERE id=%d", $sub_property_id));
        }

        $slug = sanitize_title($_POST['slug'] ?? '');
        if (!$slug) { $slug = sanitize_title($_POST['name_en']); }

        $data = [
            'id' => $id,
            'name_en' => sanitize_text_field($_POST['name_en']),
             'slug_ar'  => sanitize_text_field($_POST[ 'slug_ar' ]),
            'slug' => $slug,
            'property_type_id' => $property_type_id,
            'sub_property_id' => $sub_property_id,
            'bedrooms' => isset($_POST['bedrooms']) ? (int) $_POST['bedrooms'] : 0,
            'icon' => sanitize_text_field($_POST['icon'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        $saved_id = 0;
        if (method_exists($service, 'upsert_property_model')) {
            $saved_id = (int) $service->upsert_property_model($data);
        } else {
            // fallback direct insert/update
            $t = $wpdb->prefix . 'jawda_property_models';
            $now = current_time('mysql');
            if ($id > 0) {
                $wpdb->update($t, [
                    'name_en'=>$data['name_en'],
                     'slug_ar' =>$data[ 'slug_ar' ],
                    'slug'=>$data['slug'],
                    'property_type_id'=>$data['property_type_id'],
                    'sub_property_id'=>$data['sub_property_id'],
                    'bedrooms'=>$data['bedrooms'],
                    'icon'=>$data['icon'],
                    'is_active'=>$data['is_active'],
                    'updated_at'=>$now,
                ], ['id'=>$id], ['%s','%s','%s','%d','%d','%d','%s','%d','%s'], ['%d']);
                $saved_id = $id;
            } else {
                $wpdb->insert($t, [
                    'name_en'=>$data['name_en'],
                     'slug_ar' =>$data[ 'slug_ar' ],
                    'slug'=>$data['slug'],
                    'property_type_id'=>$data['property_type_id'],
                    'sub_property_id'=>$data['sub_property_id'],
                    'bedrooms'=>$data['bedrooms'],
                    'icon'=>$data['icon'],
                    'is_active'=>$data['is_active'],
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ], ['%s','%s','%s','%d','%d','%d','%s','%d','%s','%s']);
                $saved_id = (int) $wpdb->insert_id;
            }
        }

        if ($saved_id > 0 && method_exists($service, 'set_property_model_categories')) {
            $service->set_property_model_categories($saved_id, $cat_ids);
        } else if ($saved_id > 0) {
            // fallback pivot write
            $t_pivot = $wpdb->prefix . 'jawda_property_model_categories';
            $wpdb->delete($t_pivot, ['property_model_id'=>$saved_id], ['%d']);
            foreach ($cat_ids as $cid) {
                $wpdb->insert($t_pivot, ['property_model_id'=>$saved_id,'category_id'=>$cid], ['%d','%d']);
            }
        }

        if (function_exists('jawda_lookups_flush_cache')) { jawda_lookups_flush_cache(); }
        set_transient('jawda_lookup_success', 'Property Model saved.', 30);
        wp_safe_redirect(add_query_arg(['page'=>'jawda-lookups-types','tab'=>'property-models','updated'=>1], admin_url('admin.php')));
        exit;
    }
}

function jawda_pm_render_form() {
    if (!current_user_can('manage_options')) { return; }
    global $wpdb;

    $service = function_exists('jawda_get_lookups_service') ? jawda_get_lookups_service() : new Jawda_Lookups_Service();

    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    $edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $pm_item = null;
    if ($action === 'edit' && $edit_id > 0) {
        if (method_exists($service, 'get_property_model')) {
            $pm_item = $service->get_property_model($edit_id);
        } else {
            $t = $wpdb->prefix . 'jawda_property_models';
            $pm_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $edit_id), ARRAY_A);
        }
    }

    // categories list
    $cats = [];
    if (method_exists($service, 'get_categories')) {
        $cats = $service->get_categories(['limit'=>500]);
    } else {
        $t = $wpdb->prefix . 'jawda_categories';
        $cats = $wpdb->get_results("SELECT id,name_en,name_ar FROM {$t} ORDER BY name_en ASC", ARRAY_A);
    }
    if (!is_array($cats)) $cats = [];

    // selected cats
    $selected_cat_ids = [];
    $selected_sub_property_id = 0;
$selected_property_type_id = 0;

    if (is_array($pm_item) && !empty($pm_item['id'])) {
        $pm_id = (int) $pm_item['id'];
        $t_pivot = $wpdb->prefix . 'jawda_property_model_categories';
        $selected_cat_ids = $wpdb->get_col($wpdb->prepare("SELECT category_id FROM {$t_pivot} WHERE property_model_id=%d", $pm_id)) ?: [];
        $selected_cat_ids = array_values(array_unique(array_filter(array_map('intval', (array)$selected_cat_ids), function($v){ return $v>0; })));
        $selected_sub_property_id = isset($pm_item['sub_property_id']) ? (int)$pm_item['sub_property_id'] : 0;
  $selected_property_type_id = isset($pm_item['property_type_id']) ? (int)$pm_item['property_type_id'] : 0;
    }

    $pm_ajax_nonce = wp_create_nonce('jawda_pm_ajax');
    $id = is_array($pm_item) ? (int)($pm_item['id'] ?? 0) : 0;

    // Bedrooms field should only appear for Residential / Vacations / Coastal categories
    $show_bedrooms = false;
    if (!empty($selected_cat_ids) && is_array($cats)) {
        foreach ($cats as $__c) {
            $cid = (int)($__c['id'] ?? 0);
            if ($cid <= 0 || !in_array($cid, $selected_cat_ids, true)) continue;

            $slug = strtolower((string)($__c['slug'] ?? ''));
            $en   = strtolower((string)($__c['name_en'] ?? ''));
            $ar   = (string)($__c[ 'slug_ar' ] ?? '');

            // slug-based OR name-based matching (robust)
            if (in_array($slug, ['residential','housing','vacation','vacations','holiday','holidays','coastal','seaside','north-coast'], true)
                || strpos($en, 'residen') !== false
                || strpos($en, 'vacat')   !== false
                || strpos($en, 'holiday') !== false
                || strpos($en, 'coast')   !== false
                || strpos($en, 'seaside') !== false
                || (function_exists('mb_strpos') && (mb_strpos($ar, 'سكن') !== false
                    || mb_strpos($ar, 'إجاز') !== false
                    || mb_strpos($ar, 'اجاز') !== false
                    || mb_strpos($ar, 'ساحل') !== false
                    || mb_strpos($ar, 'ساحلي') !== false))
            ) {
                $show_bedrooms = true;
                break;
            }
        }
    }


    ?>
    <form method="post" action="<?php echo esc_url(add_query_arg(['page'=>'jawda-lookups-types','tab'=>'property-models'], admin_url('admin.php'))); ?>">
        <?php wp_nonce_field('jawda_save_lookup'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>" /><div class="form-field" style="max-width:520px;margin-top:12px;">
            <label for="jawda_pm_category_ids"><strong>Categories</strong></label>
            <select id="jawda_pm_category_ids" name="category_ids[]" multiple style="width:100%;height:120px;">
                <?php foreach ($cats as $c):
                    $cid = (int)($c['id'] ?? 0);
                    if ($cid <= 0) continue;
                    $label = ($c['name_en'] ?? '') . ' / ' . ($c[ 'slug_ar' ] ?? '');
                ?>
                    <option value="<?php echo esc_attr($cid); ?>" <?php echo in_array($cid, $selected_cat_ids, true) ? 'selected' : ''; ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
  <p class="description">Select one or more categories to load Property Types.</p>
</div>

<div class="form-field" style="max-width:520px;margin-top:12px;">
  <label for="jawda_pm_property_type_id"><strong>Property Type</strong></label>
  <select id="jawda_pm_property_type_id" name="property_type_id" style="width:100%;">
    <option value="0">— Select —</option>
  </select>
</div>
        <div class="form-field" style="max-width:520px;margin-top:12px;">
            <label for="jawda_pm_sub_property_id"><strong>Sub-Property</strong></label>
            <select id="jawda_pm_sub_property_id" name="sub_property_id" style="width:100%;">
                <option value="0">— Select —</option>
            </select>
        </div>

        <input type="hidden" id="jawda_pm_ajax_nonce" value="<?php echo esc_attr($pm_ajax_nonce); ?>">
<input type="hidden" id="jawda_pm_property_type_id_prefill" value="<?php echo esc_attr($selected_property_type_id); ?>">
        <input type="hidden" id="jawda_pm_sub_property_id_prefill" value="<?php echo esc_attr($selected_sub_property_id); ?>">

        <div class="form-field">
            <label for="name_en"><strong>Name (EN)</strong></label>
            <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($pm_item['name_en'] ?? ''); ?>" required />
        </div>

        <div class="form-field">
            <label for= 'slug_ar' ><strong>Name (AR)</strong></label>
            <input type="text" name= 'slug_ar'  id= 'slug_ar'  value="<?php echo esc_attr($pm_item[ 'slug_ar' ] ?? ''); ?>" required />
        </div>

        <div class="form-field">
            <label for="slug"><strong>Slug</strong></label>
            <input type="text" name="slug" id="slug" value="<?php echo esc_attr($pm_item['slug'] ?? ''); ?>" />
        </div>
        <?php if (!empty($show_bedrooms)) : ?>


        <div id="jawda_pm_bedrooms_wrap" class="form-field" style="max-width:520px;">
            <label for="bedrooms"><strong>Bedrooms</strong></label>
            <input type="number" name="bedrooms" id="bedrooms" value="<?php echo esc_attr((int)($pm_item['bedrooms'] ?? 0)); ?>" min="0" />
        </div>

        
        <?php endif; ?>
<div class="form-field">
            <label for="icon"><strong>Bootstrap Icon Class</strong></label>
            <input type="text" name="icon" id="icon" value="<?php echo esc_attr($pm_item['icon'] ?? ''); ?>" placeholder="bi bi-house-door" />
        </div>

        <p class="submit" style="margin-top:14px;">
            <label style="display:inline-block;margin-right:10px;">
                <input type="checkbox" name="is_active" value="1" <?php echo (!isset($pm_item['is_active']) || (int)($pm_item['is_active'] ?? 1) === 1) ? 'checked' : ''; ?> />
                Active
            </label>
            <button type="submit" class="button button-primary"><?php echo $id ? 'Update' : 'Add New'; ?></button>
        </p>
    </form>
    <?php
}




/* === PM JS bedrooms toggle by categories (AUTO) === */
add_action('admin_footer', function () {
    if (!is_admin()) return;
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    $tab  = isset($_GET['tab'])  ? sanitize_text_field($_GET['tab'])  : '';
    if ($page !== 'jawda-lookups-types' || $tab !== 'property-models') return;
    ?>
    <script>
    (function(){
      var catsSel = document.getElementById('jawda_pm_category_ids');
      var wrap = document.getElementById('jawda_pm_bedrooms_wrap');
      if (!catsSel || !wrap) return;

      function shouldShowBedrooms(){
        var allowed = {'سكني': true, 'إجازات وساحلي': true};
        for (var i=0;i<catsSel.options.length;i++){
          var o = catsSel.options[i];
          if (!o.selected) continue;
          var t = (o.textContent || '').trim();
          // label is "EN / AR" -> take AR part after "/"
          var ar = t;
          if (t.indexOf('/') !== -1) ar = t.split('/').pop().trim();
          if (allowed[ar]) return true;
        }
        return false;
      }

      function sync(){
        wrap.style.display = shouldShowBedrooms() ? '' : 'none';
        // optional: if hidden, reset value
        if (wrap.style.display === 'none') {
          var inp = document.getElementById('bedrooms');
          if (inp) inp.value = 0;
        }
      }

      catsSel.addEventListener('change', sync);
      sync();
    })();
    </script>
    <?php
});

