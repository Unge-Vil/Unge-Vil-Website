# Migration checklist: move to `uv-core-min`

## 1) Prepare
1. Make sure `uv-core-min` exists in `plugins/` and can be activated.
2. Keep **legacy plugins active** initially:
   - `uv-core`
   - `uv-people`
3. Optional but recommended: ensure ACF is active (for field UI).

## 2) Activate `uv-core-min` side-by-side
1. Activate `UV Core Min` in wp-admin.
2. Confirm admin notices:
   - Info notice about legacy plugins still active (expected during migration)
   - Warning notice if ACF is missing (non-fatal)

## 3) Validate data/admin structures (while legacy still active)
Test these screens/URLs:
- `wp-admin/edit.php?post_type=uv_activity`
- `wp-admin/edit.php?post_type=uv_partner`
- `wp-admin/edit.php?post_type=uv_experience`
- `wp-admin/edit-tags.php?taxonomy=uv_location`
- `wp-admin/edit-tags.php?taxonomy=uv_activity_type&post_type=uv_activity`
- `wp-admin/edit-tags.php?taxonomy=uv_partner_type&post_type=uv_partner`
- `wp-admin/edit-tags.php?taxonomy=uv_position`

Check that:
- Existing content appears for all UV CPTs
- Taxonomy terms are present
- `uv_location` term meta (`uv_location_image`, `uv_location_page`) can be edited/saved
- `uv_position` rank weight (`uv_rank_weight`) can be edited/saved

## 4) Theme dependency review before disabling legacy plugins
From `docs/inventory.md`, identify items still provided only by legacy plugin frontend logic:
- shortcodes (`[uv_team]`, `[uv_news]`, `[uv_activities]`, `[uv_edit_profile]`, etc.)
- helper functions (`uv_people_get_avatar`, `uv_core_get_experiences_for_user`)

Do **not** disable legacy plugins in production until theme/templates are updated to stop relying on those frontend pieces.

## 5) Staged disable test (staging environment)
1. Deactivate `uv-core` first.
2. Re-test all admin URLs above.
3. Deactivate `uv-people`.
4. Re-test admin URLs again plus any custom admin workflows (team manager/profile screens).

If frontend pages break, this is expected until theme/frontend replacements are completed.

## 6) Permalink/rewrite recovery steps
If CPT/taxonomy URLs 404 after activation/deactivation:
1. Go to `Settings → Permalinks`.
2. Click **Save Changes** once (no value changes needed).
3. Re-test:
   - one `uv_activity` single
   - one `uv_partner` single
   - one `uv_experience` single
   - one `uv_location` term archive URL

## 7) Rollback plan
If anything critical fails:
1. Re-activate legacy plugins.
2. Keep `uv-core-min` active (safe side-by-side mode).
3. Fix remaining theme/frontend dependencies, then retry staged disable.
