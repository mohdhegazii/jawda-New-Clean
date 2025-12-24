<?php
/**
 * Meta Box for linking Unit Lookups to a Listing (property).
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the "Unit Details" meta box for the 'property' post type.
 */
function jawda_unit_lookups_add_meta_box() {
    add_meta_box(
        'jawda-unit-details',
        __( 'Unit Details', 'jawda' ),
        'jawda_unit_lookups_render_meta_box',
        'property',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'jawda_unit_lookups_add_meta_box' );

/**
 * Render the HTML for the "Unit Details" meta box.
 *
 * @param WP_Post $post The current post object.
 */
function jawda_unit_lookups_render_meta_box( $post ) {
    // Add a nonce field for security
    wp_nonce_field( 'jawda_save_unit_details', 'jawda_unit_details_nonce' );

    // Get saved values
    $unit_status_id = get_post_meta( $post->ID, '_unit_status_id', true );
    $construction_status_id = get_post_meta( $post->ID, '_construction_status_id', true );
    $finishing_type_id = get_post_meta( $post->ID, '_finishing_type_id', true );
    $delivery_timeframe_id = get_post_meta( $post->ID, '_delivery_timeframe_id', true );
    $view_ids = (array) get_post_meta( $post->ID, '_view_ids', true );
    $amenity_ids = (array) get_post_meta( $post->ID, '_amenity_ids', true );
    $offer_type_ids = (array) get_post_meta( $post->ID, '_offer_type_ids', true );

    ?>
    <table class="form-table">
        <tbody>
            <?php
            // Render single-select fields
            jawda_unit_lookups_render_select_field( __( 'Unit Status', 'jawda' ), '_unit_status_id', jawda_get_unit_statuses(), $unit_status_id );
            jawda_unit_lookups_render_select_field( __( 'Construction Status', 'jawda' ), '_construction_status_id', jawda_get_construction_statuses(), $construction_status_id );
            jawda_unit_lookups_render_select_field( __( 'Finishing Type', 'jawda' ), '_finishing_type_id', jawda_get_finishing_types(), $finishing_type_id );
            jawda_unit_lookups_render_select_field( __( 'Delivery Timeframe', 'jawda' ), '_delivery_timeframe_id', jawda_get_delivery_timeframes(), $delivery_timeframe_id );

            // Render multi-select (checkbox) fields
            jawda_unit_lookups_render_checkbox_field( __( 'Views / Orientation', 'jawda' ), '_view_ids', jawda_get_unit_views(), $view_ids );
            jawda_unit_lookups_render_checkbox_field( __( 'Amenities', 'jawda' ), '_amenity_ids', jawda_get_unit_amenities(), $amenity_ids );
            jawda_unit_lookups_render_checkbox_field( __( 'Offer Types', 'jawda' ), '_offer_type_ids', jawda_get_offer_types(), $offer_type_ids );
            ?>
        </tbody>
    </table>
    <?php
}

/**
 * Helper to render a <select> dropdown.
 */
function jawda_unit_lookups_render_select_field( $label, $meta_key, $options, $selected_value ) {
    ?>
    <tr>
        <th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></label></th>
        <td>
            <select name="<?php echo esc_attr( $meta_key ); ?>" id="<?php echo esc_attr( $meta_key ); ?>">
                <option value=""><?php esc_html_e( '— Select —', 'jawda' ); ?></option>
                <?php foreach ( $options as $option ) : ?>
                    <option value="<?php echo esc_attr( $option->id ); ?>" <?php selected( $selected_value, $option->id ); ?>>
                        <?php echo esc_html( $option->label_en . ' / ' . $option->label_ar ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php
}

/**
 * Helper to render a list of checkboxes for multi-select.
 */
function jawda_unit_lookups_render_checkbox_field( $label, $meta_key, $options, $selected_values ) {
    ?>
    <tr>
        <th scope="row"><?php echo esc_html( $label ); ?></th>
        <td>
            <fieldset>
                <?php foreach ( $options as $option ) : ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="<?php echo esc_attr( $meta_key ); ?>[]" value="<?php echo esc_attr( $option->id ); ?>" <?php checked( in_array( $option->id, $selected_values ) ); ?>>
                        <?php echo esc_html( $option->label_en . ' / ' . $option->label_ar ); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        </td>
    </tr>
    <?php
}

/**
 * Save the meta box data when the 'property' post type is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */
function jawda_unit_lookups_save_meta_box_data( $post_id ) {
    // Check nonce for security
    if ( ! isset( $_POST['jawda_unit_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['jawda_unit_details_nonce'] ), 'jawda_save_unit_details' ) ) {
        return;
    }

    // Don't save on autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check user permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Define all our meta keys
    $meta_keys = [
        '_unit_status_id'         => 'int',
        '_construction_status_id' => 'int',
        '_finishing_type_id'      => 'int',
        '_delivery_timeframe_id'  => 'int',
        '_view_ids'               => 'array',
        '_amenity_ids'            => 'array',
        '_offer_type_ids'         => 'array',
    ];

    foreach ( $meta_keys as $key => $type ) {
        if ( isset( $_POST[ $key ] ) ) {
            $value = wp_unslash( $_POST[ $key ] );

            if ( $type === 'int' ) {
                $sanitized_value = intval( $value );
            } elseif ( $type === 'array' ) {
                $sanitized_value = array_map( 'intval', (array) $value );
            } else {
                $sanitized_value = sanitize_text_field( $value );
            }

            if ( ! empty( $sanitized_value ) ) {
                update_post_meta( $post_id, $key, $sanitized_value );
            } else {
                delete_post_meta( $post_id, $key );
            }
        } else {
            // If the key isn't set in POST (e.g., all checkboxes unchecked), delete the meta.
            delete_post_meta( $post_id, $key );
        }
    }
}
add_action( 'save_post_property', 'jawda_unit_lookups_save_meta_box_data' );
