#!/usr/bin/env bash
# Deploys the site to a WordPress host over SSH (the host needs WP-CLI, which most managed hosts include).
#
#   bin/deploy.sh check    Test the connection and report PHP / WordPress / WP-CLI on the host.
#   bin/deploy.sh setup    First deploy to a FRESH WordPress install: theme, plugin, all content,
#                          then hidden-preview mode (no search indexing + password gate).
#   bin/deploy.sh code     Later updates: replace the theme and plugin only. Content is untouched.
#
# Settings come from environment variables (store them as environment secrets, never in this repo):
#   VOB_SSH_HOST, VOB_SSH_USER            required
#   VOB_SSH_KEY  or  VOB_SSH_PASSWORD     one of them (private key text, or password; password needs sshpass)
#   VOB_SSH_PORT                          default 22
#   VOB_WP_PATH                           WordPress folder on the host; auto-detected if not set
#   VOB_PREVIEW_PASSWORD                  password visitors enter on the preview site (setup only)
set -euo pipefail
cd "$(dirname "$0")/.."

MODE="${1:-check}"
: "${VOB_SSH_HOST:?Set VOB_SSH_HOST}"
: "${VOB_SSH_USER:?Set VOB_SSH_USER}"
PORT="${VOB_SSH_PORT:-22}"

SSH_OPTS=(-p "$PORT" -o StrictHostKeyChecking=accept-new -o ServerAliveInterval=30)
KEYFILE=""
cleanup() { [ -n "$KEYFILE" ] && rm -f "$KEYFILE"; }
trap cleanup EXIT

if [ -n "${VOB_SSH_KEY:-}" ]; then
	KEYFILE="$(mktemp)"
	chmod 600 "$KEYFILE"
	printf '%s\n' "$VOB_SSH_KEY" > "$KEYFILE"
	SSH_OPTS+=(-i "$KEYFILE" -o IdentitiesOnly=yes)
	SSH=(ssh "${SSH_OPTS[@]}")
elif [ -n "${VOB_SSH_PASSWORD:-}" ]; then
	command -v sshpass >/dev/null || { echo "VOB_SSH_PASSWORD needs sshpass installed" >&2; exit 1; }
	export SSHPASS="$VOB_SSH_PASSWORD"
	SSH=(sshpass -e ssh "${SSH_OPTS[@]}")
else
	echo "Set VOB_SSH_KEY or VOB_SSH_PASSWORD" >&2
	exit 1
fi

remote() { "${SSH[@]}" "$VOB_SSH_USER@$VOB_SSH_HOST" "$@"; }

# Find the WordPress folder: VOB_WP_PATH, or the first common location containing wp-config.php.
WP_PATH="${VOB_WP_PATH:-$(remote 'for d in ~/public_html ~/www ~/htdocs ~/public ~/www/*/public_html ~/applications/*/public_html ~/sites/*; do [ -f "$d/wp-config.php" ] && { echo "$d"; break; }; done' || true)}"
[ -n "$WP_PATH" ] || { echo "Couldn't find WordPress on the host. Set VOB_WP_PATH." >&2; exit 1; }
WP="wp --path=$WP_PATH --skip-plugins --skip-themes"
WPFULL="wp --path=$WP_PATH"

# Upload a local folder to <wp-content>/<target>, swapping it in only after it fully arrives.
upload_dir() {
	local src="$1" target="$2" name
	name="$(basename "$target")"
	tar -C "$(dirname "$src")" -czf - "$(basename "$src")" | remote "set -e
		cd '$WP_PATH/wp-content/$(dirname "$target")'
		rm -rf '.$name.new' && mkdir '.$name.new'
		tar -xzf - -C '.$name.new'
		rm -rf '$name' && mv '.$name.new/$(basename "$src")' '$name' && rm -rf '.$name.new'"
}

case "$MODE" in
check)
	remote "set -e
		echo \"WordPress folder: $WP_PATH\"
		php -r 'echo \"PHP \", PHP_VERSION, PHP_EOL;'
		command -v wp >/dev/null && wp --version || echo 'WP-CLI: NOT FOUND (ask the host to enable it)'
		$WP core version --extra 2>/dev/null | head -1 || true
		echo \"Site URL: \$($WP option get siteurl)\"
		echo \"Posts/pages: \$($WP post list --post_type=post,page --format=count)\""
	;;

setup)
	: "${VOB_PREVIEW_PASSWORD:?Set VOB_PREVIEW_PASSWORD (the password visitors will enter)}"
	echo "Uploading theme and plugin to $WP_PATH…"
	upload_dir theme/vobrothers themes/vobrothers
	upload_dir plugin/vob-core plugins/vob-core

	echo "Uploading content…"
	remote "rm -rf ~/vob-deploy && mkdir -p ~/vob-deploy/bin"
	tar -czf - content bin/seed.php | remote "tar -xzf - -C ~/vob-deploy"

	echo "Loading content…"
	# Fresh install, so vob_info holds nothing else yet; quote the JSON for the remote shell.
	settings_json="$(php -r 'echo json_encode( array( "preview_password" => getenv( "VOB_PREVIEW_PASSWORD" ) ) );')"
	remote "set -e
		$WP option update blog_public 0
		$WPFULL plugin activate vob-core
		$WPFULL eval-file ~/vob-deploy/bin/seed.php
		$WP option update vob_info $(printf '%q' "$settings_json") --format=json
		$WPFULL rewrite flush --hard
		# Apache hosts need WordPress's rewrite rules for clean URLs; harmless on nginx.
		[ -f '$WP_PATH/.htaccess' ] || printf '%s\\n' '# BEGIN WordPress' '<IfModule mod_rewrite.c>' 'RewriteEngine On' 'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]' 'RewriteBase /' 'RewriteRule ^index\\.php\$ - [L]' 'RewriteCond %{REQUEST_FILENAME} !-f' 'RewriteCond %{REQUEST_FILENAME} !-d' 'RewriteRule . /index.php [L]' '</IfModule>' '# END WordPress' > '$WP_PATH/.htaccess'
		$WPFULL cache flush || true
		rm -rf ~/vob-deploy"
	echo "Done: $(remote "$WP option get home") (hidden preview, password protected)"
	;;

code)
	echo "Updating theme and plugin in $WP_PATH…"
	upload_dir theme/vobrothers themes/vobrothers
	upload_dir plugin/vob-core plugins/vob-core
	remote "$WPFULL rewrite flush --hard; $WPFULL cache flush || true"
	echo "Done."
	;;

*)
	echo "Usage: bin/deploy.sh check|setup|code" >&2
	exit 1
	;;
esac
