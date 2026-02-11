# Unge Vil inventory (legacy plugins + child theme dependencies)

## Scope scanned
- `plugins/uv-core`
- `plugins/uv-people`
- `themes/uv-kadence-child` (especially `functions.php`, templates, and `uv-team-manager.php`)

## CPTs

### `uv_activity` (from `plugins/uv-core/includes/cpt-taxonomies.php`)
- `public: true`
- `show_in_rest: true`
- `has_archive: true`
- `menu_icon: dashicons-forms`
- `supports: [title, editor, thumbnail, excerpt]`
- `taxonomies: [uv_location, uv_activity_type]`
- rewrite behavior: **default WP rewrite** (no explicit rewrite args set)

### `uv_partner`
- `public: true`
- `show_in_rest: true`
- `has_archive: true`
- `menu_icon: dashicons-heart`
- `supports: [title, thumbnail, excerpt]`
- `taxonomies: [uv_location, uv_partner_type]`
- rewrite behavior: **default WP rewrite** (no explicit rewrite args set)

### `uv_experience`
- `public: true`
- `show_in_rest: true`
- `has_archive: true`
- `menu_icon: dashicons-awards`
- `supports: [title, editor, thumbnail, excerpt, custom-fields]`
- rewrite behavior: **default WP rewrite** (no explicit rewrite args set)

## Taxonomies

### `uv_location`
- object types: `[post, uv_activity, uv_partner]`
- `public: true`
- `hierarchical: true`
- `show_in_rest: true`
- rewrite behavior: **default WP rewrite** (no explicit rewrite args set)

### `uv_activity_type`
- object types: `[uv_activity]`
- `public: true`
- `hierarchical: true`
- `show_in_rest: true`
- rewrite behavior: **default WP rewrite** (no explicit rewrite args set)

### `uv_partner_type`
- object types: `[uv_partner]`
- `public: true`
- `hierarchical: true`
- `show_in_rest: true`
- rewrite behavior: **default WP rewrite** (no explicit rewrite args set)

### `uv_position` (from `uv-people`)
- object types: `null` (used as user-position dictionary)
- `public: false`
- `show_ui: true`
- `hierarchical: false`
- `show_in_rest: true`
- `meta_box_cb: false`
- `show_in_menu: uv-control-panel`
- capabilities:
  - `manage_terms/edit_terms/delete_terms: manage_categories`
  - `assign_terms: edit_posts`
- rewrite behavior: effectively non-frontend/admin taxonomy use

## Shortcodes and frontend hooks (remove later)

### Shortcodes
- `uv-core`
  - `[uv_locations_grid]`
  - `[uv_news]`
  - `[uv_activities]`
  - `[uv_experiences]`
  - `[uv_partners]`
- `uv-people`
  - `[uv_edit_profile]`
  - `[uv_team]`

### Frontend hooks / render callbacks
- `uv-core`
  - `template_redirect` tax redirect for `uv_location` term pages via `uv_location_page`
  - dynamic block render callbacks for locations/news/experiences/activities/partners
- `uv-people`
  - dynamic block render callbacks for team grids
  - `redirect_canonical` filter keeps `?team=` query arg
- Child theme direct shortcode usage:
  - `taxonomy-uv_location.php`: uses `[uv_team]`, `[uv_news]`, `[uv_activities]`
  - `functions.php`: edit-profile admin page uses `[uv_edit_profile]`

## Admin pages/menus and required capabilities

### Plugin-admin pages
- `uv-people` → Tools page `uv-people-clear-cache` (`add_management_page`)
  - capability: `manage_options`
  - action handler: `admin_post_uv_people_clear_team_cache` + nonce `uv_people_clear_team_cache`

### Theme-admin pages (strong plugin dependencies)
- Top-level `uv-control-panel`
  - capability: `read`
- Hidden submenu `uv-edit-profile`
  - capability: `read`
- Submenu `uv-team-manager` (from `uv-team-manager.php`)
  - parent: `uv-control-panel`
  - capability: `edit_users`
  - nonce: `uv_team_manager_save`
- `UV settings` options page (`uv-settings`)
  - capability: `manage_options`

### Taxonomy admin usage that matters
- `uv_position` managed from control panel context (`show_in_menu => uv-control-panel`)
- `uv_location` term edit form extended by legacy plugins (image/page/member ordering)

## Used post_meta keys
- `uv_experience_org`
- `uv_experience_dates`
- `uv_experience_users`
- `uv_experience_partners`
- `uv_partner_url`
- `uv_partner_display`
- `uv_external_url`
- `uv_related_post`

Legacy/migration script also touches:
- `uv_user_id`
- `uv_location_id`
- `uv_is_primary`

## Used term_meta keys
- `uv_location_image`
- `uv_location_page`
- `uv_member_order`
- `uv_primary_team`
- `uv_rank_weight`

## Used user_meta keys
- `uv_avatar_id`
- `uv_phone`
- `uv_show_phone`
- `uv_birthdate`
- `uv_position_term`
- `uv_position_nb` (legacy fallback still read in theme/plugin)
- `uv_position_en` (legacy fallback still read in theme/plugin)
- `uv_location_terms`
- `uv_primary_locations`
- `uv_bio_nb`
- `uv_bio_en`
- `uv_quote_nb`
- `uv_quote_en`

Legacy migration keys still referenced in code:
- `uv_quote`
- `uv_rank_number`

## Used options (`get_option`/`update_option` keys)
- `uv_knowledge_url` (theme settings page/control panel link)
- `uv_people_birthdate_migrated` (one-time migration marker in `uv-people`)

## Child-theme dependencies on plugin functions/hooks

### Direct function dependencies
- `uv_people_get_avatar()`
  - used in `functions.php`, `author-team.php`, `template-parts/content-uv_experience.php`, `uv-team-manager.php`
- `uv_core_get_experiences_for_user()`
  - used in `author-team.php`

### Data-structure dependencies (must stay)
- CPTs: `uv_activity`, `uv_partner`, `uv_experience`
- Taxonomies: `uv_location`, `uv_position`
- Meta keys used in templates/admin screens:
  - `uv_location_image`, `uv_experience_users`, `uv_experience_partners`, `uv_partner_url`, `uv_partner_display`
  - user meta listed above (`uv_*` profile/team keys)

### Hook/shortcode behavior dependencies
- Theme patterns and taxonomy template currently assume legacy shortcodes exist.
- Team author view depends on query-var behavior (`?team=1`) and avatar/profile data populated by legacy plugin logic.
