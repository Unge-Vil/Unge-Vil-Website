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
- Optional: Polylang for translating taxonomy terms.

## Translation
All strings use the `uv-core` text domain. Add `.po/.mo` files in a `languages/` folder or use a translation plugin like Polylang.

## Changelog
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
