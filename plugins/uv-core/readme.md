UV Core registers CPTs, taxonomies, and shortcodes.

## Installation
1. Upload `uv-core` to `wp-content/plugins/`.
2. Activate the plugin from the WordPress admin panel.

## Shortcodes
- `[uv_locations_grid columns="3" show_links="1"]` – display all Locations in a card grid.
  - `columns` (default: 3) number of columns.
  - `show_links` (0 or 1) toggle archive links under each item.
- `[uv_news location="osl" count="3"]` – list recent posts, optionally filtered by a Location slug.
  - `location` (optional) Location slug.
  - `count` (default: 3) number of posts to display.
- `[uv_activities location="osl" columns="3"]` – grid of Activities for a Location.
  - `location` (required) Location slug.
  - `columns` (default: 3) number of columns.
- `[uv_partners location="osl" type="sponsor" columns="4"]` – show Partners with optional Location or Partner Type filtering.
  - `location` (optional) Location slug.
  - `type` (optional) Partner Type slug.
  - `columns` (default: 4) number of columns.
  - Each Partner post has a **Display** option: `circle_title` (default), `logo_only`, `logo_title`, or `title_only`.

## Usage

```html
[uv_news location="oslo" count="3"]
```

## Requirements
- WordPress 6.0+

## Translation
All strings use the `uv-core` text domain. Production relies on **GTranslate** for automatic Norwegian↔English switching, but you can still add `.po/.mo` files in a `languages/` folder or use another translation tool if we later decide to manage translations manually.

## Changelog
### 0.8.6
- Refreshed the Experiences card layout with tighter spacing, simplified headings, and more compact grids.
- Show the load-more button only when additional Experiences pages are available.
### 0.8.5
- Added load-more pagination to the Experiences block and exposed data attributes for front-end fetching.
- Restyled experience cards (including icon updates), removed list markers, and raised the block count cap to support larger lists.
### 0.8.4
- Group experiences by year in both the editor preview and front-end output with year headings.
- Added an optional year filter/date query to the Experiences block plus proper pagination offsets so paged results stay in sync.

### 0.8.3
- Refactored the Erfaringer block to share server-side rendering with the shortcode, keeping front-end markup consistent and lin
king cards to their posts.
- Improved the block preview with REST-powered cards, loading feedback, and the same grid/list/timeline styles used on the fron
t-end.
- Added a fallback Select2 registration path for Experience metaboxes so the admin scripts load even if UV People is inactive.
### 0.8.2
- Version bump to package the refreshed child theme styling and updated documentation; no core plugin changes.
### 0.8.1
- Set the Experiences block title directly in block metadata so the name shows correctly in the block inserter.
### 0.8.0
- Added grid, list, and timeline layouts to the Experiences block/shortcode with refreshed card styling, fallback icons, and optional organization/date meta.
### 0.7.9
- Version bump for release; no functional changes.
### 0.7.8
- Enhance Experience partner/user selectors with placeholders, clearer multi-select behavior, and quick clear actions.
### 0.7.7
- Version bump for release.
### 0.7.6
- Version bump for release.
### 0.7.5
- Version bump for release.
### 0.7.0
- Bug fixes and enhancements.
### 0.6.9
- Version bump for release.
### 0.6.8
- Version bump for release.
### 0.6.7
- Version bump for release.
### 0.6.6
- Version bump for release.
### 0.6.5
- Version bump for release.
### 0.6.4
- Version bump for release.
### 0.6.3
- Refactor core into modular includes.
- Specify numeric meta query type for experiences.
### 0.6.2
- Enhancements and bug fixes.
### 0.6.1
- Version bump for release.
### 0.6.0
- Version bump for release.
### 0.5.10
- Version bump for release.
### 0.5.9
- Version bump for release.
### 0.5.8
- Version bump for release.
### 0.5.7
- Version bump for release.
### 0.5.6
- Specify meta box screens and contexts.
- Add editor placeholders for empty block data.
### 0.5.5
- Version bump for release.
### 0.5.4
- Version bump for release.
### 0.5.3
- Version bump for release.
### 0.5.2
- Bug fixes and version bump.
### 0.5.1
- Add runtime WordPress and PHP version checks.
- Rename version constant to `UV_CORE_VERSION`.
### 0.5.0
- Bump to version 0.5.0.
- Use version constant for enqueued scripts.
- Update translation template.
### 0.4.1
- Minor bug fixes.
### 0.4.0
- Bump to version 0.4.0.
### 0.3.0
- Added partner display meta field with logo and title layout options.
### 0.2.0
- Enhanced shortcode parameters and updated documentation.
### 0.1.0
- Initial release.
