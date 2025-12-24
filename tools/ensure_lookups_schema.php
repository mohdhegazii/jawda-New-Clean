<?php
/**
 * Ensure Hegzz Lookups schema exists (CLI installer)
 *
 * Usage:
 *   php tools/ensure_lookups_schema.php
 */

$WP = dirname(__DIR__, 4); // .../wp-content/themes/aqarand -> .../aqarand (WordPress root)
$THEME = __DIR__ . "/..";  // tools/.. = theme root

// Minimal web context for plugins (Polylang, etc.)
$_SERVER['HTTP_HOST']       = $_SERVER['HTTP_HOST']       ?? 'localhost';
$_SERVER['SERVER_NAME']     = $_SERVER['SERVER_NAME']     ?? 'localhost';
$_SERVER['REQUEST_URI']     = $_SERVER['REQUEST_URI']     ?? '/';
$_SERVER['REQUEST_METHOD']  = $_SERVER['REQUEST_METHOD']  ?? 'GET';
$_SERVER['REMOTE_ADDR']     = $_SERVER['REMOTE_ADDR']     ?? '127.0.0.1';
$_SERVER['HTTPS']           = $_SERVER['HTTPS']           ?? 'off';
$_SERVER['SCRIPT_NAME']     = $_SERVER['SCRIPT_NAME']     ?? '/index.php';
$_SERVER['REQUEST_SCHEME']  = $_SERVER['REQUEST_SCHEME']  ?? 'http';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

chdir($WP);
require $WP . "/wp-load.php";

global $wpdb;

function table_exists($table_name) : bool {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
}

function column_exists($table_name, $col) : bool {
    global $wpdb;
    $sql = "SHOW COLUMNS FROM `{$table_name}` LIKE %s";
    $r = $wpdb->get_results($wpdb->prepare($sql, $col));
    return !empty($r);
}

function index_exists($table_name, $index_name) : bool {
    global $wpdb;
    $rows = $wpdb->get_results("SHOW INDEX FROM `{$table_name}`");
    foreach ($rows as $row) {
        if (!empty($row->Key_name) && $row->Key_name === $index_name) return true;
    }
    return false;
}

function run_sql($sql) : void {
    global $wpdb;
    $wpdb->hide_errors();
    $wpdb->query($sql);
    $err = $wpdb->last_error;
    $wpdb->show_errors();
    if ($err) {
        fwrite(STDERR, "SQL ERROR: {$err}\nSQL: {$sql}\n\n");
        throw new RuntimeException($err);
    }
}

echo "Ensure Hegzz Lookups Schema (CLI)\n";
echo "=================================\n\n";

$prefix = $wpdb->prefix;

$plan = [];
$applied = 0;

// 1) Create missing tables
$tbl_models = $prefix . "hegzz_property_models";
$tbl_model_cats = $prefix . "hegzz_property_model_categories";

if (!table_exists($tbl_models)) {
    $plan[] = "CREATE TABLE {$tbl_models}";
    run_sql("CREATE TABLE IF NOT EXISTS `{$tbl_models}` (
      `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name_en` VARCHAR(255) NOT NULL DEFAULT '',
      `name_ar` VARCHAR(255) NOT NULL DEFAULT '',
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME NULL DEFAULT NULL,
      `updated_at` DATETIME NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $applied++;
}

if (!table_exists($tbl_model_cats)) {
    $plan[] = "CREATE TABLE {$tbl_model_cats}";
    run_sql("CREATE TABLE IF NOT EXISTS `{$tbl_model_cats}` (
      `property_model_id` BIGINT(20) UNSIGNED NOT NULL,
      `category_id` BIGINT(20) UNSIGNED NOT NULL,
      PRIMARY KEY (`property_model_id`,`category_id`),
      KEY `category_id` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $applied++;
}

// 2) Add missing column project_id in aliases + index
$tbl_aliases = $prefix . "hegzz_aliases";
if (table_exists($tbl_aliases)) {
    if (!column_exists($tbl_aliases, "project_id")) {
        $plan[] = "ALTER TABLE {$tbl_aliases} ADD project_id";
        run_sql("ALTER TABLE `{$tbl_aliases}` ADD COLUMN `project_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `sub_property_id`;");
        $applied++;
    }
    if (!index_exists($tbl_aliases, "project_id")) {
        $plan[] = "ALTER TABLE {$tbl_aliases} ADD INDEX project_id";
        run_sql("ALTER TABLE `{$tbl_aliases}` ADD KEY `project_id` (`project_id`);");
        $applied++;
    }
}

// 3) Drop forbidden slug columns (if exist)
$drop_targets = [
    $prefix . "hegzz_categories",
    $prefix . "hegzz_property_types",
    $prefix . "hegzz_sub_properties",
    $prefix . "hegzz_usages",
];

foreach ($drop_targets as $t) {
    if (table_exists($t) && column_exists($t, "slug")) {
        $plan[] = "ALTER TABLE {$t} DROP slug";
        run_sql("ALTER TABLE `{$t}` DROP COLUMN `slug`;");
        $applied++;
    }
}

echo "Applied changes: {$applied}\n";
if (empty($plan)) {
    echo "Nothing to change (schema already OK).\n";
} else {
    echo "Changes:\n";
    foreach ($plan as $p) echo " - {$p}\n";
}
echo "\n";

// 4) Run verify as final check
if (!function_exists('hegzz_verify_lookups_system')) {
    require $THEME . "/tools/verify_lookups.php";
}

$ok = (bool) hegzz_verify_lookups_system();
fwrite(STDERR, "\nDONE. ok=" . ($ok ? "1" : "0") . "\n");
exit($ok ? 0 : 1);
