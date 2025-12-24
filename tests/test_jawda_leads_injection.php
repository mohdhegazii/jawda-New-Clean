<?php

require_once 'tests/bootstrap.php';
require_once 'app/functions/jawda_leads.php';

class Test_Jawda_Leads_Injection extends WP_UnitTestCase {

    private $table_name;
    private $table_class;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'leadstable';
        $this->table_class = new Jawda_leads_List_Table();

        // Mock the WordPress database and request objects
        $this->mock_wpdb();
        $this->mock_request();

        // Create the table
        // jawda_leads_install();
    }

    private function mock_wpdb() {
        global $wpdb;
        $wpdb = $this->getMockBuilder(wpdb::class)
                     ->setMethods(['get_var', 'query'])
                     ->getMock();

        // Configure the get_var method to return the table name
        $wpdb->prefix = 'wp_';
        $wpdb->expects($this->any())
             ->method('get_var')
             ->with($this->equalTo("SELECT COUNT(id) FROM {$this->table_name}"))
             ->will($this->returnValue(3)); // Simulate 3 total items
    }

    private function mock_request() {
        $_REQUEST['page'] = 'leads';
        $_REQUEST['paged'] = 1;
        $_REQUEST['orderby'] = 'name';
        $_REQUEST['order'] = 'asc';
    }

    public function test_sql_injection() {
        global $wpdb;

        // Simulate an SQL injection attack
        $_REQUEST['action'] = 'delete';
        $_REQUEST['id'] = '1 OR 1=1'; // Malicious input

        // Set up the expectation for the query method
        $expected_query = "DELETE FROM {$this->table_name} WHERE id IN(1)";
        $wpdb->expects($this->once())
             ->method('query')
             ->with($this->equalTo($expected_query));

        // Call the method that processes the bulk action
        $this->table_class->process_bulk_action();
    }

    public function tearDown(): void {
        global $wpdb;
        // Clean up the table
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        parent::tearDown();
    }
}
