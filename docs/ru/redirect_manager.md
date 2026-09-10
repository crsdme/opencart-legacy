# Redirect Manager

OpenCart — админка и источник правды. Apache, Nginx или Cloudflare исполняют **редиректы** без бутстрапа магазина. **Страницы статуса 410 / 404** рисует OpenCart (`error/gone`, `error/not_found`), чтобы работали Дизайн → Макеты.

## Установка

1. Файлы уже в проекте.
2. Админка → Дополнения → Дополнения → Модули → Redirect Manager → Установить.
3. Включите модуль и откройте **Redirect Manager** в левом меню.
4. Выдайте `access` / `modify` для:
   - `extension/module/redirect_manager`
   - `extension/redirect_manager`

Деинсталл **не** дропает правила, пока не включён **Delete module data on uninstall**.

## HTTP-бэкенды

### Apache (дефолт этого Docker-стека)

**Рекомендуется:** отдельный файл и RewriteMap в vhost. `.htaccess` не умеет RewriteMap и не тянет 15k+ правил.

```
RewriteEngine On
RewriteMap rm_redirects txt:/var/www/html/system/storage/redirect_manager/apache/redirects.map
RewriteMap rm_status txt:/var/www/html/system/storage/redirect_manager/apache/status.map
Include /var/www/html/system/storage/redirect_manager/apache/redirects.conf
```

Режим `.htaccess` переписывает только блок между:

```
# BEGIN REDIRECT MANAGER
ErrorDocument 410 /index.php?route=error/gone
Redirect 301 /old-url /new-url
Redirect gone /removed
# END REDIRECT MANAGER
```

У магазинного `.htaccess` тоже есть `ErrorDocument 410 /index.php?route=error/gone`, поэтому 410 — темированная витрина (route `error/gone`), не пустой ответ Apache.

### Nginx

Модуль **не** правит `nginx.conf`. Задайте путь вывода, затем:

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

`nginx -t` / `systemctl reload nginx` только если **Allow server reload commands** ВКЛ (по умолчанию ВЫКЛ).

### Cloudflare

Bulk Redirects API (только 301/302/307/308). 404/410/451 пропускаются с предупреждением.

Права токена: Account → Lists / Bulk Redirects Edit.

Настройки: Account ID, Bulk Redirect List ID, опционально Rule ID, домен. Токен хранится зашифрованным и больше не показывается целиком.

Если хеш правил не менялся, Cloudflare снова не вызывается.

### PHP-fallback

Для shared hosting. Карта: `system/storage/redirect_manager/php/map.php`.

Укажите `auto_prepend_file` на `system/storage/redirect_manager/php/bootstrap.php`, чтобы поиск был до OpenCart. 410/404 ставят `route=error/gone` / `error/not_found` и идут дальше в магазин.

Каталожный PHP тоже применяет правила после SEO (`startup/redirect_manager`), поэтому 410 в этом Docker работает без RewriteMap.

## Страница 410

Route: `error/gone`. HTTP 410, `common/layout`, `noindex`.

Админка → Дизайн → Макеты: route `error/gone` (макет **410** создаётся при установке Redirect Manager / дашборде). То же для `error/not_found`.

Превью: `/index.php?route=error/gone`

## Cron

```
*/5 * * * * php /var/www/html/system/cli/redirect_manager.php sync
```

Команды: `sync`, `generate`, `validate`, `cloudflare-sync`, `status`.

Каталожный фронт не поднимает.

## База (DB_PREFIX)

- `redirect_manager_rule`
- `redirect_manager_batch`
- `redirect_manager_url_history`
- `redirect_manager_sync`
- `redirect_manager_log`

## Безопасность

- CSRF админки: `user_token`
- Сгенерированные файлы только в `system/storage/redirect_manager/` или явно разрешённых каталогах
- Запись `.htaccess` только по маркерам
- Токен Cloudflare не логируется и не рисуется после сохранения

## Известные пределы

- `.htaccess` не тянет 15k–100k правил
- Cloudflare Bulk Redirects не умеет 404/410/451
- Размер списка зависит от тарифа Cloudflare
- Авто-SEO редиректы и 410-при-удалении по умолчанию ВЫКЛ
- PHP-fallback опционален и последний по приоритету
