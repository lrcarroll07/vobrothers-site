<?php
/**
 * Lead forms: [vob_form type="quote|maintenance|contact" service="group-slug"].
 *
 * Each submission is saved as a "Lead" in the admin and emailed to the lead
 * address in Settings → VO Brothers. Spam protection: nonce, honeypot field and
 * a minimum fill time. (Production: add Cloudflare Turnstile or swap to Gravity Forms.)
 */

defined( 'ABSPATH' ) || exit;

function vob_form_types() {
	return array(
		'quote'       => array(
			'heading' => 'Request a quote',
			'button'  => 'Request a quote',
			'intro'   => 'Tell us about your project and we\'ll get back to you within one business day.',
		),
		'maintenance' => array(
			'heading' => 'Get a maintenance plan',
			'button'  => 'Request my maintenance plan',
			'intro'   => 'Tell us about your facility and equipment and we\'ll build a preventative maintenance plan around it.',
		),
		'contact'     => array(
			'heading' => 'Send us a message',
			'button'  => 'Send message',
			'intro'   => '',
		),
	);
}

add_shortcode(
	'vob_form',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'type' => 'quote', 'service' => '', 'heading' => '' ), $atts );
		$types = vob_form_types();
		$type  = isset( $types[ $atts['type'] ] ) ? $atts['type'] : 'quote';
		$conf  = $types[ $type ];
		$id    = 'vob-form-' . $type;

		$status = isset( $_GET['vob_form'] ) && $type === $_GET['vob_form'] ? sanitize_key( $_GET['vob_status'] ?? '' ) : '';

		ob_start();
		?>
		<div class="vob-form" id="<?php echo esc_attr( $id ); ?>">
			<h2 class="vob-form__heading"><?php echo esc_html( $atts['heading'] ?: $conf['heading'] ); ?></h2>
			<?php if ( $conf['intro'] ) : ?>
				<p class="vob-form__intro"><?php echo esc_html( $conf['intro'] ); ?></p>
			<?php endif; ?>

			<?php if ( 'sent' === $status ) : ?>
				<div class="notice notice--success" role="status">
					<strong>Thanks, we've got it.</strong> A member of our team will be in touch shortly.
					For emergencies call <a href="<?php echo esc_attr( vob_tel( vob_info( 'emergency_phone' ) ) ); ?>"><?php echo esc_html( vob_info( 'emergency_phone' ) ); ?></a>.
				</div>
			<?php else : ?>
				<?php if ( 'error' === $status ) : ?>
					<div class="notice notice--error" role="alert">Please fill in your name, a valid email and a message, then try again.</div>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="form-grid">
					<input type="hidden" name="action" value="vob_form">
					<input type="hidden" name="vob_type" value="<?php echo esc_attr( $type ); ?>">
					<input type="hidden" name="vob_ts" value="<?php echo esc_attr( time() ); ?>">
					<input type="hidden" name="vob_return" value="<?php echo esc_url( get_permalink() ?: home_url( add_query_arg( null, null ) ) ); ?>">
					<?php wp_nonce_field( 'vob_form', 'vob_nonce' ); ?>
					<div class="hp" aria-hidden="true"><label>Website <input type="text" name="vob_website" tabindex="-1" autocomplete="off"></label></div>

					<p class="field"><label for="<?php echo esc_attr( $id ); ?>-name">Name <span class="req">*</span></label>
						<input id="<?php echo esc_attr( $id ); ?>-name" name="vob_name" required autocomplete="name"></p>
					<p class="field"><label for="<?php echo esc_attr( $id ); ?>-company">Company</label>
						<input id="<?php echo esc_attr( $id ); ?>-company" name="vob_company" autocomplete="organization"></p>
					<p class="field"><label for="<?php echo esc_attr( $id ); ?>-email">Email <span class="req">*</span></label>
						<input id="<?php echo esc_attr( $id ); ?>-email" name="vob_email" type="email" required autocomplete="email"></p>
					<p class="field"><label for="<?php echo esc_attr( $id ); ?>-phone">Phone</label>
						<input id="<?php echo esc_attr( $id ); ?>-phone" name="vob_phone" type="tel" autocomplete="tel"></p>

					<?php if ( 'contact' !== $type ) : ?>
						<p class="field field--full"><label for="<?php echo esc_attr( $id ); ?>-service">Service needed</label>
							<select id="<?php echo esc_attr( $id ); ?>-service" name="vob_service">
								<option value="">Not sure / multiple</option>
								<?php foreach ( vob_service_groups() as $group ) : ?>
									<option value="<?php echo esc_attr( $group->post_title ); ?>" <?php selected( $group->post_name, $atts['service'] ?: ( 'maintenance' === $type ? 'service-maintenance' : '' ) ); ?>><?php echo esc_html( $group->post_title ); ?></option>
								<?php endforeach; ?>
							</select></p>
					<?php endif; ?>

					<?php if ( 'maintenance' === $type ) : ?>
						<p class="field field--full"><label for="<?php echo esc_attr( $id ); ?>-facility">Facility type and equipment</label>
							<input id="<?php echo esc_attr( $id ); ?>-facility" name="vob_facility" placeholder="e.g. food processing plant: 2 steam boilers, RO system, cooling tower"></p>
					<?php endif; ?>

					<p class="field field--full"><label for="<?php echo esc_attr( $id ); ?>-message"><?php echo 'contact' === $type ? 'Message' : 'Project details'; ?> <span class="req">*</span></label>
						<textarea id="<?php echo esc_attr( $id ); ?>-message" name="vob_message" rows="5" required></textarea></p>

					<p class="field field--full"><button type="submit" class="btn btn--primary"><?php echo esc_html( $conf['button'] ); ?></button></p>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
);

add_action( 'admin_post_nopriv_vob_form', 'vob_handle_form' );
add_action( 'admin_post_vob_form', 'vob_handle_form' );

function vob_handle_form() {
	$type   = sanitize_key( $_POST['vob_type'] ?? 'quote' );
	$type   = array_key_exists( $type, vob_form_types() ) ? $type : 'quote';
	$return = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['vob_return'] ?? '' ) ), home_url( '/' ) );
	$back   = function ( $status ) use ( $type, $return ) {
		wp_safe_redirect( add_query_arg( array( 'vob_form' => $type, 'vob_status' => $status ), $return ) . '#vob-form-' . $type );
		exit;
	};

	// Bots: bad nonce, filled honeypot, or submitted in under 3 seconds. Pretend success.
	$nonce_ok = isset( $_POST['vob_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['vob_nonce'] ), 'vob_form' );
	$too_fast = time() - absint( $_POST['vob_ts'] ?? 0 ) < 3;
	if ( ! $nonce_ok || ! empty( $_POST['vob_website'] ) || $too_fast ) {
		$back( 'sent' );
	}

	$fields = array(
		'Name'     => sanitize_text_field( wp_unslash( $_POST['vob_name'] ?? '' ) ),
		'Company'  => sanitize_text_field( wp_unslash( $_POST['vob_company'] ?? '' ) ),
		'Email'    => sanitize_email( wp_unslash( $_POST['vob_email'] ?? '' ) ),
		'Phone'    => sanitize_text_field( wp_unslash( $_POST['vob_phone'] ?? '' ) ),
		'Service'  => wp_specialchars_decode( sanitize_text_field( wp_unslash( $_POST['vob_service'] ?? '' ) ) ),
		'Facility' => sanitize_text_field( wp_unslash( $_POST['vob_facility'] ?? '' ) ),
		'Message'  => sanitize_textarea_field( wp_unslash( $_POST['vob_message'] ?? '' ) ),
	);
	if ( ! $fields['Name'] || ! is_email( $fields['Email'] ) || ! $fields['Message'] ) {
		$back( 'error' );
	}

	$label = vob_form_types()[ $type ]['heading'];
	$body  = '';
	foreach ( array_filter( $fields ) as $k => $v ) {
		$body .= "$k: $v\n";
	}
	$body .= "\nSubmitted from: $return\n";

	wp_insert_post(
		array(
			'post_type'    => 'vob_lead',
			'post_status'  => 'private',
			'post_title'   => sprintf( '%s — %s%s', $label, $fields['Name'], $fields['Company'] ? " ({$fields['Company']})" : '' ),
			'post_content' => $body,
		)
	);

	wp_mail(
		vob_info( 'lead_email' ),
		sprintf( '[Website] %s from %s', $label, $fields['Name'] ),
		$body,
		array( 'Reply-To: ' . $fields['Name'] . ' <' . $fields['Email'] . '>' )
	);

	$back( 'sent' );
}
