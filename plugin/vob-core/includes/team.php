<?php
/**
 * [vob_team group="utah|quantum"]: leadership cards from the Team admin screen,
 * in menu order, with headshot, name and job title.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'vob_team',
	function ( $atts ) {
		$atts    = shortcode_atts( array( 'group' => 'utah' ), $atts );
		$members = get_posts(
			array(
				'post_type'   => 'vob_team',
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'meta_key'    => 'vob_group',
				'meta_value'  => $atts['group'],
			)
		);
		if ( ! $members ) {
			return '';
		}
		$out = '<ul class="team-grid">';
		foreach ( $members as $member ) {
			$out .= sprintf(
				'<li class="team-card">%s<strong class="team-card__name">%s</strong><span class="team-card__role">%s</span></li>',
				get_the_post_thumbnail( $member, 'medium', array( 'loading' => 'lazy', 'alt' => esc_attr( $member->post_title ) ) ),
				esc_html( $member->post_title ),
				esc_html( get_post_meta( $member->ID, 'vob_role', true ) )
			);
		}
		return $out . '</ul>';
	}
);
