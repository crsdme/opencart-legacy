# Auto Backup

Бэкапы продакшена: база и фото товаров. Docker только для разработки; модуль крутится на живом хосте (crontab + PHP).

Код по-прежнему в git. Фото (`image/catalog`) и MySQL живут на сервере и пакуются здесь.

## Установка

1. Файлы уже в проекте.
2. Админка → Дополнения → Дополнения → Модули → Auto Backup → Установить.
3. Выдайте `access` / `modify` для:
   - `extension/module/auto_backup`
   - `extension/auto_backup`
4. Откройте **Auto Backup** в левом меню (или форму модуля).

Деинсталл **не** дропает историю, пока не включён **Delete history on uninstall**. Zip в `system/storage/backup/` не удаляются.

## Что копируется

| Что | По умолчанию | Заметки |
| --- | --- | --- |
| База | да | PHP-дамп таблиц `DB_PREFIX`. `session` и `customer_online` пропускаются. |
| Картинки | да | Оригиналы `image/catalog`. `image/cache` (включая `webp`) никогда. |
| Загрузки | нет | `system/storage/download/` |
| config.php | выкл | Там пароли БД. Оставляйте выкл, если удалённый приёмник не приватный. |

Архив: `backup-YYYY-mm-dd-His.zip` с `database.sql` и файлами.

## Куда класть

- **Этот сервер** — `system/storage/backup/` (не с веба). Запасной вариант, не единственная копия.
- **Google Drive** — OAuth, scope `drive.file`. Создаёт папку `OpenTail Backups`, если не вставить folder ID.
- **FTP / FTPS** — другой хост. Удобно, если уже есть ящик под бэкапы.

Письмо — **отчёт** (успех/ошибка). Zip с фото не прикладывается.

Хранить N копий (по умолчанию 7). После загрузки на Drive/FTP локальный zip можно удалить.

## Cron на проде

Магазин сам себя не планирует. crontab **на боевом сервере**.

**VPS (лучше, без HTTP-таймаута):**

```
0 3 * * * php /var/www/html/system/cli/auto_backup.php
```

`force` игнорирует интервал: `php system/cli/auto_backup.php force`

**Shared hosting (URL):**

```
0 * * * * curl -fsS "https://shop/index.php?route=extension/auto_backup/cron&cron_token=TOKEN"
```

Точные строки — на экране настроек. Интервал (6ч / 12ч / день / 2 дня / неделя) всё равно действует, даже если crontab чаще.

Режим обслуживания эти route не блокирует.

PHP `max_execution_time` всё равно режет URL. Большой фотокаталог — CLI.

## Google Drive

1. [Google Cloud Console](https://console.cloud.google.com/) → включить **Google Drive API**.
2. Credentials → OAuth client → **Web application**.
3. Authorized redirect URI = URI из настроек Auto Backup (`index.php?route=extension/auto_backup/oauth`).
4. Вставить Client ID и secret, сохранить, затем **Connect Google Drive**.
5. На проде нужен HTTPS (требование Google, кроме `localhost`).

Секреты хранятся зашифрованными (`config_encryption`). После сохранения не показываются.

## Восстановление

Не в один клик. Скачайте zip (из истории, если локальная копия ещё есть) или с Drive/FTP.

1. Импорт `database.sql` (phpMyAdmin или `mysql`).
2. Распаковать `image/catalog` поверх картинок магазина.
3. Если в архиве был `config.php`, сверьте доступы; не перезаписывайте рабочий конфиг вслепую.

## База (DB_PREFIX)

- `auto_backup` — история запусков

## Безопасность

- Cron-токен в URL
- Папка бэкапов закрыта `.htaccess`
- Скачивание из админки только из `system/storage/backup/`
- Пароль FTP, Google client secret, refresh token в форме маскируются
- `drive.file` не читает остальной Drive
