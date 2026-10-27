<?php
/**
 * Default page. Pages in a family (e.g. About → Core Values) get a section sub-menu.
 */

get_header();
the_post();

$top_id = get_the_ID();
$ancestors = get_post_ancestors( $top_id );
if ( $ancestors ) {
	$top_id = end( $ancestors );
}
$family = get_pages( array( 'child_of' => $top_id, 'sort_column' => 'menu_order', 'parent' => $top_id ) );

vob_page_hero(
	array(
		'title'    => get_the_title(),
		'intro'    => has_excerpt() ? get_the_excerpt() : '',
		'image_id' => get_post_thumbnail_id(),
	)
);
?>

<?php if ( $family ) : ?>
	<nav class="subnav" aria-label="<?php echo esc_attr( get_the_title( $top_id ) ); ?> pages">
		<div class="container">
			<ul>
				<li><a href="<?php echo esc_url( get_permalink( $top_id ) ); ?>"<?php echo get_the_ID() === $top_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( get_the_title( $top_id ) ); ?></a></li>
				<?php foreach ( $family as $sibling ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $sibling ) ); ?>"<?php echo get_the_ID() === $sibling->ID ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $sibling->post_title ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
<?php endif; ?>

<section class="section">
	<div class="container prose prose--page">
		<?php the_content(); ?>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
