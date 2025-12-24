<?php
/**
 * Hegzz Lookups System Verification Script
 *
 * To run this script, visit: /wp-admin/?verify_hegzz_lookups=true
 */

if (!function_exists('hegzz_verify_lookups_system')) {
function hegzz_verify_lookups_system() {
    $is_cli = (PHP_SAPI === 'cli');

    // Allow CLI execution without web params/cap checks
    if (!$is_cli) {
        if (!isset($_GET['verify_hegzz_lookups']) || !current_user_can('manage_options')) {
            return;
        }
    }

    global $wpdb;
    $errors = [];
    $success = [];

    $tables = [
        'hegzz_categories',
        'hegzz_property_types',
        'hegzz_sub_properties',
        'hegzz_usages',
        'hegzz_aliases',
        'hegzz_property_type_categories',
        'hegzz_property_type_usages',
        'hegzz_property_models',
        'hegzz_property_model_categories',
    ];

    $columns_to_check = [
        'hegzz_categories' => ['id', 'name_en',  'slug_ar' , 'is_active'],
        'hegzz_property_types' => ['id', 'name_en',  'slug_ar' , 'is_active'],
        'hegzz_sub_properties' => ['id', 'name_en',  'slug_ar' , 'property_type_id', 'is_active'],
        'hegzz_usages' => ['id', 'name_en',  'slug_ar' , 'is_active'],
        'hegzz_aliases' => ['id', 'name_en',  'slug_ar' , 'sub_property_id', 'project_id', 'is_deleted'],
        'hegzz_property_type_categories' => ['property_type_id', 'category_id'],
        'hegzz_property_type_usages' => ['property_type_id', 'usage_id'],
    ];

    $columns_to_forbid = [
        'hegzz_categories' => 'slug',
        'hegzz_property_types' => 'slug',
        'hegzz_sub_properties' => 'slug',
        'hegzz_usages' => 'slug',
    ];

    if ($is_cli) {
        echo "Hegzz Lookups System Verification\n";
        echo "=================================\n\n";
    } else {
        echo '<div class="wrap">';
        echo '<h1>Hegzz Lookups System Verification</h1>';
        echo '<p>This script checks the integrity of the Hegzz Lookups database tables.</p>';
        echo '<hr />';
    }

    // 1. Check table existence
    echo ($is_cli ? "\n1) Checking Table Existence...\n" : '<h2>1. Checking Table Existence...</h2>');
    foreach ($tables as $table_short_name) {
        $table_name = $wpdb->prefix . $table_short_name;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $errors[] = "Table <strong>{$table_name}</strong> is missing!";
        } else {
            $success[] = "Table <strong>{$table_name}</strong> exists.";
        }
    }

    // 2. Check column existence
    echo ($is_cli ? "\n2) Checking Column Existence...\n" : '<h2>2. Checking Column Existence...</h2>');
    foreach ($columns_to_check as $table_short_name => $cols) {
        $table_name = $wpdb->prefix . $table_short_name;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) continue;

        foreach($cols as $col) {
            if (!$wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE '{$col}'")) {
                $errors[] = "Column <strong>{$col}</strong> is missing from table <strong>{$table_name}</strong>!";
            } else {
                $success[] = "Column <strong>{$col}</strong> exists in table <strong>{$table_name}</strong>.";
            }
        }
    }

    // 3. Check for forbidden columns
    echo ($is_cli ? "\n3) Checking for Forbidden Columns...\n" : '<h2>3. Checking for Forbidden Columns...</h2>');
    foreach ($columns_to_forbid as $table_short_name => $col) {
        $table_name = $wpdb->prefix . $table_short_name;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) continue;

        if ($wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE '{$col}'")) {
            $errors[] = "Forbidden column <strong>{$col}</strong> still exists in table <strong>{$table_name}</strong>!";
        } else {
            $success[] = "Forbidden column <strong>{$col}</strong> does not exist in table <strong>{$table_name}</strong>.";
        }
    }

    // 4. Run simple test queries
    echo ($is_cli ? "\n4) Running Simple Test Queries...\n" : '<h2>4. Running Simple Test Queries...</h2>');
    foreach ($tables as $table_short_name) {
        $table_name = $wpdb->prefix . $table_short_name;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) continue;

        $wpdb->hide_errors();
        $result = $wpdb->get_results("SELECT * FROM `{$table_name}` LIMIT 1");
        if ($wpdb->last_error) {
            $errors[] = "Test query failed for table <strong>{$table_name}</strong>. SQL Error: " . $wpdb->last_error;
        } else {
            $success[] = "Test query passed for table <strong>{$table_name}</strong>.";
        }
        $wpdb->show_errors();
    }

    if ($is_cli) {
        echo "\n\n== Summary ==\n";
        echo "Errors: " . count($errors) . "\n";
        echo "Success: " . count($success) . "\n";

        if (!empty($errors)) {
            echo "\n-- Errors --\n";
            foreach ($errors as $e) {
                echo " - " . trim(strip_tags($e)) . "\n";
            }
        } else {
            echo "\nAll checks passed ✅\n";
        }

    } else {
        echo '<hr />';
        echo '<h2>Verification Summary</h2>';

        if (!empty($errors)) {
            echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin-bottom: 20px;">';
            echo '<h3><span style="color: red;">&#10008;</span> Found ' . count($errors) . ' Errors!</h3>';
            echo '<ul style="list-style-type: disc; padding-left: 20px;">';
            foreach ($errors as $error) { echo '<li>' . $error . '</li>'; }
            echo '</ul></div>';
        } else {
            echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin-bottom: 20px;">';
            echo '<h3><span style="color: green;">&#10004;</span> No errors found. All checks passed!</h3>';
            echo '</div>';
        }

        if (!empty($success)) {
            echo '<h4>Successful Checks:</h4>';
            echo '<ul style="list-style-type: disc; padding-left: 20px;">';
            foreach ($success as $msg) { echo '<li>' . $msg . '</li>'; }
            echo '</ul>';
        }
    }

    if (!$is_cli) {
        echo '</div>';
        exit;
    }

    // CLI: return boolean instead of exit
    return empty($errors);
}
}
add_action('admin_init', 'hegzz_verify_lookups_system');
