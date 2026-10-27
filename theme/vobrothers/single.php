<?php
/**
 * Single news post.
 */

get_header();
the_post();

vob_page_hero(
	array(
		'title'    => get_the_title(),
		'eyebrow'  => get_the_date(),
		'image_id' => get_post_thumbnail_id(),
	)
);
?>

<section class="section">
	<div class="container prose prose--page">
		<?php the_content(); ?>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
