UV Events Bridge connects Locations to The Events Calendar.

## Installation
1. Upload `uv-events-bridge` to `wp-content/plugins/`.
2. Activate the plugin in WordPress.

## Shortcodes
- `[uv_upcoming_events location="osl" count="5"]` – list upcoming Events optionally filtered by a Location term.
  - `location` (optional) Location term slug.
  - `count` (default: 5) number of events to display.

## Usage

```html
[uv_upcoming_events location="bergen" count="3"]
```

## Requirements
- WordPress 6.0+
- The Events Calendar plugin must be active.

## Translation
All strings use the `uv-events-bridge` text domain. Translation files can be placed in `languages/` or handled by translation plugins.

## Changelog
### 0.2.0
- Added location filter option to upcoming events shortcode and improved docs.
### 0.1.0
- Initial release.
