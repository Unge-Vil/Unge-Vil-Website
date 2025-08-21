UV Core registers CPTs, taxonomies, and shortcodes.

## Installation
1. Upload `uv-core` to `wp-content/plugins/`.
2. Activate the plugin from the WordPress admin panel.

## Shortcodes
- `[uv_locations_grid columns="3" show_links="1"]` – display all Locations in a card grid.
- `[uv_news location="osl" count="3"]` – list recent posts, optionally filtered by a Location slug.
- `[uv_activities location="osl" columns="3"]` – grid of Activities for a Location.
- `[uv_partners location="osl" type="sponsor" columns="4"]` – show Partners with optional Location or Partner Type filtering.

## Requirements
- WordPress 6.0+
- Optional: Polylang for translating taxonomy terms.

## Example Usage

```html
[uv_locations_grid columns="4" show_links="0"]
```

## Translation
All strings use the `uv-core` text domain. Add `.po/.mo` files in a `languages/` folder or use a translation plugin like Polylang.
