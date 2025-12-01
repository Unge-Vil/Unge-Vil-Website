# WordPress Stack & Plugins

This page summarizes the technical stack so volunteers know what powers the site and where to look when something needs updating. You do not need to write code—everything is handled through normal WordPress screens.

## Core pieces
- **WordPress**: Chosen for ease of use and volunteer-friendly editing.
- **Kadence**: Base theme; we use a small child theme (`uv-kadence-child`) for layout and accessibility tweaks.
- **GTranslate**: Provides automatic Norwegian↔English translation on the public site.

## Custom plugins
- **uv-core**
  - Registers Locations, Activities, Partners, and Experiences.
  - Provides shortcodes/blocks for location grids, activities, partners, news, and experiences.
- **uv-people**
  - Adds public profile fields, avatar uploads, and location assignments.
  - Provides team grids and a self-service profile edit form.

## How content relates
- **Locations/avdelinger** tie everything together. News, activities, partners, experiences, and team members can all be filtered by location.
- Department pages are normal Pages that embed the uv-core/uv-people blocks to show local data.

## Deployment notes
- Works on standard shared hosting; staging can be a cloned subdomain or folder.
- Releases are packaged as ZIPs via GitHub Actions tags—download and upload through WordPress when updating.
- If something looks untranslated, check GTranslate first; manual `.po` files are optional and not required for production.
