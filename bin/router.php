<?php
/**
 * Router for PHP's built-in server: serve real files, send everything else to WordPress.
 * Usage: php -S localhost:8080 -t wp bin/router.php
 */

$path = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$file = $_SERVER['DOCUMENT_ROOT'] . $path;

if ( '/' !== $path && is_file( $file ) ) {
	if ( str_ends_with( $file, '.php' ) ) {
		chdir( dirname( $file ) );
		require $file;
		return true;
	}
	return false;
}
if ( is_dir( $file ) && is_file( rtrim( $file, '/' ) . '/index.php' ) ) {
	chdir( $file );
	require rtrim( $file, '/' ) . '/index.php';
	return true;
}

chdir( $_SERVER['DOCUMENT_ROOT'] );
require 'index.php';
