# Unge Vil Admin (branding & control panel)

The `uv-admin` plugin provides:
- A **Control Panel** (Dashboard and top-level menu) with big buttons for common tasks.
- A **Docs URL** setting (link to your Google Workspace website with guides).
- A **custom admin color scheme** (purple accent) and **branded login screen**.
- Cleaner dashboards for editors (hides noisy widgets and certain plugin menus).
- Optional admin bar shortcut.

## Set Docs URL
Go to **Settings → Unge Vil Admin**, paste your Google Sites URL (or any docs URL), and Save.

## Customize Logo
Replace `themes/uv-kadence-child/assets/img/logo.svg` with your logo (same filename) to update the login screen and Control Panel header.

## Design Control Panel Page
Create a normal WordPress page and build its layout with Kadence blocks. Set its ID in the `uv_admin_control_panel_page_id` option so the plugin renders that page inside the Control Panel screen. Use the `[uv_display_name]` shortcode anywhere in the page to greet the current user.
