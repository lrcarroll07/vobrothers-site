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
	array( 'Josh VanOrden', 'President', 'team/josh-vanorden.jpg', 'utah' ),
	array( 'Jeff Warner', 'General Manager', 'team/jeff-warner.jpg', 'utah' ),
	array( 'Jason Wilson', 'Construction Operations Manager', 'team/jason-wilson.jpg', 'utah' ),
	array( 'Dan Fonoti', 'Preconstruction Manager', 'team/dan-fonoti.jpg', 'utah' ),
	array( 'Courtney Cannon', 'Senior Project Manager', 'team/courtney-cannon.jpg', 'utah' ),
	array( 'Dennis Valdez', 'Safety Manager', 'team/dennis-valdez.jpg', 'utah' ),
	array( 'Melody Stong', 'Business Development', 'team/melody-stong.jpg', 'utah' ),
	array( 'Jordan Abbott', 'VDC/BIM Manager', 'team/jordan-abbott.jpg', 'utah' ),
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
foreach ( $data['awards'] as $order => list( $name, $project, $issuer, $year, $kind ) ) {
	wp_insert_post(
		array(
			'post_type'   => 'vob_award',
			'post_status' => 'publish',
			'post_title'  => $name,
			'menu_order'  => $order,
			'meta_input'  => array(
				'vob_award_project' => $project,
				'vob_award_issuer'  => $issuer,
				'vob_award_year'    => $year,
				'vob_award_kind'    => $kind,
			),
		)
	);
}

// About page: award summary linking to the full Awards page.
$award_total  = count( $data['awards'] );
$award_safety = count( array_filter( $data['awards'], fn( $a ) => 'safety' === $a[4] ) );
$abc_award    = '<div class="award-callout">' . vob_icon_markup( 'award' ) . '<div><strong>' . $award_total . ' industry awards since 2018</strong><br>Including ' . $award_safety . ' safety awards, ABC Excellence in Construction awards, and Utah Construction &amp; Design honors for our 2025 Davis Aqueduct, Provo Wastewater and Schreiber Foods projects. <a href="' . esc_url( home_url( '/about/awards/' ) ) . '">See all awards</a></div></div>';

$about_image = vob_seed_image( 'site/about.jpg', 'The VO Brothers Mechanical team' );
$about       = vob_seed_page(
	'About VO Brothers',
	'about',
	'<p>We hold ourselves to a high standard on every job, and we never forget who does the work: people. Our crews are close-knit. We learn from each other, push each other and genuinely enjoy what we do. Put the right people together, give them room to grow, and great work follows.</p>
<h2>Our purpose</h2>
<p>We\'re a team of skilled tradespeople, project managers and friends who take pride in building the systems facilities can\'t run without. We earn our customers\' trust one project at a time by planning carefully, communicating honestly and finishing what we start.</p>
<p>Years of work on boilers, chillers, process piping, pure water and medical gas have taught us how facilities actually operate. Many of our customers can\'t simply shut down, so we plan every detail up front and deliver time-sensitive work with as little disruption as possible.</p>
<figure class="wp-block-image">' . wp_get_attachment_image( $about_image, 'large' ) . '</figure>
<h2>Our history</h2>
<p>VO Brothers Mechanical was founded by Chet &amp; Scott VanOrden before Chet\'s son, Josh, took over as President in 2011. Since then, the company has grown into a renowned mechanical and plumbing contractor in the Intermountain West, known for specialty process piping, pure water, steam and medical gas work on campuses, in hospitals and at manufacturing plants across Utah.</p>
<p>In early 2024, VO Brothers joined Quantum Industrial Services, an industrial mechanical and plumbing contractor serving clients nationwide. The people you work with haven\'t changed: the same local team still runs VO Brothers from our shop in Ogden. What\'s new is what stands behind us, including Quantum\'s fabrication facility, training programs and resources. <a href="' . esc_url( home_url( '/about/quantum-industrial/' ) ) . '">Learn more about Quantum Industrial</a>.</p>
' . $abc_award . '
<h2>Utah leadership</h2>
[vob_team group="utah"]
' . vob_placeholder( 'add the founding year and any milestones you\'d like to highlight.' ),
	array( 'post_excerpt' => 'A close-knit team of Utah tradespeople, building the systems facilities depend on.' )
);

vob_seed_page(
	'Core Values',
	'core-values',
	'<p class="lead">At Quantum Industrial, we build people. Developing and empowering our workforce is our most important mission, and it\'s how we deliver an innovative approach to industrial services with safe, quality craftsmanship down to the Quantum level. As a Quantum Industrial company, VO Brothers lives by the same four values.</p>
<div class="value-grid">
<div class="value-card"><h3>Integrity</h3><p>Integrity is central to how we interact with others and how we approach our work. We hold ourselves to the highest standard of safety, honesty and quality in every project and client interaction. We take pride in our work and never take shortcuts or settle for mediocrity.</p></div>
<div class="value-card"><h3>Accountability</h3><p>We take full responsibility for our actions and results. We don\'t just point out problems; we find solutions. We see it, we own it.</p></div>
<div class="value-card"><h3>Partnership</h3><p>We see our teammates and clients as true partners and care about their success as much as our own. Our goal is to be the best partner our customers have, with unmatched safety, service and quality.</p></div>
<div class="value-card"><h3>Innovation</h3><p>We seek out cutting-edge solutions that deliver better outcomes for our clients and make our work safer, better and more efficient. We look for every opportunity to challenge the status quo.</p></div>
</div>',
	array( 'post_parent' => $about, 'menu_order' => 1, 'post_excerpt' => 'We build people.' )
);

vob_seed_page(
	'Safety',
	'safety',
	'<p class="lead">Our goal is zero incidents, and our culture is built around reaching it: continuous safety instruction, certified training and a team that treats safety as everyone\'s job.</p>
<p>We\'re proactive about protecting our employees and everyone around our work. We follow federal and state safety regulations to the letter and hold every site to stringent protocols, without sacrificing quality or productivity. We\'re proud of our safety record and our commitment to a zero-injury workforce.</p>
<div class="value-grid">
<div class="value-card"><h3>Planned for every site</h3><p>We assess each project\'s unique conditions and protocols in advance, so our crews arrive prepared and everyone on or near the site stays safe.</p></div>
<div class="value-card"><h3>Training that never stops</h3><p>Comprehensive safety protocols and continuous training make every job site a safe place to work, from apprentices to superintendents.</p></div>
<div class="value-card"><h3>Everyone looks out</h3><p>We invest in the latest safety equipment and technologies, and cultivate a culture where everyone looks out for one another.</p></div>
</div>
<p>Safety shapes everything we do, from the office to the field. Our reputation rests on how our people work, and keeping safety first produces the best outcome for our team and our clients.</p>
<p>Our Utah safety program is led by <strong>Dennis Valdez, Safety Manager</strong>.</p>
<h2>Safety recognition</h2>
<p>Our safety program has earned recognition year after year, including ABC\'s STEP awards (Safety Training and Evaluation Process) at Silver, Platinum and Diamond levels, the Annual Workplace Safety Award from the Utah Labor Commission and ABC, and ENR Mountain States\' Excellence in Safety award for the Primary Children\'s Hospital surgical expansion.</p>
[vob_awards kind="safety" layout="list"]',
	array( 'post_parent' => $about, 'menu_order' => 2, 'post_excerpt' => 'Committed to zero incidents and a zero-injury workforce.' )
);

vob_seed_page(
	'Awards',
	'awards',
	'<p class="lead">Year after year, our work and our safety record have been recognized by Associated Builders and Contractors, the Utah Labor Commission, Utah Construction &amp; Design, ENR Mountain States and Pulse of the City News.</p>
[vob_award_stats]
[vob_awards]',
	array( 'post_parent' => $about, 'menu_order' => 3, 'post_excerpt' => 'Recognized for construction excellence and safety across Utah.' )
);

vob_seed_page(
	'Testimonials',
	'testimonials',
	'<p>Hear from the general contractors, owners and facility managers who work with VO Brothers Mechanical.</p>
[vob_testimonials audience="client"]
<h2>From our team</h2>
[vob_testimonials audience="employee"]',
	array( 'post_parent' => $about, 'menu_order' => 4, 'post_excerpt' => 'What our customers and our people say.' )
);

$quantum_page = vob_seed_page(
	'Quantum Industrial',
	'quantum-industrial',
	'<p class="lead">VO Brothers Mechanical is a Quantum Industrial company. Quantum Industrial Services is a premier industrial mechanical and plumbing contractor serving clients across the United States. Its teams build and maintain some of the largest, most critical industrial projects in the country.</p>
<p>Backed by strong financial resources, Quantum offers heating and cooling systems, refrigeration, plumbing, process piping, pipe fabrication and specialty pipe welding, with a commitment to safety, quality and innovation that sets it apart as an industry leader.</p>
<p>In early 2024, Quantum acquired VO Brothers Mechanical, building on our foundation and legacy to become a leading provider of plumbing, process piping, pipe fabrication and mechanical services in the Intermountain West. We still operate as VO Brothers Mechanical, with the same local team, from our shop in Ogden.</p>
<h2>What it means for our customers</h2>
<div class="value-grid">
<div class="value-card"><h3>Fabrication capacity</h3><p>Access to Quantum\'s 136,000 sq ft pipe fabrication facility with robotic cutting and welding.</p></div>
<div class="value-card"><h3>Depth of resources</h3><p>Quantum\'s strong financial backing, training and equipment behind every project.</p></div>
<div class="value-card"><h3>Same local team</h3><p>The VO Brothers people you know, in Ogden, serving all of Utah.</p></div>
</div>
<h2>Quantum leadership</h2>
[vob_team group="quantum"]
<p><a class="btn btn--primary" href="https://quantumindustrial.com" target="_blank" rel="noopener">Visit Quantum Industrial</a></p>',
	array( 'post_parent' => $about, 'menu_order' => 5, 'post_excerpt' => 'Our parent company.' )
);

$careers = vob_seed_page(
	'Careers',
	'careers',
	'<p class="lead">Great work starts with a great culture. We want every person here to feel valued, respected and empowered to grow, whether you\'re a seasoned journeyman, an estimator, a BIM modeler or brand new to the trades.</p>
<div class="value-grid">
<div class="value-card"><h3>Safety first</h3><p>Your well-being comes first. Comprehensive safety protocols, continuous training and the latest equipment make every job site a safe place to work.</p></div>
<div class="value-card"><h3>Respect &amp; support</h3><p>Our team is our family. Open communication and mutual respect are the cornerstones of our culture, whether you\'re a seasoned professional or just starting out.</p></div>
<div class="value-card"><h3>Training &amp; development</h3><p>From apprenticeships to advanced certifications, our training programs give you the skills to excel and the path to advance.</p></div>
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
	array( 'post_excerpt' => 'Request a quote, set up a maintenance plan or ask about 24/7 emergency service.' )
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
