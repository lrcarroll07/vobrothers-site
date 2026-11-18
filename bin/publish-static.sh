#!/usr/bin/env bash
# Publishes the static preview (bin/static-export.py) to GitHub Pages and prints its link.
#
# Saves every page from the running local site (start it with bin/serve.sh) and force-pushes
# the files as the single commit on the `gh-pages` branch. GitHub Pages serves that branch at
# https://<user>.github.io/<repo>/ (Settings → Pages → "Deploy from a branch" → gh-pages).
#
# Usage: bin/serve.sh &   then   bin/publish-static.sh
set -euo pipefail
cd "$(dirname "$0")/.."

BRANCH=gh-pages
REPO="$(git remote get-url origin | sed -E 's#.*github.com[:/]##; s#\.git$##')"
OWNER="${REPO%%/*}"
NAME="${REPO##*/}"

python3 bin/static-export.py dist/static "/$NAME"

stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
cp -a dist/static/. "$stage/"
git -C "$stage" init -q -b "$BRANCH"
git -C "$stage" add -A
git -C "$stage" -c user.name="$(git config user.name || echo preview)" -c user.email="$(git config user.email || echo preview@localhost)" \
	commit -qm "Static preview build from main@$(git rev-parse --short HEAD)"
git -C "$stage" push -q --force "$(git remote get-url origin)" "$BRANCH:$BRANCH"

echo "Preview link (live a minute or two after each publish):"
echo "https://$OWNER.github.io/$NAME/"
