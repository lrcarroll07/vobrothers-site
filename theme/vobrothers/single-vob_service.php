<?php
/**
 * Service group page (top level) or individual service page (child of a group).
 */

get_header();
the_post();

$post_id   = get_the_ID();
$parent_id = wp_get_post_parent_id( $post_id );
$group_id  = $parent_id ?: $post_id;
$group     = get_post( $group_id );
$children  = get_children( array( 'post_parent' => $group_id, 'post_type' => 'vob_service', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
$bullets   = vob_meta_lines( $post_id, 'vob_bullets' );
$emergency = get_post_meta( $group_id, 'vob_emergency', true );
$form_type = 'service-maintenance' === $group->post_name ? 'maintenance' : 'quote';

vob_page_hero(
	array(
		'title'    => get_the_title(),
		'eyebrow'  => $parent_id ? $group->post_title . ' //' : 'Services //',
		'intro'    => $parent_id ? get_the_excerpt() : get_post_meta( $post_id, 'vob_tagline', true ),
		'image_id' => get_post_thumbnail_id() ?: get_post_thumbnail_id( $group_id ),
	)
);
?>

<section class="section">
	<div class="container detail">
		<div class="detail__main">
			<?php if ( $emergency ) : ?>
				<a class="emergency-callout" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>">
					<span class="pulse" aria-hidden="true"></span>
					<span><strong>Need help now?</strong> Our emergency line is answered 24 hours a day, 7 days a week.</span>
					<strong class="emergency-callout__phone"><?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></strong>
				</a>
			<?php endif; ?>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<?php if ( $bullets ) : ?>
				<ul class="checklist checklist--cols">
					<?php foreach ( $bullets as $bullet ) : ?>
						<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $bullet ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! $parent_id && $children ) : ?>
				<h2 class="detail__subhead">What we do in <?php echo esc_html( strtolower( get_the_title() ) ); ?></h2>
				<div class="mini-grid">
					<?php foreach ( $children as $child ) : ?>
						<a class="mini-card" href="<?php echo esc_url( get_permalink( $child ) ); ?>">
							<?php echo get_the_post_thumbnail( $child, 'vob-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
							<span class="mini-card__body">
								<strong><?php echo esc_html( $child->post_title ); ?></strong>
								<span><?php echo esc_html( $child->post_excerpt ); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $parent_id && count( $children ) > 1 ) : ?>
				<div class="sibling-nav">
					<h2 class="detail__subhead">More in <a href="<?php echo esc_url( get_permalink( $group ) ); ?>"><?php echo esc_html( $group->post_title ); ?></a></h2>
					<ul class="tag-list">
						<?php foreach ( $children as $child ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $child ) ); ?>"<?php echo $child->ID === $post_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $child->post_title ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<aside class="detail__aside">
			<div class="form-card">
				<?php echo do_shortcode( sprintf( '[vob_form type="%s" service="%s"]', $form_type, esc_attr( $group->post_name ) ) ); ?>
			</div>
		</aside>
	</div>
</section>

<section class="section section--mist">
	<div class="container">
		<h2 class="section-title section-title--sm">Other services</h2>
		<ul class="tag-list tag-list--lg">
			<?php foreach ( vob_service_groups() as $other ) : ?>
				<?php if ( $other->ID !== $group_id ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $other ) ); ?>"><?php echo vob_icon( get_post_meta( $other->ID, 'vob_icon', true ), 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $other->post_title ); ?></a></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php
get_footer();
