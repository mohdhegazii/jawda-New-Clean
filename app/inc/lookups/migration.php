<?php
/**
 * Migration and Seeding script for Unified Lookups.
 *
 * Usage: Visit wp-admin/?jawda_clean_seed_lookups=confirm as admin to run.
 */

if (!defined('ABSPATH')) {
    exit;
}

function jawda_clean_and_seed_lookups() {
    jawda_require_manage_options('jawda_clean_and_seed_lookups');

    // Prevent timeout
    set_time_limit(300);

    echo '<div class="wrap">';
    echo '<h1>Running Jawda Lookups Seeder...</h1>';

    if (!class_exists('Jawda_Lookups_Seeder')) {
        require_once __DIR__ . '/class-jawda-lookups-seeder.php';
    }

    $seeder = new Jawda_Lookups_Seeder();
    $seeder->reset_lookups();
    $seeder->seed_default_data();

    echo '<h1>Operation Complete!</h1>';
    echo '</div>';
}

function jawda_handle_migration_request() {
    if (isset($_GET['jawda_clean_seed_lookups']) && $_GET['jawda_clean_seed_lookups'] === 'confirm') {
        jawda_require_manage_options('jawda_handle_migration_request');

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'jawda_lookups_migrate')) {
            jawda_log_blocked_request('jawda_handle_migration_request');
            wp_die(esc_html__('Unauthorized', 'jawda'), esc_html__('Unauthorized', 'jawda'), ['response' => 403]);
        }
        jawda_clean_and_seed_lookups();
        exit; // Stop further execution
    }
}
add_action('admin_init', 'jawda_handle_migration_request');

/**
 * Generate a nonce-protected migration URL.
 *
 * @return string
 */
function jawda_get_lookups_migration_url(): string {
    $url = add_query_arg('jawda_clean_seed_lookups', 'confirm', admin_url('admin.php'));

    return wp_nonce_url($url, 'jawda_lookups_migrate');
}

/**
 * Render an admin notice with the migration trigger link.
 */
function jawda_render_lookups_migration_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['page']) || 'jawda-lookups' !== $_GET['page']) {
        return;
    }

    $url = jawda_get_lookups_migration_url();
    ?>
    <div class="notice notice-warning">
        <p>
            <?php esc_html_e('Running the lookups migration will reset existing lookup data to defaults.', 'jawda'); ?>
            <a class="button button-secondary" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Run Lookups Migration', 'jawda'); ?></a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'jawda_render_lookups_migration_notice');
