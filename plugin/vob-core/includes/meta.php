<?php
/**
 * Custom fields for projects, services and testimonials, with simple meta boxes.
 * (Production can swap these boxes for ACF field groups using the same meta keys.)
 */

defined( 'ABSPATH' ) || exit;

/**
 * post type => [meta key => [label, input type]]
 */
function vob_meta_fields() {
	return array(
		'vob_project'     => array(
			'vob_location_label' => array( 'Location shown on the page (e.g. "Ogden, Utah")', 'text' ),
			'vob_size'           => array( 'Size / capacity (e.g. "154,000 sq ft", "4,900 tons")', 'text' ),
			'vob_client'         => array( 'Owner / client', 'text' ),
			'vob_scope'          => array( 'VO Brothers scope', 'textarea' ),
			'vob_status'         => array( 'Status (Completed / Ongoing)', 'text' ),
			'vob_featured'       => array( 'Feature on the home page carousel', 'checkbox' ),
			'vob_gallery'        => array( 'Gallery image IDs (comma separated)', 'text' ),
			'vob_lat'            => array( 'Map latitude (e.g. 41.1915)', 'text' ),
			'vob_lng'            => array( 'Map longitude (e.g. -111.9445)', 'text' ),
		),
		'vob_service'     => array(
			'vob_tagline'   => array( 'Short tagline shown on cards', 'text' ),
			'vob_icon'      => array( 'Icon name (water, steam, medical, pipe, weld, gauge, wrench, service)', 'text' ),
			'vob_bullets'   => array( 'Key points (one per line)', 'textarea' ),
			'vob_emergency' => array( 'Show the 24/7 emergency call-out on this page', 'checkbox' ),
		),
		'vob_team'        => array(
			'vob_role'  => array( 'Job title', 'text' ),
			'vob_group' => array( 'Show in (utah / quantum)', 'text' ),
		),
		'vob_testimonial' => array(
			'vob_person'   => array( 'Name', 'text' ),
			'vob_role'     => array( 'Role / company', 'text' ),
			'vob_audience' => array( 'Show on (client / employee)', 'text' ),
		),
	);
}

add_action(
	'init',
	function () {
		foreach ( vob_meta_fields() as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'         => 'string',
						'single'       => true,
						'show_in_rest' => true,
					)
				);
			}
		}
	}
);

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array_keys( vob_meta_fields() ) as $post_type ) {
			add_meta_box( 'vob_details', 'Details', 'vob_render_meta_box', $post_type, 'normal', 'high' );
		}
	}
);

function vob_render_meta_box( $post ) {
	wp_nonce_field( 'vob_meta', 'vob_meta_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( vob_meta_fields()[ $post->post_type ] as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th><td>';
		if ( 'textarea' === $field[1] ) {
			echo '<textarea class="large-text" rows="4" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'checkbox' === $field[1] ) {
			echo '<input type="checkbox" value="1" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" ' . checked( $value, '1', false ) . '>';
		} else {
			echo '<input class="regular-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( ! isset( $_POST['vob_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['vob_meta_nonce'] ), 'vob_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || empty( vob_meta_fields()[ $post->post_type ] ) ) {
			return;
		}
		foreach ( vob_meta_fields()[ $post->post_type ] as $key => $field ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$val = 'textarea' === $field[1] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
			update_post_meta( $post_id, $key, $val );
		}
	},
	10,
	2
);

/**
 * Lines of a textarea field as an array.
 */
function vob_meta_lines( $post_id, $key ) {
	return array_values( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $post_id, $key, true ) ) ) ) );
}

/**
 * Attachment IDs for a project gallery, always starting with the featured image.
 */
function vob_project_gallery( $post_id ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, 'vob_gallery', true ) ) ) );
	$featured = get_post_thumbnail_id( $post_id );
	if ( $featured ) {
		array_unshift( $ids, $featured );
	}
	return array_values( array_unique( $ids ) );
}
