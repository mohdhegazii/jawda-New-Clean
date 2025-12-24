<?php
/**
 * WP_List_Table for displaying Unit Lookups.
 *
 * @package Jawda
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Jawda_Unit_Lookups_List_Table extends WP_List_Table {

    private $group_key = '';

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Lookup', 'jawda' ),
            'plural'   => __( 'Lookups', 'jawda' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Prepare the items for the table to process.
     */
    public function prepare_items() {
        $this->group_key = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'unit_status';

        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        // We fetch all items for the group. Pagination can be added later if needed.
        $this->items = Jawda_Unit_Lookups_Service::get_lookups_by_group( $this->group_key, ['is_active' => null] );
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table.
     * @return array
     */
    public function get_columns() {
        return [
            'slug'       => __( 'Slug', 'jawda' ),
            'label_en'   => __( 'Label (English)', 'jawda' ),
            'label_ar'   => __( 'Label (Arabic)', 'jawda' ),
            'sort_order' => __( 'Sort Order', 'jawda' ),
            'is_active'  => __( 'Status', 'jawda' ),
        ];
    }

    /**
     * Define which columns are hidden
     * @return array
     */
    public function get_hidden_columns() {
        return [];
    }

    /**
     * Define the sortable columns
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'slug'       => [ 'slug', false ],
            'label_en'   => [ 'label_en', false ],
            'label_ar'   => [ 'label_ar', false ],
            'sort_order' => [ 'sort_order', true ],
            'is_active'  => [ 'is_active', false ],
        ];
    }

    /**
     * Render a column when no column specific method exist.
     * @param array $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'label_en':
            case 'label_ar':
            case 'slug':
            case 'sort_order':
                return esc_html( $item->$column_name );
            case 'is_active':
                return $item->is_active ? '✅ ' . __( 'Active', 'jawda' ) : '❌ ' . __( 'Inactive', 'jawda' );
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the 'slug' column with action links.
     * @param object $item
     * @return string
     */
    function column_slug( $item ) {
        $edit_url = sprintf(
            '?page=%s&tab=%s&action=edit&lookup_id=%s',
            esc_attr( $_REQUEST['page'] ),
            esc_attr( $this->group_key ),
            absint( $item->id )
        );

        $delete_nonce = wp_create_nonce( 'delete_lookup_' . $item->id );
        $delete_url = sprintf(
            '?page=%s&tab=%s&action=delete&lookup_id=%s&_wpnonce=%s',
             esc_attr( $_REQUEST['page'] ),
            esc_attr( $this->group_key ),
            absint( $item->id ),
            $delete_nonce
        );

        $actions = [
            'edit' => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'jawda' ) . '</a>',
            'delete' => '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'' . __( 'Are you sure you want to deactivate this item?', 'jawda' ) . '\');">' . __( 'Delete (Deactivate)', 'jawda' ) . '</a>',
        ];

        return '<strong>' . esc_html( $item->slug ) . '</strong>' . $this->row_actions( $actions );
    }

    /**
     * Display a message when no items are found
     */
    public function no_items() {
        _e( 'No lookups found in this group.', 'jawda' );
    }
}
