<?php
/**
 * Loader for the Project Features lookup system.
 *
 * @package Jawda
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/template-helpers.php';

if (is_admin() && !wp_doing_ajax()) {
    require_once __DIR__ . '/admin-menu.php';
}
