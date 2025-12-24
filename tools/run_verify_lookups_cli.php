<?php
$WP = "/Applications/XAMPP/xamppfiles/htdocs/aqarand";
$THEME = $WP . "/wp-content/themes/aqarand";

// Minimal web context for plugins (Polylang...etc)
$_SERVER['HTTP_HOST']       = $_SERVER['HTTP_HOST']       ?? 'localhost';
$_SERVER['SERVER_NAME']     = $_SERVER['SERVER_NAME']     ?? 'localhost';
$_SERVER['REQUEST_URI']     = $_SERVER['REQUEST_URI']     ?? '/';
$_SERVER['REQUEST_METHOD']  = $_SERVER['REQUEST_METHOD']  ?? 'GET';
$_SERVER['REMOTE_ADDR']     = $_SERVER['REMOTE_ADDR']     ?? '127.0.0.1';
$_SERVER['HTTPS']           = $_SERVER['HTTPS']           ?? 'off';
$_SERVER['SCRIPT_NAME']     = $_SERVER['SCRIPT_NAME']     ?? '/index.php';
$_SERVER['REQUEST_SCHEME']  = $_SERVER['REQUEST_SCHEME']  ?? 'http';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

chdir($WP);
require $WP . "/wp-load.php";

if (!function_exists('hegzz_verify_lookups_system')) {
    require $THEME . "/tools/verify_lookups.php";
}

$ok = hegzz_verify_lookups_system();
fwrite(STDERR, "\nDONE. ok=" . ($ok ? "1" : "0") . "\n");
exit($ok ? 0 : 1);
