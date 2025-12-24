<?php
/**
 * Loader for Catalog SEO overrides for dynamic catalogs.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/db-update.php';
require_once __DIR__ . '/class-jawda-catalog-context.php';
require_once __DIR__ . '/class-jawda-catalog-seo-service.php';
require_once __DIR__ . '/seo-hooks.php';

if (is_admin()) {
    require_once __DIR__ . '/admin/menu.php';
}
