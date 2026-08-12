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

Tables: `users` (admin auth), `settings` (key-value — all site config), `nav_links` (menu, `is_cta` renders as gold button), `pages` (custom pages served by `page.php?slug=`), `services`, `gallery`, `testimonials`, `faqs`, `quotes` (Get-a-Quote submissions, `sms_sent`/`sms_error` track Twilio), `contact_messages`.

## Architecture

- `includes/config.php` — DB constants, `APP_ROOT`, computes `BASE_URL` from DOCUMENT_ROOT (works at root or subdirectory), starts session, sets America/Toronto.
- `includes/db.php` — `db()` PDO singleton (ERRMODE_EXCEPTION, FETCH_ASSOC).
- `includes/functions.php` — `esc()`, `base_url()`, `setting($key)` (per-request cached), `settings_save()`, `nav_links()`, `is_nav_active()`, CSRF (`csrf_field()`/`csrf_check()`), flash messages, `redirect()`, `phone_href()`.
- `includes/twilio.php` — `twilio_send_sms()` (plain cURL to Twilio REST API, no SDK), `twilio_notify_quote()` (never throws; failure recorded on the quote row, never blocks the customer).
- `includes/header.php` / `footer.php` — shared layout; header emits CSS variables from the color settings, so admin color changes restyle the whole site.
- **Mobile navigation is custom, not Bootstrap collapse.** Below 992px `#mainNav` (`.site-nav-panel`) becomes a `position: fixed; inset: 0` overlay driven by a `body.nav-open` class; `#navBurger`'s three spans morph into an X. Links carry `style="--i: N"` for the staggered reveal. Logic lives in the "Full-screen mobile navigation" block of `assets/js/scripts.js` (Escape, link-click close, Tab focus trap, auto-close on resize ≥992px). Do not re-add `data-bs-toggle="collapse"` or the `.navbar-collapse` class.
- Frontend pages at root require the header/footer; `quote.php` and `contact.php` handle their own POST (CSRF + honeypot field `website`) before rendering.
- `page.php?slug=x` renders rows from `pages`; content is trusted admin-authored HTML (rendered unescaped by design).

### Admin (`admin/`)

- `admin/includes/auth.php` — bootstraps config/db/functions, `admin_user()`, `require_admin()`, `admin_csrf_or_die()`. Every admin page starts with these.
- `admin/includes/helpers.php` — `handle_image_upload()` (finfo MIME check, 8 MB cap, lands in `assets/uploads/<subdir>/`), `delete_upload()` (only deletes inside `assets/uploads/`, so seeded `assets/img/` files are safe), `move_row()` (up/down reordering, whitelisted tables), `slugify()`.
- List pages handle POST actions (toggle/move/delete) then redirect; `*-form.php` pages handle add/edit.
- `admin/settings.php` — tabbed (general/contact/homepage/colors/integrations/account); each tab posts `section`. Twilio token field keeps the stored value when left blank; "Save & Send Test SMS" button verifies Twilio live.

## Conventions

- Always output through `esc()`; all queries are prepared statements.
- All state-changing POSTs carry `csrf_field()` and are checked.
- Use `base_url('path')` for every link/asset/redirect — never hardcode the folder name.
- New setting = add a seed row in `sql/schema.sql` + a field in the right settings tab; read it with `setting('key', 'default')`.
- Images: photos are optimized JPGs in `assets/img/`; admin uploads go to `assets/uploads/` (git-ignorable, 777 for XAMPP).

## Integrations

- **Twilio SMS** — credentials in `settings` table (Settings → SMS & Maps). New quote → SMS to `twilio_to`. XAMPP PHP has working cURL+TLS.
- **Google Maps** — Maps Embed API iframe on contact.php, key + location in settings.
