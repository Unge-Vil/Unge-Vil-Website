# Migration: Remove legacy people meta

As of `uv-people` **v0.5.0**, the plugin relies solely on the translated meta keys `uv_role_nb`, `uv_role_en`, `uv_quote_nb`, and `uv_quote_en`.
English fields are optional because public translation is handled by GTranslate, so only Norwegian entries are required for production unless you want to provide manual English overrides.
Legacy fields `uv_role_title` and `uv_quote` are no longer read when rendering team members.

To migrate existing data run the bundled WP‑CLI command:

```sh
wp uv-people migrate-legacy-meta
```

The command copies old values into the new translated fields and deletes the legacy keys.
Run it once after updating to `uv-people` v0.5.0 or later.
