<?php
/**
 * Read-only Developer Types list inside Jawda Lookups.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function() {
    add_submenu_page(
        'jawda-lookups',
        __('Developer Types', 'jawda'),
        __('Developer Types', 'jawda'),
        'manage_options',
        'jawda-developer-types',
        'jawda_render_developer_types_lookup'
    );
});

function jawda_render_developer_types_lookup() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $types = jawda_get_developer_types();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Developer Types Lookup', 'jawda'); ?></h1>
        <p class="description"><?php esc_html_e('This list feeds the Developer Type dropdown in the Developers Engine.', 'jawda'); ?></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'jawda'); ?></th>
                    <th><?php esc_html_e('Label', 'jawda'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($types as $type) : ?>
                <tr>
                    <td><?php echo esc_html($type['id']); ?></td>
                    <td><?php echo esc_html($type['label'] ?? ($type['name'] ?? '')); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e('To change these values, override the jawda_developer_types filter or update the lookup source.', 'jawda'); ?></p>
    </div>
    <?php
}
