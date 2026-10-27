<?php
/**
 * Services overview: one large card per service group.
 */

get_header();

vob_page_hero(
	array(
		'title'   => 'Services',
		'eyebrow' => 'What we do //',
		'intro'   => 'Mechanical, process piping, plumbing, service and maintenance for facilities across Utah. We understand facility operational needs and plan extensively up front so time-sensitive projects are executed successfully.',
	)
);
?>

<section class="section">
	<div class="container group-list">
		<?php
		while ( have_posts() ) :
			the_post();
			$children = get_children( array( 'post_parent' => get_the_ID(), 'post_type' => 'vob_service', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
			?>
			<article class="group-row">
				<a class="group-row__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
					<?php the_post_thumbnail( 'vob-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
				</a>
				<div class="group-row__body">
					<span class="service-card__icon"><?php echo vob_icon( get_post_meta( get_the_ID(), 'vob_icon', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h2 class="group-row__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php if ( $children ) : ?>
						<ul class="tag-list">
							<?php foreach ( $children as $child ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $child ) ); ?>"><?php echo esc_html( $child->post_title ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<a class="link-arrow" href="<?php the_permalink(); ?>">Explore <?php the_title(); ?> <?php echo vob_icon( 'arrow', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
