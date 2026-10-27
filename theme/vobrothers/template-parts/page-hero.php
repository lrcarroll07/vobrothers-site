<?php
/**
 * Inner-page hero. Args: title, intro, image_id, eyebrow.
 */

$title    = $args['title'] ?? get_the_title();
$intro    = $args['intro'] ?? '';
$image_id = $args['image_id'] ?? 0;
$eyebrow  = $args['eyebrow'] ?? '';
?>
<section class="page-hero<?php echo $image_id ? ' page-hero--image' : ''; ?>">
	<?php if ( $image_id ) : ?>
		<?php echo wp_get_attachment_image( $image_id, 'vob-hero', false, array( 'class' => 'page-hero__bg', 'alt' => '', 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?>
	<?php endif; ?>
	<div class="container page-hero__inner">
		<?php vob_the_breadcrumbs(); ?>
		<?php if ( $eyebrow ) : ?>
			<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>
		<h1 class="page-hero__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $intro ) : ?>
			<p class="page-hero__intro"><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>
	</div>
</section>
