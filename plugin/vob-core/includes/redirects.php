<?php
/**
 * 301 redirects from the old Wix site's URLs so existing links and search
 * rankings carry over. Add any other old URLs found in Google Search Console here.
 */

defined( 'ABSPATH' ) || exit;

function vob_legacy_redirects() {
	return array(
		// /about, /projects and /careers keep the same URLs on the new site.
		'services-1'    => '/services/',
		'blog-feed.xml' => '/feed/',
	);
}

add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		$map  = vob_legacy_redirects();
		if ( isset( $map[ $path ] ) ) {
			wp_safe_redirect( home_url( $map[ $path ] ), 301 );
			exit;
		}
	}
);
