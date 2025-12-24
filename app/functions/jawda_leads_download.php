<?php

	/* ------------------------------------------------------------------------ */
	// wp load
	require_once(ABSPATH . 'wp-load.php');

	/* ------------------------------------------------------------------------ */
	// Admin Page
	function my_admin_menu() {
		add_submenu_page('Leads', __('Download Leads'), __('Download Leads'), 'activate_plugins', 'leads_download', 'jawda_leads_download_page_handler');
	}
	add_action( 'admin_menu', 'my_admin_menu' );


	/* ------------------------------------------------------------------------ */
	// add_action
	if( isset($GLOBALS['pagenow']) AND $GLOBALS['pagenow'] == 'admin.php' AND isset($_GET['page']) AND $_GET['page'] == 'leads_download' )
	{
		//do_action('init', 'jawda_dwonload_function');
		jawda_dwonload_function();
		die();
	}

	/* ------------------------------------------------------------------------ */
	// Page Content
	function jawda_leads_download_page_handler() {}


	/* ------------------------------------------------------------------------ */
	// Page Content
	function array2csv($array) {

		$ja_csv_headers = array('ID','Name','email','phone','massege','packagename','date');

		// $first = true;
		$rows = '';

		foreach ($ja_csv_headers as $header) {
		$rows .= '"' . $header . '",';
		}
		$rows .= "\n";

		foreach ($array as $lead) {

			foreach ($lead as $key => $value) {
				$rows .= '"' . $value . '",';
			}

			$rows .= "\n";

		}

		return $rows;
	}

	/* ------------------------------------------------------------------------ */
	// Page Content
	function jawda_dwonload_function() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			die('ليس لديك الصلاحيات الكافية للوصول إلى هذه الصفحة.');
		}

		$filename = "leads_" . date("Y-m-d") . ".csv";
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Expires: 0' );
    header( 'Pragma: public' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'leadstable';
		$query = "SELECT * FROM $table_name	";
		$result = $wpdb->get_results($query);
		$filename = "leads_" . date("Y-m-d") . ".csv";
		echo array2csv($result);
		die();

	}
