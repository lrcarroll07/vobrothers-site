#!/usr/bin/env bash
# Builds a local copy of the site from scratch:
#   WordPress + SQLite (no database server), the VO Brothers theme and plugin, seeded content.
# Usage: bin/setup.sh            then: bin/serve.sh  → http://localhost:8080
set -euo pipefail
cd "$(dirname "$0")/.."
ROOT="$PWD"

if [ ! -f wp/wp-load.php ]; then
	echo "Downloading WordPress…"
	tmp="$(mktemp -d)"
	curl -fsSL -o "$tmp/wp.zip" https://wordpress.org/latest.zip
	unzip -q "$tmp/wp.zip" -d "$tmp" && mv "$tmp/wordpress" wp
	curl -fsSL -o "$tmp/sqlite.zip" https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip
	unzip -q "$tmp/sqlite.zip" -d wp/wp-content/plugins/
	rm -rf "$tmp"
fi

cp bin/wp-config.php wp/wp-config.php
sed -e "s#'{SQLITE_IMPLEMENTATION_FOLDER_PATH}'#__DIR__ . '/plugins/sqlite-database-integration'#" \
    -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" \
    wp/wp-content/plugins/sqlite-database-integration/db.copy > wp/wp-content/db.php

ln -sfn "$ROOT/theme/vobrothers" wp/wp-content/themes/vobrothers
ln -sfn "$ROOT/plugin/vob-core" wp/wp-content/plugins/vob-core

rm -rf wp/wp-content/database wp/wp-content/uploads
mkdir -p wp/wp-content/database

php bin/install.php
php bin/seed.php
echo "Done. Start the site with: bin/serve.sh"
