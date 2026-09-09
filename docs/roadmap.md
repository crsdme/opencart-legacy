# Roadmap

Open work and a running changelog. How features work is in `DOCUMENTATION.md`.

## To do

- legacy-cart.com buy domain
- create addons for languages like
- taxes
- rework mail opencart
- variant products
- cache clear admin
- recently viewed products
- SEO URL має бути унікальним
- points
- logs speed each controller fint start and end
- md file in admin
- interface admin refactor
- route=setting/setting

- turn off auto maintaince
- брошенные корзины
- Refactor or remove Фільтр
- show more products (need check with ocfilter)
- cookies

- windows - linux bind dev speed error
- button-group https://ui.shadcn.com/docs/components/radix/button-group
- collapsible https://ui.shadcn.com/docs/components/radix/collapsible
- checkbox https://ui.shadcn.com/docs/components/radix/checkbox
- table https://ui.shadcn.com/docs/components/radix/data-table
- hover https://ui.shadcn.com/docs/components/radix/hover-card
- tooltip https://ui.shadcn.com/docs/components/radix/tooltip
- input group https://ui.shadcn.com/docs/components/radix/input-group
- input https://ui.shadcn.com/docs/components/radix/input
- nav https://ui.shadcn.com/docs/components/radix/navigation-menu
- progress https://ui.shadcn.com/docs/components/radix/progress
- popover https://ui.shadcn.com/docs/components/radix/popover
- radio https://ui.shadcn.com/docs/components/radix/radio-group
- select https://ui.shadcn.com/docs/components/radix/select
- sonner https://ui.shadcn.com/docs/components/radix/sonner
- switch https://ui.shadcn.com/docs/components/radix/switch
- tabs https://ui.shadcn.com/docs/components/radix/tabs
- typography https://ui.shadcn.com/docs/components/radix/typography

## Changelog

What the kit adds on top of ocStore. Not a dated release log: git is one bulk commit plus uncommitted work. Details live in `DOCUMENTATION.md` and the linked guides.

### Theme and storefront

- Default theme on Tailwind (`catalog/view/theme/default`): shared `layout` / `head`, theme settings
- Storefront restyle: product, listings, header sheet, footer, cart modal, account, compare, wishlist
- Live search and header announcement bar
- Carousel module (desktop/mobile banners, Embla)
- Product modules (featured / latest / special / bestseller) restyle and admin options
- Blog restyle and author pages (admin entity, storefront, sitemap, microdata)
- Guest wishlist theme setting
- CSS split (`ui` / `catalog` / `product`) and JS split (`ui` / `catalog` / `product` / `live-search` / `phone-login` / `checkout`)
- Components gallery at `/index.php?route=docs/components`

### SEO

- Custom router (`system/library/custom/router.php`)
- Language URL prefixes and hreflang
- Auto meta title / description / H1 templates
- Microdata (OpenGraph, JSON-LD) plus admin Microdata tab
- FAQ module (product / category / manufacturer, FAQPage JSON-LD) — `faq.md`
- Branched XML sitemap (`extension/feed/sitemap`) with admin settings
- 410 Gone page
- Redirect Manager (301 / 410, import, CLI) — `redirect_manager.md`
- Storefront page families can be turned off (404 + sitemap/footer hide)

### Checkout and account

- One-page checkout (admin module for fields, shipping, payment)
- Nova Poshta shipping (warehouse / locker / courier) — see Overview
- Phone login and SMS facade (TurboSMS / HTTP / log) — `sms.md`
- GA4 ecommerce events on catalog and checkout

### Media and performance

- WebP from `model/tool/image` when the browser accepts it
- CSS/JS minifier bundler (`Custom\Minifier`), admin on/off

### Ops and DX

- Docker Compose (PHP 7.4, MySQL 5.7, phpMyAdmin)
- Secured admin URL
- Auto Backup (DB + `image/catalog`, cron, Drive / FTP) — `backup.md`
- Import / Export (CSV/JSON catalog, templates, preview, CLI) — `import_export.md`
- Markdown docs site (`/index.php?route=docs`)
- Prettier (Twig + PHP)
- `dev_dump` debug helper
- Removed unused stock modules (slideshow, banner module, sidebar category/information, unused payment/shipping)

From here: when a to-do ships, move the bullet here in the same session. Do not reconstruct from chats again unless a whole area is missing.
