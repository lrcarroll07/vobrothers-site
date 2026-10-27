<?php
/**
 * Closing call-to-action band shown above the footer on most pages.
 */
?>
<section class="cta-band">
	<div class="container cta-band__inner">
		<div>
			<h2 class="cta-band__title">Have a project or a system that needs attention?</h2>
			<p>From new construction to a 2&nbsp;a.m. boiler failure, our team is ready to help.</p>
		</div>
		<div class="cta-band__actions">
			<a class="btn btn--light" href="<?php echo esc_url( home_url( '/contact/#vob-form-quote' ) ); ?>">Request a Quote</a>
			<a class="btn btn--outline-light" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>"><?php echo vob_icon( 'phone', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></a>
		</div>
	</div>
</section>
