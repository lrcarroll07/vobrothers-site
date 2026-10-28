<?php
/**
 * Preview mode for a hidden staging copy of the site.
 *
 * - Settings → Reading → "Discourage search engines" (blog_public = 0) makes WordPress
 *   add noindex meta tags; we also send an X-Robots-Tag header so images and other
 *   files stay out of search results too.
 * - Settings → VO Brothers → "Preview password": when set, visitors who aren't logged
 *   in must enter it once (remembered for 30 days). Leave blank to make the site public.
 */

defined( 'ABSPATH' ) || exit;

const VOB_PREVIEW_COOKIE = 'vob_preview';

add_action(
	'send_headers',
	function () {
		if ( '0' === (string) get_option( 'blog_public' ) ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}
);

// The REST API bypasses template_redirect, so close it to logged-out visitors too.
add_filter(
	'rest_authentication_errors',
	function ( $result ) {
		if ( null === $result && '' !== vob_info( 'preview_password' ) && ! is_user_logged_in() ) {
			return new WP_Error( 'vob_preview', 'This site is a private preview.', array( 'status' => 401 ) );
		}
		return $result;
	}
);

function vob_preview_token( $password ) {
	return hash_hmac( 'sha256', $password, wp_salt( 'auth' ) );
}

add_action(
	'template_redirect',
	function () {
		$password = vob_info( 'preview_password' );
		// robots.txt stays public: crawlers that can't fetch it may treat the site as unrestricted.
		if ( '' === $password || is_user_logged_in() || is_robots() ) {
			return;
		}

		$token = vob_preview_token( $password );
		if ( isset( $_COOKIE[ VOB_PREVIEW_COOKIE ] ) && hash_equals( $token, (string) $_COOKIE[ VOB_PREVIEW_COOKIE ] ) ) {
			return;
		}

		$error = false;
		if ( isset( $_POST['vob_preview_password'] ) ) {
			if ( hash_equals( $password, (string) wp_unslash( $_POST['vob_preview_password'] ) ) ) {
				setcookie( VOB_PREVIEW_COOKIE, $token, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
				wp_safe_redirect( home_url( add_query_arg( array() ) ) );
				exit;
			}
			$error = true;
		}

		status_header( 401 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
		vob_render_preview_gate( $error );
		exit;
	},
	0
);

function vob_render_preview_gate( $error ) {
	$logo = get_theme_file_uri( 'assets/img/logo-mark.png' );
	?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Preview — <?php echo esc_html( vob_info( 'company_name' ) ); ?></title>
	<style>
		body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #111316; color: #cfd3d8; font: 16px/1.5 system-ui, sans-serif; padding: 16px; box-sizing: border-box; }
		form { width: 100%; max-width: 360px; background: #1c1f23; border-top: 4px solid #c1272d; padding: 32px; border-radius: 4px; text-align: center; }
		img { height: 96px; margin: 0 auto 16px; display: block; }
		h1 { color: #fff; font-size: 20px; margin: 0 0 4px; }
		p { margin: 0 0 20px; font-size: 14px; }
		input { width: 100%; box-sizing: border-box; padding: 12px; border-radius: 4px; border: 1px solid #3a3f46; background: #111316; color: #fff; font-size: 16px; margin-bottom: 12px; }
		button { width: 100%; padding: 12px; border: 0; border-radius: 4px; background: #c1272d; color: #fff; font-weight: 600; font-size: 16px; cursor: pointer; }
		.error { color: #ff8a8e; }
	</style>
</head>
<body>
	<form method="post">
		<img src="<?php echo esc_url( $logo ); ?>" alt="">
		<h1>Website preview</h1>
		<p>This site is a private preview. Enter the password to continue.</p>
		<?php if ( $error ) : ?>
			<p class="error" role="alert">That password isn't right. Please try again.</p>
		<?php endif; ?>
		<label for="vob-preview-password" style="position:absolute;left:-9999px">Password</label>
		<input id="vob-preview-password" type="password" name="vob_preview_password" autocomplete="current-password" required autofocus>
		<button type="submit">View preview</button>
	</form>
</body>
</html>
	<?php
}
