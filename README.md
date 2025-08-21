# Unge Vil Website – Starter Kit

## Executive Summary (Plain Language)
- **Goal by Sept 1:** Launch a fast, accessible site with Home, core info pages, and location-based **Team pages** (primary contacts highlighted). Norwegian first; English ready.
- **Later (phase 2+):** Activities, Events, Partners, Experiences, deeper translations.
- **Admin:** Clean, branded Control Panel in English; link to our Google Workspace docs.
- **Accessibility & SEO:** Built-in best practices; editors add alt text and clear headings.
- **Future-proof:** Small, readable plugins; easy to extend. Volunteers welcome — this is largely vibe-coded with help from ChatGPT, so expert contributors are invited!


This repository bootstraps the **Kadence child theme** and three lightweight plugins for the Unge Vil website.
It’s designed for **shared hosting**, with a focus on **accessibility, performance, and translation readiness**.

## Contents
- `themes/uv-kadence-child/` – Kadence child theme (styles, small a11y tweaks).
- `plugins/uv-core/` – CPTs & taxonomies (Locations, Activities, Partners, Experiences) + basic shortcodes and term image fields.
- `plugins/uv-people/` – User extensions, per-location assignments, team grid shortcode, and **media-library avatars** (no Gravatar).
- `plugins/uv-events-bridge/` – Adds Location taxonomy to Events (The Events Calendar) + upcoming events shortcode.
- `docs/` – Admin setup, staging plan, GDPR notes, and content guides.
- `.github/` – Issue templates and a GitHub Actions workflow to package ZIPs on tags.

> All code is **translation-ready**. Use Polylang (free) when you’re ready.


## About Unge Vil
**Unge Vil** is a Norwegian non‑profit that empowers young people to explore and create across **music, film, gaming, and other creative fields**. 
We collaborate with local communities across multiple cities and run initiatives like **Create A Spark (international)** and the **Unge Vil model**. 
The website prioritizes **accessibility**, **privacy**, and **performance**, and is built to let volunteers and staff contribute content safely.

- Contact (volunteering, partnerships): **org@ungevil.no**
- Organization type: Non‑profit / volunteer‑driven

## Admin & Docs
- **WP Admin language:** English is the default for all custom UI. (Public site content can be Norwegian/English.)
- **Control Panel:** The `uv-admin` plugin (included) adds a clean **Unge Vil Control Panel** for editors (big buttons for common tasks), an admin bar shortcut, a custom admin color scheme, and a branded login screen.
- **Docs:** Host internal guides on your Google Workspace Site. Put the URL in **Settings → Unge Vil Admin** and the Control Panel will link to it.
- **Minimal dashboards:** For non‑admins, the Control Panel hides noisy widgets (e.g., Site Kit, Wordfence) to reduce clutter.

## Quick Start (local or shared host)
1. Install WordPress 6.x and the free **Kadence** theme.
2. Upload & activate the child theme from `themes/uv-kadence-child`.
3. Upload & activate the plugins in this order:
   - `uv-core`
   - `uv-people`
   - `uv-events-bridge` (optional; only if using The Events Calendar)
4. In **Settings → Permalinks**, click **Save** once.
5. Create **Location** terms (e.g., Haugesund, Oslo) under **Locations** (taxonomy) and set images for each term.
6. Build a **Department page** per location using shortcodes/blocks (see below).

## Shortcodes
- **Locations grid** (front page):  
  `[uv_locations_grid columns="3" show_links="1"]`
- **Activities list** (for a location):  
  `[uv_activities location="haugesund" columns="3"]`
- **Partners list** (for a location):  
  `[uv_partners location="haugesund" type="" columns="4"]`
- **News for a location** (uses posts with Location term):  
  `[uv_news location="haugesund" count="3"]`
- **Team grid** (primary contacts first):  
  `[uv_team location="haugesund" columns="4" highlight_primary="1"]`
- **Upcoming events** (Events Calendar required):  
  `[uv_upcoming_events location="haugesund" count="5"]`

## Accessibility Defaults
- Semantic lists for cards; focus-visible styles; alt text required in UI fields.
- Slider not included (use Kadence/blocks); keep motion minimal and respect prefers-reduced-motion.

## Translation
- All strings use `__()` with text domains: `uv-core`, `uv-people`, `uv-events-bridge`, `uv-kadence-child`.
- Provide translations via Polylang or `.po` files in each plugin’s `languages/` folder.

## Staging on Shared Host
- In **DirectAdmin → Subdomain Management**, create `staging.ungevil.no` (or a `/staging` subfolder). Then use **MySQL Management** to add a fresh database and user.
- Copy the production site into this folder and update `wp-config.php` with the new database credentials. Free plugins like **WP STAGING**, **UpdraftPlus**, or **Duplicator** can handle the clone for you.
- Block indexing via **Settings → Reading → Discourage search engines** and a `robots.txt` with `Disallow: /`.
- Optionally add HTTP Basic Auth (.htaccess) to staging and request a free **Let's Encrypt** certificate in DirectAdmin.
- Maintain separate Google OAuth credentials for staging (callback URL must match).

## Maintenance Mode
While deploying or migrating, enable **Maintenance** plugin (already installed), or use a minimal `maintenance.html` served by `.htaccess` rewrite (see docs).

## GDPR & Privacy
- Use **Complianz** for cookie consent and policies.
- Avoid storing sensitive data in user profiles; phone and public email fields are optional.
- Limit who can view phone/email on team cards; defaults are off.
- See `docs/GDPR-NOTES.md`.

## Admin UX for Editors
- `uv-people` adds a Team Assignments UI and removes noisy menus for non-admin roles.
- Add your how-to videos/links in **Dashboard → Team Guide** widget (config in `uv-people`).

## Building ZIPs from GitHub
Tag a release like `v0.1.0` and GitHub Actions will attach zips for the child theme and each plugin.
On shared hosting, download the zips from the release and install via **Plugins → Add New → Upload**.

---
© 2025 Unge Vil. MIT License.
