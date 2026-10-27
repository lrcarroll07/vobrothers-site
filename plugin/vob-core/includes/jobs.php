<?php
/**
 * Live job openings from the Rippling job board.
 *
 * Rippling publishes a public JSON feed for each board. We fetch it server-side,
 * cache it for an hour, and keep the last good copy so the Careers page never
 * goes blank if Rippling is slow or down. Applications always happen on Rippling.
 *
 * Usage: [vob_jobs] in any page, or vob_jobs() / vob_jobs_board_url() in templates.
 */

defined( 'ABSPATH' ) || exit;

const VOB_JOBS_TTL = HOUR_IN_SECONDS;

function vob_jobs_board_url() {
	return 'https://ats.rippling.com/' . rawurlencode( vob_info( 'rippling_board' ) ) . '/jobs';
}

function vob_jobs_feed_url() {
	return 'https://api.rippling.com/platform/api/ats/v1/board/' . rawurlencode( vob_info( 'rippling_board' ) ) . '/jobs';
}

/**
 * Open jobs as [ ['title','department','location','url'], ... ], or null if the
 * feed has never been reachable.
 */
function vob_jobs() {
	$cached = get_transient( 'vob_jobs' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$response = wp_remote_get( vob_jobs_feed_url(), array( 'timeout' => 8 ) );
	$data     = is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response )
		? null
		: json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) ) {
		// Feed unavailable: serve the last good copy and retry in 5 minutes.
		$last = get_option( 'vob_jobs_last_good', null );
		if ( is_array( $last ) ) {
			set_transient( 'vob_jobs', $last, 5 * MINUTE_IN_SECONDS );
		}
		return $last;
	}

	$jobs = array();
	foreach ( $data as $item ) {
		if ( empty( $item['name'] ) || empty( $item['url'] ) ) {
			continue;
		}
		$jobs[] = array(
			'title'      => sanitize_text_field( $item['name'] ),
			'department' => sanitize_text_field( $item['department']['label'] ?? 'General' ),
			'location'   => sanitize_text_field( $item['workLocation']['label'] ?? '' ),
			'url'        => esc_url_raw( $item['url'] ),
		);
	}
	usort( $jobs, fn( $a, $b ) => array( $a['department'], $a['title'] ) <=> array( $b['department'], $b['title'] ) );

	set_transient( 'vob_jobs', $jobs, VOB_JOBS_TTL );
	update_option( 'vob_jobs_last_good', $jobs, false );
	return $jobs;
}

add_shortcode(
	'vob_jobs',
	function () {
		$jobs  = vob_jobs();
		$board = vob_jobs_board_url();

		ob_start();
		if ( null === $jobs ) {
			?>
			<div class="vob-jobs vob-jobs--fallback">
				<p>See our current openings and apply online through our careers portal.</p>
				<p><a class="btn btn--primary" href="<?php echo esc_url( $board ); ?>" target="_blank" rel="noopener">View all openings</a></p>
			</div>
			<?php
			return ob_get_clean();
		}

		if ( ! $jobs ) {
			?>
			<div class="vob-jobs vob-jobs--empty">
				<p>We don't have any open positions posted right now, but we're always glad to meet skilled tradespeople.</p>
				<p><a class="btn btn--primary" href="<?php echo esc_url( $board ); ?>" target="_blank" rel="noopener">Visit our careers portal</a></p>
			</div>
			<?php
			return ob_get_clean();
		}

		$departments = array_values( array_unique( wp_list_pluck( $jobs, 'department' ) ) );
		?>
		<div class="vob-jobs" data-vob-filter>
			<div class="filter-bar" role="toolbar" aria-label="Filter jobs by department">
				<button type="button" class="chip is-active" data-filter="*" aria-pressed="true">All departments <span class="chip__count"><?php echo count( $jobs ); ?></span></button>
				<?php foreach ( $departments as $dept ) : ?>
					<button type="button" class="chip" data-filter="<?php echo esc_attr( sanitize_title( $dept ) ); ?>" aria-pressed="false"><?php echo esc_html( $dept ); ?></button>
				<?php endforeach; ?>
			</div>
			<ul class="job-list">
				<?php foreach ( $jobs as $job ) : ?>
					<li class="job" data-groups="<?php echo esc_attr( sanitize_title( $job['department'] ) ); ?>">
						<div class="job__main">
							<h3 class="job__title"><a href="<?php echo esc_url( $job['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $job['title'] ); ?></a></h3>
							<p class="job__meta">
								<span><?php echo esc_html( $job['department'] ); ?></span>
								<?php if ( $job['location'] ) : ?>
									<span><?php echo esc_html( $job['location'] ); ?></span>
								<?php endif; ?>
							</p>
						</div>
						<a class="btn btn--primary btn--sm" href="<?php echo esc_url( $job['url'] ); ?>" target="_blank" rel="noopener">View &amp; apply<span class="screen-reader-text"> for <?php echo esc_html( $job['title'] ); ?> (opens Rippling)</span></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="vob-jobs__source">Applications are handled securely through Rippling. <a href="<?php echo esc_url( $board ); ?>" target="_blank" rel="noopener">View the full job board</a>.</p>
		</div>
		<?php
		return ob_get_clean();
	}
);
