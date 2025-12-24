<?php
/**
 * Capability helpers for admin actions.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('jawda_log_blocked_request')) {
    /**
     * Log blocked admin requests when debugging is enabled.
     *
     * @param string $handler_name Handler identifier.
     * @return void
     */
    function jawda_log_blocked_request(string $handler_name): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[%s] Blocked request by user ID %d', $handler_name, get_current_user_id()));
        }
    }
}

if (!function_exists('jawda_require_manage_options')) {
    /**
     * Require manage_options capability or die with 403.
     *
     * @param string $handler_name Handler identifier for logging.
     * @return void
     */
    function jawda_require_manage_options(string $handler_name): void {
        if (current_user_can('manage_options')) {
            return;
        }

        jawda_log_blocked_request($handler_name);
        wp_die(
            esc_html__('Unauthorized', 'jawda'),
            esc_html__('Unauthorized', 'jawda'),
            ['response' => 403]
        );
    }
}
