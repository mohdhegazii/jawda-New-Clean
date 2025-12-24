<?php
/**
 * WP_List_Table class for displaying Market Type lookups.
 *
 * @package Jawda
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Jawda_Market_Types_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Market Type', 'jawda' ),
            'plural'   => __( 'Market Types', 'jawda' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Get the list of columns.
     *
     * @return array
     */
    public function get_columns() {
        return [
            'cb'         => '<input type="checkbox" />',
            'label_en'   => __( 'Name (EN)', 'jawda' ),
            'label_ar'   => __( 'Name (AR)', 'jawda' ),
            'listings_count' => __( 'Listings Count', 'jawda' ),
            'is_active'  => __( 'Status', 'jawda' ),
        ];
    }

    /**
     * Get the sortable columns.
     *
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'label_en' => [ 'label_en', false ],
            'label_ar' => [ 'label_ar', false ],
        ];
    }

    /**
     * Prepare the items for the table.
     */
    public function prepare_items() {
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

        $args = [
            'orderby' => ! empty( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'label_en',
            'order'   => ! empty( $_REQUEST['order'] ) ? sanitize_key( $_REQUEST['order'] ) : 'ASC',
            'include_inactive' => true,
        ];

        $this->items = Jawda_Market_Type_Service::get_all_market_types( $args );
    }

    /**
     * Default column rendering.
     *
     * @param object $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
    }

    /**
     * Render the checkbox column.
     *
     * @param object $item
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="market_type_id[]" value="%s" />', $item->id );
    }

    /**
     * Render the 'is_active' column.
     *
     * @param object $item
     * @return string
     */
    public function column_is_active( $item ) {
        return $item->is_active ? __( 'Active', 'jawda' ) : __( 'Inactive', 'jawda' );
    }

    /**
     * Render the name column with edit/delete actions.
     *
     * @param object $item
     * @return string
     */
    public function column_label_en( $item ) {
        $actions = [
            'edit'   => sprintf( '<a href="?page=%s&action=edit&lookup_id=%s&tab=market_type">' . __( 'Edit', 'jawda' ) . '</a>', $_REQUEST['page'], $item->id ),
            'delete' => sprintf( '<a href="?page=%s&action=delete&lookup_id=%s&_wpnonce=%s&tab=market_type">' . __( 'Delete', 'jawda' ) . '</a>', $_REQUEST['page'], $item->id, wp_create_nonce( 'delete_lookup_' . $item->id ) ),
        ];
        return sprintf( '<strong>%s</strong>%s', esc_html( $item->label_en ), $this->row_actions( $actions ) );
    }

    /**
     * Render the 'listings_count' column.
     *
     * @param object $item
     * @return string
     */
    public function column_listings_count( $item ) {
        $count = Jawda_Market_Type_Service::get_linked_listings_count( $item->id );
        $url = admin_url( 'edit.php?post_type=property&jawda_market_type_id=' . $item->id );
        return sprintf( '<a href="%s">%d</a>', esc_url( $url ), $count );
    }
}
