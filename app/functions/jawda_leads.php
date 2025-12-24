<?php

global $jawda_leads_db_version;
$jawda_leads_db_version = '1.2';

/**
 * register_activation_hook implementation
 *
 * will be called when user activates plugin first time
 * must create needed database tables
 */
function jawda_leads_install()
{
    global $wpdb;
    global $jawda_leads_db_version;

    $table_name = $wpdb->prefix . 'leadstable'; // do not forget about tables prefix

    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      name tinytext NOT NULL,
      email text NULL,
      phone tinytext NOT NULL,
      massege text NULL,
      packagename tinytext NULL,
      `date` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    );";

    // we do not execute sql directly
    // we are calling dbDelta which cant migrate database
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // save current database version for later use (on upgrade)
    add_option('jawda_leads_db_version', $jawda_leads_db_version);

    /**
     * [OPTIONAL] Example of updating to 1.1 version
     *
     * If you develop new version of plugin
     * just increment $jawda_leads_db_version variable
     * and add following block of code
     *
     * must be repeated for each new version
     * in version 1.1 we change email field
     * to contain 200 chars rather 100 in version 1.0
     * and again we are not executing sql
     * we are using dbDelta to migrate table changes
     */
    $installed_ver = get_option('jawda_leads_db_version');
    if ($installed_ver != $jawda_leads_db_version) {
        $sql = "CREATE TABLE " . $table_name . " (
          id int(11) NOT NULL AUTO_INCREMENT,
          name tinytext NOT NULL,
          email text NULL,
          phone tinytext NOT NULL,
          massege text NULL,
          packagename tinytext NULL,
          `date` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // notice that we are updating option, rather than adding it
        update_option('jawda_leads_db_version', $jawda_leads_db_version);
    }
}

add_action('after_switch_theme', 'jawda_leads_install');


/**
 * Trick to update plugin database, see docs
 */
function jawda_leads_update_db_check()
{
    global $jawda_leads_db_version;
    if (get_site_option('jawda_leads_db_version') != $jawda_leads_db_version) {
        jawda_leads_install();
    }
}

add_action('after_switch_theme', 'jawda_leads_update_db_check');

/**
 * PART 2. Defining Custom Table List
 * ============================================================================
 *
 * In this part you are going to define custom table list class,
 * that will display your database records in nice looking table
 *
 * http://codex.wordpress.org/Class_Reference/WP_List_Table
 * http://wordpress.org/extend/plugins/custom-list-table-example/
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Jawda_leads_List_Table class that will display our custom table
 * records in nice table
 */
class Jawda_leads_List_Table extends WP_List_Table
{
    /**
     * [REQUIRED] You must declare constructor and give some basic params
     */
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'lead',
            'plural' => 'leads',
        ));
    }

    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        return esc_html($item[$column_name]);
    }


    /**
     * when you hover row "Edit | Delete" links showed
     */
    function column_name($item)
    {
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &lead=2
        $actions = array(
            'edit' => sprintf('<a href="?page=leads_form&id=%s">%s</a>', $item['id'], __('Edit')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete')),
        );

        return sprintf('%s %s',
            esc_html($item['name']),
            $this->row_actions($actions)
        );
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => __('Name'),
            'date' => __('Date'),
            'email' => __('E-Mail'),
            'phone' => __('phone'),
            'massege' => __('massege'),
            'packagename' => __('packagename'),
        );
        return $columns;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'date' => array('date', false),
            'email' => array('email', false),
            'phone' => array('phone', false),
        );
        return $sortable_columns;
    }

    /**
     * [OPTIONAL] Return array of bult actions if has any
     */
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadstable';

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) {
                $ids = array_map('intval', $ids); // Sanitize all IDs
                $ids = implode(',', $ids);
            } elseif (is_numeric($ids)) {
                $ids = (int) $ids;
            } else {
                $ids = intval($ids);
            }

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadstable'; // do not forget about tables prefix

        $per_page = 15; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array

        // Harden ORDER BY (leads): allow only real table columns, prevent invalid column / injection
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}", 0 );
        if ( ! is_array( $columns ) ) { $columns = array(); }

        // allowlist only columns that actually exist in DB (important for different schemas)
        $allowed_orderby = array_values( array_intersect(
            array( 'id','name','email','phone','massege','packagename','date' ),
            $columns
        ) );

        $orderby_req = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : '';
        $order_req   = isset( $_REQUEST['order'] ) ? strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : '';

        $orderby = ( $orderby_req && in_array( $orderby_req, $allowed_orderby, true ) )
            ? $orderby_req
            : ( in_array( 'id', $allowed_orderby, true ) ? 'id' : ( $allowed_orderby[0] ?? 'id' ) );

        $order = ( $order_req === 'asc' ) ? 'ASC' : 'DESC';
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}

/**
 * PART 3. Admin page
 * ============================================================================
 *
 * In this part you are going to add admin page for custom table
 *
 * http://codex.wordpress.org/Administration_Menus
 */

/**
 * admin_menu hook implementation, will add pages to list leads and to add new one
 */
function jawda_leads_admin_menu()
{
    add_menu_page(__('Leads'), __('Leads'), 'activate_plugins', 'leads', 'jawda_leads_leads_page_handler');
    add_submenu_page('leads', __('Leads'), __('Leads'), 'activate_plugins', 'leads', 'jawda_leads_leads_page_handler');
    add_submenu_page('Leads', __('Add new'), __('Add new'), 'activate_plugins', 'leads_form', 'jawda_leads_leads_form_page_handler');
}

add_action('admin_menu', 'jawda_leads_admin_menu');

/**
 * List page handler
 *
 * This function renders our custom table
 * Notice how we display message about successfull deletion
 * Actualy this is very easy, and you can add as many features
 * as you want.
 *
 * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
 */
function jawda_leads_leads_page_handler()
{
    global $wpdb;

    $table = new Jawda_leads_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $cnt = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? count(array_map('intval', $_REQUEST['id'])) : 0;
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d'), $cnt) . '</p></div>';
    }
    ?>
<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h1 class="wp-heading-inline"><?php _e('Jawda Leads')?></h1>
    <?php
    /*
    <a class="page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=leads_form');?>"><?php _e('Add new')?></a>
    */
    ?>
    <hr class="wp-header-end">
    <div class="alignright">
      <a class="page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=leads_download');?>"><?php _e('Download My Leads')?></a>
    </div>

    <?php echo $message; ?>

    <form id="leads-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display() ?>
    </form>

</div>
<?php
}

/**
 * PART 4. Form for adding andor editing row
 * ============================================================================
 *
 * In this part you are going to add admin page for adding andor editing items
 * You cant put all form into this function, but in this example form will
 * be placed into meta box, and if you want you can split your form into
 * as many meta boxes as you want
 *
 * http://codex.wordpress.org/Data_Validation
 * http://codex.wordpress.org/Function_Reference/selected
 */

/**
 * Sanitize incoming lead payloads before database writes.
 *
 * @param array $payload Raw request payload.
 * @return array
 */
function jawda_leads_sanitize_payload(array $payload): array
{
    $allowed = [
        'id'         => 'int',
        'name'       => 'text',
        'email'      => 'email',
        'phone'      => 'text',
        'massege'    => 'text',
        'packagename'=> 'text',
    ];

    $sanitized = [];

    foreach ($allowed as $key => $type) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }

        $value = $payload[$key];

        if (is_array($value)) {
            $value = wp_json_encode($value);
        }

        if (is_string($value)) {
            $value = wp_unslash($value);
        }

        switch ($type) {
            case 'email':
                $sanitized[$key] = sanitize_email($value);
                break;
            case 'int':
                $sanitized[$key] = absint($value);
                break;
            case 'url':
                $sanitized[$key] = esc_url_raw($value);
                break;
            default:
                $sanitized[$key] = sanitize_text_field($value);
        }
    }

    return $sanitized;
}

function jawda_leads_leads_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'leadstable'; // do not forget about tables prefix

    $message = '';
    $notice = '';

    // this is default $item which will be used for new records
    $default = array('id' => 0,'name' => '','email' => '','phone' => null,'massege' => null,'packagename' => null);

    // here we are verifying does this request is post back and have correct nonce
    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        // combine our default item with request params
        $raw_item = shortcode_atts($default, $_REQUEST);
        $item = jawda_leads_sanitize_payload($raw_item);


            if ($item['id'] == 0) {
                $result = $wpdb->insert($table_name, $item);
                $item['id'] = $wpdb->insert_id;
                if ($result) {
                    $message = __('Item was successfully saved');
                } else {
                    $notice = __('There was an error while saving item');
                }
            } else {
                $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                if ($result) {
                    $message = __('Item was successfully updated');
                } else {
                    $notice = __('There was an error while updating item');
                }
            }

    }
    else {
        // if this is not post back we load item to edit or give new one to create
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", (int) $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item = $default;
                $notice = __('Item not found');
            }
        }
    }

    // here we adding our custom meta box
    add_meta_box('leads_form_meta_box', 'lead data', 'jawda_leads_leads_form_meta_box_handler', 'lead', 'normal', 'default');

    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Add New lead')?> </h2>
      <a class="add-new-h2"
                                href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=leads');?>"><?php _e('back to list')?></a>


    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php /* And here we call our custom meta box */ ?>
                    <?php do_meta_boxes('lead', 'normal', $item); ?>
                    <input type="submit" value="<?php _e('Save')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>
<?php
}

/**
 * This function renders our custom meta box
 * $item is row
 *
 * @param $item
 */
function jawda_leads_leads_form_meta_box_handler($item)
{
    ?>

<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="name"><?php _e('Name')?></label>
        </th>
        <td>
    <input id="name" name="name" type="text" style="width: 95%" value="<?php echo esc_attr($item['name']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your name')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="email"><?php _e('E-Mail')?></label>
        </th>
        <td>
            <input id="email" name="email" type="email" style="width: 95%" value="<?php echo esc_attr($item['email']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your E-Mail')?>" required>
        </td>
    </tr>

    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="phone"><?php _e('phone')?></label>
        </th>
        <td>
            <input id="phone" name="phone" type="text" style="width: 95%" value="<?php echo esc_attr($item['phone']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your phone')?>" required>
        </td>
    </tr>

    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="massege"><?php _e('massege')?></label>
        </th>
        <td>
            <input id="massege" name="massege" type="text" style="width: 95%" value="<?php echo esc_attr($item['massege']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your massege')?>" required>
        </td>
    </tr>

    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="packagename"><?php _e('packagename')?></label>
        </th>
        <td>
            <input id="packagename" name="packagename" type="text" style="width: 95%" value="<?php echo esc_attr($item['packagename']); ?>"
                   size="50" class="code" placeholder="<?php _e('Your packagename')?>" required>
        </td>
    </tr>

    </tbody>
</table>
<?php
}
