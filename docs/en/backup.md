# Auto Backup

Production backups of the database and product images. Docker is only for development; this module runs on the live host (crontab + PHP).

Git still holds code. Photos (`image/catalog`) and MySQL live on the server and are packed here.

## Installation

1. Files are already in the project.
2. Admin → Extensions → Extensions → Modules → Auto Backup → Install.
3. Grant `access` / `modify` for:
   - `extension/module/auto_backup`
   - `extension/auto_backup`
4. Open **Auto Backup** in the left menu (or the module form).

Uninstall does **not** drop history unless **Delete history on uninstall** is enabled. Zip files under `system/storage/backup/` are not deleted.

## What is copied

| On | Default | Notes |
| --- | --- | --- |
| Database | yes | PHP dump of `DB_PREFIX` tables. `session` and `customer_online` skipped. |
| Images | yes | `image/catalog` originals. `image/cache` (including `webp`) never packed. |
| Downloads | no | `system/storage/download/` |
| config.php | off | Contains DB credentials. Leave off unless the remote target is private. |

Archive: `backup-YYYY-mm-dd-His.zip` with `database.sql` plus files.

## Destinations

- **This server** — `system/storage/backup/` (not web-accessible). Fallback, not the only copy.
- **Google Drive** — OAuth, scope `drive.file`. Creates folder `OpenTail Backups` unless you paste a folder ID.
- **FTP / FTPS** — another host. Best when you already have a backup box.

Email is a **report** (success/fail). Photo zips are not attached.

Keep N copies (default 7). After a Drive/FTP upload you can drop the local zip.

## Production cron

The shop does not schedule itself. Add crontab **on the production server**.

**VPS (preferred, no HTTP timeout):**

```
0 3 * * * php /var/www/html/system/cli/auto_backup.php
```

`force` ignores the interval: `php system/cli/auto_backup.php force`

**Shared hosting (URL):**

```
0 * * * * curl -fsS "https://shop/index.php?route=extension/auto_backup/cron&cron_token=TOKEN"
```

The exact lines are on the settings screen. Interval (6h / 12h / day / 2 days / week) still applies if crontab is more frequent.

Maintenance mode does not block these routes.

PHP `max_execution_time` still applies to the URL. Large photo catalogs: use CLI.

## Google Drive

1. [Google Cloud Console](https://console.cloud.google.com/) → enable **Google Drive API**.
2. Credentials → OAuth client → **Web application**.
3. Authorized redirect URI = the URI shown in Auto Backup settings (`index.php?route=extension/auto_backup/oauth`).
4. Paste Client ID and secret, save, then **Connect Google Drive**.
5. Production needs HTTPS (Google requirement except `localhost`).

Secrets are stored encrypted (`config_encryption`). They are not shown after save.

## Restore

Not a one-click restore. Download the zip (history, if a local copy remains) or from Drive/FTP.

1. Import `database.sql` (phpMyAdmin or `mysql`).
2. Unpack `image/catalog` over the shop images.
3. If `config.php` was included, compare credentials; do not blindly overwrite a working config.

## Database (DB_PREFIX)

- `auto_backup` — run history

## Security

- Cron token in the URL
- Backup folder denied by `.htaccess`
- Admin download only from `system/storage/backup/`
- FTP password, Google client secret, refresh token are masked in the form
- `drive.file` cannot read the rest of the Drive
