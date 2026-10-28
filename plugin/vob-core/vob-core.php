<?php
/**
 * Plugin Name: VO Brothers Core
 * Description: Content types, company settings, Rippling job feed, lead forms, schema and legacy redirects for the VO Brothers Mechanical site.
 * Version: 0.1.0
 * Author: VO Brothers Mechanical
 * Requires PHP: 8.0
 * Text Domain: vob-core
 *
 * Keeping this functionality in a plugin (not the theme) means projects, services,
 * leads and the jobs feed survive a future theme change.
 */

defined( 'ABSPATH' ) || exit;

define( 'VOB_CORE_VERSION', '0.1.0' );
define( 'VOB_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VOB_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once VOB_CORE_DIR . 'includes/settings.php';
require_once VOB_CORE_DIR . 'includes/post-types.php';
require_once VOB_CORE_DIR . 'includes/meta.php';
require_once VOB_CORE_DIR . 'includes/jobs.php';
require_once VOB_CORE_DIR . 'includes/forms.php';
require_once VOB_CORE_DIR . 'includes/testimonials.php';
require_once VOB_CORE_DIR . 'includes/team.php';
require_once VOB_CORE_DIR . 'includes/schema.php';
require_once VOB_CORE_DIR . 'includes/redirects.php';
require_once VOB_CORE_DIR . 'includes/preview.php';

register_activation_hook(
	__FILE__,
	function () {
		vob_register_post_types();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
