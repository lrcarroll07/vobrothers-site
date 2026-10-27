</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="site-footer__brand">
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo-lockup.png' ) ); ?>" width="569" height="200" alt="<?php echo esc_attr( vob_t_info( 'company_name' ) ); ?>, a <?php echo esc_attr( vob_t_info( 'parent_name' ) ); ?> company" loading="lazy">
			<p>Mechanical, process piping, plumbing, service and maintenance serving the state of Utah.</p>
			<ul class="social">
				<?php
				$social = array(
					'linkedin'  => 'LinkedIn',
					'facebook'  => 'Facebook',
					'instagram' => 'Instagram',
					'x'         => 'X',
				);
				foreach ( $social as $key => $label ) :
					if ( vob_t_info( $key ) ) :
						?>
						<li><a href="<?php echo esc_url( vob_t_info( $key ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a></li>
						<?php
					endif;
				endforeach;
				?>
			</ul>
		</div>

		<div>
			<h2 class="site-footer__heading">Contact</h2>
			<ul class="site-footer__contact">
				<li><?php echo vob_icon( 'pin', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( vob_t_info( 'street' ) ); ?><br><?php echo esc_html( vob_t_info( 'city' ) . ', ' . vob_t_info( 'state' ) . ' ' . vob_t_info( 'zip' ) ); ?></span></li>
				<li><?php echo vob_icon( 'phone', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<a href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'phone' ) ) ); ?>"><?php echo esc_html( vob_t_info( 'phone' ) ); ?></a></li>
				<li><?php echo vob_icon( 'mail', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<a href="mailto:<?php echo esc_attr( vob_t_info( 'email' ) ); ?>"><?php echo esc_html( vob_t_info( 'email' ) ); ?></a></li>
			</ul>
			<a class="btn btn--primary btn--sm" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>">24/7 Emergency: <?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></a>
		</div>

		<div>
			<h2 class="site-footer__heading">Services</h2>
			<ul class="site-footer__links">
				<?php foreach ( function_exists( 'vob_service_groups' ) ? vob_service_groups() : array() as $group ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $group ) ); ?>"><?php echo esc_html( $group->post_title ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div>
			<h2 class="site-footer__heading">Company</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'site-footer__links',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</div>
	</div>

	<div class="site-footer__bottom">
		<div class="container site-footer__bottom-inner">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( vob_t_info( 'legal_name' ) ); ?>. A <a href="<?php echo esc_url( vob_t_info( 'parent_url' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( vob_t_info( 'parent_name' ) ); ?></a> company.</p>
			<p>Licensed &amp; insured mechanical and plumbing contractor &middot; Ogden, Utah</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
