<?php
/**
 * Loads the prototype content into a fresh install: activates the theme and
 * plugin, imports images, creates services, projects, pages and menus.
 *
 * Locally: run by bin/setup.sh. On a host: `wp eval-file seed.php` (see bin/deploy.sh).
 * Only runs against a fresh install, unless VOB_SEED_FORCE=1 is set.
 */

if ( ! defined( 'ABSPATH' ) ) {
	$_SERVER['HTTP_HOST']   = 'localhost';
	$_SERVER['REQUEST_URI'] = '/';
	require __DIR__ . '/../wp/wp-load.php';
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$root = dirname( __DIR__ );
$data = require $root . '/content/seed-data.php';

// Refuse to run over real content: a fresh install has at most 3 posts/pages
// (Hello world, Sample Page, Privacy Policy) and no projects.
global $wpdb;
$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type IN ('post','page','vob_project','vob_service') AND post_status <> 'auto-draft'" );
if ( $existing > 3 && ! getenv( 'VOB_SEED_FORCE' ) ) {
	fwrite( STDERR, "This site already has content ($existing posts/pages). Not seeding. Set VOB_SEED_FORCE=1 to override.\n" );
	exit( 1 );
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
wp_set_current_user( $admins ? $admins[0]->ID : 1 );

// Theme, plugin, settings ----------------------------------------------------

$activated = activate_plugin( 'vob-core/vob-core.php' );
if ( is_wp_error( $activated ) ) {
	fwrite( STDERR, $activated->get_error_message() . "\n" );
	exit( 1 );
}
vob_register_post_types(); // init already ran in this request.
switch_theme( 'vobrothers' );
// The theme's after_setup_theme hook already ran for this request, so register its
// image sizes here; otherwise imported images get no card or hero crops.
add_image_size( 'vob-card', 720, 480, true );
add_image_size( 'vob-hero', 2000, 1100, true );

update_option( 'blogname', 'VO Brothers Mechanical' );
update_option( 'blogdescription', 'Mechanical, process piping, plumbing, service and maintenance serving the state of Utah' );
update_option( 'timezone_string', 'America/Denver' );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );
update_option( 'permalink_structure', '/%postname%/' );

// Remove WordPress sample content.
foreach ( get_posts( array( 'post_type' => array( 'post', 'page' ), 'numberposts' => -1, 'post_status' => 'any' ) ) as $sample ) {
	wp_delete_post( $sample->ID, true );
}

// Media ------------------------------------------------------------------------

/**
 * Import an image from content/images once and return its attachment ID.
 */
function vob_seed_image( $relative, $title = '' ) {
	static $cache = array();
	if ( isset( $cache[ $relative ] ) ) {
		return $cache[ $relative ];
	}
	$source = dirname( __DIR__ ) . '/content/images/' . $relative;
	$upload = wp_upload_bits( basename( $source ), null, file_get_contents( $source ) );
	if ( $upload['error'] ) {
		fwrite( STDERR, "Image failed: $relative {$upload['error']}\n" );
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_title'     => $title ?: ucwords( str_replace( '-', ' ', pathinfo( $source, PATHINFO_FILENAME ) ) ),
			'post_mime_type' => wp_check_filetype( $upload['file'] )['type'],
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return $cache[ $relative ] = $id;
}

// Services ---------------------------------------------------------------------

foreach ( $data['services'] as $order => $group ) {
	$group_id = wp_insert_post(
		array(
			'post_type'    => 'vob_service',
			'post_status'  => 'publish',
			'post_title'   => $group['title'],
			'post_name'    => $group['slug'],
			'post_excerpt' => $group['excerpt'],
			'post_content' => $group['content'],
			'menu_order'   => $order,
			'meta_input'   => array(
				'vob_tagline'   => $group['tagline'],
				'vob_icon'      => $group['icon'],
				'vob_bullets'   => $group['bullets'],
				'vob_emergency' => empty( $group['emergency'] ) ? '' : '1',
			),
		)
	);
	set_post_thumbnail( $group_id, vob_seed_image( $group['image'], $group['title'] ) );

	foreach ( $group['children'] as $child_order => list( $title, $image, $excerpt ) ) {
		$child_id = wp_insert_post(
			array(
				'post_type'    => 'vob_service',
				'post_status'  => 'publish',
				'post_parent'  => $group_id,
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
				'post_content' => '<p>' . esc_html( $excerpt ) . '</p>' .
					'<p class="placeholder-note">Placeholder: expand this page with typical applications, equipment, standards and a related project or two. Pages with 300+ words rank much better in search.</p>',
				'menu_order'   => $child_order,
			)
		);
		set_post_thumbnail( $child_id, vob_seed_image( $image, $title ) );
	}
}

// Projects ---------------------------------------------------------------------

foreach ( $data['sector_terms'] as $term ) {
	wp_insert_term( $term, 'vob_sector' );
}
foreach ( $data['location_terms'] as $term ) {
	wp_insert_term( $term, 'vob_location' );
}

foreach ( $data['projects'] as $order => $project ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'vob_project',
			'post_status'  => 'publish',
			'post_title'   => $project['title'],
			'post_content' => '<p>' . esc_html( $project['content'] ) . '</p>',
			'post_excerpt' => $project['content'],
			'menu_order'   => $order,
			'meta_input'   => array(
				'vob_location_label' => $project['label'] ?? '',
				'vob_size'           => $project['size'] ?? '',
				'vob_client'         => $project['client'] ?? '',
				'vob_scope'          => $project['scope'] ?? '',
				'vob_status'         => $project['status'] ?? 'Completed',
				'vob_featured'       => empty( $project['featured'] ) ? '' : '1',
			),
		)
	);
	wp_set_object_terms( $id, $project['sectors'], 'vob_sector' );
	if ( ! empty( $project['location'] ) ) {
		wp_set_object_terms( $id, $project['location'], 'vob_location' );
	}
	set_post_thumbnail( $id, vob_seed_image( $project['image'], $project['title'] ) );
}

// Pages ------------------------------------------------------------------------

function vob_seed_page( $title, $slug, $content, $args = array() ) {
	return wp_insert_post(
		array_merge(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			),
			$args
		)
	);
}

function vob_placeholder( $text ) {
	return '<p class="placeholder-note">Placeholder: ' . esc_html( $text ) . '</p>';
}

$home = vob_seed_page( 'Home', 'home', '' );
set_post_thumbnail( $home, vob_seed_image( 'services/pump-rooms.jpg', 'Pump room piping installed by VO Brothers Mechanical' ) );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );

$about_image = vob_seed_image( 'site/about.jpg', 'The VO Brothers Mechanical team' );
$about       = vob_seed_page(
	'About VO Brothers',
	'about',
	'<p>Although we value performance, we also value our friendships. Life is about growing, learning and enjoying. Here we enjoy our team and value our culture and community, knowing that without the right mix of people, and a place to live and grow, we could never expect to perform as a high-quality company. With the right team, magic will happen.</p>
<h2>Our purpose</h2>
<p>Our company is a community of friends, teammates and talented mechanics. We understand our place in the market and strive to create value for all our customers.</p>
<p>Our company has been cultivated through years of supporting a wide range of mechanical systems. Our culture knows and understands facility operational needs, and has adapted to time-sensitive projects that require extensive upfront planning for successful execution.</p>
<figure class="wp-block-image">' . wp_get_attachment_image( $about_image, 'large' ) . '</figure>
<h2>A Quantum Industrial company</h2>
<p>VO Brothers Mechanical is part of Quantum Industrial. We operate under the VO Brothers name, with the same local team, from our shop in Ogden, Utah. <a href="' . esc_url( home_url( '/about/quantum-industrial/' ) ) . '">Learn more about our parent company</a>.</p>
' . vob_placeholder( 'add company history (founding year, founders, milestones), leadership team photos and bios, and licenses held.' ),
	array( 'post_excerpt' => 'A community of friends, teammates and talented mechanics, determined for progress.' )
);

vob_seed_page(
	'Core Values',
	'core-values',
	'<p>These values are drafted from how VO Brothers describes itself today. Edit them to match the values the team actually uses.</p>
<div class="value-grid">
<div class="value-card"><h3>Performance</h3><p>We deliver high-quality work on time-sensitive projects through careful upfront planning.</p></div>
<div class="value-card"><h3>Friendship</h3><p>We are a community of friends and teammates who look out for each other.</p></div>
<div class="value-card"><h3>Growth</h3><p>Life is about growing, learning and enjoying. We invest in every mechanic\'s development.</p></div>
<div class="value-card"><h3>Customer value</h3><p>We understand our place in the market and strive to create value for every customer.</p></div>
</div>
' . vob_placeholder( 'confirm or replace these draft values.' ),
	array( 'post_parent' => $about, 'menu_order' => 1, 'post_excerpt' => 'What we stand for, on every job site.' )
);

vob_seed_page(
	'Safety',
	'safety',
	vob_placeholder( 'draft copy. Replace with the actual safety program summary, EMR rating, OSHA recordable rate, safety awards and training programs.' ) . '
<p>Everyone goes home safe, every day. Safety is planned into every project from the first pre-construction meeting to the final walkthrough.</p>
<div class="value-grid">
<div class="value-card"><h3>Planning</h3><p>Job hazard analysis and pre-task planning before work begins.</p></div>
<div class="value-card"><h3>Training</h3><p>Ongoing safety training for every craft and every level.</p></div>
<div class="value-card"><h3>Accountability</h3><p>Every team member has the authority to stop unsafe work.</p></div>
</div>',
	array( 'post_parent' => $about, 'menu_order' => 2, 'post_excerpt' => 'Everyone goes home safe, every day.' )
);

vob_seed_page(
	'Testimonials',
	'testimonials',
	'<p>What general contractors, owners and facility managers say about working with VO Brothers Mechanical.</p>
[vob_testimonials audience="client"]',
	array( 'post_parent' => $about, 'menu_order' => 3, 'post_excerpt' => 'What our customers say.' )
);

vob_seed_page(
	'Quantum Industrial',
	'quantum-industrial',
	'<p>VO Brothers Mechanical is a Quantum Industrial company. We operate under the VO Brothers Mechanical name, with the same local team and the same commitment to our customers, from our shop in Ogden, Utah.</p>
<p><a class="btn btn--primary" href="https://quantumindustrial.com" target="_blank" rel="noopener">Visit Quantum Industrial</a></p>
' . vob_placeholder( 'add an approved description of Quantum Industrial, its other companies, and what the relationship means for VO Brothers customers.' ),
	array( 'post_parent' => $about, 'menu_order' => 4, 'post_excerpt' => 'Our parent company.' )
);

$careers = vob_seed_page(
	'Careers',
	'careers',
	'<p>We are a community of friends, teammates and talented mechanics, and we are always looking for skilled people who want to grow. Whether you are a seasoned journeyman, an estimator, a BIM modeler or just starting out, there is a place for you here.</p>
<div class="value-grid">
<div class="value-card"><h3>Grow your skills</h3><p>Work on process piping, pure water, steam, medical gas and controls: specialty systems most contractors never touch.</p></div>
<div class="value-card"><h3>Real teammates</h3><p>We value performance, and we value our friendships. People here have your back.</p></div>
<div class="value-card"><h3>Local team</h3><p>Based in Ogden, working on projects across Utah, as part of Quantum Industrial.</p></div>
</div>
' . vob_placeholder( 'add benefits (health, 401(k), PTO, tools, training/apprenticeship support) and a team photo.' ) . '
<h2 id="openings">Open positions</h2>
[vob_jobs]
<h2>Hear from the team</h2>
[vob_testimonials audience="employee"]',
	array( 'post_excerpt' => 'Build a career with a team that has your back.' )
);
set_post_thumbnail( $careers, vob_seed_image( 'services/specialty-welding.jpg', 'VO Brothers welder at work' ) );

$contact = vob_seed_page(
	'Contact Us',
	'contact',
	'',
	array( 'post_excerpt' => 'Request a quote, set up a maintenance plan or reach our 24/7 emergency line.' )
);

// Menus ------------------------------------------------------------------------

function vob_seed_menu_item( $menu_id, $title, $url, $parent = 0, $classes = '' ) {
	return wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => $title,
			'menu-item-url'       => $url,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
			'menu-item-classes'   => $classes,
		)
	);
}

function vob_seed_menu_post( $menu_id, $post_id, $parent = 0, $title = '' ) {
	$post = get_post( $post_id );
	return wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => $title ?: $post->post_title,
			'menu-item-object'    => $post->post_type,
			'menu-item-object-id' => $post_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
		)
	);
}

$primary  = wp_create_nav_menu( 'Main menu' );
$services = vob_seed_menu_item( $primary, 'Services', home_url( '/services/' ), 0, 'menu-wide' );
foreach ( vob_service_groups() as $group ) {
	vob_seed_menu_post( $primary, $group->ID, $services );
}
vob_seed_menu_item( $primary, 'Projects', home_url( '/projects/' ) );
$about_item = vob_seed_menu_post( $primary, $about, 0, 'About' );
vob_seed_menu_post( $primary, $about, $about_item, 'Our Company' );
foreach ( get_pages( array( 'parent' => $about, 'sort_column' => 'menu_order' ) ) as $page ) {
	vob_seed_menu_post( $primary, $page->ID, $about_item );
}
vob_seed_menu_post( $primary, $careers );
vob_seed_menu_post( $primary, $contact, 0, 'Contact' );

$footer = wp_create_nav_menu( 'Footer quick links' );
vob_seed_menu_post( $footer, $about, 0, 'About Us' );
vob_seed_menu_item( $footer, 'Projects', home_url( '/projects/' ) );
foreach ( get_pages( array( 'parent' => $about, 'sort_column' => 'menu_order' ) ) as $page ) {
	vob_seed_menu_post( $footer, $page->ID );
}
vob_seed_menu_post( $footer, $careers );
vob_seed_menu_post( $footer, $contact, 0, 'Contact' );

set_theme_mod( 'nav_menu_locations', array( 'primary' => $primary, 'footer' => $footer ) );
// switch_theme() leaves a "theme_switched" flag; on the next page load WordPress would
// re-map menu locations from the previous theme (none) and wipe the assignments above.
delete_option( 'theme_switched' );

flush_rewrite_rules();

printf(
	"Seeded %d service pages, %d projects, %d pages.\n",
	wp_count_posts( 'vob_service' )->publish,
	wp_count_posts( 'vob_project' )->publish,
	wp_count_posts( 'page' )->publish
);
