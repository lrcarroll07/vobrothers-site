<?php
/**
 * VO Brothers Mechanical theme.
 *
 * Presentation only. Content types, settings, forms and the jobs feed live in
 * the VO Brothers Core plugin; this theme degrades gracefully without it.
 */

defined( 'ABSPATH' ) || exit;

define( 'VOB_THEME_VERSION', '0.1.0' );

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );

		add_image_size( 'vob-card', 720, 480, true );
		add_image_size( 'vob-hero', 2000, 1100, true );

		register_nav_menus(
			array(
				'primary' => 'Main menu',
				'footer'  => 'Footer quick links',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'vob-fonts', 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600&family=Michroma&display=swap', array(), null );
		wp_enqueue_style( 'vob-main', get_theme_file_uri( 'assets/css/main.css' ), array( 'vob-fonts' ), VOB_THEME_VERSION );
		wp_enqueue_script( 'vob-main', get_theme_file_uri( 'assets/js/main.js' ), array(), VOB_THEME_VERSION, array( 'strategy' => 'defer' ) );

		// Project map (Leaflet, bundled locally) only where it's used.
		if ( is_post_type_archive( 'vob_project' ) || is_tax( array( 'vob_sector', 'vob_location' ) ) ) {
			wp_enqueue_style( 'leaflet', get_theme_file_uri( 'assets/vendor/leaflet/leaflet.css' ), array(), '1.9.4' );
			wp_enqueue_script( 'leaflet', get_theme_file_uri( 'assets/vendor/leaflet/leaflet.js' ), array(), '1.9.4', array( 'strategy' => 'defer' ) );
			wp_enqueue_script( 'vob-project-map', get_theme_file_uri( 'assets/js/project-map.js' ), array( 'leaflet', 'vob-main' ), VOB_THEME_VERSION, array( 'strategy' => 'defer' ) );
		}
	}
);

add_action(
	'wp_head',
	function () {
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		if ( ! has_site_icon() ) {
			echo '<link rel="icon" href="' . esc_url( get_theme_file_uri( 'assets/img/icon.png' ) ) . '">' . "\n";
		}
	},
	1
);

/**
 * Company setting with a fallback when the core plugin is inactive.
 */
function vob_t_info( $key ) {
	return function_exists( 'vob_info' ) ? vob_info( $key ) : '';
}

function vob_t_tel( $phone ) {
	return 'tel:+1' . preg_replace( '/\D/', '', $phone );
}

/**
 * Inline SVG icon. Simple 24px line icons, coloured with currentColor.
 */
function vob_icon( $name, $class = 'icon' ) {
	$paths = array(
		'water'   => '<path d="M12 3s6 6.6 6 11a6 6 0 0 1-12 0c0-4.4 6-11 6-11z"/><path d="M9 15a3 3 0 0 0 3 3"/>',
		'steam'   => '<path d="M8 3c-1.5 1.5-1.5 3 0 4.5S9.5 10.5 8 12M12 3c-1.5 1.5-1.5 3 0 4.5s1.5 3 0 4.5M16 3c-1.5 1.5-1.5 3 0 4.5s1.5 3 0 4.5"/><rect x="4" y="14" width="16" height="7" rx="1.5"/>',
		'medical' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M12 7v10M7 12h10"/>',
		'pipe'    => '<path d="M3 8h8a3 3 0 0 1 3 3v10"/><path d="M3 14h4a1 1 0 0 1 1 1v6"/><path d="M3 6v10M12 21h6"/>',
		'weld'    => '<path d="M4 20l7-7"/><path d="M13 11l2-2 4 4-2 2z"/><path d="M17 3l1 2M21 7l-2 1M20 3l-1.5 1.5"/>',
		'gauge'   => '<circle cx="12" cy="13" r="8"/><path d="M12 13l4-4"/><path d="M8 18h8"/>',
		'wrench'  => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.2L3 17.8V21h3.2l6.3-6.3a4 4 0 0 0 5.2-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
		'service' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'phone'   => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'pin'     => '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
		'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'left'    => '<path d="M15 6l-6 6 6 6"/>',
		'right'   => '<path d="M9 6l6 6-6 6"/>',
		'check'   => '<path d="M5 12l5 5L20 7"/>',
		'award'   => '<circle cx="12" cy="9" r="6"/><path d="M9 14.5L8 22l4-2 4 2-1-7.5"/>',
		'shield'  => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
		'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'   => '<path d="M6 6l12 12M18 6L6 18"/>',
		'factory' => '<path d="M3 21V10l5 3V10l5 3V6h3l1-3h2l1 3v15z"/><path d="M7 17h2M12 17h2"/>',
		'flask'   => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4A2 2 0 0 0 19 18l-5-9V3"/><path d="M7.5 15h9"/>',
		'cap'     => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
		'heart'   => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/><path d="M8 11h2l1-2 2 4 1-2h2"/>',
		'server'  => '<rect x="4" y="3" width="16" height="7" rx="1.5"/><rect x="4" y="14" width="16" height="7" rx="1.5"/><path d="M8 6.5h.01M8 17.5h.01"/>',
		'cup'     => '<path d="M6 3h12l-1.5 18h-9z"/><path d="M6.5 9h11"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		$name = 'wrench';
	}
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Visible breadcrumbs (same trail as the schema markup).
 */
function vob_the_breadcrumbs() {
	if ( ! function_exists( 'vob_breadcrumbs' ) ) {
		return;
	}
	$trail = vob_breadcrumbs();
	if ( count( $trail ) < 2 ) {
		return;
	}
	echo '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>';
	foreach ( $trail as $i => $crumb ) {
		$last = $i === count( $trail ) - 1;
		echo '<li>' . ( $last ? '<span aria-current="page">' . esc_html( $crumb['title'] ) . '</span>' : '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['title'] ) . '</a>' ) . '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Short label for a project's sectors, e.g. "Higher Education · Central Plants".
 */
function vob_project_sectors( $post_id, $sep = ' · ' ) {
	$terms = get_the_terms( $post_id, 'vob_sector' );
	return $terms && ! is_wp_error( $terms ) ? implode( $sep, wp_list_pluck( $terms, 'name' ) ) : '';
}

/**
 * Space-separated term slugs, used by the client-side filters.
 */
function vob_term_slugs( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return $terms && ! is_wp_error( $terms ) ? implode( ' ', wp_list_pluck( $terms, 'slug' ) ) : '';
}

/**
 * Mark the "Services" / "Projects" menu items current on their single and archive pages.
 */
add_filter(
	'nav_menu_css_class',
	function ( $classes, $item ) {
		$url = trailingslashit( $item->url );
		if ( ( is_singular( 'vob_service' ) || is_post_type_archive( 'vob_service' ) ) && str_ends_with( $url, '/services/' ) ) {
			$classes[] = 'current-menu-ancestor';
		}
		if ( ( is_singular( 'vob_project' ) || is_post_type_archive( 'vob_project' ) || is_tax( array( 'vob_sector', 'vob_location' ) ) ) && str_ends_with( $url, '/projects/' ) ) {
			$classes[] = 'current-menu-ancestor';
		}
		return $classes;
	},
	10,
	2
);

/**
 * Add an accessible toggle button to menu items that have sub-menus.
 */
add_filter(
	'walker_nav_menu_start_el',
	function ( $output, $item, $depth, $args ) {
		if ( 'primary' === ( $args->theme_location ?? '' ) && in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
			$output .= '<button class="submenu-toggle" type="button" aria-expanded="false"><span class="screen-reader-text">Show ' . esc_html( $item->title ) . ' submenu</span>' . vob_icon( 'right', 'icon icon--sm' ) . '</button>';
		}
		return $output;
	},
	10,
	4
);

/**
 * Shared page hero: title, optional intro, background image.
 */
function vob_page_hero( $args = array() ) {
	get_template_part( 'template-parts/page-hero', null, $args );
}
