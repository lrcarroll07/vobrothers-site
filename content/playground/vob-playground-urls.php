<?php
/**
 * Playground only (added by bin/playground-bundle.sh, never deployed to a real host).
 *
 * The bundle's database was built at http://localhost:8080. On first load inside
 * WordPress Playground, rewrite those stored URLs (menus, page content, image
 * links) to Playground's own address. Serialized values are decoded and
 * re-encoded so their lengths stay valid.
 */

const VOB_BUNDLE_URL = 'http://localhost:8080';

add_action(
	'init',
	function () {
		$new = untrailingslashit( home_url() );
		if ( get_option( 'vob_bundle_url_fixed' ) === $new || VOB_BUNDLE_URL === $new ) {
			return;
		}
		global $wpdb;
		$map = array(
			VOB_BUNDLE_URL                        => $new,
			str_replace( '/', '\/', VOB_BUNDLE_URL ) => str_replace( '/', '\/', $new ),
		);
		$replace = function ( $value ) use ( &$replace, $map ) {
			if ( is_array( $value ) ) {
				return array_map( $replace, $value );
			}
			if ( ! is_string( $value ) ) {
				return $value;
			}
			if ( is_serialized( $value ) && ! preg_match( '/(?:^|[;{}])[OC]:\d+:/', $value ) ) {
				$decoded = @unserialize( $value ); // phpcs:ignore
				if ( false !== $decoded || 'b:0;' === $value ) {
					return serialize( $replace( $decoded ) ); // phpcs:ignore
				}
			}
			return strtr( $value, $map );
		};
		$tables = array(
			array( $wpdb->posts, 'ID', array( 'post_content', 'post_excerpt', 'guid' ) ),
			array( $wpdb->postmeta, 'meta_id', array( 'meta_value' ) ),
			array( $wpdb->options, 'option_id', array( 'option_value' ) ),
		);
		foreach ( $tables as list( $table, $id, $columns ) ) {
			foreach ( $columns as $column ) {
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT `$id`, `$column` FROM `$table` WHERE `$column` LIKE %s", '%' . $wpdb->esc_like( 'localhost:8080' ) . '%' ), ARRAY_A ); // phpcs:ignore
				foreach ( $rows as $row ) {
					$value = $replace( $row[ $column ] );
					if ( $value !== $row[ $column ] ) {
						$wpdb->update( $table, array( $column => $value ), array( $id => $row[ $id ] ) );
					}
				}
			}
		}
		wp_cache_flush();
		update_option( 'vob_bundle_url_fixed', $new );
	},
	1
);
