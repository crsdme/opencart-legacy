# Философия проекта

OpenCart (ocStore) — движок. Tailwind и Docker — обвязка. Платформа легаси нарочно: это точка старта, а не повод накручивать модули на живой магазин.

- Один общий каркас вместо форков на каждый магазин: вёрстка, sitemap, языки, бандлинг, картинки, компоненты, SEO.
- Работа здесь, не на проде. Тот же стек, архитектура и стиль кода.
- Стили не копятся мёртвым слоем. Компонент живёт рядом со своим видом (`public_html/catalog/view/theme/default/components/`) и уходит вместе с ним.
- Свой код каталога — в `catalog/controller` / `catalog/model` / `system/library/custom`.
- SEO — часть ядра: meta, H1, sitemap, hreflang, 410, FAQ schema, а не модуль «на потом».
- Типы страниц выключаются флагами route. Виджеты макета — не выключатель.
- Проиндексированные URL, которые должны исчезнуть, идут в Redirect Manager (301/410), не в тихий 404.

# DOCKER

- Compose из трёх сервисов:
    - `mysql` (`mysql:5.7`) — поведение БД как у OpenCart.
    - `php` (свой `php:7.4-apache`) — рантайм.
    - `phpmyadmin` — быстрый взгляд в БД.
- Зачем так:
    - одинаковое окружение у всех;
    - код примонтирован (`./public_html:/var/www/html`), PHP/Twig применяются сразу;
    - БД в томе `mysql_data`;
    - сеть `ocnet` со стабильными именами хостов.
- Образ PHP (`docker/php/Dockerfile`):
    - `gd` с JPEG/PNG/WebP;
    - `mysqli`, `zip`, `opcache`;
    - Apache `mod_rewrite` для ЧПУ и sitemap.
- Тюнинг: `docker/php/php.ini` (загрузки, память, opcache).
- Vhost (`docker/php/000-default.conf`): `AllowOverride All`, чтобы работал `.htaccess`. Скриншоты docs отдаются с `/docs-media/` из папки `docs/`.

# INSTALL

Гайд с слотами скриншотов: `getting-started.md`. Два пути установки: **Docker (разработка)** и **живой хост (production)**. Docker на прод не ставят.

Шаг 3 `/install` спрашивает стартовый контент. Схема и системный сид всегда. Демо — по желанию.

- SQL в `public_html/install/sql/`:
    - `schema.sql` — `DROP` / `CREATE` всех таблиц (ещё upgrade `1000.php`).
    - `system/*.sql` — локали, статусы, налоги, события, системные дополнения, настройки, имена/роуты макетов, юридические страницы, системные SEO keyword. Всегда.
    - `demo/*.sql` — каталог, блог, баннеры, модули главной (carousel, latest, special, blog_latest), размещения на макетах, демо-SEO. Только если выбран **Demo catalog**.
- **Empty store** (по умолчанию): нет товаров, категорий, производителей, блога, баннеров и модулей главной. Записи макетов остаются (Home, Product, 404, 410, …) с роутами, без модулей главной. Modifications со статусом `0`. Меню блога и семейство `config_pages_blog` выкл. Блог включают в Система → Настройки → Опции.
- **Demo catalog**: демо-магазин ocStore (товары, блог, баннер и товарные/блоговые блоки на главной).
- CLI: `php install/cli_install.php install … --sample_data 0` (пусто) или `--sample_data 1` (демо).
- Всегда ставятся: тема default, COD, flat shipping, итоги заказа, дашборды, отчёты, валюта ECB, XML Sitemap. Google Shopping и NBU не ставятся (есть в админке). Carousel / latest / special / blog_latest — только демо (потом можно поставить из админки).
- Языки магазина: украинский (основной), английский, русский. `config_seo_url`, `config_seo_pro`, `config_minifier` включены с инсталла.
- API-пользователь создаётся и включён (`status = 1`), разрешены `127.0.0.1` / `::1` (и IP установщика). Редактирование заказа в админке без этого не работает.
- Пояс `Europe/Kiev` (на PHP 7.4; `Europe/Kyiv` нет). Невалидные ID мапятся или падают в UTC.

# LANGUAGE

- Мультиязык стартует в `public_html/catalog/controller/startup/startup.php`.
- Инсталл сеет украинский (`ua`, основной), английский (`en`), русский (`ru`). Папки витрины: `catalog/language/{ua,en,ru}`. Админка — украинский / английский.
- `config_language_main` = код основного языка (без префикса в URL).
- `config_language` = текущий выбранный код.
- `config_language_id` = текущий id (для `seo_url`).
- Разбор префикса: `public_html/catalog/controller/startup/multilang.php`:
    - читает первый сегмент `_route_`,
    - пишет язык в session/config/cookie,
    - снимает префикс с `_route_` до SEO.
- Сборка ссылок: `public_html/catalog/controller/startup/multilang_rewrite.php`:
    - основной язык — чистые URL,
    - остальные — `/{code}/...`.
- Hreflang: `public_html/catalog/controller/event/hreflang.php` через `system/library/multilang.php`.

По шагам запроса: `language-flow-session-config.md`.

# ROUTER

- Прокси старта: `public_html/catalog/controller/startup/seo_url.php`.
- Парсер и сборка URL: `public_html/system/library/custom/router.php`.
- Входящий URL:
    1. `startup.php` готовит язык.
    2. `multilang.php` снимает префикс с `_route_`.
    3. `seo_url.php` зовёт `prepareRoute(...)`.
    4. `router.php` резолвит keyword (`seo_url`) в параметры.
    5. `resolveFinalRoute()` ставит итоговый route (`product/category`, `product/product`, …).
- Исходящий URL:
    1. `router.php::rewrite()` собирает ЧПУ из route/query.
    2. `multilang_rewrite.php::rewrite()` добавляет префикс не-основному языку.
- После SEO `startup/pages` может заменить route на `error/not_found`, если семейство выкл (см. **TURN OFF USELESS PAGES**). Redirect Manager раньше, явный 301/410 побеждает.
- Canonical `validate()` — после регистрации всех rewrite.
- Источник маппинга: таблица `seo_url` (`query`, `keyword`, `store_id`, `language_id`).
- Настройки роутера (админка → настройки магазина):
    - `config_seo_url`
    - `config_seo_pro`
    - `config_seo_url_include_path`
    - `config_seo_url_cache`
    - `config_page_postfix`
    - `config_seopro_addslash`

# SEO

## META-DATA

- Шаблоны в рантайме, не генерация в БД. Ручные `meta_title` / `meta_description` / `meta_h1` у сущности всегда побеждают.
- В настройках магазина нет глобальных Title / Description / Keywords. Это была одна строка на все языки. Шаблоны: `catalog/language/{en,ua,ru}/seo/meta.php` (`{shop}` = имя магазина).
- Префиксы: `home`, `product`, `category`, `manufacturer`, `manufacturer_list`, `information`, `contact`, `special`, `search`, `compare`, `sitemap`, `blog`, `blog_category`, `blog_article`, `blog_author`, `not_found`, `gone`. У каждого `_title` / `_description` / `_h1`.
- Движок: `public_html/catalog/model/seo/meta.php`.
- `build($entity, $vars, $prefix, $route, $query)` возвращает данные: `title`, `description`, `h1`, `canonical`, `robots`.
- `apply($seo)` пишет title, description, canonical и robots в document. Контроллер сам ставит `$data['heading_title']` из `$seo['h1']`.
- Canonical: передайте `$route` / `$query`. Листинги их не передают и оставляют `addPaginationLinks()`.
- Robots: `noindex` сущности (при `config_noindex_status`) или явный `robots` (поиск, 404).
- Canonical + prev/next листингов: `catalog/model/product/helper.php::addPaginationLinks()`.
- Суффикс страницы (`title_page`) при `page > 1`.
- Description чистится от HTML и режется до 160 символов.
- `common/microdata` читает те же title/description document, OpenGraph следует сам.

## MICRO-DATA

- Точка входа: `public_html/catalog/controller/common/microdata.php`.
- В макете:
    - `public_html/catalog/controller/common/layout.php` подмешивает `microdata` через `common/microdata`.
    - `public_html/catalog/view/theme/default/template/common/layout.twig`:
        - `{{ microdata.head }}` в `<head>`,
        - `{{ microdata.body }}` перед `</body>`.
- Сейчас отдаётся:
    - блок OpenGraph (`microdata.head`);
    - Organization / WebSite / WebPage / BreadcrumbList JSON-LD (`microdata.body`). Логотип Organization — `image/favicon/web-app-manifest-512x512.png` (логотип шапки — спрайт темы);
    - Product JSON-LD на товаре;
    - ItemList JSON-LD на листингах;
    - FAQPage JSON-LD, если на странице видны вопросы FAQ (те же, что в аккордеоне).
- FAQ только JSON-LD. Не ставьте FAQ-микродату на HTML-аккордеон (дубль FAQPage).
- `common/footer` тоже зовёт `common/microdata`, но футер его не рисует; канонический вывод в `common/layout.twig`.

## FAQ

- Модуль: Админка → Дополнения → Модули → FAQ. Полный гайд: `faq.md`.
- Движок: `public_html/catalog/model/seo/faq.php`.
- Вкладки админки вешаются events (`extension/faq/event`), не OCMOD. Формы товара, категории, производителя получают вкладку **FAQ**.
- Настройки: глобально вкл/выкл, по типам, только первая страница листинга, общие вопросы типа, вкл/выкл на странице.
- Контроллеры `product/product`, `product/category`, `product/manufacturer` кладут `$data['faq']`.
- Витрина: товар после характеристик; категория / производитель после списка. На `page` 2+ скрыто, если включено **только первая страница**. Страницы с `filter=` FAQ не показывают.
- Вёрстка: `default/components/faq.twig` + `accordion.twig` (`<details>` / `<summary>`).
- Схема: `common/microdata` → `FAQPage` с `Question` / `acceptedAnswer`. Только JSON-LD.
- Плейсхолдеры (`{name}`, `{price}`, `{category}`, `{month}`, `{year}`, …) подставляются на витрине. Список: `faq.md`.
- Таблицы (`DB_PREFIX`): `faq`, `faq_description`, `faq_page`. Деинсталл не дропает данные, пока не включён **Delete FAQ data on uninstall**.

## IMPORT / EXPORT

- Модуль: Админка → Дополнения → Модули → Import / Export. Полный гайд: `import_export.md`.
- CSV/JSON товаров, категорий, производителей, групп характеристик и атрибутов. Это не SQL-дамп (`tool/backup`).
- Upsert сначала по **id** (`product_id`, `category_id`, …). `0` = всегда создать; без id = создать или найти по SKU / имени; отрицательные id — локальные алиасы файла, чтобы новая категория и товар связались в одном JSON. Товары линкуются через `category_ids` и `attribute_id`. Preview, затем запись через админские `add*` / `edit*`.
- Порядок JSON-бандла: производители → группы атрибутов → атрибуты → категории → товары. В скачанных JSON рядом с `version` есть поле `help` (в CSV — комментарий `#`).
- Картинки `http(s)` товара/категории/производителя качаются в `image/catalog/import/` (Cloudflare `/cdn-cgi/image/...` разворачивается к оригиналу). Пустые поля картинок пропускаются. Локальные пути `image/catalog/...` работают.
- CLI: `system/cli/import_export.php` (не с веба). Таблица (`DB_PREFIX`): `import_export_job`. Деинсталл не дропает историю, пока не включён **Delete job history on uninstall**.

## AUTO BACKUP

- Модуль: Админка → Дополнения → Модули → Auto Backup. Полный гайд: `backup.md`.
- Для **прода**, не Docker. Cron на живом хосте: `system/cli/auto_backup.php` (VPS) или `index.php?route=extension/auto_backup/cron&cron_token=...` (хостинг).
- Пакует MySQL + `image/catalog` (не кэш картинок) в `system/storage/backup/`, затем опционально Google Drive или FTP. Письмо — только отчёт.
- Таблица (`DB_PREFIX`): `auto_backup`. Деинсталл не дропает историю, пока не включён **Delete history on uninstall**.

## HEADINGS

H1 — тот же движок, что title и description: `public_html/catalog/model/seo/meta.php` (`build()` → `h1`). Второго сборщика заголовка нет.

- Приоритет:
    1. Поле сущности `meta_h1`, если заполнено (форма в админке).
    2. Шаблон языка `{prefix}_h1` в `catalog/language/*/seo/meta.php` (`product_h1`, `category_h1`, …) с `{name}`, `{shop}` и другими переменными.
    3. Запасной вариант: `$vars['name']` (имя товара / категории / статьи).
- `apply()` пишет title, description, canonical, robots в document. H1 **не** пишет. Контроллер: `$data['heading_title'] = $seo['h1']`; Twig рисует `<h1>`.
- `<title>` может отличаться от H1 (`meta_title` / `{prefix}_title` / `fallback_title`). Так задумано.
- Статические страницы (home, contact, sitemap, search, special, compare, 404, 410, индекс блога) берут title / description / H1 из `{prefix}_*` в `seo/meta.php`. Ручные поля сущности всё равно побеждают, где они есть.
- Суффикс пагинации (`title_page`) только в `<title>`, не в H1.

## 410 PAGE

Темированный ответ для URL, который убрали нарочно. Не путать с выключением семейства страниц (это 404).

- Route: `error/gone`. Контроллер: `public_html/catalog/controller/error/gone.php`.
- HTTP: `410 Gone`. Robots: `noindex,follow` через `seo/meta` (тот же `build()` / `apply()`). `$data['heading_title']` — H1 из этого билда.
- Шаблон: `catalog/view/theme/default/template/error/gone.twig`. Макет магазина (Дизайн → Макеты).
- Язык: `catalog/language/*/error/gone.php`.
- Кто ставит route: Redirect Manager 410 (`startup/redirect_manager`, Apache `ErrorDocument 410`, nginx `error_page 410`). Флаги витрины 410 не отдают.
- `.htaccess` магазина мапит Apache 410 на `/index.php?route=error/gone`, клиент видит темированную страницу, не пустую ошибку сервера.
- Гайд: `redirect_manager.md`.

## TEXT

## SITEMAP

- Два слоя:
    - HTML-карта: `public_html/catalog/controller/information/sitemap.php` (`route=information/sitemap`).
    - XML Sitemap (установлен и включён): `public_html/catalog/controller/extension/feed/sitemap.php` (`route=extension/feed/sitemap`). Заголовок в админке **XML Sitemap**. Старый канал `google_sitemap` убран.
- ЧПУ XML в `public_html/.htaccess`:
    - `/(lang/)?sitemap.xml`
    - `/(lang/)?sitemap-<branch>.xml`
    - внутри: `index.php?route=extension/feed/sitemap&language=<code>&type=<branch>`.
- Ветки XML:
    - `sitemap-main` — главная плюс статичные страницы (`information/contact`, HTML-карта, акции, список производителей, лента блога). URL нет, если семейство выкл. Это не CMS-статьи; они в `sitemap-information`.
    - `sitemap-categories`
    - `sitemap-products`
    - `sitemap-information`
    - `sitemap-manufacturers`
    - `sitemap-blog-categories`
    - `sitemap-blog-articles`
    - `sitemap-blog-authors`
- Данные: `public_html/catalog/model/extension/feed/sitemap.php`:
    - с учётом языка (`config_language_id`),
    - с учётом магазина при `feed_branched_sitemap_multishop`,
    - пути категорий/товаров/блога,
    - опциональные поля `noindex`.
- Файловый кэш в контроллере:
    - TTL: `86400` (`$cacheTtl`),
    - файлы `system/storage/cache/sitemap_<name>_store<id>_lang<id>.xml`.
- Ветки XML уважают флаги витрины (`Custom\Pages::sitemapAllowed()`). Если товары/категории/статьи/производители/блог выкл, ветки нет в индексе и она отдаёт 404. Статика внутри `sitemap-main` — по одному флагу (контакт выкл → нет URL контакта; главная остаётся).
- HTML-карта прячет те же семейства и сама может быть выключена.
- Кэш XML-индекса не сбрасывается при смене флага; ждите TTL или удалите `system/storage/cache/sitemap_*`.
- `robots.txt`: `Sitemap: https://site.com/sitemap.xml` (подставьте домен).

# DEV TOOLS

## PREETIER

- Конфиг: `.prettierrc`.
- Зависимости (`package.json`):
    - `prettier`
    - `@prettier/plugin-php`
    - `@zackad/prettier-plugin-twig`
- Зачем:
    - один стиль Twig + PHP;
    - чище диффы в PR;
    - меньше ручного форматирования.
- Скрипт:
    - `npm run prettier`
    - `prettier --write` по Twig/CSS/JS темы и PHP каталога.
- Правила:
    - `tabWidth: 4`
    - `singleQuote: true`
    - `printWidth: 120`
    - оверрайд Twig-парсера.

# SMS

Фасад исходящих SMS. Контроллеры зовут `$this->sms->send($phone, $message)`. Провайдер — класс шлюза, не модуль.

- Регистрируется в каталоге: `public_html/catalog/controller/startup/startup.php` (`new Sms($this->registry)`). В админке объекта нет.
- Фасад: `public_html/system/library/sms.php`. Телефон к цифрам; пустой телефон или текст → `false`.
- Интерфейс: `public_html/system/library/sms/gateway.php` (`Sms\Gateway::send()`).
- Шлюзы в `public_html/system/library/sms/gateway/`:
    - `log` — в лог OpenCart (дефолт, локальный Docker);
    - `turbosms` — HTTP API TurboSMS (`message/send.json`). Нужны токен и отправитель;
    - `http` — общий POST JSON; плейсхолдеры `{phone}` `{message}` `{sender}` `{token}`.
- Неизвестный `config_sms_gateway` падает в `log`.
- Админка: Система → Настройки → вкладка **SMS**. Выпадашка — glob `sms/gateway/*.php`.
- Сейчас зовёт вход по телефону: `public_html/system/library/custom/account.php` (`sendLoginCode()`). Текст — поле темы **Login SMS text** (`{code}`).
- Новый провайдер: класс в `sms/gateway/{code}.php` с `Sms\Gateway` плюс ключ `text_sms_gateway_{code}`. Гайд: `sms.md`.

# STRUCTURE LAYOUT/HEAD

- Макет централизован:
    - `public_html/catalog/controller/common/layout.php`
    - `public_html/catalog/view/theme/default/template/common/layout.twig`
- Порядок:
    1. `head`
    2. `header`
    3. `content` route
    4. `footer`
    5. `microdata.body` перед `</body>`.
- Данные head: `public_html/catalog/controller/common/head.php`.
- Вёрстка head: `public_html/catalog/view/theme/default/template/common/head.twig`.
- В head:
    - meta/title/robots/keywords/description,
    - canonical/hreflang,
    - пак favicon (`image/favicon/`, не одна иконка из админки),
    - логотип шапки из спрайта (`assets/icons/sprite.svg#logo`),
    - блоки аналитики,
    - скрипты/стили шапки (с опциональным minifier).
- Зачем так:
    - одна оболочка на все route;
    - одно место для сквозного SEO/meta;
    - контроллер готовит данные, Twig рисует HTML.

# SECURED ADMIN URL

# TURN OFF USELESS PAGES

Флаги на этапе разработки для целых семейств route. Выкл = route не существует (`404` / `error/not_found`). Это не 410: если проиндексированные URL надо снять позже — правило Redirect Manager.

- Админка: Система → Настройки → Опции → **Сторінки вітрини** / **Сторінки облікового запису**.
- Нет ключа в `oc_setting` = включено. Первое сохранение формы пишет ключи.
- Карта семейств: `public_html/system/library/custom/pages.php` (`Custom\Pages::families()`).
- Перехват: `public_html/catalog/controller/startup/pages.php`, в `public_html/system/config/catalog.php` после `startup/seo_url` и `startup/redirect_manager`.
- Поток:
    1. SEO резолвит keyword в route (`product/product`, `information/information`, …).
    2. Redirect Manager может 301/410.
    3. Если семейство выкл, `route` = `error/not_found`. URL в браузере не меняется (нет редиректа на `/not-found`).
- Совпадение по префиксу: `product/product` закрывает и `product/product/review`. `except` оставляет метод живым (`information/information/agree` для оферты на чекауте/регистрации).
- Связки:
    - **product** гасит сравнение, поиск, акции и live search (`common/search`).
    - **blog** гасит все `blog/*` (статья, категория, лента, меню).
    - **manufacturer** — список + карточка (`product/manufacturer`).
- Флаги кабинета — по страницам (`account/edit`, `account/order`, …). Всегда вкл: вход, выход, забытый/сброс пароля, дашборд `account/account`, корзина, чекаут.
- XML/HTML sitemap, футер, поиск в шапке, меню категорий, меню блога и ссылки кабинета следуют тем же флагам (`$pages->flags()` / `$pages->enabled(...)`).
- Виджеты макета — не выключатель. Мёртвые ссылки модуля могут остаться, если модуль всё ещё на макете.
- Новое семейство: запись в `families()`, ключи `entry_pages_*` / `help_pages_*` в админском `setting/setting.php`. Пункты кабинета — `'group' => 'account'`. Опционально `'sitemap'` — имя или список веток XML.

## Minifier (тема Tailwind)

- Склейка локальных CSS/JS: `system/library/custom/minifier.php`.
- Ассеты темы регистрируются в контроллерах, не в Twig: `addStyle(..., true)` / `addScript(..., true)` prepend (`Document`). `tailwind.css` в head; jQuery + `script.js` в футере (jQuery первым).
- Подряд идущие локальные файлы становятся одним бандлом; CDN/`http(s):`/`//` остаются на месте и не склеиваются.
- JS/CSS склеиваются и минифицируются (комментарии и пробелы вне строк/шаблонов). Уже `*.min.js` / `*.min.css` склеиваются как есть.
- Пути `url()` в CSS переписываются относительно `image/cache/minifier/<store_id>/`.
- Бандлы: `public_html/image/cache/minifier/<store_id>/bundle-<hash>.{css,js}`.
- Админка: Система → Настройки → Сервер → **Об’єднання CSS/JS** (`config_minifier`). С инсталла вкл. Нет ключа = выкл.
- HTML не минифицируется.

# WEBP IMAGES

- Реализация: `public_html/catalog/model/tool/image.php` (`resize()`).
- Поток:
    - классический кэш в `image/cache/...`;
    - если можно — WebP в `image/cache/webp/...`.
- WebP только когда:
    - GD умеет `imagewebp`;
    - в `HTTP_ACCEPT` есть `image/webp`.
- Пересборка:
    - WebP заново, если нет или старше кэша-источника.
- Движок `public_html/system/library/image.php`:
    - читает WebP (`imagecreatefromwebp`);
    - пишет WebP (`imagewebp`);
    - сохраняет прозрачность PNG/WebP.
- Зачем:
    - быстрее картинки в современных браузерах;
    - откат на JPEG/PNG/GIF у остальных;
    - шаблоны с `model_tool_image->resize()` менять не нужно.

# CHECKOUT

Одностраничный чекаут на `checkout/checkout`. Старый аккордеон — `checkout_old`.

- **Админка:** Дополнения → Модули → Checkout или Продажи → Оформление. Настройки в `module_checkout_*`, не в теме.
- **Вкладки:** Поля (минимум заказа, показ / обязательность), Доставка (заголовок, текст под методом, прятать адрес), Оплата (заголовок, текст, с какими доставками видна). Купон по-прежнему Учёт заказа → Купон.
- Каталог: `module_checkout_fields` / `module_checkout_min_total` / `module_checkout_shipping` / `module_checkout_payment` через `catalog/model/checkout/setting.php`. Самовывоз прячет адрес, пока не сменят этот чекбокс. Котировка с `extra` всегда прячет общий блок адреса, даже если чекбокс выкл. Нулевая стоимость доставки прячется только в списке методов, не в итогах корзины.
- **Confirm:** `checkout/confirm/save` валидирует поля, создаёт заказ. COD / банк / cheque / free_checkout идут на `checkout/success`. Остальные (LiqPay) остаются со статусом 0 и вставляют HTML оплаты в `#checkout-payment-form`.
- Права: `extension/module/checkout` и `extension/checkout`.
- **Extra перевозчика:** котировка может поставить `extra` в catalog route (`index.php?route=extension/shipping/{code}/extra`). Чекаут рисует это в `#checkout-shipping-extra` и сам прячет общий адрес. Виджет пишет `city` / `address_1` / `address_2` / `shipping_ref`. Поиск остаётся на модуле доставки (`.../search`). Так же для Укрпошти и других; не складывайте перевозчиков в общий shippingdata.

# NOVA POSHTA

Тонкий модуль доставки (без ТТН, лицензии и Ocmax). Админка: Дополнения → Доставка → Nova Poshta.

- **Общие:** статус, сорт, API-ключ, методы (отделение / поштомат / курьер) с именем, ценой, геозоной, налоговым классом.
- **База:** синхронизация областей, городов, отделений в `oc_novaposhta_*`. Опциональный онлайн-поиск населённых пунктов и улиц (курьер).
- Extra на чекауте: город + отделение/поштомат или город + улица + дом для курьера. Поставьте модуль один раз, чтобы создались таблицы.

# REFACTOR ADMIN INTERFACE

# MD FILES FOR ADMIN PANEL

# SQLITE FOR FAST SETUP
