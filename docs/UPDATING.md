# Updating from GitHub releases

## 1. Get the new ZIPs
1. Visit the repository's [Releases](https://github.com/ungevil/Unge-Vil-Website/releases) page.
2. Download the newest `uv-kadence-child` theme ZIP and plugin ZIPs (`uv-core`, `uv-people`, `uv-events-bridge`) for the latest version (e.g. `v0.5.0`).

## 2. Back up the site
1. In your hosting panel or backup plugin (e.g. UpdraftPlus), run a full backup.
2. Ensure both the database and the `wp-content` directory are included.
3. Store the backup somewhere safe before continuing.

## 3. Upload updates via WP Admin
### Child theme
1. In **Appearance → Themes → Add New → Upload Theme**, choose the `uv-kadence-child` ZIP.
2. Click **Install Now**, then **Replace current version** if prompted.
3. Make sure the child theme is active after the upload.

### Plugins
1. Go to **Plugins → Add New → Upload Plugin**.
2. Upload each plugin ZIP (`uv-core`, `uv-people`, `uv-events-bridge`) one at a time.
3. Click **Install Now**, choose **Replace current version** when asked, then **Activate**.

## 4. Verify versions
1. In **Plugins**, confirm each plugin lists the new version number (e.g. `0.5.0`).
2. In **Appearance → Themes**, open the child theme details and verify its version.
3. Browse a few pages on the front‑end to confirm the site loads as expected.
