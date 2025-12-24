<?php
/**
 * Admin menu and page loader for Project Features lookup management.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jawda_Project_Features_Admin {
    private $features_page_hook = '';
    private $finishing_page_hook = '';
    private $views_page_hook = '';
    private $orientations_page_hook = '';
    private $facades_page_hook = '';
    private $marketing_page_hook = '';

    private $page_handler;
    private $finishing_page_handler;
    private $views_page_handler;
    private $orientations_page_handler;
    private $facades_page_handler;
    private $marketing_page_handler;

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * DISABLED: Project Features admin module removed safely
     */
    public function register_menu() {
        return;
    }

    public function on_load() {
        $this->page_handler = $this->boot_page_handler([
            'page_slug' => 'jawda-project-features',
            'allowed_types' => ['feature', 'amenity', 'facility'],
        ]);
    }

    public function on_load_finishing() {
        $this->finishing_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-finishing',
            'forced_type'      => 'finishing',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['finishing'],
            'labels'           => [
                'list_title'      => __('Finishing Types', 'jawda'),
                'add_new'         => __('Add Finishing Type', 'jawda'),
                'add_heading'     => __('Add Finishing Type', 'jawda'),
                'edit_heading'    => __('Edit Finishing Type', 'jawda'),
                'add_button'      => __('Add Finishing Type', 'jawda'),
                'update_button'   => __('Update Finishing Type', 'jawda'),
                'success_message' => __('Finishing type saved successfully.', 'jawda'),
                'delete_success'  => __('Finishing type deleted.', 'jawda'),
                'delete_error'    => __('Failed to delete finishing type.', 'jawda'),
            ],
        ]);
    }

    public function on_load_views() {
        $this->views_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-views',
            'forced_type'      => 'view',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['view'],
            'labels'           => [
                'list_title'      => __('Unit Views', 'jawda'),
                'add_new'         => __('Add View', 'jawda'),
                'add_heading'     => __('Add View', 'jawda'),
                'edit_heading'    => __('Edit View', 'jawda'),
                'add_button'      => __('Add View', 'jawda'),
                'update_button'   => __('Update View', 'jawda'),
                'success_message' => __('View saved successfully.', 'jawda'),
                'delete_success'  => __('View deleted.', 'jawda'),
                'delete_error'    => __('Failed to delete view.', 'jawda'),
            ],
        ]);
    }

    public function on_load_orientations() {
        $this->orientations_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-orientations',
            'forced_type'      => 'orientation',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['orientation'],
            'labels'           => [
                'list_title'      => __('Orientations', 'jawda'),
                'add_new'         => __('Add Orientation', 'jawda'),
                'add_heading'     => __('Add Orientation', 'jawda'),
                'edit_heading'    => __('Edit Orientation', 'jawda'),
                'add_button'      => __('Add Orientation', 'jawda'),
                'update_button'   => __('Update Orientation', 'jawda'),
                'success_message' => __('Orientation saved successfully.', 'jawda'),
                'delete_success'  => __('Orientation deleted.', 'jawda'),
                'delete_error'    => __('Failed to delete orientation.', 'jawda'),
            ],
        ]);
    }

    public function on_load_facades() {
        $this->facades_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-facades',
            'forced_type'      => 'facade',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['facade'],
            'labels'           => [
                'list_title'      => __('Facades & Positions', 'jawda'),
                'add_new'         => __('Add Facade / Position', 'jawda'),
                'add_heading'     => __('Add Facade / Position', 'jawda'),
                'edit_heading'    => __('Edit Facade / Position', 'jawda'),
                'add_button'      => __('Add Facade / Position', 'jawda'),
                'update_button'   => __('Update Facade / Position', 'jawda'),
                'success_message' => __('Facade saved successfully.', 'jawda'),
                'delete_success'  => __('Facade deleted.', 'jawda'),
                'delete_error'    => __('Failed to delete facade.', 'jawda'),
            ],
        ]);
    }

    public function on_load_marketing_orientation() {
        $this->marketing_page_handler = $this->boot_page_handler([
            'page_slug'        => 'jawda-project-features-marketing-orientation',
            'forced_type'      => 'marketing_orientation',
            'default_contexts' => ['projects' => 0, 'properties' => 1],
            'allowed_types'    => ['marketing_orientation'],
            'labels'           => [
                'list_title'      => __('Marketing Orientation Labels', 'jawda'),
                'add_new'         => __('Add Marketing Label', 'jawda'),
                'add_heading'     => __('Add Marketing Label', 'jawda'),
                'edit_heading'    => __('Edit Marketing Label', 'jawda'),
                'add_button'      => __('Add Marketing Label', 'jawda'),
                'update_button'   => __('Update Marketing Label', 'jawda'),
                'success_message' => __('Marketing label saved successfully.', 'jawda'),
                'delete_success'  => __('Marketing label deleted.', 'jawda'),
                'delete_error'    => __('Failed to delete marketing label.', 'jawda'),
            ],
        ]);
    }

    public function render_page() {
        if ($this->page_handler) {
            $this->page_handler->render_page();
        }
    }

    public function render_finishing_page() {
        if ($this->finishing_page_handler) {
            $this->finishing_page_handler->render_page();
        }
    }

    public function render_views_page() {
        if ($this->views_page_handler) {
            $this->views_page_handler->render_page();
        }
    }

    public function render_orientations_page() {
        if ($this->orientations_page_handler) {
            $this->orientations_page_handler->render_page();
        }
    }

    public function render_facades_page() {
        if ($this->facades_page_handler) {
            $this->facades_page_handler->render_page();
        }
    }

    public function render_marketing_orientation_page() {
        if ($this->marketing_page_handler) {
            $this->marketing_page_handler->render_page();
        }
    }

    private function boot_page_handler($args = []) {
        require_once __DIR__ . '/features-list-table.php';
        require_once __DIR__ . '/features-page.php';

        $handler = new Jawda_Project_Features_Page($args);
        $handler->handle_form_submission();
        $handler->list_table->process_bulk_action();
        $handler->list_table->prepare_items();

        return $handler;
    }
}

new Jawda_Project_Features_Admin();
