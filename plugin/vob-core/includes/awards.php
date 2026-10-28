<?php
/**
 * Awards, managed under Awards in the admin.
 *
 * vob_awards( $kind ) returns them newest first; [vob_awards kind="safety|project"
 * layout="timeline|list"] renders them. "Kind" separates safety awards (ABC STEP,
 * Utah Labor Commission) from project awards (Excellence in Construction, UC+D).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Awards as arrays, newest year first (then menu order).
 */
function vob_awards( $kind = '' ) {
	$args = array(
		'post_type'   => 'vob_award',
		'numberposts' => -1,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
	);
	if ( $kind ) {
		$args['meta_key']   = 'vob_award_kind';
		$args['meta_value'] = $kind;
	}
	$awards = array_map(
		fn( $post ) => array(
			'name'    => get_the_title( $post ),
			'project' => get_post_meta( $post->ID, 'vob_award_project', true ),
			'issuer'  => get_post_meta( $post->ID, 'vob_award_issuer', true ),
			'year'    => get_post_meta( $post->ID, 'vob_award_year', true ),
			'kind'    => get_post_meta( $post->ID, 'vob_award_kind', true ),
		),
		get_posts( $args )
	);
	usort( $awards, fn( $a, $b ) => (int) $b['year'] <=> (int) $a['year'] );
	return $awards;
}

/**
 * One award as a card.
 */
function vob_award_card( $award ) {
	return sprintf(
		'<li class="award-item award-item--%s"><span class="award-item__icon" aria-hidden="true">%s</span><div><strong class="award-item__name">%s</strong>%s<span class="award-item__issuer">%s</span></div></li>',
		esc_attr( $award['kind'] ?: 'project' ),
		function_exists( 'vob_icon' ) ? vob_icon( 'safety' === $award['kind'] ? 'shield' : 'award' ) : '',
		esc_html( $award['name'] ),
		$award['project'] ? '<span class="award-item__project">' . esc_html( $award['project'] ) . '</span>' : '',
		esc_html( $award['issuer'] )
	);
}

add_shortcode(
	'vob_awards',
	function ( $atts ) {
		$atts   = shortcode_atts( array( 'kind' => '', 'layout' => 'timeline' ), $atts );
		$awards = vob_awards( $atts['kind'] );
		if ( ! $awards ) {
			return '';
		}

		if ( 'list' === $atts['layout'] ) {
			$out = '<ul class="award-list">';
			foreach ( $awards as $award ) {
				$out .= str_replace( '<div>', '<div><span class="award-item__year">' . esc_html( $award['year'] ) . '</span>', vob_award_card( $award ) );
			}
			return $out . '</ul>';
		}

		$out = '<div class="award-timeline">';
		foreach ( array_unique( array_column( $awards, 'year' ) ) as $year ) {
			$out .= '<section class="award-year"><h3 class="award-year__label">' . esc_html( $year ) . '</h3><ul class="award-list">';
			foreach ( $awards as $award ) {
				if ( $award['year'] === $year ) {
					$out .= vob_award_card( $award );
				}
			}
			$out .= '</ul></section>';
		}
		return $out . '</div>';
	}
);

/**
 * [vob_award_stats]: totals for the Awards page header.
 */
add_shortcode(
	'vob_award_stats',
	function () {
		$all    = vob_awards();
		$years  = array_filter( array_map( 'intval', array_column( $all, 'year' ) ) );
		$safety = count( array_filter( $all, fn( $a ) => 'safety' === $a['kind'] ) );
		return sprintf(
			'<div class="award-stats"><div><strong>%d</strong><span>Industry awards since %d</span></div><div><strong>%d</strong><span>Safety awards from ABC, the Utah Labor Commission and ENR Mountain States</span></div><div><strong>%d</strong><span>Excellence and project awards</span></div></div>',
			count( $all ),
			$years ? min( $years ) : (int) wp_date( 'Y' ),
			$safety,
			count( $all ) - $safety
		);
	}
);
