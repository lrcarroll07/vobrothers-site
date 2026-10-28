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
// Theme helpers (icons) for content built below; the theme wasn't active when this request loaded.
require_once get_theme_file_path( 'functions.php' );

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
				'vob_lat'            => (string) ( $project['lat'] ?? '' ),
				'vob_lng'            => (string) ( $project['lng'] ?? '' ),
				'vob_award'          => $project['award'] ?? '',
			),
		)
	);
	wp_set_object_terms( $id, $project['sectors'], 'vob_sector' );
	if ( ! empty( $project['location'] ) ) {
		wp_set_object_terms( $id, $project['location'], 'vob_location' );
	}
	$image = vob_seed_image( $project['image'], $project['title'] );
	set_post_thumbnail( $id, $image );
	if ( ! empty( $project['placeholder'] ) ) {
		// "Photos coming soon" graphic: shown on cards, but not as the page hero.
		update_post_meta( $image, '_vob_placeholder', '1' );
	}
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

function vob_icon_markup( $name ) {
	return function_exists( 'vob_icon' ) ? vob_icon( $name, 'icon icon--sm' ) : '';
}

function vob_placeholder( $text ) {
	return '<p class="placeholder-note">Placeholder: ' . esc_html( $text ) . '</p>';
}

$home = vob_seed_page( 'Home', 'home', '' );
set_post_thumbnail( $home, vob_seed_image( 'services/pump-rooms.jpg', 'Pump room piping installed by VO Brothers Mechanical' ) );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );

// Team (leadership) and team testimonials, shared from Quantum Industrial's site.
$team = array(
	array( 'Josh VanOrden', 'Founder, VO Brothers Mechanical', 'team/josh-vanorden.jpg', 'utah' ),
	array( 'Jeff Warner', 'General Manager', 'team/jeff-warner.jpg', 'utah' ),
	array( 'Jason Wilson', 'Construction Operations Manager', 'team/jason-wilson.jpg', 'utah' ),
	array( 'Dan Fonoti', 'Preconstruction Manager', 'team/dan-fonoti.jpg', 'utah' ),
	array( 'Courtney Cannon', 'Senior Project Manager', 'team/courtney-cannon.jpg', 'utah' ),
	array( 'Jordan Abbott', 'VDC/BIM Manager', 'team/jordan-abbott.jpg', 'utah' ),
	array( 'Dennis Valdez', 'Safety Manager', 'team/dennis-valdez.jpg', 'utah' ),
	array( 'Melody Stong', 'Business Development', 'team/melody-stong.jpg', 'utah' ),
	array( 'Mark Estep', 'CEO, Quantum Industrial', 'team/mark-estep.jpg', 'quantum' ),
	array( 'Chris Potter', 'COO, Quantum Industrial', 'team/chris-potter.jpg', 'quantum' ),
	array( 'Eric Voltin', 'VP of Operations, Quantum Industrial', 'team/eric-voltin.jpg', 'quantum' ),
	array( 'Jordan Booth', 'VP of Finance & Accounting, Quantum Industrial', 'team/jordan-booth.jpg', 'quantum' ),
	array( 'Sandi Potter', 'Corporate Controller, Quantum Industrial', 'team/sandi-potter.jpg', 'quantum' ),
);
foreach ( $team as $order => list( $name, $role, $photo, $group ) ) {
	$member = wp_insert_post(
		array(
			'post_type'   => 'vob_team',
			'post_status' => 'publish',
			'post_title'  => $name,
			'menu_order'  => $order,
			'meta_input'  => array( 'vob_role' => $role, 'vob_group' => $group ),
		)
	);
	if ( $photo ) {
		set_post_thumbnail( $member, vob_seed_image( $photo, $name ) );
	}
}

$quotes = array(
	'I wanted to join the trades right out of high school and I thought plumbing fit me best. I started working here in June of 2019 as an apprentice and now I’m a journeyman plumber! I wouldn’t change a thing, this has been amazing for me! True Brotherhood!',
	'I love working here! From day one I felt like I had worked here forever. Everyone is so friendly and feels like family. The management team has really figured out how to blend the right personalities together to create a cohesive team that flows. I am definitely looking forward to a long, happy career here.',
	'I started working here at age 22, looking for a career path, and found out I was a part of something bigger than myself. I found a brotherhood I knew I needed. If there were something I could share with young men looking to join the trade, it’s craftsmanship. One word to describe our company is integrity!',
	'I was looking for a future with my career and found that. I have been here since 2013 and I love that every day is different. I like the people I meet and the people I work with. The word I use to describe our company is “family.”',
	'… I’ve been working my way up from a plumber’s helper to a field superintendent. I love that there are so many things to learn in our trade and that we are on a new build from start to finish. If I had one word to describe working here it would be “brotherhood.”',
	'I have NEVER worked for a company that values their workforce so highly. I feel very lucky to be part of the team, and to feel respected by my peers and supervisors alike. This company is truly a great experience.',
);
foreach ( $quotes as $order => $quote ) {
	wp_insert_post(
		array(
			'post_type'    => 'vob_testimonial',
			'post_status'  => 'publish',
			'post_title'   => 'Team testimonial ' . ( $order + 1 ),
			'post_content' => $quote,
			'menu_order'   => $order,
			'meta_input'   => array( 'vob_person' => 'Team member', 'vob_role' => 'Quantum Industrial family', 'vob_audience' => 'employee' ),
		)
	);
}

// Utah awards: ABC Utah (2020) and Utah Construction + Design "Most Outstanding Projects of 2025".
$abc_award = '<div class="award-callout">' . vob_icon_markup( 'award' ) . '<div><strong>Most Outstanding Specialty Contracting Project</strong><br>Utah Construction + Design, 2025 &middot; Provo Wastewater Reclamation Facility (plumbing contractor)</div></div>'
	. '<div class="award-callout">' . vob_icon_markup( 'award' ) . '<div><strong>Most Outstanding Water Project</strong><br>Utah Construction + Design, 2025 &middot; Davis Aqueduct Reach 1 Parallel Pipeline (plumbing contractor)</div></div>'
	. '<div class="award-callout">' . vob_icon_markup( 'award' ) . '<div><strong>ABC Utah Sub-Contractor of the Year</strong><br>Associated Builders and Contractors of Utah, 2020</div></div>';

$about_image = vob_seed_image( 'site/about.jpg', 'The VO Brothers Mechanical team' );
$about       = vob_seed_page(
	'About VO Brothers',
	'about',
	'<p>Although we value performance, we also value our friendships. Life is about growing, learning and enjoying. Here we enjoy our team and value our culture and community, knowing that without the right mix of people, and a place to live and grow, we could never expect to perform as a high-quality company. With the right team, magic will happen.</p>
<h2>Our purpose</h2>
<p>Our company is a community of friends, teammates and talented mechanics. We understand our place in the market and strive to create value for all our customers.</p>
<p>Our company has been cultivated through years of supporting a wide range of mechanical systems. Our culture knows and understands facility operational needs, and has adapted to time-sensitive projects that require extensive upfront planning for successful execution.</p>
<figure class="wp-block-image">' . wp_get_attachment_image( $about_image, 'large' ) . '</figure>
<h2>Our history</h2>
<p>VO Brothers Mechanical was founded by Josh VanOrden and grew into a renowned mechanical and plumbing contractor in the Intermountain West, known for specialty process piping, pure water, steam and medical gas work on campuses, hospitals and manufacturing plants across the state.</p>
<p>In early 2024, VO Brothers joined Quantum Industrial Services, an industrial mechanical and plumbing contractor serving clients across the United States. We still operate as VO Brothers Mechanical, with the same local team, from our shop in Ogden, now with Quantum\'s fabrication facility, training and resources behind us. <a href="' . esc_url( home_url( '/about/quantum-industrial/' ) ) . '">Learn more about Quantum Industrial</a>.</p>
' . $abc_award . '
<h2>Utah leadership</h2>
[vob_team group="utah"]
' . vob_placeholder( 'add the founding year and any milestones you\'d like to highlight.' ),
	array( 'post_excerpt' => 'A community of friends, teammates and talented mechanics, determined for progress.' )
);

vob_seed_page(
	'Core Values',
	'core-values',
	'<p class="lead">At Quantum Industrial, we build people. Developing and empowering our workforce is our most important mission and critical to our ability to offer an innovative approach to industrial services and provide safe and quality craftsmanship down to the Quantum level. As a Quantum Industrial company, VO Brothers lives by the same four values.</p>
<div class="value-grid">
<div class="value-card"><h3>Integrity</h3><p>Integrity is central to how we interact with others and how we approach our work. We hold ourselves to the highest standard of safety, honesty and quality in every project and client interaction. We take pride in our work and never take shortcuts or settle for mediocrity.</p></div>
<div class="value-card"><h3>Accountability</h3><p>We take full responsibility for our actions and results. We proactively look for and find solutions instead of just identifying problems. We see it, we own it.</p></div>
<div class="value-card"><h3>Partnership</h3><p>We see our teammates and our clients as true partners and care about their success as much as our own. We strive to be the best partner our customers work with, delivering unparalleled safety, service and quality.</p></div>
<div class="value-card"><h3>Innovation</h3><p>We proactively seek out and implement cutting-edge solutions to achieve better outcomes for our clients and improve safety, quality and efficiency for our team. We look for opportunities to challenge the status quo.</p></div>
</div>',
	array( 'post_parent' => $about, 'menu_order' => 1, 'post_excerpt' => 'We build people.' )
);

vob_seed_page(
	'Safety',
	'safety',
	'<p class="lead">Our commitment to zero incidents is reinforced through our culture and attitude towards safety. We pride ourselves on our unwavering focus to provide continuous safety instruction and certified training.</p>
<p>We are proactive when it comes to ensuring the safety of our employees and the safety of others. We are dedicated to strict adherence to federal and state safety regulations and follow stringent safety protocols while maintaining quality and productivity. We are proud of our safety record and our unwavering commitment to a zero-injury workforce.</p>
<div class="value-grid">
<div class="value-card"><h3>Planned for every site</h3><p>We evaluate each project\'s unique circumstances and safety protocols to properly prepare our team and keep everyone on or near the site as safe as possible.</p></div>
<div class="value-card"><h3>Training that never stops</h3><p>Comprehensive safety protocols and continuous training make every job site a safe place to work, from apprentices to superintendents.</p></div>
<div class="value-card"><h3>Everyone looks out</h3><p>We invest in the latest safety equipment and technologies, and cultivate a culture where everyone looks out for one another.</p></div>
</div>
<p>Safety is a significant part of our daily life. Our reputation relies heavily on our team and our conduct, from the office to the field and everything in between. By maintaining the highest level of safety, we achieve the best possible outcome for our team and our clients.</p>
<p>Our Utah safety program is led by <strong>Dennis Valdez, Safety Manager</strong>.</p>',
	array( 'post_parent' => $about, 'menu_order' => 2, 'post_excerpt' => 'Committed to zero incidents and a zero-injury workforce.' )
);

vob_seed_page(
	'Testimonials',
	'testimonials',
	'<p>What general contractors, owners and facility managers say about working with VO Brothers Mechanical.</p>
[vob_testimonials audience="client"]
<h2>From our team</h2>
[vob_testimonials audience="employee"]',
	array( 'post_parent' => $about, 'menu_order' => 3, 'post_excerpt' => 'What our customers and our people say.' )
);

$quantum_page = vob_seed_page(
	'Quantum Industrial',
	'quantum-industrial',
	'<p class="lead">VO Brothers Mechanical is a Quantum Industrial company. Quantum Industrial Services is a premier industrial mechanical and plumbing contractor serving clients across the United States. Its teams build and maintain some of the largest, most critical industrial projects in the country.</p>
<p>With strong financial backing, Quantum provides a comprehensive range of services including heating and cooling systems, refrigeration, plumbing, process piping, pipe fabrication and specialty pipe welding. Its commitment to safety, quality and innovation sets it apart as an industry leader.</p>
<p>In early 2024, Quantum expanded its capabilities by acquiring VO Brothers Mechanical, building on the strong foundation and legacy of our business to become a leading provider of plumbing, process piping, pipe fabrication and mechanical services in the Intermountain West. We operate as VO Brothers Mechanical, with the same local team, from our shop in Ogden.</p>
<h2>What it means for our customers</h2>
<div class="value-grid">
<div class="value-card"><h3>Fabrication capacity</h3><p>Access to Quantum\'s 136,000 sq ft pipe fabrication facility with robotic cutting and welding.</p></div>
<div class="value-card"><h3>Depth of resources</h3><p>Quantum\'s strong financial backing, training and equipment behind every project.</p></div>
<div class="value-card"><h3>Same local team</h3><p>The VO Brothers people you know, in Ogden, serving all of Utah.</p></div>
</div>
<h2>Quantum leadership</h2>
[vob_team group="quantum"]
<p><a class="btn btn--primary" href="https://quantumindustrial.com" target="_blank" rel="noopener">Visit Quantum Industrial</a></p>',
	array( 'post_parent' => $about, 'menu_order' => 4, 'post_excerpt' => 'Our parent company.' )
);

$careers = vob_seed_page(
	'Careers',
	'careers',
	'<p class="lead">We believe that a strong company culture is the foundation of our success. We are committed to creating an environment where our people feel valued, respected and empowered to grow. Whether you\'re a seasoned journeyman, an estimator, a BIM modeler or just starting out, there\'s a place for you here.</p>
<div class="value-grid">
<div class="value-card"><h3>Safety first</h3><p>The well-being of our team is paramount. Comprehensive safety protocols, continuous training and the latest equipment make every job site a safe place to work.</p></div>
<div class="value-card"><h3>Respect &amp; support</h3><p>Our team is our family. Open communication and mutual respect are the cornerstones of our culture, whether you\'re a seasoned professional or just starting out.</p></div>
<div class="value-card"><h3>Training &amp; development</h3><p>Extensive training programs and continuous learning, from apprenticeships to advanced certifications, give you the tools to excel and advance your career.</p></div>
</div>
<h2>Benefits</h2>
<ul class="benefit-grid">
<li>' . vob_icon_markup( 'check' ) . ' Competitive wages</li>
<li>' . vob_icon_markup( 'check' ) . ' Bonuses &amp; incentives</li>
<li>' . vob_icon_markup( 'check' ) . ' Health insurance</li>
<li>' . vob_icon_markup( 'check' ) . ' Retirement &amp; matching</li>
<li>' . vob_icon_markup( 'check' ) . ' Life insurance</li>
<li>' . vob_icon_markup( 'check' ) . ' Vacation &amp; holiday pay</li>
</ul>
<h2 id="openings">Open positions</h2>
[vob_jobs]
<h2>Hear from the team</h2>
[vob_testimonials audience="employee"]',
	array( 'post_excerpt' => 'We build people. Grow your career with a team that feels like family.' )
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
