# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

"Worldwide Handyman" — marketing site + admin panel for a GTA handyman business (sole proprietor: Sercan). Plain PHP 8 / MySQL / Bootstrap 5 (CDN), no framework, no composer, no build step. Runs on XAMPP.

- Frontend: `http://localhost/Worldwidehandyman/`
- Admin: `http://localhost/Worldwidehandyman/admin/` (login: `admin` / `password` — change in Settings → Account)
- Tagline: "We Fix. You Relax." Brand colors from the logo: navy `#10203F`/`#0A1428`, gold `#F5A800`/`#FFC933`, red `#D2382C`.

`HelpingFiles/` is the original asset staging folder (logos, hero drafts, RTF copy) — not code, not served.

## Database

Single DB `worldwidehandyman_db` (root, no password, host 127.0.0.1). Import/reset:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root --host=127.0.0.1 < sql/schema.sql
```

**Warning:** `sql/schema.sql` DROPs and recreates all tables and reseeds content — it wipes live data.

Tables: `users` (admin auth), `settings` (key-value — all site config), `nav_links` (menu, `is_cta` renders as gold button), `pages` (custom pages served at `/pages/{slug}`), `services`, `gallery`, `testimonials`, `faqs`, `quotes` (Get-a-Quote submissions, `sms_sent`/`sms_error` track Twilio), `contact_messages`.

SEO landing-page tables: `seo_locations`, `seo_services`, `seo_service_locations` — see the SEO section below. `sql/schema.sql` creates these with `IF NOT EXISTS`, so re-running it resets the site content **without** destroying generated landing pages. To rebuild those three tables from scratch, run `sql/seo.sql` (drops and recreates them) then re-import `sql/seo-seed.sql`.

## Architecture

- `includes/config.php` — DB constants, `APP_ROOT`, computes `BASE_URL` from DOCUMENT_ROOT (works at root or subdirectory), starts session, sets America/Toronto.
- `includes/db.php` — `db()` PDO singleton (ERRMODE_EXCEPTION, FETCH_ASSOC).
- `includes/functions.php` — `esc()`, `base_url()`, `setting($key)` (per-request cached), `settings_save()`, `nav_links()`, `is_nav_active()`, CSRF (`csrf_field()`/`csrf_check()`), flash messages, `redirect()`, `phone_href()`.
- `includes/twilio.php` — `twilio_send_sms()` (plain cURL to Twilio REST API, no SDK), `twilio_notify_quote()` (never throws; failure recorded on the quote row, never blocks the customer).
- `includes/header.php` / `footer.php` — shared layout; header emits CSS variables from the color settings, so admin color changes restyle the whole site.
- **Mobile navigation is custom, not Bootstrap collapse.** Below 992px `#mainNav` (`.site-nav-panel`) becomes a `position: fixed; inset: 0` overlay driven by a `body.nav-open` class; `#navBurger`'s three spans morph into an X. Links carry `style="--i: N"` for the staggered reveal. Logic lives in the "Full-screen mobile navigation" block of `assets/js/scripts.js` (Escape, link-click close, Tab focus trap, auto-close on resize ≥992px). Do not re-add `data-bs-toggle="collapse"` or the `.navbar-collapse` class.
- Frontend pages at root require the header/footer; `quote.php` and `contact.php` handle their own POST (CSRF + honeypot field `website`) before rendering.
- `/pages/{slug}` renders rows from `pages`; old `page.php?slug=x` URLs permanently redirect when clean URLs are available. Content is trusted admin-authored HTML (rendered unescaped by design).

### Admin (`admin/`)

- `admin/includes/auth.php` — bootstraps config/db/functions, `admin_user()`, `require_admin()`, `admin_csrf_or_die()`. Every admin page starts with these.
- `admin/includes/helpers.php` — `handle_image_upload()` (finfo MIME check, 8 MB cap, lands in `assets/uploads/<subdir>/`), `delete_upload()` (only deletes inside `assets/uploads/`, so seeded `assets/img/` files are safe), `move_row()` (up/down reordering, whitelisted tables), `slugify()`.
- List pages handle POST actions (toggle/move/delete) then redirect; `*-form.php` pages handle add/edit.
- `admin/settings.php` — tabbed (general/contact/homepage/colors/integrations/account); each tab posts `section`. Twilio token field keeps the stored value when left blank; "Save & Send Test SMS" button verifies Twilio live.

## SEO

`includes/seo.php` is the SEO layer — canonical URLs, meta/Open Graph tags, JSON-LD, and all data access for the landing pages. It requires config/db/functions itself, so a page only needs `require_once __DIR__ . '/includes/seo.php';` at the top. `includes/header.php` requires it too.

**Page-scoped variables**, set *before* requiring `includes/header.php`:

| Variable | Purpose |
|---|---|
| `$pageTitle` | rendered as `{title} \| {site name}` |
| `$pageTitleFull` | complete `<title>`, overrides `$pageTitle` |
| `$metaDescription` | meta description |
| `$canonicalPath` | site-relative path — **always set this on pages that take a query string** |
| `$robots` | defaults to `index, follow` |
| `$ogImage` / `$ogType` | social card image and type |
| `$breadcrumbs` | `[['label' => 'Services', 'url' => 'services'], ['label' => 'TV Mounting']]` — drives both `breadcrumb_html()` and BreadcrumbList schema |
| `$schemas` | extra JSON-LD nodes merged into the `@graph` |

The header always emits `LocalBusiness` + `WebSite` + `WebPage` (+ `BreadcrumbList` when `$breadcrumbs` is set) as a single `@graph`. Pages add `Service`, `FAQPage`, `HowTo`, `ItemList`, `Person`, `ContactPage`, `ImageGallery` via `$schemas`. Builders: `schema_service()`, `schema_faq()`, `schema_item_list()`, `schema_breadcrumbs()`. It also 301-redirects safe requests for legacy `.php`, trailing-slash, or non-canonical host/protocol variants to the configured canonical URL.

`seo_query()` swallows "table doesn't exist" errors and returns `[]`, so the site still runs before `sql/seo.sql` has been imported.

### Programmatic landing pages

Three page types, all routed by `.htaccess` and all admin-editable:

| URL | File | Table | Targets |
|---|---|---|---|
| `/handyman/{city}` | `location.php` | `seo_locations` | "handyman north york" |
| `/services/{service}` | `service.php` | `seo_services` | "tv mounting toronto" |
| `/services/{service}/{city}` | `service-location.php` | `seo_service_locations` | "tv mounting north york" |

Hubs: `/services` (`services.php`) lists every service page; `/service-areas` (`service-areas.php`) lists every location grouped by region. Both are in the main nav, which is what gives the landing pages a crawl path. The footer carries a full location link strip.

**A service × city page exists only where a `seo_service_locations` row exists.** Nothing is generated from the cross product. This is deliberate: auto-filling 26 services × 50 locations would publish 1,300 near-identical pages and read as doorway spam. `tier = 1` on a location and `is_pillar = 1` on a service mark the combinations worth writing by hand.

The combo page renders only the pair's own copy (`intro`, `local_angle`, `common_jobs`, one FAQ) plus links up to the full service page and across to the city page — it never repeats the service body, so the URLs do not compete with each other.

Content columns: plain-text fields (`intro`, `local_notes`, `pricing_notes`) render through `seo_paragraphs()`; `body_html` through `seo_safe_html()`, which strips everything outside `<h2> <h3> <h4> <p> <ul> <ol> <li> <strong> <em> <br>`; line-per-item fields (`neighbourhoods`, `common_jobs`, `jobs`) through `seo_lines()`; `faqs_json` / `process_json` through `seo_json_list()`.

### Crawler files

`/robots.txt` → `robots.php` and `/sitemap.xml` → `sitemap.php`, both generated so the URLs match whatever domain (or subfolder) the site is running under. Unmatched URLs rewrite to `404.php`, which returns a real 404 through `seo_not_found()`.

### Admin

Admin → **SEO** has Locations, Service Pages and Service × City, plus a **Settings → SEO** tab holding `site_url` (required before launch — everything canonical is built from it), verification tokens, GA4/GTM, and the LocalBusiness schema fields.

`seo_aggregate_rating` is **off by default** and should stay off unless every testimonial is a real, evidenceable review — Google issues manual penalties for review markup it judges fabricated, and the seeded example testimonials would qualify.

### Content rules

Landing-page copy is written in **first person singular** ("I", never "we" or "our team") because the business is a sole proprietor. It must never claim licensed / insured / bonded / WSIB / certified / award-winning status, never quote prices, and never offer work requiring a licensed trade (panel wiring, gas, structural, roofing, HVAC) — swapping existing fixtures is fine, anything past that gets referred on. Canadian English throughout.

## Conventions

- Always output through `esc()`; all queries are prepared statements.
- All state-changing POSTs carry `csrf_field()` and are checked.
- Use `base_url('path')` for every link/asset/redirect — never hardcode the folder name.
- New setting = add a seed row in `sql/schema.sql` + a field in the right settings tab; read it with `setting('key', 'default')`.
- Images: photos are optimized JPGs in `assets/img/`; admin uploads go to `assets/uploads/` (git-ignorable, 777 for XAMPP).

## Integrations

- **Twilio SMS** — credentials in `settings` table (Settings → SMS & Maps). New quote → SMS to `twilio_to`. XAMPP PHP has working cURL+TLS.
- **Google Maps** — Maps Embed API iframe on contact.php, key + location in settings.
