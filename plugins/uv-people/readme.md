UV People adds user profile fields, per-location assignments, and a team grid shortcode.

## Installation
1. Upload `uv-people` to `wp-content/plugins/`.
2. Activate via the WordPress admin.

## Shortcodes
- `[uv_team location="osl" columns="4" highlight_primary="1"]` – output a grid of team members for a Location. Requires the `location` attribute.

## Requirements
- WordPress 6.0+
- Polylang for multilingual quotes (optional but recommended).

## Example Usage

```html
[uv_team location="bergen" columns="3"]
```

## Translation
All strings use the `uv-people` text domain. Place translation files in `languages/` or manage translations through Polylang or another translation plugin.
