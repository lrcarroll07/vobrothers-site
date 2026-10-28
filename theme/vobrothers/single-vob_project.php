<?php
/**
 * Single project.
 */

get_header();
the_post();

$post_id  = get_the_ID();
$facts    = array(
	'Location' => get_post_meta( $post_id, 'vob_location_label', true ) ?: 'Utah',
	'Type'     => vob_project_sectors( $post_id, ', ' ),
	'Size'     => get_post_meta( $post_id, 'vob_size', true ),
	'Owner'    => get_post_meta( $post_id, 'vob_client', true ),
	'Status'   => get_post_meta( $post_id, 'vob_status', true ),
	'Award'    => get_post_meta( $post_id, 'vob_award', true ),
);
$scope    = get_post_meta( $post_id, 'vob_scope', true );
$gallery  = vob_project_gallery( $post_id );
$sectors  = wp_get_post_terms( $post_id, 'vob_sector', array( 'fields' => 'ids' ) );
$related  = new WP_Query(
	array(
		'post_type'      => 'vob_project',
		'posts_per_page' => 3,
		'post__not_in'   => array( $post_id ),
		'tax_query'      => $sectors ? array( array( 'taxonomy' => 'vob_sector', 'terms' => $sectors ) ) : array(),
		'orderby'        => 'rand',
	)
);

vob_page_hero(
	array(
		'title'    => get_the_title(),
		'eyebrow'  => vob_project_sectors( $post_id ),
		'image_id' => get_post_meta( get_post_thumbnail_id(), '_vob_placeholder', true ) ? 0 : get_post_thumbnail_id(),
	)
);
?>

<section class="section">
	<div class="container detail">
		<div class="detail__main prose">
			<?php the_content(); ?>
			<?php if ( $scope ) : ?>
				<h2>Our scope</h2>
				<p><?php echo esc_html( $scope ); ?></p>
			<?php endif; ?>

			<?php if ( count( $gallery ) > 1 ) : ?>
				<h2>Gallery</h2>
				<div class="gallery-grid">
					<?php foreach ( $gallery as $image_id ) : ?>
						<a href="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'full' ) ); ?>"><?php echo wp_get_attachment_image( $image_id, 'vob-card', false, array( 'loading' => 'lazy' ) ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="placeholder-note">Placeholder: add 4–8 high-resolution project photos (wide shots plus mechanical rooms, piping and welds).</p>
			<?php endif; ?>
		</div>

		<aside class="detail__aside">
			<div class="fact-card">
				<h2 class="fact-card__title">Project facts</h2>
				<dl>
					<?php foreach ( array_filter( $facts ) as $label => $value ) : ?>
						<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<a class="btn btn--primary btn--block" href="<?php echo esc_url( home_url( '/contact/#vob-form-quote' ) ); ?>">Start a similar project</a>
			</div>
		</aside>
	</div>
</section>

<?php if ( $related->have_posts() ) : ?>
<section class="section section--mist">
	<div class="container">
		<div class="section-head section-head--split">
			<h2 class="section-title">Related projects</h2>
			<a class="link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'vob_project' ) ); ?>">All projects <?php echo vob_icon( 'arrow', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
		<div class="project-grid project-grid--3">
			<?php
			while ( $related->have_posts() ) {
				$related->the_post();
				get_template_part( 'template-parts/project-card' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
