<?php
/**
 * Company information (address, phones, email, social links) editable under
 * Settings → VO Brothers, and read everywhere through vob_info().
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions: key => [label, default].
 */
function vob_info_fields() {
	return array(
		'company_name'    => array( 'Company name', 'VO Brothers Mechanical' ),
		'legal_name'      => array( 'Legal name', 'VO Brothers Mechanical LLC' ),
		'parent_name'     => array( 'Parent company', 'Quantum Industrial' ),
		'parent_url'      => array( 'Parent company URL', 'https://quantumindustrial.com' ),
		'street'          => array( 'Street address', '1563 S. 1100 W.' ),
		'city'            => array( 'City', 'Ogden' ),
		'state'           => array( 'State', 'UT' ),
		'zip'             => array( 'ZIP', '84404' ),
		'phone'           => array( 'Main phone', '866-722-1267' ),
		'emergency_phone' => array( '24/7 emergency phone', '866-722-1267' ),
		'email'           => array( 'Sales email', 'Sales@QuantumIndustrial.com' ),
		'lead_email'      => array( 'Send form submissions to', 'Sales@QuantumIndustrial.com' ),
		'rippling_board'  => array( 'Rippling job board slug', 'vo-brothers-mechanical' ),
		'linkedin'        => array( 'LinkedIn URL', 'https://www.linkedin.com/in/vo-brothers-mechanical-llc-b55196180/' ),
		'facebook'        => array( 'Facebook URL', 'https://www.facebook.com/VO-Brothers-Mechanical-102961519742192/' ),
		'instagram'       => array( 'Instagram URL', 'https://www.instagram.com/vomech18/' ),
		'x'               => array( 'X (Twitter) URL', 'https://x.com/vomechanical' ),
	);
}

/**
 * Read one company setting, falling back to its default.
 */
function vob_info( $key ) {
	$fields = vob_info_fields();
	$saved  = get_option( 'vob_info', array() );
	if ( isset( $saved[ $key ] ) && '' !== $saved[ $key ] ) {
		return $saved[ $key ];
	}
	return isset( $fields[ $key ] ) ? $fields[ $key ][1] : '';
}

/**
 * "tel:" href for a display phone number.
 */
function vob_tel( $phone ) {
	return 'tel:+1' . preg_replace( '/\D/', '', $phone );
}

/**
 * One-line postal address.
 */
function vob_address_line() {
	return sprintf( '%s, %s, %s %s', vob_info( 'street' ), vob_info( 'city' ), vob_info( 'state' ), vob_info( 'zip' ) );
}

add_action(
	'admin_menu',
	function () {
		add_options_page( 'VO Brothers', 'VO Brothers', 'manage_options', 'vob-info', 'vob_render_settings_page' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'vob_info',
			'vob_info',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $input ) {
					$clean = array();
					foreach ( vob_info_fields() as $key => $field ) {
						$value         = isset( $input[ $key ] ) ? trim( wp_unslash( $input[ $key ] ) ) : '';
						$clean[ $key ] = str_ends_with( $key, 'url' ) || in_array( $key, array( 'linkedin', 'facebook', 'instagram', 'x' ), true )
							? esc_url_raw( $value )
							: sanitize_text_field( $value );
					}
					delete_transient( 'vob_jobs' );
					return $clean;
				},
			)
		);
	}
);

function vob_render_settings_page() {
	?>
	<div class="wrap">
		<h1>VO Brothers company information</h1>
		<p>Used in the header, footer, contact page, forms and Google business markup.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'vob_info' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( vob_info_fields() as $key => $field ) : ?>
					<tr>
						<th scope="row"><label for="vob_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
						<td><input class="regular-text" id="vob_<?php echo esc_attr( $key ); ?>" name="vob_info[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( vob_info( $key ) ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
