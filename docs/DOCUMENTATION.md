# Project Philosophy

OpenCart (ocStore) is the engine. Tailwind and Docker are the kit around it. The platform is legacy on purpose: that is the starting point, not a reason to pile modules onto a live shop.

- One shared core instead of per-shop forks: markup, sitemap, languages, bundling, images, components, SEO.
- Work happens here, not on production. Same stack, architecture, and code style while you write.
- Styles do not accumulate as a dead layer. Components live next to their look (`public_html/catalog/view/theme/default/components/`) and leave with it.
- Custom catalog code lives in `catalog/controller` / `catalog/model` / `system/library/custom`.
- SEO is first-class: meta, H1, sitemap, hreflang, 410, FAQ schema — not an afterthought module.
- Turn page types off with route flags. Do not pretend layout widgets are kill-switches.
- Indexed URLs that must disappear use Redirect Manager (301/410), not a silent 404.

# DOCKER

- Compose stack is split into 3 services:
    - `mysql` (`mysql:5.7`) for OpenCart-compatible DB behavior.
    - `php` (custom `php:7.4-apache`) for app runtime.
    - `phpmyadmin` for quick DB inspection/debug.
- Why this setup is good:
    - reproducible environment for all developers;
    - code is mounted (`./public_html:/var/www/html`), so PHP/Twig changes apply instantly;
    - DB persistence via `mysql_data` volume;
    - isolated network (`ocnet`) with stable service hostnames.
- PHP image (`docker/php/Dockerfile`) enables required features:
    - `gd` with JPEG/PNG/WebP;
    - `mysqli`, `zip`, `opcache`;
    - Apache `mod_rewrite` for SEO routes and sitemap rewrite rules.
- Runtime tuning is in `docker/php/php.ini` (upload size, memory limit, opcache behavior).
- Apache vhost (`docker/php/000-default.conf`) has `AllowOverride All`, so project `.htaccess` rules are active.

# INSTALL

Step 3 of `/install` asks for starter content. Schema and system seed always run. Demo files are optional.

- SQL lives in `public_html/install/sql/`:
    - `schema.sql` — `DROP` / `CREATE` for every table (also used by upgrade `1000.php`).
    - `system/*.sql` — locales, statuses, tax, events, core extensions, settings, layout names/routes, legal pages, system SEO keywords. Always applied.
    - `demo/*.sql` — catalog, blog, banners, carousel/featured modules, layout module placements, demo SEO, demo coupons. Applied only when **Demo catalog** is selected.
- **Empty store** (default): no products, categories, manufacturers, blog posts, banners or homepage modules. Layout records stay (Home, Product, 404, 410, …) with routes, but without carousel/featured placements. Account column on account layouts stays. Modifications are status `0`. Blog menu and the blog page family (`config_pages_blog`) are off. Turn blog back on in System → Settings → Option when you need it.
- **Demo catalog**: the ocStore sample shop (products, blog, carousels).
- CLI: `php install/cli_install.php install … --sample_data 0` (empty) or `--sample_data 1` (demo).
- System extensions always installed: default theme, COD, flat shipping, order totals, dashboards, reports, ECB currency, XML Sitemap, account module. Google Shopping and NBU currency are not installed (still available from admin). Carousel / featured / blog modules are demo-only (still available to install later from admin).
- Store languages: Ukrainian (main), English, Russian. `config_seo_url`, `config_seo_pro`, and `config_minifier` (CSS/JS bundling) are on by default.
- Default API user is created and enabled (`status = 1`) with `127.0.0.1` / `::1` (and the installer IP) allowed. Admin order editing needs this.
- Store timezone is `Europe/Kiev` (valid on PHP 7.4; `Europe/Kyiv` is not). Invalid IDs are mapped or fall back to UTC.

# LANGUAGE

- Multi-language flow starts in `public_html/catalog/controller/startup/startup.php`.
- Install seeds Ukrainian (`ua`, main), English (`en`), and Russian (`ru`). Storefront folders: `catalog/language/{ua,en,ru}`. Admin UI stays Ukrainian / English.
- `config_language_main` = main language code (no URL prefix on main language pages).
- `config_language` = current selected language code.
- `config_language_id` = current selected language ID (used in `seo_url` lookups).
- URL prefix parsing happens in `public_html/catalog/controller/startup/multilang.php`:
    - reads first segment from `_route_`,
    - applies language to session/config/cookie,
    - strips prefix from `_route_` before SEO route resolving.
- URL prefix generation for links happens in `public_html/catalog/controller/startup/multilang_rewrite.php`:
    - main language keeps clean URLs,
    - non-main language gets `/{code}/...`.
- Hreflang links are built in `public_html/catalog/controller/event/hreflang.php` through `system/library/multilang.php`.

# ROUTER

- Startup proxy for routing/rewrite is `public_html/catalog/controller/startup/seo_url.php`.
- Core route parser and URL builder is `public_html/system/library/custom/router.php`.
- Incoming URL resolving path:
    1. `startup.php` prepares language config.
    2. `multilang.php` removes language prefix from `_route_`.
    3. `seo_url.php` calls custom router `prepareRoute(...)`.
    4. `router.php` resolves keywords (`seo_url`) to route params.
    5. `resolveFinalRoute()` sets final route (`product/category`, `product/product`, etc.).
- Outgoing URL generation path:
    1. `router.php::rewrite()` builds SEO path from route/query.
    2. `multilang_rewrite.php::rewrite()` adds language prefix for non-main language.
- After SEO resolve, `startup/pages` may replace the route with `error/not_found` if that page family is off (see **TURN OFF USELESS PAGES**). Redirect Manager runs first, so an explicit 301/410 still wins.
- Canonical validation (`validate()`) must run after all rewrites are registered.
- Main DB source for SEO mapping: table `seo_url` (`query`, `keyword`, `store_id`, `language_id`).
- Important router settings (admin -> store settings):
    - `config_seo_url`
    - `config_seo_pro`
    - `config_seo_url_include_path`
    - `config_seo_url_cache`
    - `config_page_postfix`
    - `config_seopro_addslash`

# SEO

## META-DATA

- Runtime templates, not DB generation. Manual `meta_title` / `meta_description` / `meta_h1` on the entity always win.
- Store settings have no global Title / Description / Keywords. Those were one string for every language. Templates live in `catalog/language/{en,ua,ru}/seo/meta.php` (`{shop}` = store name).
- Prefixes: `home`, `product`, `category`, `manufacturer`, `manufacturer_list`, `information`, `contact`, `special`, `search`, `compare`, `sitemap`, `blog`, `blog_category`, `blog_article`, `blog_author`, `not_found`, `gone`. Each has `_title` / `_description` / `_h1`.
- Engine: `public_html/catalog/model/seo/meta.php`.
- `build($entity, $vars, $prefix, $route, $query)` only returns data: `title`, `description`, `h1`, `canonical`, `robots`.
- `apply($seo)` writes title, description, canonical and robots to the document. The controller still assigns `$data['heading_title']` from `$seo['h1']`.
- Canonical: pass `$route` / `$query`. Listing pages omit them and keep `addPaginationLinks()`.
- Robots: entity `noindex` (with `config_noindex_status`) or explicit `robots` (search, 404).
- Listing canonical + prev/next stay in `catalog/model/product/helper.php::addPaginationLinks()`.
- Page suffix (`title_page`) is appended when `page > 1`.
- Description is stripped of HTML and trimmed to 160 characters.
- `common/microdata` reads the same `document` title/description, so OpenGraph follows automatically.

## MICRO-DATA

- Main entry point is `public_html/catalog/controller/common/microdata.php`.
- Layout integration:
    - `public_html/catalog/controller/common/layout.php` injects `microdata` via `common/microdata`.
    - `public_html/catalog/view/theme/default/template/common/layout.twig` renders:
        - `{{ microdata.head }}` inside `<head>`,
        - `{{ microdata.body }}` before `</body>`.
- Current active output:
    - OpenGraph meta block (`microdata.head`).
    - Organization / WebSite / WebPage / BreadcrumbList JSON-LD (`microdata.body`). Organization `logo` is `image/favicon/web-app-manifest-512x512.png` (header logo is the theme sprite, not a settings image).
    - Product JSON-LD on product pages.
    - ItemList JSON-LD on listing pages.
    - FAQPage JSON-LD when the page has visible FAQ items (same questions as the accordion).
- FAQ is JSON-LD only. Do not add FAQ microdata attributes on the HTML accordion (duplicate FAQPage).
- `common/footer` also calls `common/microdata`, but footer template does not render it directly; canonical rendering happens in `common/layout.twig`.

## FAQ

- Module: Admin → Extensions → Extensions → Modules → FAQ. Full notes: `docs/faq.md`.
- Engine: `public_html/catalog/model/seo/faq.php`.
- Admin tabs are injected with events (`extension/faq/event`), not OCMOD. Catalog forms (product, category, manufacturer) get a **FAQ** tab.
- Settings: global on/off, per-type on/off, first listing page only, shared questions for a type, per-page on/off.
- Controllers `product/product`, `product/category`, `product/manufacturer` attach `$data['faq']`.
- Storefront: product after attributes; category / manufacturer after the product list. Hidden on listing page 2+ when **First listing page only** is on. Filtered category pages (`filter=`) skip FAQ.
- Markup: `default/components/faq.twig` + `accordion.twig` (`<details>` / `<summary>`).
- Schema: `common/microdata` → `FAQPage` with `Question` / `acceptedAnswer`. JSON-LD only — do not add FAQ microdata on the HTML (duplicate FAQPage).
- Placeholders (`{name}`, `{price}`, `{category}`, `{month}`, `{year}`, …) are replaced on the storefront. Full list: `docs/faq.md`.
- Tables (`DB_PREFIX`): `faq`, `faq_description`, `faq_page`. Uninstall does not drop data unless **Delete FAQ data on uninstall** is on.

## IMPORT / EXPORT

- Module: Admin → Extensions → Extensions → Modules → Import / Export. Full notes: `docs/import_export.md`.
- CSV/JSON for products, categories, manufacturers, attribute groups and attributes. Not the SQL dump (`tool/backup`).
- Upsert by **id** first (`product_id`, `category_id`, …). `0` = always create; omit id = create or match SKU / name; negative ids are file-local aliases so a new category and product can link in one JSON bundle. Products link with `category_ids` and `attribute_id`. Preview, then write through admin `add*` / `edit*` models.
- JSON bundle import order: manufacturers → attribute groups → attributes → categories → products.
- `http(s)` product/category/manufacturer images are downloaded into `image/catalog/import/` on import. Local `image/catalog/...` paths still work.
- CLI: `system/cli/import_export.php` (not web-accessible). Table (`DB_PREFIX`): `import_export_job`. Uninstall does not drop history unless **Delete job history on uninstall** is on.

## AUTO BACKUP

- Module: Admin → Extensions → Extensions → Modules → Auto Backup. Full notes: `docs/backup.md`.
- For **production**, not Docker. Cron on the live host: `system/cli/auto_backup.php` (VPS) or `index.php?route=extension/auto_backup/cron&cron_token=...` (shared hosting).
- Packs MySQL + `image/catalog` (not image cache) into `system/storage/backup/`, then optional Google Drive or FTP. Email is a status report only.
- Table (`DB_PREFIX`): `auto_backup`. Uninstall does not drop history unless **Delete history on uninstall** is on.

## HEADINGS

H1 is the same engine as title and description: `public_html/catalog/model/seo/meta.php` (`build()` → `h1`). There is no second heading builder.

- Priority:
    1. Entity field `meta_h1` if filled (admin catalog form).
    2. Language template `{prefix}_h1` in `catalog/language/*/seo/meta.php` (`product_h1`, `category_h1`, …) with `{name}`, `{shop}`, and other vars.
    3. Fallback: `$vars['name']` (product / category / information title).
- `apply()` writes title, description, canonical, robots to the document. It does **not** write H1. The controller sets `$data['heading_title'] = $seo['h1']`; Twig renders `<h1>`.
- `<title>` can differ from H1 (`meta_title` / `{prefix}_title` / `fallback_title`). That is intended.
- Static pages (home, contact, sitemap, search, special, compare, 404, 410, blog index) take title / description / H1 from `{prefix}_*` in `seo/meta.php`. Manual entity fields still win where they exist.
- Pagination suffix (`title_page`) applies to `<title>` only, not H1.

## 410 PAGE

Themed response for URLs removed on purpose. Not the same as turning a page family off (that is 404).

- Route: `error/gone`. Controller: `public_html/catalog/controller/error/gone.php`.
- HTTP: `410 Gone`. Robots: `noindex,follow` via `seo/meta` (same `build()` / `apply()` as other pages). `$data['heading_title']` is the H1 from that build.
- Template: `catalog/view/theme/default/template/error/gone.twig`. Uses the shop layout (Design → Layouts).
- Language: `catalog/language/*/error/gone.php`.
- Who sets the route: Redirect Manager 410 (`startup/redirect_manager`, Apache `ErrorDocument 410`, nginx `error_page 410`). Storefront page flags never send 410.
- Shop `.htaccess` maps Apache 410 to `/index.php?route=error/gone`, so the client sees the themed page, not a blank server error.
- Docs: `docs/redirect_manager.md`.

## TEXT

## SITEMAP

- There are two sitemap layers:
    - HTML page sitemap: `public_html/catalog/controller/information/sitemap.php` (`route=information/sitemap`).
    - XML Sitemap feed (installed and on by default): `public_html/catalog/controller/extension/feed/sitemap.php` (`route=extension/feed/sitemap`). Admin heading is **XML Sitemap**. The old `google_sitemap` feed is removed.
- Rewrite to human-readable XML URLs is in `public_html/.htaccess`:
    - `/(lang/)?sitemap.xml`
    - `/(lang/)?sitemap-<branch>.xml`
    - internally mapped to `index.php?route=extension/feed/sitemap&language=<code>&type=<branch>`.
- XML sitemap branches generated by controller:
    - `sitemap-main` — home plus static storefront pages (`information/contact`, HTML sitemap, specials, manufacturer list, blog latest). Each URL is omitted when that page family is off. These are not CMS information rows; those stay in `sitemap-information`.
    - `sitemap-categories`
    - `sitemap-products`
    - `sitemap-information`
    - `sitemap-manufacturers`
    - `sitemap-blog-categories`
    - `sitemap-blog-articles`
    - `sitemap-blog-authors`
- Data provider is `public_html/catalog/model/extension/feed/sitemap.php`:
    - language-aware (`config_language_id`),
    - store-aware when `feed_branched_sitemap_multishop` is enabled,
    - supports category/product/blog paths,
    - checks optional `noindex` fields.
- File cache is enabled in controller:
    - TTL: `86400` seconds (`$cacheTtl`),
    - files like `system/storage/cache/sitemap_<name>_store<id>_lang<id>.xml`.
- XML branches also respect storefront page flags (`Custom\Pages::sitemapAllowed()`). If products/categories/information/manufacturers/blog are off, those branches are omitted from the index and return 404. Static URLs inside `sitemap-main` follow the same flags one by one (contact off → no contact URL; home stays).
- HTML sitemap (`information/sitemap`) hides the same families and can itself be turned off.
- Cached XML index is not flushed when a page flag changes; wait for TTL or delete `system/storage/cache/sitemap_*`.
- `robots.txt` points search engines to sitemap entry via `Sitemap: https://site.com/sitemap.xml` (replace domain in real env).

# DEV TOOLS

## PREETIER

- Formatter config is in `.prettierrc`.
- Installed formatter dependencies (`package.json`):
    - `prettier`
    - `@prettier/plugin-php`
    - `@zackad/prettier-plugin-twig`
- Why this is useful:
    - one style for Twig + PHP files;
    - cleaner diffs in PRs;
    - less manual formatting work.
- Script:
    - `npm run prettier`
    - executes: `prettier --write "public_html/catalog/view/theme/default/**/*.twig"`.
- Active style rules:
    - `tabWidth: 4`
    - `singleQuote: true`
    - `printWidth: 120`
    - Twig parser override with project-specific options.

# SMS

Facade for outbound SMS. Controllers call `$this->sms->send($phone, $message)`. Provider is a gateway class, not a module.

- Registered on the catalog registry in `public_html/catalog/controller/startup/startup.php` (`new Sms($this->registry)`). Not loaded in admin.
- Facade: `public_html/system/library/sms.php`. Phone is reduced to digits; empty phone or message returns `false`.
- Gateway interface: `public_html/system/library/sms/gateway.php` (`Sms\Gateway::send()`).
- Built-in gateways in `public_html/system/library/sms/gateway/`:
    - `log` — writes to the OpenCart log (default, local Docker).
    - `turbosms` — TurboSMS HTTP API (`message/send.json`). Needs token and sender.
    - `http` — generic POST JSON; URL/body placeholders `{phone}` `{message}` `{sender}` `{token}`.
- Unknown `config_sms_gateway` falls back to `log`.
- Admin: System → Settings → tab **SMS** (`config_sms_gateway`, `config_sms_sender`, `config_sms_http_token`, `config_sms_http_url`, `config_sms_http_body`). Dropdown is a glob of `sms/gateway/*.php`.
- Current caller: phone login in `public_html/system/library/custom/account.php` (`sendLoginCode()`). Message template is theme **Login SMS text** (`{code}`).
- New provider: PHP class in `sms/gateway/{code}.php` implementing `Sms\Gateway`, plus language key `text_sms_gateway_{code}`. Full notes: `docs/sms.md`.

# STRUCTURE LAYOUT/HEAD

- Layout flow is centralized in:
    - `public_html/catalog/controller/common/layout.php`
    - `public_html/catalog/view/theme/default/template/common/layout.twig`
- Render order in layout:
    1. `head`
    2. `header`
    3. route `content`
    4. `footer`
    5. `microdata.body` before `</body>`.
- Head data aggregation is in `public_html/catalog/controller/common/head.php`.
- Head markup is in `public_html/catalog/view/theme/default/template/common/head.twig`.
- Head includes:
    - meta/title/robots/keywords/description,
    - canonical/hreflang links,
    - favicon pack (`image/favicon/`, not a single admin icon),
    - header logo from the theme sprite (`assets/icons/sprite.svg#logo`),
    - analytics blocks,
    - header scripts/styles (with optional minifier output).
- Why this structure is good:
    - one reusable page shell for all routes;
    - easy place for cross-cutting SEO/meta logic;
    - clear separation: controller prepares data, Twig renders HTML.

# SECURED ADMIN URL

# TURN OFF USELESS PAGES

Dev-time flags for whole route families. Off = the route does not exist (`404` / `error/not_found`). Not a 410: if indexed URLs must be dropped later, add a Redirect Manager rule.

- Admin: System → Settings → Option → **Сторінки вітрини** / **Сторінки облікового запису**.
- Missing `oc_setting` key = enabled. First save of the settings form writes the keys.
- Map of families: `public_html/system/library/custom/pages.php` (`Custom\Pages::families()`).
- Request intercept: `public_html/catalog/controller/startup/pages.php`, registered in `public_html/system/config/catalog.php` after `startup/seo_url` and `startup/redirect_manager`.
- Flow:
    1. SEO resolves the keyword to a route (`product/product`, `information/information`, …).
    2. Redirect Manager may 301/410 the URL.
    3. If the family is off, `route` is set to `error/not_found`. The browser URL stays the same (no redirect to `/not-found`).
- Matching is prefix-based: `product/product` also blocks `product/product/review`. `except` keeps a method alive (`information/information/agree` for checkout/register terms).
- Bundles:
    - **product** also turns off compare, search, special, and live search (`common/search`).
    - **blog** turns off every `blog/*` route (article, category, latest, menu).
    - **manufacturer** is list + info (`product/manufacturer` prefix).
- Account flags are per page (`account/edit`, `account/order`, …). Always on: login, logout, forgotten/reset, dashboard `account/account`, cart, checkout.
- XML/HTML sitemaps, footer, header search, category menu, blog menu, and account dashboard links follow the same flags (`$pages->flags()` / `$pages->enabled(...)`).
- Layout widgets (column modules) are not a kill-switch. Dead module links can remain if the module is still assigned to a layout.
- Adding a family: entry in `families()`, language keys `entry_pages_*` / `help_pages_*` in admin `setting/setting.php`. Account items use `'group' => 'account'`. Optional `'sitemap'` string or list of XML branch names.

## Minifier (Tailwind theme)

- Runtime concat for local CSS/JS: `system/library/custom/minifier.php`.
- Theme assets are registered in controllers, not Twig: `addStyle(..., true)` / `addScript(..., true)` prepends (`Document`). `tailwind.css` in head; jQuery + `script.js` in footer (jQuery first).
- Consecutive local files become one bundle; CDN/`http(s):`/`//` stay in original order and are not concatenated.
- JS/CSS are concatenated and minified (comments and whitespace outside strings/templates). Already `*.min.js` / `*.min.css` are concatenated as-is.
- CSS `url()` paths are rewritten relative to `image/cache/minifier/<store_id>/`.
- Bundles: `public_html/image/cache/minifier/<store_id>/bundle-<hash>.{css,js}`.
- Admin: System → Settings → Server → **Об’єднання CSS/JS** (`config_minifier`). On by default at install. Missing key = off.
- HTML is not minified.

# WEBP IMAGES

- Implemented in `public_html/catalog/model/tool/image.php` (`resize()`).
- Resize flow:
    - generate classic cached image in `image/cache/...`;
    - if supported, generate WebP in `image/cache/webp/...`.
- WebP is used only when:
    - PHP GD supports `imagewebp`;
    - browser `HTTP_ACCEPT` contains `image/webp`.
- Rebuild logic:
    - regenerate WebP if missing or outdated vs cached source image.
- Engine support in `public_html/system/library/image.php`:
    - reads WebP (`imagecreatefromwebp`);
    - writes WebP (`imagewebp`);
    - preserves transparency handling for PNG/WebP.
- Why this is good:
    - faster image delivery on modern browsers;
    - automatic fallback to JPEG/PNG/GIF on unsupported clients;
    - no template changes where `model_tool_image->resize()` is already used.

# CHECKOUT

One-page checkout on `checkout/checkout`. Old accordion checkout is under `checkout_old`.

- **Admin:** Extensions → Modules → Checkout, or Sales → Checkout. Settings live in `module_checkout_*`, not the theme.
- **Tabs:** Fields (minimum order amount, show / required), Shipping (checkout title, text under the method, hide address), Payment (checkout title, text under the method, which shipping methods it appears with). Coupon is still Order Totals → Coupon.
- Catalog: `module_checkout_fields` / `module_checkout_min_total` / `module_checkout_shipping` / `module_checkout_payment` via `catalog/model/checkout/setting.php`. Pickup hides the address until that checkbox is changed. A quote with `extra` always hides the generic address block, even if the checkbox is off. Zero shipping cost is hidden in the method list only, not in cart totals.
- **Confirm:** `checkout/confirm/save` validates fields, creates the order. COD / bank / cheque / free_checkout go to `checkout/success`. Other methods (LiqPay) keep status 0 and inject the payment HTML into `#checkout-payment-form`.
- Permissions: `extension/module/checkout` and `extension/checkout`.
- **Carrier extra:** a quote may set `extra` to a catalog route (`index.php?route=extension/shipping/{code}/extra`). Checkout renders it in `#checkout-shipping-extra` and hides the generic address automatically. The extra widget writes `city` / `address_1` / `address_2` / `shipping_ref`. Search stays on that shipping module (`.../search`). Repeat this for Ukrposhta and others; do not add carriers to a shared shippingdata file.

# NOVA POSHTA

Slim shipping module (no TTN, license, or Ocmax). Admin: Extensions → Shipping → Nova Poshta.

- **General:** status, sort, API key, methods (warehouse / locker / courier) with name, cost, geo zone, tax class.
- **Database:** sync regions, cities, warehouses into `oc_novaposhta_*`. Optional online search for settlements and streets (courier).
- Checkout extra: city + warehouse/locker, or city + street + building for courier. Install the module once so tables are created.

# REFACTOR ADMIN INTERFACE

# MD FILES FOR ADMIN PANEL

# SQLITE FOR FAST SETUP
