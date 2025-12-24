<?php
/**
 * Loader for the Developers Engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/db-update.php';
require_once __DIR__ . '/class-jawda-developers-service.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/lookups.php';
require_once __DIR__ . '/routing.php';

if (is_admin()) {
    require_once __DIR__ . '/admin/menu.php';
    require_once __DIR__ . '/admin/developer-types.php';
}
