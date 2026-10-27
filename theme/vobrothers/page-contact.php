<?php
/**
 * Contact page (/contact/): quote form, contact details and map.
 */

get_header();
the_post();

vob_page_hero(
	array(
		'title'   => get_the_title(),
		'eyebrow' => 'Contact //',
		'intro'   => has_excerpt() ? get_the_excerpt() : '',
	)
);

$map_query = rawurlencode( vob_address_line() );
?>

<section class="section">
	<div class="container contact">
		<div class="contact__form form-card">
			<?php echo do_shortcode( '[vob_form type="quote"]' ); ?>
		</div>

		<aside class="contact__info">
			<a class="emergency-callout emergency-callout--stack" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>">
				<span class="pulse" aria-hidden="true"></span>
				<span><strong>24/7 emergency service</strong> Call any time, day or night.</span>
				<strong class="emergency-callout__phone"><?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></strong>
			</a>

			<div class="info-card">
				<h2 class="info-card__title"><?php echo esc_html( vob_t_info( 'company_name' ) ); ?></h2>
				<p class="info-card__sub">A <?php echo esc_html( vob_t_info( 'parent_name' ) ); ?> Company</p>
				<ul class="site-footer__contact">
					<li><?php echo vob_icon( 'pin', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( vob_t_info( 'street' ) ); ?><br><?php echo esc_html( vob_t_info( 'city' ) . ', ' . vob_t_info( 'state' ) . ' ' . vob_t_info( 'zip' ) ); ?></span></li>
					<li><?php echo vob_icon( 'phone', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<a href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'phone' ) ) ); ?>"><?php echo esc_html( vob_t_info( 'phone' ) ); ?></a></li>
					<li><?php echo vob_icon( 'mail', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<a href="mailto:<?php echo esc_attr( vob_t_info( 'email' ) ); ?>"><?php echo esc_html( vob_t_info( 'email' ) ); ?></a></li>
				</ul>
				<p class="info-card__links">
					<a class="link-arrow" href="<?php echo esc_url( home_url( '/careers/' ) ); ?>">Looking for a job? See open positions <?php echo vob_icon( 'arrow', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</p>
			</div>

			<div class="map">
				<iframe title="Map to VO Brothers Mechanical" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
					src="https://maps.google.com/maps?q=<?php echo esc_attr( $map_query ); ?>&amp;z=14&amp;output=embed"></iframe>
			</div>
		</aside>
	</div>
	<?php if ( trim( get_the_content() ) ) : ?>
		<div class="container prose prose--page"><?php the_content(); ?></div>
	<?php endif; ?>
</section>

<?php
get_footer();
