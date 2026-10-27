<?php
/**
 * Structured data for Google: the business (with Quantum Industrial as parent
 * organization) on every page, and breadcrumbs on inner pages.
 * The address here must match the Google Business Profile exactly.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	function () {
		$social = array_values( array_filter( array( vob_info( 'linkedin' ), vob_info( 'facebook' ), vob_info( 'instagram' ), vob_info( 'x' ) ) ) );

		$graph = array(
			array(
				'@type'              => array( 'LocalBusiness', 'HVACBusiness', 'Plumber' ),
				'@id'                => home_url( '/#business' ),
				'name'               => vob_info( 'company_name' ),
				'legalName'          => vob_info( 'legal_name' ),
				'url'                => home_url( '/' ),
				'telephone'          => '+1-' . vob_info( 'phone' ),
				'email'              => vob_info( 'email' ),
				'logo'               => get_theme_file_uri( 'assets/img/logo-mark.png' ),
				'image'              => get_theme_file_uri( 'assets/img/logo-mark.png' ),
				'address'            => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => vob_info( 'street' ),
					'addressLocality' => vob_info( 'city' ),
					'addressRegion'   => vob_info( 'state' ),
					'postalCode'      => vob_info( 'zip' ),
					'addressCountry'  => 'US',
				),
				'areaServed'         => array( '@type' => 'State', 'name' => 'Utah' ),
				'openingHoursSpecification' => array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ),
					'opens'     => '00:00',
					'closes'    => '23:59',
				),
				'parentOrganization' => array(
					'@type' => 'Organization',
					'name'  => vob_info( 'parent_name' ),
					'url'   => vob_info( 'parent_url' ),
				),
				'sameAs'             => $social,
			),
		);

		if ( ! is_front_page() ) {
			$crumbs = vob_breadcrumbs();
			$items  = array();
			foreach ( $crumbs as $i => $crumb ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $crumb['title'],
					'item'     => $crumb['url'],
				);
			}
			if ( count( $items ) > 1 ) {
				$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $items );
			}
		}

		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES )
		);
	}
);

/**
 * Breadcrumb trail for the current page: [ ['title','url'], ... ].
 * Shared by the schema above and the theme's visible breadcrumbs.
 */
function vob_breadcrumbs() {
	$trail = array( array( 'title' => 'Home', 'url' => home_url( '/' ) ) );

	if ( is_post_type_archive( 'vob_project' ) ) {
		$trail[] = array( 'title' => 'Projects', 'url' => get_post_type_archive_link( 'vob_project' ) );
	} elseif ( is_post_type_archive( 'vob_service' ) ) {
		$trail[] = array( 'title' => 'Services', 'url' => get_post_type_archive_link( 'vob_service' ) );
	} elseif ( is_tax( array( 'vob_sector', 'vob_location' ) ) ) {
		$trail[] = array( 'title' => 'Projects', 'url' => get_post_type_archive_link( 'vob_project' ) );
		$trail[] = array( 'title' => single_term_title( '', false ), 'url' => get_term_link( get_queried_object() ) );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( 'vob_project' === $post->post_type ) {
			$trail[] = array( 'title' => 'Projects', 'url' => get_post_type_archive_link( 'vob_project' ) );
		} elseif ( 'vob_service' === $post->post_type ) {
			$trail[] = array( 'title' => 'Services', 'url' => get_post_type_archive_link( 'vob_service' ) );
		} elseif ( 'post' === $post->post_type && get_option( 'page_for_posts' ) ) {
			$trail[] = array( 'title' => get_the_title( get_option( 'page_for_posts' ) ), 'url' => get_permalink( get_option( 'page_for_posts' ) ) );
		}
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
			$trail[] = array( 'title' => get_the_title( $ancestor ), 'url' => get_permalink( $ancestor ) );
		}
		$trail[] = array( 'title' => get_the_title( $post ), 'url' => get_permalink( $post ) );
	} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
		$trail[] = array( 'title' => get_the_title( get_option( 'page_for_posts' ) ), 'url' => get_permalink( get_option( 'page_for_posts' ) ) );
	}

	return $trail;
}
