#!/usr/bin/env python3
"""
Saves the running local site (bin/serve.sh) as plain HTML files for static hosting
such as GitHub Pages: no PHP or database needed, so the preview link loads instantly.

Each color scheme gets its own copy of the pages, and the Colors switch links between them:
    /            Original VO
    /slightly-quantum/
    /full-quantum/
Theme files and images are stored once and shared.

Forms can't send without WordPress, so on the static copy they show a notice instead.

Usage: python3 bin/static-export.py [out_dir] [base_path]
    base_path is where the site will live, e.g. /vobrothers-site for
    https://<user>.github.io/vobrothers-site/ (default), or "" for a domain root.
"""

import os
import re
import shutil
import subprocess
import sys
import tempfile

SRC = 'http://localhost:8080'
OUT = sys.argv[1] if len(sys.argv) > 1 else 'dist/static'
BASE = sys.argv[2] if len(sys.argv) > 2 else '/vobrothers-site'
SCHEMES = {'vo': '', 'family': '/slightly-quantum', 'quantum': '/full-quantum'}
EMAIL = 'Sales@QuantumIndustrial.com'


def crawl(palette, dest):
	"""Mirror the site with this color scheme selected (by cookie), skipping the switch links."""
	subprocess.run(
		[
			'wget', '--quiet', '-e', 'robots=off', '--mirror', '--page-requisites', '--no-host-directories',
			'--header', f'Cookie: vob_palette={palette}',
			'--reject-regex', r'(palette=|/wp-json|/feed/?$|/xmlrpc|/wp-admin|/wp-login|/comments/|\?p=|replytocom)',
			'--directory-prefix', dest, SRC + '/',
		],
		check=False,  # wget exits non-zero on any 404 (e.g. a missing srcset size); the site is still saved
	)


def strip_query_names(root):
	"""wget keeps ?ver=… in file names; the web server ignores the query, so drop it."""
	for folder, _, files in os.walk(root):
		for name in files:
			if '?' in name:
				clean = os.path.join(folder, name.split('?', 1)[0])
				if not os.path.exists(clean):
					os.rename(os.path.join(folder, name), clean)
				else:
					os.remove(os.path.join(folder, name))


def rewrite(html, page_path, prefix):
	"""Point links at the static copy: assets shared at BASE, pages within this scheme's copy."""
	for src, esc in ((SRC, False), (SRC.replace('/', '\\/'), True)):
		slash = '\\/' if esc else '/'
		base = BASE.replace('/', '\\/') if esc else BASE
		pre = prefix.replace('/', '\\/') if esc else prefix
		html = html.replace(src + slash + 'wp-content' + slash, base + slash + 'wp-content' + slash)
		html = html.replace(src + slash + 'wp-includes' + slash, base + slash + 'wp-includes' + slash)
		html = html.replace(src, base + pre)

	# Colors switch: same page in the chosen scheme's copy.
	def switch(m):
		return 'href="' + BASE + SCHEMES[m.group(1)] + '/' + page_path + '"'
	html = re.sub(r'href="[^"]*[?&](?:amp;|#038;)?palette=(vo|family|quantum)"', switch, html)

	# Forms post to WordPress, which the static copy doesn't have.
	notice = f"alert('This is a preview copy of the website, so forms are turned off. Please email {EMAIL}.');return false;"
	html = re.sub(r'<form method="post" action="[^"]*admin-post\.php"', f'<form method="post" action="#" onsubmit="{notice}"', html)
	return html


def main():
	work = tempfile.mkdtemp(prefix='vob-static-')
	try:
		if os.path.isdir(OUT):
			shutil.rmtree(OUT)
		os.makedirs(OUT)
		for palette, prefix in SCHEMES.items():
			dest = os.path.join(work, palette)
			crawl(palette, dest)
			strip_query_names(dest)
			for folder, _, files in os.walk(dest):
				rel_folder = os.path.relpath(folder, dest)
				is_asset = rel_folder.split(os.sep)[0] in ('wp-content', 'wp-includes')
				for name in files:
					src_file = os.path.join(folder, name)
					rel = os.path.normpath(os.path.join(rel_folder, name))
					if is_asset:
						if palette == 'vo':  # assets are identical in every scheme; keep one copy
							target = os.path.join(OUT, rel)
							os.makedirs(os.path.dirname(target), exist_ok=True)
							shutil.copy2(src_file, target)
						continue
					if not name.endswith('.html'):
						continue
					page_path = os.path.dirname(rel)
					page_path = '' if page_path == '.' else page_path + '/'
					with open(src_file, encoding='utf-8') as f:
						html = rewrite(f.read(), page_path, prefix)
					target = os.path.join(OUT, prefix.lstrip('/'), rel)
					os.makedirs(os.path.dirname(target), exist_ok=True)
					with open(target, 'w', encoding='utf-8') as f:
						f.write(html)

		# Not-found page (GitHub Pages serves /404.html), robots.txt, and no Jekyll processing.
		page = subprocess.run(['curl', '-s', '--cookie', 'vob_palette=vo', SRC + '/this-page-does-not-exist/'], capture_output=True, text=True).stdout
		with open(os.path.join(OUT, '404.html'), 'w', encoding='utf-8') as f:
			f.write(rewrite(page, '', ''))
		with open(os.path.join(OUT, 'robots.txt'), 'w') as f:
			f.write('User-agent: *\nDisallow: /\n')
		open(os.path.join(OUT, '.nojekyll'), 'w').close()
	finally:
		shutil.rmtree(work, ignore_errors=True)

	pages = sum(1 for _, _, files in os.walk(OUT) for n in files if n.endswith('.html'))
	print(f'Saved {pages} pages to {OUT}')


if __name__ == '__main__':
	main()
