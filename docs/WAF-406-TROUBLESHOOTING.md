# 406 troubleshooting for Block Editor (host/WAF)

If the WordPress editor returns `406 Not Acceptable` for `wp-json` or `admin-ajax.php`, the block is typically caused by server firewall/WAF rules (not plugin PHP logic).

## Observed failing endpoints

Examples from production logs:

- `GET /wp-json/wp/v2/categories?...`
- `GET /wp-json/wp/v2/taxonomies/category?...`
- `GET /wp-json/wp/v2/pages/<id>?context=edit...`
- `GET /wp-json/wp/v2/uv_location?...`
- `POST /wp-admin/admin-ajax.php` (heartbeat)

## What the host/WAF must allow

1. Allow authenticated editor/admin requests to:
   - `/wp-json/*`
   - `/wp-admin/admin-ajax.php`
2. Do not block common Gutenberg REST query parameters:
   - `_locale`, `_fields`, `context`, `per_page`, `orderby`, `order`, `page`, `search`
3. Keep protection enabled globally; only exempt the specific false-positive rule IDs or create a scoped bypass for the paths above.

## Cloudflare guidance

Create scoped WAF Skip rules for logged-in WordPress users:

1. Condition: request path starts with `/wp-json/` OR request path equals `/wp-admin/admin-ajax.php`
2. Condition: authenticated WP session (for example `wordpress_logged_in_` cookie exists)
3. Action: skip managed WAF checks for these requests only

## Apache/ModSecurity guidance

1. Check ModSecurity audit logs and capture exact blocking rule ID(s).
2. Apply targeted exception for those IDs on:
   - `/wp-json/*`
   - `/wp-admin/admin-ajax.php`
3. Do not disable ModSecurity globally.

## Message to send hosting support

```
We are getting 406 Not Acceptable in WordPress block editor for authenticated users.
Please whitelist/allow authenticated requests to /wp-json/* and /wp-admin/admin-ajax.php,
and add targeted exceptions for the ModSecurity/ WAF rule IDs that block Gutenberg query parameters
such as _locale, _fields, context, per_page, orderby, order, page.
Please share the blocked rule IDs and timestamps so we can verify.
```

## Verification after host change

1. Open post editor and check browser Network.
2. Confirm no `406` on `/wp-json/...` and `/wp-admin/admin-ajax.php`.
3. Create and publish a normal post.
