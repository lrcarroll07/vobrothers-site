<?php
/**
 * Creates a fresh WordPress install (SQLite). Run via bin/setup.sh.
 */

define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/../wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$password = getenv( 'VOB_ADMIN_PASSWORD' ) ?: 'vobrothers-local';
$result   = wp_install( 'VO Brothers Mechanical', 'admin', 'admin@example.com', false, '', $password );
echo "Installed. Admin login: admin / $password\n";
