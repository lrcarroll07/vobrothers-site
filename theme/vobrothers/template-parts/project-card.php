<?php
/**
 * Project card, used in the home carousel, projects grid and related projects.
 */

$post_id  = get_the_ID();
$location = get_post_meta( $post_id, 'vob_location_label', true ) ?: 'Utah';
$size     = get_post_meta( $post_id, 'vob_size', true );
$status   = get_post_meta( $post_id, 'vob_status', true );
?>
<article class="project-card"
	data-groups="<?php echo esc_attr( vob_term_slugs( $post_id, 'vob_sector' ) ); ?>"
	data-location="<?php echo esc_attr( vob_term_slugs( $post_id, 'vob_location' ) ); ?>">
	<a class="project-card__link" href="<?php the_permalink(); ?>">
		<div class="project-card__media">
			<?php the_post_thumbnail( 'vob-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
			<?php if ( 'Ongoing' === $status ) : ?>
				<span class="badge">Ongoing</span>
			<?php endif; ?>
		</div>
		<div class="project-card__body">
			<p class="project-card__sector"><?php echo esc_html( vob_project_sectors( $post_id ) ); ?></p>
			<h3 class="project-card__title"><?php the_title(); ?></h3>
			<p class="project-card__meta">
				<span><?php echo esc_html( $location ); ?></span>
				<?php if ( $size ) : ?>
					<span><?php echo esc_html( $size ); ?></span>
				<?php endif; ?>
			</p>
		</div>
	</a>
</article>
