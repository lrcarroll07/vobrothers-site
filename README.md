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

Pages show yellow "Placeholder" notes where these go:

1. Awards, certifications and safety recognitions (home page, Safety page).
2. Safety program details: the Safety page cards are drafted and must be confirmed.
3. Core values: drafted from the old site's wording; confirm or replace.
4. Client and employee testimonials (admin → Testimonials).
5. Company history, leadership, licenses (About).
6. Approved description of Quantum Industrial (About → Quantum Industrial).
7. Careers benefits and a team photo.
8. 4–8 high-resolution photos per project; longer descriptions for the 26 individual service pages.
9. Confirm the **24/7 emergency number**. It's currently the main line, 866-722-1267.
10. Confirm project locations marked only "Utah" (Xactware, Fresenius, Elkay, US Ski & Snowboard), and the full scope for the U of U Guest House (the old site's text was cut off).

## Going to production

1. Managed WordPress host (MySQL); install the theme and plugin zips; enter content (or adapt `bin/seed.php` to import it).
2. Set up SMTP email delivery (e.g. WP Mail SMTP with Microsoft 365/Google) so form emails reliably reach Sales@QuantumIndustrial.com.
3. Add an SEO plugin (Rank Math or Yoast) for titles/descriptions and the XML sitemap, plus caching, security and backups.
4. Optional: Cloudflare Turnstile on forms; swap to Gravity Forms if the team wants a form builder.
5. Launch checklist: point vomech.com DNS, confirm SSL, submit the sitemap in Search Console, match the address on the Google Business Profile exactly (1563 S. 1100 W., Ogden, UT 84404).
