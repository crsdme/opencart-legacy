# Store setup

Walkthrough for a new shop: install, settings, modules, pages, catalog, SEO, then how the theme is wired. Deeper engine notes stay in `DOCUMENTATION.md`. Module-specific guides: `faq.md`, `redirect_manager.md`, `import_export.md`, `backup.md`, `sms.md`.

Grey dashed boxes are screenshot slots. Drop PNG files into `docs/images/getting-started/` using the filename on the box. The page picks them up on reload.

## Install

Two ways to reach `/install/`. The wizard after that is the same. Pick one.

### Development (Docker)

Local kit only. Do not run `docker-compose` on the live host.

```bash
docker-compose up -d --build
```

Empty `public_html/config.php` and `public_html/admin/config.php` if they already have DB constants (a previous install). Then open `http://localhost:8080/install`. phpMyAdmin is `http://localhost:8081`.

The PHP image already has GD (WebP), mysqli, zip, OpenSSL, rewrite. Step 2 of the wizard should be all green.

### Production (live host)

Apache + PHP 7.4 + MySQL 5.7 (or MariaDB with the same SQL mode). Document root is `public_html/`, not the repo root. Do not deploy the Docker stack.

1. Upload `public_html/` (or `npm run git:deploy` if you use the `deploy` subtree). Compiled `tailwind.css` goes with the theme — the server does not run `npm run dev`.
2. Copy `config-dist.php` → `config.php` and `admin/config-dist.php` → `admin/config.php`. Both must be writable for the installer.
3. Copy `.htaccess.example` → `.htaccess` if the file is missing. HTTPS www→non-www rules already skip localhost; they apply on a real domain.
4. Create an empty MySQL database in the hosting panel. Note host, name, user, password (host is often `localhost`).
5. PHP extensions: GD with WebP, mysqli, zip, OpenSSL, mbstring, curl, json. Folders `image/`, `image/cache/`, `image/catalog/`, `system/storage/` (cache, logs, download, modification, session, upload) must be writable.
6. Open `https://your-domain/install/`.

After the wizard: delete or block the `install/` folder. Uncomment the `/secureadmin` block in `.htaccess` to hide `/admin`. Set SMTP, then Auto Backup cron (`backup.md`) — not Docker.

![Installer start at /install/](images/getting-started/01-install-start.png)

### Step 1 — License

Accept the GPL. Nothing to configure.

![Install step 1: license](images/getting-started/02-install-license.png)

### Step 2 — Requirements

All rows green. If `config.php` is missing, copy it from `config-dist.php` (same for `admin/config.php`) and make both writable.

![Install step 2: PHP and folders](images/getting-started/03-install-requirements.png)

### Step 3 — Database and admin

**Development (Docker):**

| Field | Value |
| --- | --- |
| Driver | MySQLi |
| Hostname | `mysql` or `mysql-db` (compose alias) |
| Username | `opencart` |
| Password | `opencart` |
| Database | `opencart` |
| Port | `3306` |
| Prefix | `oc_` |

**Production:** same fields, values from the hosting panel. Hostname is usually `localhost`, not `mysql`. Use a strong prefix only if the database is shared; otherwise `oc_` is fine.

Then create the admin user.

![Install step 3: database form](images/getting-started/04-install-database.png)

**Starter content** is the important choice:

- **Empty store** (default for a real shop). Schema, locales, statuses, legal pages, layout *names*, core extensions. No products, categories, blog, banners, or homepage carousels. Blog storefront pages stay off.
- **Demo catalog** — sample products, blog, banners, homepage carousel / latest / special / blog_latest. Use this to learn the admin, not as a production seed.

![Install step 3: Empty store vs Demo catalog](images/getting-started/05-install-starter.png)

CLI equivalent: `php install/cli_install.php install … --sample_data 0` or `--sample_data 1`. Details: `DOCUMENTATION.md`.

After install, SEO URLs, SeoPro, and CSS/JS bundling (`config_minifier`) are on. Store languages: Ukrainian (main), English, Russian. Timezone `Europe/Kiev`.

### Step 4 — Done

Open the catalog and the admin.

- **Development:** you can leave `/install/` in Docker.
- **Production:** delete or block `install/` immediately. Confirm HTTPS, minifier on, error display off.

![Install step 4: finished, admin login](images/getting-started/06-install-done.png)

---

## Admin and store settings

Admin: **System → Settings** (`route=setting/setting`). Save once even if you change nothing — that writes the storefront page-flag keys.

![Admin dashboard after login](images/getting-started/07-admin-dashboard.png)

### General

Theme **Default**. Default layout **За замовчуванням** unless you want every unmatched route to use Home.

### Store

Fill **name, owner, address, email, phone**. These feed the footer, contact page, mail, and Organization JSON-LD. Logo in the header is the theme sprite, not this form — do not hunt for a header image here.

![Settings → Store: name, contacts](images/getting-started/08-settings-store.png)

### Local

Country Ukraine, zone, timezone `Europe/Kiev`. Length/weight if you ship by size. Currency UAH; auto-update only if you enabled a currency engine (ECB is installed, NBU is available but not installed).

Main language should stay Ukrainian unless the shop is English-first. Non-main languages get a URL prefix (`/en/…`, `/ru/…`). See `language-flow-session-config.md`.

![Settings → Local: language and currency](images/getting-started/09-settings-local.png)

### Option — catalog and checkout

| Setting | Recommendation |
| --- | --- |
| Product limit (storefront) | 16–24. Higher lists are slower. |
| Description length on listings | Short; the card does not need the full HTML. |
| Product count in categories | **Off** on large catalogs (extra COUNT per category). |
| Reviews | On only if you will moderate them. Guest reviews: off unless you want spam. |
| Guest checkout | **On** for most shops. |
| Stock checkout | Off if you sell only what you have. |
| Login attempts | Keep the default. |
| Cookie / GDPR pages | Point at the legal information pages from install. |

Checkout field layout is **not** here — it is the Checkout module (below).

### Option — storefront pages

**Сторінки вітрини**. Off = the route is a 404, hidden from sitemap, footer, and menus. Not a 410. If Google already indexed a URL, add a Redirect Manager 301/410 first.

| Family | Keep on when | Turn off when |
| --- | --- | --- |
| Products | You sell SKUs. Also kills compare, search, specials, live search. | Content-only / blog site. |
| Categories | Normal catalog. | Single landing + search only (rare). |
| Manufacturers | You have brands worth their own URL. | No brands, or brand is only an attribute. |
| Information | About, delivery, terms. Checkout terms popup still works if you turn this off. | You replaced CMS pages with one contact form. |
| Contact | You want the form. | Phone/messengers only. |
| HTML sitemap | Extra crawl path. XML sitemap stays. | You do not need a human sitemap. |
| Blog | You will publish. Empty-store install already leaves this off. | No editorial content. |

Do not use layout widgets as a kill-switch. A featured module on Home still links to products if the product family is on.

![Settings → Option: storefront page flags](images/getting-started/10-settings-pages.png)

### Option — account pages

Login, logout, forgotten password, dashboard, cart, checkout **cannot** be turned off.

Turn off what you will not operate: affiliate, tracking, vouchers, recurring, rewards, downloads, returns, newsletter. Wishlist off if you disabled it in the theme. Register off only if checkout is guest-only **and** you do not need accounts (phone login still creates accounts).

![Settings → Option: account page flags](images/getting-started/11-settings-account-pages.png)

### Mail and SMS

Production needs working SMTP (Mail tab). Docker can stay on PHP `mail()`.

SMS tab: Docker default gateway is **log**. Production phone login needs TurboSMS or the generic HTTP gateway. Template `{code}` lives on the **Default theme** settings, not here. Full notes: `sms.md`.

### Server

| Setting | Recommendation |
| --- | --- |
| Maintenance | Off on live. On only while you swap catalog. |
| SEO URLs | **On**. Requires Apache rewrite (this stack has it). |
| CSS/JS bundling (`config_minifier`) | **On** in production. Off while you iterate on CSS if the hash cache confuses you. |
| Compression | Leave 0; gzip belongs on the web server. |
| Error display | Off on live. Errors go to `system/storage/logs/error.log`. |
| Encryption key | Leave the install value. |

![Settings → Server: SEO URL and minifier](images/getting-started/12-settings-server.png)

### SeoPro

Keep SeoPro **on**. Lowercase URLs **on**. Trailing slash: pick one style and do not flip it after index. Category path in product URLs (`config_seo_url_include_path`): on if you want `/category/product`; off for short `/product`. SEO URL cache **on** in production; clear cache after bulk keyword edits.

Page postfix (`.html`) — leave empty unless you are matching an old site.

![Settings → SeoPro](images/getting-started/13-settings-seopro.png)

### Microdata

JSON-LD + OpenGraph **on**. Twitter cards optional. `sameAs` — one social/profile URL per line. Facebook app id only if you use FB pixel/domain verify. Organization logo is the favicon pack, not the header sprite.

![Settings → Microdata](images/getting-started/14-settings-microdata.png)

### Default theme

**Extensions → Extensions → Themes → Default**. Guest wishlist if you want it. **Account login**: email/password vs phone OTP. Phone login hides email/password pages and auto-creates accounts. Set **Login SMS text** with `{code}` before turning phone login on.

![Theme Default: wishlist and login](images/getting-started/15-theme-default.png)

---

## Modules

**Extensions → Extensions**. Filter by type (Modules, Shipping, Payments, Feeds, Analytics, Captcha, Order Totals, Themes). Install, then **Edit**, then assign to a layout if it is a storefront block.

Admin-only tools (FAQ, Redirect Manager, Import/Export, Auto Backup, Checkout) do not go on a layout.

![Extensions list: Modules](images/getting-started/16-extensions-modules.png)

### Storefront blocks (layouts)

Assign in **Design → Layouts**. Positions: `content_top`, `content_bottom`, `column_left`, `column_right`. This theme is mostly one column — **content_top / content_bottom** on Home, Category, Product. Account layouts already have the Account module in the right column.

![Design → Layouts: Home with carousel](images/getting-started/17-layouts.png)

| Module | What it does | Use |
| --- | --- | --- |
| Carousel | Desktop/mobile banners (Embla). Own width/height per breakpoint. | Home hero. Create **Design → Banners** first. |
| Featured / Latest / Special / Bestseller | Product rails. Featured can take products and/or categories. | Home and category bottoms. Limit 8–16. |
| Blog Latest / Blog Featured | Article cards. | Home only if blog is on. |
| HTML | Arbitrary HTML/Twig-less block. | Short promo copy. Not a place for a new page type. |
| Filter | Attribute filter on category. | Only if you filled attributes and will maintain them. Roadmap still flags this as legacy-ish. |
| Account | Links in the customer area. | Already on account layouts. |
| Store | Multi-store switcher. | Skip unless you have extra stores. |

![Carousel module: desktop and mobile sizes](images/getting-started/18-carousel.png)

![Featured module: products or categories](images/getting-started/19-featured.png)

Empty-store install has **no** carousel/featured instances. Create them, then drop them on the Home layout.

### Checkout, shipping, payment, totals

**Checkout** (Modules, also **Sales → Checkout**): one-page checkout. Tabs for required fields, minimum order, shipping titles, payment titles, which payments appear with which shipping. Pickup can hide the address. Coupon stays under Order Totals.

![Checkout module: fields and min order](images/getting-started/20-checkout-module.png)

Shipping worth installing: **Nova Poshta** (warehouse / locker / courier + city extra widget), **Pickup**, **Flat**. Weight/item/free only if that is your pricing.

Payments: **COD**, **Bank transfer**, **LiqPay**. Cheque/free checkout for testing.

Order totals that must stay enabled: Sub-Total, Shipping, Total. Coupon if you run codes. Tax only if you configured tax classes.

### SEO and ops modules

| Module | Role |
| --- | --- |
| FAQ | Questions on product / category / manufacturer + FAQPage JSON-LD. Install and grant permissions. `faq.md`. |
| Redirect Manager | 301 and 410 at Apache/Nginx/Cloudflare/PHP. `redirect_manager.md`. |
| Import / Export | CSV/JSON catalog (not SQL dump). `import_export.md`. |
| Auto Backup | Production cron: DB + `image/catalog`. Not for Docker. `backup.md`. |
| XML Sitemap | Feed, installed and on. Human URLs `/sitemap.xml`. |
| Google Analytics | **Does not inject gtag.** You paste the counter yourself. The module only fires GA4 ecommerce events into `gtag` or `dataLayer`. |
| Google Shopping / Google Base | Optional product feed. Not installed by default. |
| Captcha | Basic or Google. Put it on contact/register if you get spam. |

![FAQ module settings](images/getting-started/21-faq-module.png)

![Redirect Manager list](images/getting-started/22-redirect-manager.png)

![Import / Export](images/getting-started/23-import-export.png)

![Google Analytics: ecommerce events](images/getting-started/24-analytics.png)

![XML Sitemap feed settings](images/getting-started/25-sitemap-feed.png)

![Shipping and payment list](images/getting-started/26-shipping-payment.png)

---

## Pages

Each family below is a layout (Design → Layouts) plus a route. Turn the family off in settings if you will not use it. Per-entity **Design** tab can override the layout (a product with a custom landing).

### Home (`common/home`)

Layout **Головна**. Put carousel + one or two product rails here. Do not dump every module on Home. Keep above-the-fold light (one hero, not three sliders).

![Storefront home](images/getting-started/27-home.png)

### Categories (`product/category`)

Tree in **Catalog → Categories**. SEO keyword per language. Parent/child path becomes the URL when include-path is on. Listing FAQ sits under the product grid. Filters only if the Filter module is on this layout.

Do not create empty categories “for later” — they still get indexed.

![Category page](images/getting-started/28-category.png)

### Products (`product/product`)

Card, gallery, options, attributes, FAQ, reviews. Compare and search are tied to the product family flag. Specials page (`product/special`) is the same flag.

![Product page](images/getting-started/29-product.png)

### Manufacturers (`product/manufacturer`)

List + brand page. Enable only if brands are a real navigation path. Otherwise set manufacturer on the product for the name on the card and skip the pages.

![Manufacturer page](images/getting-started/30-manufacturer.png)

### Blog (`blog/*`)

Latest, category, article, author. Empty install leaves `config_pages_blog` off — turn it on in Option when you are ready. Menu in the header follows that flag. Layouts: Блог / Категорії Блогу / Статті Блогу.

![Blog latest](images/getting-started/31-blog.png)

### Account (`account/*`)

Dashboard, orders, addresses, and the flags from Option. Phone login changes how register/login look. Cart modal and checkout stay reachable.

![Account dashboard](images/getting-started/32-account.png)

### Checkout (`checkout/checkout`)

One-page. Old accordion lives under `checkout_old` and should not be linked. Configure fields in the Checkout module, methods under Shipping/Payment. Success page fires `purchase` if Analytics events are on.

![One-page checkout](images/getting-started/33-checkout.png)

### Information, contact, HTML sitemap

CMS rows: **Catalog → Information** (delivery, terms, privacy). Contact is `information/contact`. HTML sitemap is `information/sitemap` and can be off while XML stays.

![Information or contact page](images/getting-started/34-information-contact.png)

### 404 and 410

404 (`error/not_found`) is “this page family is off or the keyword is unknown”. 410 (`error/gone`) is “we removed this URL on purpose”. Both have layouts so you can still place HTML modules. Indexed URLs you retire → Redirect Manager 410, not a silent 404.

![410 Gone page](images/getting-started/35-error-410.png)

---

## Catalog content

Fill taxonomy first, then products. Import/Export is faster than clicking once you have a spreadsheet. Preview before write.

### Categories

Name, parent, image (optional), stores, sort, status, SEO keyword unique per language. Description: a short unique intro, not a dump of child names. `meta_h1` only if H1 must differ from the name. `noindex` for faceted or duplicate trees.

![Category form](images/getting-started/40-category-form.png)

### Manufacturers

Same idea: name, image, keyword. Skip the whole entity if you turned manufacturer pages off.

### Attributes

**Catalog → Attributes**: groups (e.g. Display, Power) then attributes (Diagonal, Capacity). Values are per product. These power the product table, compare, and the Filter module. Keep names stable; do not invent a new attribute per SKU.

![Attribute groups](images/getting-started/42-attribute-groups.png)

### Product form

Tabs that matter:

| Tab | Fill |
| --- | --- |
| General | Name, description (real copy), `meta_title` / `meta_description` / `meta_h1` only when the language templates in `catalog/language/*/seo/meta.php` are not enough. FAQ tab is injected after install of FAQ. |
| Data | Model/SKU unique, price, quantity, status, date available, shipping flag, weight if you use weight shipping. |
| Links | Categories (one primary + extras), manufacturer, related (sparingly), stores. |
| Attribute | Values from the groups above. Same wording across SKUs. |
| Option | Size/color only if it changes the cart line. Not a place for “material” that belongs in attributes. |
| Image | Main + extras. Importer can pull `http(s)` into `image/catalog/import/`. |
| SEO | Keyword per language. Unique. No spaces. |
| Design | Custom layout only for odd landings. |
| Discount / Special / Reward / Recurring | Skip unless you run those programs. |

![Product → General](images/getting-started/36-product-general.png)

![Product → Data: SKU, price, stock](images/getting-started/37-product-data.png)

![Product → Attributes](images/getting-started/38-product-attributes.png)

![Product → SEO keyword](images/getting-started/39-product-seo.png)

Do not publish products with placeholder images or identical descriptions. Thin pages either stay `status=0` or `noindex`.

### Blog

Authors → categories → articles. Image, excerpt, body, SEO keyword, related products if it is a buying guide. Do not enable the blog family until the first three articles exist.

![Blog article form](images/getting-started/41-blog-article.png)

---

## SEO

Speed and crawl hygiene are built in. Do not install a random “SEO pack” on top.

### Bundler (minifier)

`Custom\Minifier` concatenates consecutive local CSS/JS registered from controllers. CDN URLs stay separate. Bundles land in `image/cache/minifier/<store_id>/`. Toggle: Settings → Server → **Об’єднання CSS/JS**. HTML is not minified. Theme assets: `theme-assets` skill — page CSS/JS from the controller, not from Twig.

### WebP

`catalog/model/tool/image.php` `resize()` writes JPEG/PNG cache plus WebP when GD can and `Accept` has `image/webp`. Templates keep using `resize()`; no extra markup. Originals stay in `image/catalog/`.

### Microdata

`common/microdata` → OpenGraph in `<head>`, JSON-LD before `</body>`: Organization, WebSite, WebPage, BreadcrumbList, Product, ItemList, FAQPage. FAQ JSON-LD only — do not add FAQ microdata on the accordion. Settings tab Microdata.

### Google Analytics ecommerce

Paste gtag or GTM yourself (information HTML module, or `head.twig` only if you must). Then enable **Extensions → Analytics → Google Analytics** and the events you need: `view_item`, `view_item_list`, `view_search_results`, `view_cart`, `add_to_cart`, `remove_from_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `purchase`.

### Redirect Manager

301 when a URL moved. 410 when it is gone for good. Import old site maps here **before** launch. Apache RewriteMap in Docker; PHP fallback exists. `redirect_manager.md`.

![Redirect Manager rules](images/getting-started/44-redirect-rules.png)

### Multilang and router

Startup: language → strip prefix → `Custom\Router` → Redirect Manager → page flags. Keywords live in `seo_url`. Main language has no prefix. Hreflang is automatic. Do not invent a second router.

### Meta-data

Templates in `catalog/language/{ua,en,ru}/seo/meta.php` (`{shop}`, `{name}`, …). Entity fields win when filled. H1 uses the same builder. Pagination suffix is on `<title>` only. No global title in store settings.

![Product meta fields vs templates](images/getting-started/43-seo-meta.png)

### FAQ

Shared questions per type + per page. Placeholders `{name}` `{price}` `{category}` `{month}` `{year}`. First listing page only if you do not want FAQ on `?page=2`. `faq.md`.

![FAQ accordion on a product](images/getting-started/46-faq-storefront.png)

### 410

Themed `error/gone`, HTTP 410, `noindex,follow`. Only Redirect Manager (or the web server) sets this route. Page flags never send 410.

### Sitemap

XML at `/sitemap.xml` (and `/en/sitemap.xml`). Branches: main, categories, products, information, manufacturers, blog-*. Off families are omitted. Cache TTL 24h in `system/storage/cache/sitemap_*`. Point `robots.txt` `Sitemap:` at the live domain. HTML sitemap is optional.

![sitemap.xml index](images/getting-started/45-sitemap-xml.png)

---

## Development

Work in this repo, not on production. Same Docker stack, same theme, same code style.

### Layout of the project

| Path | Role |
| --- | --- |
| `public_html/catalog/controller` | Storefront routes. |
| `public_html/catalog/model` | DB reads. |
| `public_html/catalog/view/theme/default` | Twig, CSS, JS, `components/`. |
| `public_html/catalog/language/{ua,en,ru}` | Storefront strings + `seo/meta.php`. |
| `public_html/system/library/custom` | Router, pages flags, minifier, docs, multilang helpers. |
| `public_html/admin` | Admin MVC. |
| `docs/en`, `docs/ru` | This site (`/index.php?route=docs`). Screenshots in `docs/images/`. |
| `docker/` | PHP 7.4 Apache, php.ini, vhost. |

Shell: `common/layout.twig` renders head → header → route content → footer → microdata. Columns come from **Design → Layouts**, not from the route Twig.

![Layout form: positions](images/getting-started/48-layout-positions.png)

### How to put functionality on a page

Pick the smallest hook that fits:

1. **Existing module + layout.** Carousel, featured, HTML, blog latest. Admin → Design → Layouts → the page type → `content_top` / `content_bottom`. This is the default for marketing blocks.
2. **Per-entity layout.** Product / category **Design** tab → another layout, if one landing needs a different stack.
3. **Page family flag.** Settings → Option. Whole route gone. Not for hiding a single widget.
4. **Twig include.** Reusable UI: `{% include 'default/components/….twig' %}`. Gallery: `/index.php?route=docs/components`. New kit pieces belong in `components/` plus the matching CSS/JS file, then a gallery preview.
5. **Controller assets.** Page-only CSS/JS: `$this->document->addStyle(...)` / `addScript(..., 'footer')` in that controller. Global UI → `stylesheet/ui.css`; catalog chrome → `catalog.css`; product page → `product.css`; checkout → `checkout.css` (loaded from the checkout controller, not the Tailwind bundle). Never put component CSS in `stylesheet.css` or `docs.css`.
6. **Events.** FAQ tabs are events, not OCMOD. Use events when every product form needs a new tab.
7. **Custom library.** Shared PHP in `system/library/custom` (or `system/library/sms` for gateways). Controllers stay thin.

Do **not** fork a second theme. Do **not** edit production files. Do **not** copy-paste a 200-line HTML module when a component exists.

![Components gallery](images/getting-started/49-components-gallery.png)

### Local loop

```bash
docker-compose up -d
npm run dev          # Tailwind → tailwind.css
npm run prettier     # Twig / CSS / JS / catalog PHP
```

`dev_dump($var)` prints a `<pre>` dump. Error log: `public_html/system/storage/logs/error.log`. After pulling, recreate the PHP container if `docs/` is not mounted.

### Adding a new storefront module

1. Admin controller/model/Twig under `extension/module/{code}`.
2. Install from Extensions, enable status.
3. If it is a block: **Add** an instance (name, settings), then assign that instance on the layout. Status on the extension is not enough — the instance must be on the layout and enabled.
4. If it is settings-only (Checkout, FAQ), skip the layout and read config from the catalog controller.

That is the same pattern Featured uses: extension status + instance + layout row.

---

## Launch checklist

1. Empty-store install (or demo wiped).
2. Settings: store identity, languages, SEO URL, minifier, microdata, unused page flags off.
3. Theme: login mode, SMS text if phone login.
4. Checkout + one shipping + one payment + required totals.
5. Categories → attributes → products (or Import/Export).
6. Home layout: carousel + featured only if you have banners and products.
7. FAQ + Analytics events + Redirect Manager for the old site.
8. `robots.txt` sitemap URL, favicon pack, legal information pages.
9. Auto Backup cron on the live host, not in Docker.
