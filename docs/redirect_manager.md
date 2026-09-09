# Redirect Manager

OpenCart is the admin UI and the source of truth. Apache, Nginx or Cloudflare execute **redirects** without bootstrapping the shop. **410 / 404 status pages** are rendered by OpenCart (`error/gone`, `error/not_found`) so they can use Design → Layouts.

## Installation

1. Files are already in the project.
2. Admin → Extensions → Extensions → Modules → Redirect Manager → Install.
3. Enable the module and open **Redirect Manager** in the left menu.
4. Grant `access` / `modify` for:
   - `extension/module/redirect_manager`
   - `extension/redirect_manager`

Uninstall does **not** drop rules unless **Delete module data on uninstall** is enabled.

## HTTP backends

### Apache (default in this Docker stack)

**Recommended:** Generate a separate file and RewriteMap in the vhost. `.htaccess` cannot use RewriteMap and does not scale to 15k+ rules.

```
RewriteEngine On
RewriteMap rm_redirects txt:/var/www/html/system/storage/redirect_manager/apache/redirects.map
RewriteMap rm_status txt:/var/www/html/system/storage/redirect_manager/apache/status.map
Include /var/www/html/system/storage/redirect_manager/apache/redirects.conf
```

`.htaccess` mode only rewrites the block between:

```
# BEGIN REDIRECT MANAGER
ErrorDocument 410 /index.php?route=error/gone
Redirect 301 /old-url /new-url
Redirect gone /removed
# END REDIRECT MANAGER
```

The shop `.htaccess` also has `ErrorDocument 410 /index.php?route=error/gone`, so a 410 is the themed storefront page (route `error/gone`), not an empty Apache response.

### Nginx

The module never edits `nginx.conf`. Set the output path, then:

```
http {
    map_hash_max_size 262144;
    include /var/www/html/system/storage/redirect_manager/nginx/redirects.conf;
}

server {
    error_page 410 =410 /index.php?route=error/gone;
    error_page 404 =404 /index.php?route=error/not_found;
    if ($rm_code = 410) { return 410; }
    if ($rm_code = 404) { return 404; }
    if ($rm_code = 451) { return 451; }
    if ($rm_target != "") { return $rm_code $rm_target; }
}
```

`nginx -t` / `systemctl reload nginx` run only if **Allow server reload commands** is ON (default OFF).

### Cloudflare

Uses Bulk Redirects API (301/302/307/308 only). 404/410/451 are skipped with a warning.

Required token permissions: Account → Lists / Bulk Redirects Edit.

Settings: Account ID, Bulk Redirect List ID, optional Rule ID, domain. The token is stored encrypted and never shown in full.

If the rules hash did not change, Cloudflare is not called again.

### PHP fallback

For shared hosting. Generated map: `system/storage/redirect_manager/php/map.php`.

Point `auto_prepend_file` at `system/storage/redirect_manager/php/bootstrap.php` so lookup happens before OpenCart. 410/404 set `route=error/gone` / `error/not_found` and continue into the shop.

Catalog PHP also applies rules after SEO resolve (`startup/redirect_manager`), so 410 works in this Docker stack without RewriteMap.

## 410 page

Route: `error/gone`. HTTP 410, `common/layout`, `noindex`.

Admin → Design → Layouts: route `error/gone` (a **410** layout is created on Redirect Manager install / dashboard). Same for `error/not_found`.

Preview: `/index.php?route=error/gone`

## Cron

```
*/5 * * * * php /var/www/html/system/cli/redirect_manager.php sync
```

Commands: `sync`, `generate`, `validate`, `cloudflare-sync`, `status`.

Does not boot the catalog frontend.

## Database (DB_PREFIX)

- `redirect_manager_rule`
- `redirect_manager_batch`
- `redirect_manager_url_history`
- `redirect_manager_sync`
- `redirect_manager_log`

## Security

- Admin CSRF: `user_token`
- Generated files only under `system/storage/redirect_manager/` or extra allowed directories
- `.htaccess` writes are marker-only
- Cloudflare token is not logged and not rendered after save

## Known limits

- `.htaccess` is not suitable for 15k–100k rules
- Cloudflare Bulk Redirects do not support 404/410/451
- Cloudflare list size depends on the Cloudflare plan
- Automatic SEO redirects and 410-on-delete default to OFF
- PHP fallback is optional and last in priority
