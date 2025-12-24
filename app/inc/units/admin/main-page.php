<?php
/**
 * Admin UI for managing Unit Lookups.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the "Unit Lookups" submenu page.
 */
function jawda_unit_lookups_add_admin_menu() {
    add_submenu_page(
        'jawda-lookups', // Parent slug for "Jawda Lookups".
        __( 'Unit Lookups', 'jawda' ),
        __( 'Unit Lookups', 'jawda' ),
        'manage_options',
        'jawda-unit-lookups',
        'jawda_unit_lookups_render_admin_page'
    );
}
add_action( 'admin_menu', 'jawda_unit_lookups_add_admin_menu' );

/**
 * Renders the main admin page with a tabbed interface.
 */
function jawda_unit_lookups_render_admin_page() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'jawda' ) );
    }

    $tabs = jawda_unit_lookups_get_tabs();
    $current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? sanitize_key( $_GET['tab'] ) : 'unit_status';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Unit Lookups Management', 'jawda' ); ?></h1>
        <?php settings_errors(); ?>

        <h2 class="nav-tab-wrapper">
            <?php
            foreach ( $tabs as $tab_key => $tab_name ) {
                $active = ( $current_tab === $tab_key ) ? ' nav-tab-active' : '';
                echo '<a href="?page=jawda-unit-lookups&tab=' . esc_attr( $tab_key ) . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $tab_name ) . '</a>';
            }
            ?>
        </h2>

        <div class="tab-content">
            <?php
            // The content for each tab (list table and forms) will be handled here.
            // We will include a separate file for the list table to keep this clean.
            if ( $current_tab === 'market_type' ) {
                require_once __DIR__ . '/class-jawda-market-types-list-table.php';
                $list_table = new Jawda_Market_Types_List_Table();
            } else {
                require_once __DIR__ . '/class-jawda-unit-lookups-list-table.php';
                $list_table = new Jawda_Unit_Lookups_List_Table();
            }
            $list_table->prepare_items();

            // Check for add/edit actions
            $action = $list_table->current_action();
            if ( 'add' === $action || 'edit' === $action ) {
                 // The form for adding/editing will be displayed
                jawda_unit_lookups_render_edit_form( $current_tab );
            } else {
                // Display the list table
                echo '<h3>' . sprintf( esc_html__( 'Manage %s', 'jawda' ), esc_html( $tabs[$current_tab] ) ) . '</h3>';
                echo '<p><a href="?page=jawda-unit-lookups&tab=' . esc_attr( $current_tab ) . '&action=add" class="button button-primary">' . esc_html__( 'Add New', 'jawda' ) . '</a></p>';
                $list_table->display();
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Defines the tabs for the admin page.
 *
 * @return array
 */
function jawda_unit_lookups_get_tabs() {
    return [
        'unit_status'         => __( 'Unit Status', 'jawda' ),
        'construction_status' => __( 'Construction Status', 'jawda' ),
        'finishing_type'      => __( 'Finishing Types', 'jawda' ),
        'delivery_timeframe'  => __( 'Delivery Timeframes', 'jawda' ),
        'view'                => __( 'Views / Orientation', 'jawda' ),
        'amenity'             => __( 'Amenities', 'jawda' ),
        'offer_type'          => __( 'Offer Types', 'jawda' ),
        'market_type'         => __( 'Market Types', 'jawda' ),
    ];
}


/**
 * Handles form submissions for adding, editing, and deleting lookups.
 * This runs on 'admin_init' before the page is rendered.
 */
function jawda_unit_lookups_handle_form_actions() {
    if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'jawda-unit-lookups' ) {
        return;
    }

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';

     // Handle delete action from the list table (GET request)
    if ( isset( $_GET['action'], $_GET['lookup_id'], $_GET['_wpnonce'] ) && 'delete' === $_GET['action'] ) {
        $id = absint( $_GET['lookup_id'] );
        $nonce = sanitize_key( $_GET['_wpnonce'] );

        if ( wp_verify_nonce( $nonce, 'delete_lookup_' . $id ) ) {
            if ( $current_tab === 'market_type' ) {
                $can_hard_delete = Jawda_Market_Type_Service::can_hard_delete( $id );
                $result = Jawda_Market_Type_Service::delete_market_type( $id );
                $message = $can_hard_delete ? __( 'Market type deleted successfully.', 'jawda' ) : __( 'Market type deactivated because it is linked to listings.', 'jawda' );
            } else {
                $result = Jawda_Unit_Lookups_Service::delete_lookup( $id );
                $message = __( 'Lookup deleted (deactivated) successfully.', 'jawda' );
            }
            $message_type = is_wp_error( $result ) ? 'error' : 'updated';
            if ( is_wp_error( $result ) ) {
                $message = $result->get_error_message();
            }
            add_settings_error('jawda_lookups', 'jawda_lookups_message', $message, $message_type);
        }
    }
}
add_action( 'admin_init', 'jawda_unit_lookups_handle_form_actions' );


/**
 * Renders the add/edit form.
 *
 * @param string $current_tab The active tab (group_key).
 */
function jawda_unit_lookups_render_edit_form($current_tab) {
    $tabs = jawda_unit_lookups_get_tabs();
    $id = isset( $_GET['lookup_id'] ) ? absint( $_GET['lookup_id'] ) : 0;

    if ( $current_tab === 'market_type' ) {
        $item = ( $id > 0 ) ? Jawda_Market_Type_Service::get_market_type( $id ) : null;
    } else {
        $item = ( $id > 0 ) ? Jawda_Unit_Lookups_Service::get_lookup( $id ) : null;
    }

    $is_editing = ! is_null( $item );

    $slug = $is_editing ? $item->slug : '';
    $label_en = $is_editing ? $item->label_en : '';
    $label_ar = $is_editing ? $item->label_ar : '';
    $sort_order = $is_editing ? $item->sort_order : 0;
    $extra_data = $is_editing ? Jawda_Unit_Lookups_Service::decode_extra_data($item->extra_data) : [];

    ?>
    <h3><?php echo $is_editing ? esc_html__( 'Edit Lookup', 'jawda' ) : esc_html__( 'Add New Lookup', 'jawda' ); ?></h3>
    <p><?php echo sprintf( esc_html__( 'Editing in group: %s', 'jawda' ), '<strong>' . esc_html( $tabs[$current_tab] ) . '</strong>' ); ?></p>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="jawda_save_lookup_action" />
        <input type="hidden" name="page" value="jawda-unit-lookups" />
        <input type="hidden" name="tab" value="<?php echo esc_attr($current_tab); ?>" />
        <input type="hidden" name="group_key" value="<?php echo esc_attr( $current_tab ); ?>" />
        <input type="hidden" name="lookup_id" value="<?php echo esc_attr( $id ); ?>" />
        <?php wp_nonce_field( 'jawda_save_lookup', 'jawda_lookup_nonce' ); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="slug"><?php esc_html_e( 'Slug', 'jawda' ); ?></label></th>
                    <td><input name="slug" type="text" id="slug" value="<?php echo esc_attr( $slug ); ?>" class="regular-text" required>
                    <p class="description"><?php esc_html_e( 'A unique, machine-readable key. E.g., "ready-to-move".', 'jawda' ); ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="label_en"><?php esc_html_e( 'Label (English)', 'jawda' ); ?></label></th>
                    <td><input name="label_en" type="text" id="label_en" value="<?php echo esc_attr( $label_en ); ?>" class="regular-text"></td>
                </tr>
                 <tr>
                    <th scope="row"><label for="label_ar"><?php esc_html_e( 'Label (Arabic)', 'jawda' ); ?></label></th>
                    <td><input name="label_ar" type="text" id="label_ar" value="<?php echo esc_attr( $label_ar ); ?>" class="regular-text" dir="rtl"></td>
                </tr>
                 <tr>
                    <th scope="row"><label for="sort_order"><?php esc_html_e( 'Sort Order', 'jawda' ); ?></label></th>
                    <td><input name="sort_order" type="number" id="sort_order" value="<?php echo esc_attr( $sort_order ); ?>" class="small-text"></td>
                </tr>
                <?php if ( $current_tab === 'delivery_timeframe' ) : ?>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Delivery Details', 'jawda' ); ?></th>
                        <td>
                            <label for="year"><?php esc_html_e( 'Year', 'jawda' ); ?></label>
                            <input name="year" type="number" id="year" value="<?php echo esc_attr( $extra_data['year'] ?? '' ); ?>" placeholder="e.g., 2025" class="small-text">

                            <label for="quarter"><?php esc_html_e( 'Quarter', 'jawda' ); ?></label>
                            <select name="quarter" id="quarter">
                                <option value="" <?php selected( ($extra_data['quarter'] ?? ''), '' ); ?>>-</option>
                                <option value="Q1" <?php selected( ($extra_data['quarter'] ?? ''), 'Q1' ); ?>>Q1</option>
                                <option value="Q2" <?php selected( ($extra_data['quarter'] ?? ''), 'Q2' ); ?>>Q2</option>
                                <option value="Q3" <?php selected( ($extra_data['quarter'] ?? ''), 'Q3' ); ?>>Q3</option>
                                <option value="Q4" <?php selected( ($extra_data['quarter'] ?? ''), 'Q4' ); ?>>Q4</option>
                            </select>

                             <label for="profile"><?php esc_html_e( 'Profile', 'jawda' ); ?></label>
                            <input name="profile" type="text" id="profile" value="<?php echo esc_attr( $extra_data['profile'] ?? '' ); ?>" placeholder="e.g., ready-to-move" class="regular-text">
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php submit_button( $is_editing ? __( 'Save Changes', 'jawda' ) : __( 'Add New Lookup', 'jawda' ) ); ?>
         <a href="?page=jawda-unit-lookups&tab=<?php echo esc_attr($current_tab); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'jawda' ); ?></a>
    </form>
    <?php
}

/**
 * Handle admin-post action for saving.
 */
function jawda_save_lookup_action_handler() {
    jawda_require_manage_options('jawda_save_lookup_action_handler');

    // Check nonce for security
    $nonce = isset( $_POST['jawda_lookup_nonce'] ) ? sanitize_key( wp_unslash( $_POST['jawda_lookup_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'jawda_save_lookup' ) ) {
        jawda_log_blocked_request('jawda_save_lookup_action_handler');
        wp_die(__( 'Invalid nonce specified', 'jawda' ), __( 'Error', 'jawda' ), ['response' => 403]);
    }

    $id = isset( $_POST['lookup_id'] ) ? absint( $_POST['lookup_id'] ) : 0;
    $group_key = isset( $_POST['group_key'] ) ? sanitize_key( $_POST['group_key'] ) : '';

    if ( $group_key === 'market_type' ) {
        $data = [
            'label_en'   => isset( $_POST['label_en'] ) ? sanitize_text_field( wp_unslash( $_POST['label_en'] ) ) : '',
            'label_ar'   => isset( $_POST['label_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['label_ar'] ) ) : '',
        ];
        if ( $id > 0 ) {
            $result = Jawda_Market_Type_Service::update_market_type( $id, $data );
            $message = __( 'Market type updated successfully.', 'jawda' );
        } else {
            $result = Jawda_Market_Type_Service::create_market_type( $data );
            $message = __( 'Market type created successfully.', 'jawda' );
        }
    } else {
        $data = [
            'slug'       => isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '',
            'label_en'   => isset( $_POST['label_en'] ) ? sanitize_text_field( wp_unslash( $_POST['label_en'] ) ) : '',
            'label_ar'   => isset( $_POST['label_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['label_ar'] ) ) : '',
            'sort_order' => isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0,
            // For delivery timeframe
            'year'       => isset( $_POST['year'] ) ? intval( $_POST['year'] ) : null,
            'quarter'    => isset( $_POST['quarter'] ) ? sanitize_text_field( wp_unslash( $_POST['quarter'] ) ) : null,
            'profile'    => isset( $_POST['profile'] ) ? sanitize_text_field( wp_unslash( $_POST['profile'] ) ) : null,
        ];

        if ( $id > 0 ) {
            // Update existing
            $result = Jawda_Unit_Lookups_Service::update_lookup( $id, $data );
            $message = __( 'Lookup updated successfully.', 'jawda' );
        } else {
            // Create new
            $result = Jawda_Unit_Lookups_Service::create_lookup( $group_key, $data );
            $message = __( 'Lookup created successfully.', 'jawda' );
        }
    }

    $message_type = is_wp_error( $result ) ? 'error' : 'updated';
    if( is_wp_error( $result ) ) {
        $message = $result->get_error_message();
    }

    add_settings_error('jawda_lookups', 'jawda_lookups_message', $message, $message_type);

    // Redirect back to the tab.
    $redirect_url = admin_url( 'admin.php?page=jawda-unit-lookups&tab=' . urlencode( $group_key ) );
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action('admin_post_jawda_save_lookup_action', 'jawda_save_lookup_action_handler');
