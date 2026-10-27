<?php
// Local prototype config: SQLite via the sqlite-database-integration drop-in.
define( 'DB_NAME', 'vobrothers' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'DB_DIR', __DIR__ . '/wp-content/database/' );
define( 'DB_FILE', 'vobrothers.sqlite' );

define( 'AUTH_KEY', 'local-prototype-1' );
define( 'SECURE_AUTH_KEY', 'local-prototype-2' );
define( 'LOGGED_IN_KEY', 'local-prototype-3' );
define( 'NONCE_KEY', 'local-prototype-4' );
define( 'AUTH_SALT', 'local-prototype-5' );
define( 'SECURE_AUTH_SALT', 'local-prototype-6' );
define( 'LOGGED_IN_SALT', 'local-prototype-7' );
define( 'NONCE_SALT', 'local-prototype-8' );

$table_prefix = 'wp_';

define( 'WP_HOME', getenv( 'WP_HOME' ) ?: 'http://localhost:8080' );
define( 'WP_SITEURL', WP_HOME );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
