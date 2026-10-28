<?php
/**
 * Projects: every project with client-side filtering by type and location.
 * Also used for the /projects/type/{slug}/ and /projects/location/{slug}/ archives.
 */

get_header();

$sectors   = get_terms( array( 'taxonomy' => 'vob_sector', 'hide_empty' => true ) );
$locations = get_terms( array( 'taxonomy' => 'vob_location', 'hide_empty' => true ) );
$active    = is_tax( 'vob_sector' ) ? get_queried_object()->slug : '*';
$area      = is_tax( 'vob_location' ) ? get_queried_object()->slug : '*';

// Term archives list every project with that filter pre-selected, so visitors can widen it.
$projects = is_tax()
	? new WP_Query( array( 'post_type' => 'vob_project', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) )
	: $GLOBALS['wp_query'];

// Map markers for projects that have coordinates.
$markers = array();
foreach ( $projects->posts as $project ) {
	$lat = (float) get_post_meta( $project->ID, 'vob_lat', true );
	$lng = (float) get_post_meta( $project->ID, 'vob_lng', true );
	if ( ! $lat || ! $lng ) {
		continue;
	}
	// Plain text for the map script (it inserts text, never HTML), so decode entities.
	$plain     = fn( $text ) => html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$markers[] = array(
		'title'     => $plain( get_the_title( $project ) ),
		'url'       => get_permalink( $project ),
		'image'     => get_the_post_thumbnail_url( $project, 'medium' ),
		'sector'    => $plain( vob_project_sectors( $project->ID ) ),
		'location'  => $plain( get_post_meta( $project->ID, 'vob_location_label', true ) ),
		'size'      => $plain( get_post_meta( $project->ID, 'vob_size', true ) ),
		'groups'    => explode( ' ', vob_term_slugs( $project->ID, 'vob_sector' ) ),
		'areas'     => explode( ' ', vob_term_slugs( $project->ID, 'vob_location' ) ),
		'lat'       => $lat,
		'lng'       => $lng,
	);
}

vob_page_hero(
	array(
		'title'   => is_tax() ? single_term_title( '', false ) . ' Projects' : 'Projects',
		'eyebrow' => 'Experience //',
		'intro'   => 'Outside of service, we have built a portfolio of projects all over the state of Utah. We are proud of our work, proud of our friendships and proud to be a preferred mechanical contractor.',
	)
);
?>

<section class="section">
	<div class="container" data-vob-filter>
		<div class="filter-panel">
			<div class="filter-bar" role="toolbar" aria-label="Filter by project type">
				<button type="button" class="chip<?php echo '*' === $active ? ' is-active' : ''; ?>" data-filter="*" aria-pressed="<?php echo '*' === $active ? 'true' : 'false'; ?>">All projects</button>
				<?php foreach ( $sectors as $term ) : ?>
					<button type="button" class="chip<?php echo $term->slug === $active ? ' is-active' : ''; ?>" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="<?php echo $term->slug === $active ? 'true' : 'false'; ?>"><?php echo esc_html( $term->name ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php if ( $locations ) : ?>
				<label class="select-filter">
					<span>Area</span>
					<select data-filter-location>
						<option value="*">All areas</option>
						<?php foreach ( $locations as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $term->slug, $area ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
		</div>

		<?php if ( $markers ) : ?>
			<div class="project-map" data-project-map role="region" aria-label="Map of project locations">
				<noscript><p class="project-map__noscript">Turn on JavaScript to see our projects on a map.</p></noscript>
			</div>
			<script type="application/json" data-project-map-data><?php echo wp_json_encode( $markers ); ?></script>
		<?php endif; ?>

		<p class="filter-count" aria-live="polite" data-filter-count></p>

		<div class="project-grid">
			<?php
			while ( $projects->have_posts() ) {
				$projects->the_post();
				get_template_part( 'template-parts/project-card' );
			}
			wp_reset_postdata();
			?>
		</div>
		<p class="filter-empty" hidden data-filter-empty>No projects match those filters yet.</p>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
