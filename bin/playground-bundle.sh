#!/usr/bin/env bash
# Packages the local site (from bin/setup.sh) as a WordPress Playground import:
#   dist/vobrothers-playground.zip
# Open https://playground.wordpress.net → Import (or drag the zip onto the page).
# Playground supplies its own WordPress core and SQLite driver; the zip carries the
# theme, plugin, uploads and database, plus a manifest so Playground rewrites URLs.
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f wp/wp-content/database/vobrothers.sqlite ] || { echo "Run bin/setup.sh first." >&2; exit 1; }

stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
mkdir -p "$stage/wp-content/themes" "$stage/wp-content/plugins" "$stage/wp-content/database"
cp -rL theme/vobrothers "$stage/wp-content/themes/vobrothers"
cp -rL plugin/vob-core "$stage/wp-content/plugins/vob-core"
cp -r wp/wp-content/uploads "$stage/wp-content/uploads"
# Rewrites the stored localhost URLs to Playground's address on first load.
mkdir -p "$stage/wp-content/mu-plugins"
cp content/playground/vob-playground-urls.php "$stage/wp-content/mu-plugins/"
# Copy the database consistently even if the local server is running.
php -r '$db = new SQLite3( $argv[1] ); $db->exec( "VACUUM INTO " . "\x27" . $argv[2] . "\x27" );' \
	wp/wp-content/database/vobrothers.sqlite "$stage/wp-content/database/.ht.sqlite"
printf '{"siteUrl":"http://localhost:8080","formatVersion":2}\n' > "$stage/playground-export.json"

mkdir -p dist
rm -f dist/vobrothers-playground.zip
( cd "$stage" && zip -qr "$OLDPWD/dist/vobrothers-playground.zip" . )
ls -lh dist/vobrothers-playground.zip
