# Admin Setup & Staging

## Roles
- Admins: full access.
- Editors: manage content (posts, pages, activities, partners, events).
- Authors: own content only.

## Staging
1) Create subdomain `staging.example.org` with its own DB.
2) Copy production to staging using UpdraftPlus or Duplicator.
3) In staging, enable: Discourage search engines + robots.txt `Disallow: /`.
4) Update Google OAuth app to use staging callback URL.
5) Optional: add HTTP Basic Auth on staging via .htaccess.

## Maintenance mode
Use the **Maintenance** plugin or temporarily place `maintenance.html` in web root and add an .htaccess rewrite (see `docs/MAINTENANCE-HTACCESS.md`).