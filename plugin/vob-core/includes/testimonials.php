<?php
/**
 * [vob_testimonials audience="client|employee"]: quotes from the Testimonials
 * admin screen. Shows a clearly marked placeholder until real quotes are added.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'vob_testimonials',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'audience' => 'client' ), $atts );
		$posts = get_posts(
			array(
				'post_type'   => 'vob_testimonial',
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'meta_key'    => 'vob_audience',
				'meta_value'  => $atts['audience'],
			)
		);

		ob_start();
		echo '<div class="quote-grid">';
		if ( ! $posts ) {
			for ( $i = 0; $i < 3; $i++ ) {
				echo '<figure class="quote placeholder-box"><blockquote><p>' .
					( 'employee' === $atts['audience'] ? 'Quote from a VO Brothers team member about training, culture and growth.' : 'Quote from a general contractor, facility manager or owner about working with VO Brothers.' ) .
					'</p></blockquote><figcaption><strong>Name</strong><span>' . ( 'employee' === $atts['audience'] ? 'Role' : 'Title, Company' ) . '</span></figcaption></figure>';
			}
			echo '</div><p class="placeholder-note">Placeholder: add real quotes under Testimonials in the WordPress admin.</p>';
			return ob_get_clean();
		}
		foreach ( $posts as $post ) {
			printf(
				'<figure class="quote"><blockquote>%s</blockquote><figcaption><strong>%s</strong><span>%s</span></figcaption></figure>',
				wp_kses_post( wpautop( $post->post_content ) ),
				esc_html( get_post_meta( $post->ID, 'vob_person', true ) ),
				esc_html( get_post_meta( $post->ID, 'vob_role', true ) )
			);
		}
		echo '</div>';
		return ob_get_clean();
	}
);
