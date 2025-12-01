# Locations & Departments

Use this guide to manage location/avdeling content so each local team has its own hub. Everything happens in the normal WordPress admin—no coding required.

## Key concepts
- **Locations taxonomy**: Stores cities/avdelinger (e.g., Haugesund, Oslo). Each term has an image and optional description.
- **Department pages**: Normal WordPress pages that use blocks to surface location-specific content. These pages are usually prepared for you—edit text and images without touching code.
- **Primary contacts**: Marked in uv-people so the most important volunteers show first in team grids.

## Creating a new location
1. In **Locations → Locations**, click **Add New Location**.
2. Set the **Name**, **Slug**, and **Description** (optional).
3. Upload a **Featured Image** for the term so it appears in grids and hero sections.
4. Save the term. Repeat for each avdeling. If you are unsure about names or slugs, ask an administrator before publishing.

## Building a department page
1. Create a normal **Page** in WordPress and title it after the location (e.g., “Oslo”).
2. Insert or duplicate the existing **uv-** blocks already used on other department pages (activities, partners, news, experiences, and team).
3. If you prefer shortcodes, you can paste the same ones used elsewhere and change the `location` value (e.g., `oslo`, `haugesund`).
4. Publish the page and link to it from menus or the front-page locations grid.

## Keeping local content fresh
- Encourage each avdeling to post **news** and **experiences** regularly with their location term attached.
- Add or update **partners** for each location to keep cards relevant.
- Use **primary contact** toggles in uv-people so the right volunteers appear first.

## Tips for consistency
- Reuse the same hero or header structure on each department page so readers understand they are in a local area.
- Keep slugs lowercase and short (e.g., `oslo`, `haugesund`).
- Avoid duplicating content between locations; link to national resources instead.
- If a page layout looks broken, undo changes or ask an administrator—do not edit template code.
