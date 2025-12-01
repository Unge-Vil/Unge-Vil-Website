# Unge Vil Website Overview

This page introduces how the Unge Vil website is organized for volunteers and editors. It is written for GitBook import, so keep the headings intact. You only need the normal WordPress admin; no coding is required.

## What the site is built for
- Share what Unge Vil does across cities and disciplines.
- Highlight local contacts and partners in each avdeling/department.
- Publish news, experiences, and later **events** once Påmeldinger.no API details are available.

## Platform
- **WordPress** with a Kadence child theme for accessibility and performance.
- Custom plugins (already installed for you):
  - **uv-core**: Locations, Activities, Partners, Experiences, and supporting shortcodes/blocks.
  - **uv-people**: Public profiles, location assignments, team grids, and profile editing.
- Hosting: works on normal shared hosting; staging can be a subdomain or subfolder clone.

## Current feature status
- ✅ Locations/avdelinger with tailored partner, activity, and news feeds.
- ✅ Team pages with highlighted primary contacts and profile cards.
- ✅ Experiences content type with grid/list/timeline layouts.
- ✅ Automatic translation via **GTranslate** (no Polylang setup required).
- ⏳ Event integrations planned when Påmeldinger.no API documentation is available.

## How volunteers will interact
- Log in with their WordPress account (Google SSO if enabled) and update their profile.
- Add or edit content in their location: news, activities, experiences, partners. Always set the right **Location** so it shows on the correct department page.
- Use the provided blocks and shortcodes already placed on department pages. If a page is missing a block, copy an existing block or ask an administrator to add it.

## Helpful links
- [Locations & Departments](locations-and-departments.md)
- [Translation & Language](translation-and-language.md)
- [WordPress Stack & Plugins](wordpress-stack-and-plugins.md)
- [Writing News Posts](writing-news-posts.md)
- [Editing Your Profile](editing-profile.md)
