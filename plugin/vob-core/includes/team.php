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
			// No headshot yet: show initials in the same square so the grid stays even.
			$photo = has_post_thumbnail( $member )
				? get_the_post_thumbnail( $member, 'medium', array( 'loading' => 'lazy', 'alt' => esc_attr( $member->post_title ) ) )
				: '<span class="team-card__initials" aria-hidden="true">' . esc_html( implode( '', array_map( fn( $word ) => mb_substr( $word, 0, 1 ), array_slice( preg_split( '/\s+/', trim( $member->post_title ) ), 0, 2 ) ) ) ) . '</span>';
			$role  = get_post_meta( $member->ID, 'vob_role', true );
			$out  .= sprintf(
				'<li class="team-card">%s<strong class="team-card__name">%s</strong>%s</li>',
				$photo,
				esc_html( $member->post_title ),
				$role ? '<span class="team-card__role">' . esc_html( $role ) . '</span>' : ''
			);
		}
		return $out . '</ul>';
	}
);
