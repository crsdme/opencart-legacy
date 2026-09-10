# FAQ

FAQ for product, category and manufacturer pages. Questions are edited in admin. The storefront renders an accessible accordion and matching `FAQPage` JSON-LD.

## Installation

1. Files are already in the project.
2. Admin → Extensions → Extensions → Modules → FAQ → Install.
3. Enable the module (install turns it on).
4. Grant `access` / `modify` for:
   - `extension/module/faq`
   - `extension/faq`

Uninstall does **not** drop questions unless **Delete FAQ data on uninstall** is enabled.

## Where to edit

- **Module settings:** global on/off, per-type on/off, first listing page only, block titles, questions that appear on every page of a type.
- **Catalog forms:** tab **FAQ** on product, category and manufacturer.
  - Per-page on/off (hides own and global questions).
  - Questions for this page.

## Storefront

- Product: after attributes.
- Category / manufacturer: after the product list.
- Listing FAQ is hidden on page 2+ when **First listing page only** is on.
- Filtered category pages (`filter=`) do not output FAQ.

Visible markup is a native `<details>` accordion (`default/components/accordion.twig`). Schema is JSON-LD only (`FAQPage` in `common/microdata`). JSON-LD and HTML microdata are not mixed, so Google does not see a duplicate FAQPage.

## Placeholders

Replaced on the storefront.

Product: `{name}` `{product_name}` `{heading_title}` `{meta_title}` `{price}` `{product_price}` `{manufacturer}` `{model}` `{sku}` `{category}` `{category_name}` `{month}` `{year}`

Category: `{name}` `{category_name}` `{heading_title}` `{meta_title}` `{month}` `{year}`

Manufacturer: `{name}` `{manufacturer_name}` `{heading_title}` `{meta_title}` `{month}` `{year}`

## Database (`DB_PREFIX`)

- `faq` — entity_type (`product` / `category` / `manufacturer`), entity_id (`0` = global), scope (`page`), sort_order, status
- `faq_description` — question, answer per language
- `faq_page` — per-page on/off

## Schema

`FAQPage` with `mainEntity` → `Question` → `acceptedAnswer` → `Answer`. `name` / `text` are plain text. The visible accordion content must match these questions. Auto-generated “latest products as FAQ” lists are not used: they do not match FAQ guidelines.
