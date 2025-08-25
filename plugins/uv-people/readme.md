UV People adds user profile fields, per-location assignments, and a team grid shortcode.

## Installation
1. Upload `uv-people` to `wp-content/plugins/`.
2. Activate via the WordPress admin.

## Shortcodes
- `[uv_team location="osl" columns="4" highlight_primary="1"]` – output a grid of team members for a Location.
  - `location` (required) Location slug to filter team members.
  - `columns` (default: 4) number of columns in the grid.
  - `highlight_primary` (0 or 1) emphasize primary team members.

## Usage

```html
[uv_team location="bergen" columns="3"]
```

## Requirements
- WordPress 6.0+
- Polylang for multilingual quotes (optional but recommended).

## Translation
All strings use the `uv-people` text domain. Place translation files in `languages/` or manage translations through Polylang or another translation plugin.

## Changelog
### 0.5.0
- Bump to version 0.5.0.
### 0.4.1
- Minor bug fixes.
### 0.4.0
- Bump to version 0.4.0.
### 0.3.0
- Bump to version 0.3.0.
### 0.2.0
- Added `highlight_primary` option to the team grid and improved assignment handling.
### 0.1.0
- Initial release.
