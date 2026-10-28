# VO Brothers Mechanical — WordPress site (prototype)

New website for VO Brothers Mechanical, a Quantum Industrial company, replacing the Wix site at vomech.com.
Feature set modeled on archermechanicalinc.com.

## What's here

| Folder | What it is |
|---|---|
| `theme/vobrothers/` | The WordPress theme (design, page templates, menu, CSS/JS). |
| `plugin/vob-core/` | The VO Brothers Core plugin: projects, services, testimonials, leads, company settings, Rippling jobs feed, forms, Google business markup, old-URL redirects. |
| `content/` | Starting content from vomech.com (`seed-data.php`) and its images. |
| `bin/` | Scripts to build and run a local copy. |

Content types and settings live in the plugin, so they survive a future theme change.

## Run it locally

Needs PHP 8.0+ with the `pdo_sqlite` extension. No database server: the local copy uses SQLite.

```bash
bin/setup.sh     # downloads WordPress, installs theme + plugin, loads all content (resets the local site)
bin/serve.sh     # http://localhost:8080   admin: http://localhost:8080/wp-admin  (admin / vobrothers-local)
node bin/screenshots.js   # optional: desktop + mobile screenshots into screenshots/ (needs Playwright)
```

## Features

- **Home:** full-width hero, 24/7 emergency bar, proof points, featured-projects carousel, service groups, industries, service & maintenance, awards (placeholder), live job openings, Quantum Industrial tie-in.
- **Services:** 8 service groups with 26 individual service pages. Each group page has a quote form pre-set to that service; Service & Maintenance uses a maintenance-plan form and the emergency call-out.
- **Projects:** 18 projects, filterable by type and area, each with a facts panel and related projects. Type/area URLs also work (`/projects/type/higher-education/`).
- **Careers:** live openings pulled from Rippling (`ats.rippling.com/vo-brothers-mechanical/jobs`), filterable by department; "Apply" goes to Rippling. Cached for an hour; if Rippling is unreachable, the last good list (or a link to the board) is shown.
- **Forms:** quote, maintenance-plan and contact forms. Every submission is saved under **Leads** in the admin *and* emailed to the address in **Settings → VO Brothers**. Spam protection: nonce, honeypot, minimum fill time.
- **Company settings** (Settings → VO Brothers): address, phones (main + 24/7), email, social links, Rippling board. Used across header, footer, forms and Google markup.
- **SEO:** LocalBusiness markup (Quantum Industrial as parent organization), breadcrumbs, clean URLs, 301 redirect from the old `/services-1`.
- Mobile-first, accessible navigation, no jQuery, no paid plugins.

## Content still needed from VO Brothers

Core values, safety, benefits, team testimonials, leadership and the fabrication facility now come from
quantumindustrial.com (Crawford/Arizona material left out, except the fabrication facility). The only
Utah-specific award there, ABC Utah Sub-Contractor of the Year 2020, is used. Still open (yellow
"Placeholder" notes mark most of them):

1. Client testimonials (admin → Testimonials, audience "client").
2. Founding year and milestones (About).
3. Safety stats (EMR, OSHA rate), if you want them published. Awards are managed under **Awards** in the admin (23 listed); add new ones there and every page updates.
4. 4–8 high-resolution photos per project; longer descriptions for the individual service pages.
5. Confirm the **24/7 emergency number**. It's currently the main line, 866-722-1267.
6. Confirm project locations marked only "Utah" (Xactware, Fresenius, Elkay, US Ski & Snowboard) so they can go on the map, and the full scope for the U of U Guest House.
7. Map pins use approximate campus/street coordinates (edit Latitude/Longitude on each project).
8. Project photos for the placeholders: Provo Wastewater, Davis Aqueduct Reach 1, Davis Tech Allied Health, Brigham City Community Hospital, FAA SLC ARTCC, USU Life Sciences and Thermo Fisher. Also Thermo Fisher's city (for the map).
9. Second location (1572 S. 1100 W.) is assumed to be Ogden, UT 84404 and labeled "Service location"; edit under Settings → VO Brothers.

## Project map

The Projects page shows every project with coordinates on an interactive map (Leaflet, bundled in the
theme) over USGS shaded relief, which is public domain and needs no API key. The map follows the type
and area filters; overlapping pins merge into a numbered bubble that zooms in, then lists its projects.

## Free preview in WordPress Playground

`bin/playground-bundle.sh` (after `bin/setup.sh`) builds `dist/vobrothers-playground.zip`.
To view it: open https://playground.wordpress.net → **New** → **Import zip** → choose the file.
The whole site runs in that browser tab; nothing is public, and changes vanish when the tab closes.
Forms won't send email there. The bundle includes a Playground-only startup file
(`content/playground/vob-playground-urls.php`) that rewrites the stored local URLs on first load.

**Shareable link** (public repo only): `bin/publish-preview.sh` rebuilds the bundle, force-pushes it to
the `playground-preview` branch and prints the link:

https://playground.wordpress.net/?mode=seamless&blueprint-url=https://raw.githubusercontent.com/lrcarroll07/vobrothers-site/playground-preview/blueprint.json

Re-run it after content changes; the same link then shows the new version.

## Hidden preview site

`bin/deploy.sh` publishes to any WordPress host with SSH and WP-CLI. Credentials are read from
environment variables (`VOB_SSH_HOST`, `VOB_SSH_USER`, `VOB_SSH_KEY` or `VOB_SSH_PASSWORD`,
optional `VOB_SSH_PORT` / `VOB_WP_PATH`, and `VOB_PREVIEW_PASSWORD`). Never commit them.

```bash
bin/deploy.sh check   # test the connection; shows PHP, WordPress and WP-CLI versions on the host
bin/deploy.sh setup   # first time, on a FRESH WordPress install: theme, plugin, all content, preview mode
bin/deploy.sh code    # later: update theme + plugin only (content edited in wp-admin is untouched)
```

Preview mode:
- **Hidden from search:** "Discourage search engines" is on (noindex tags) and every response sends `X-Robots-Tag: noindex, nofollow`.
- **Password gate:** visitors enter the preview password once (remembered 30 days); logged-in editors skip it. The REST API is closed to logged-out visitors.
- **To launch for real:** clear the preview password (Settings → VO Brothers), untick "Discourage search engines" (Settings → Reading), and move the site to vomech.com.

`setup` refuses to run on a site that already has content, so it can't overwrite real work.

## Going to production

1. Managed WordPress host (MySQL); install the theme and plugin zips; enter content (or adapt `bin/seed.php` to import it).
2. Set up SMTP email delivery (e.g. WP Mail SMTP with Microsoft 365/Google) so form emails reliably reach Sales@QuantumIndustrial.com.
3. Add an SEO plugin (Rank Math or Yoast) for titles/descriptions and the XML sitemap, plus caching, security and backups.
4. Optional: Cloudflare Turnstile on forms; swap to Gravity Forms if the team wants a form builder.
5. Launch checklist: point vomech.com DNS, confirm SSL, submit the sitemap in Search Console, match the address on the Google Business Profile exactly (1563 S. 1100 W., Ogden, UT 84404).
