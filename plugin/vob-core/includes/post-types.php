<?php
/**
 * Content types:
 * - vob_project      Portfolio projects at /projects/, filterable by sector and location.
 * - vob_service      Hierarchical: top level = service group (/services/steam-hydronics/),
 *                    children = individual services (/services/steam-hydronics/clean-steam/).
 * - vob_testimonial  Client / employee quotes shown on About and Careers.
 * - vob_lead         Form submissions (admin only), so no lead is lost if email fails.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'vob_register_post_types' );

function vob_register_post_types() {
	// Taxonomies first: their /projects/type/… rewrite rules must outrank the project permalinks.
	register_taxonomy(
		'vob_sector',
		'vob_project',
		array(
			'labels'            => vob_labels( 'Project Type', 'Project Types' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'projects/type', 'with_front' => false ),
			'show_in_rest'      => true,
		)
	);

	register_taxonomy(
		'vob_location',
		'vob_project',
		array(
			'labels'            => vob_labels( 'Location', 'Locations' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'projects/location', 'with_front' => false ),
			'show_in_rest'      => true,
		)
	);

	register_post_type(
		'vob_project',
		array(
			'labels'        => vob_labels( 'Project', 'Projects' ),
			'public'        => true,
			'has_archive'   => 'projects',
			'rewrite'       => array( 'slug' => 'projects', 'with_front' => false ),
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 20,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'vob_service',
		array(
			'labels'        => vob_labels( 'Service', 'Services' ),
			'public'        => true,
			'hierarchical'  => true,
			'has_archive'   => 'services',
			'rewrite'       => array( 'slug' => 'services', 'with_front' => false ),
			'menu_icon'     => 'dashicons-admin-tools',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'vob_testimonial',
		array(
			'labels'        => vob_labels( 'Testimonial', 'Testimonials' ),
			'public'        => false,
			'show_ui'       => true,
			'menu_icon'     => 'dashicons-format-quote',
			'menu_position' => 22,
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'vob_lead',
		array(
			'labels'          => vob_labels( 'Lead', 'Leads' ),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 23,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}

function vob_labels( $singular, $plural ) {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'add_new_item'  => "Add New $singular",
		'edit_item'     => "Edit $singular",
		'new_item'      => "New $singular",
		'view_item'     => "View $singular",
		'search_items'  => "Search $plural",
		'not_found'     => 'Nothing found',
		'all_items'     => "All $plural",
		'menu_name'     => $plural,
	);
}

/**
 * Show every project and every top-level service group on their archive pages
 * (filtering happens client-side) and keep them in the editor's menu order.
 */
add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_post_type_archive( 'vob_project' ) || $query->is_tax( array( 'vob_sector', 'vob_location' ) ) ) {
			$query->set( 'posts_per_page', -1 );
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}
		if ( $query->is_post_type_archive( 'vob_service' ) ) {
			$query->set( 'posts_per_page', -1 );
			$query->set( 'post_parent', 0 );
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
);

/**
 * Top-level service groups, in menu order. Used by the header menu, footer and forms.
 */
function vob_service_groups() {
	return get_posts(
		array(
			'post_type'   => 'vob_service',
			'post_parent' => 0,
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		)
	);
}
