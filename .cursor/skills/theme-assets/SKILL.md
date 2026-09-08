---
name: theme-assets
description: >-
  Place theme CSS and JS in the correct default-theme files and attach
  page-only assets from controllers. Use when splitting stylesheets, adding
  scripts, connecting CSS/JS, editing ui.css catalog.css product.css
  checkout.css, or when the user says styles should not go into the main
  bundle / docs.css / stylesheet.css.
---

# Theme CSS and JS

Theme root: `public_html/catalog/view/theme/default`.

`stylesheet.css` is the Tailwind **entry**. It compiles to `tailwind.css` (`npm run dev`). Put almost no component rules there.

`docs.css` / `docs.js` are for `/docs` and `/docs/components` only. Link them in `template/docs/layout.twig`. Never import them into `stylesheet.css`.

## Where a rule or script goes

| Scope | CSS | JS | How it loads |
|---|---|---|---|
| Every store page, UI primitive | `stylesheet/ui.css` | `javascript/ui.js` | Imported / footer.php |
| Catalog chrome (header, footer, listings, cards) | `stylesheet/catalog.css` | `javascript/catalog.js` | Imported / footer.php |
| Product page | `stylesheet/product.css` | `javascript/product.js` | CSS imported; JS `addScript` in `controller/product/product.php` |
| Live search | `stylesheet/live-search.css` | `javascript/live-search.js` | CSS imported; JS `addScript` in `controller/common/search.php` |
| Phone login | `stylesheet/phone-login.css` | `javascript/phone-login.js` | CSS imported; JS via `system/library/custom/account.php` |
| Checkout | `stylesheet/checkout.css` | `javascript/checkout.js` | Both `addStyle` / `addScript` in `controller/checkout/checkout.php` |
| Docs / Components | `stylesheet/docs.css` | `javascript/docs.js` | `template/docs/layout.twig` only |

Tiny one-off page styles may live in the Twig file (rare). Prefer the table above.

## Attaching a new page-only file

Do it in the **controller**, not Twig:

```php
$this->document->addStyle('catalog/view/theme/default/stylesheet/{name}.css');
$this->document->addScript('catalog/view/theme/default/javascript/{name}.js', 'footer');
```

Global footer order is already: jquery → `ui.js` → `catalog.js`. Do not duplicate those.

Head already links `tailwind.css` (`controller/common/head.php`). Do not add `ui.css` / `catalog.css` as extra `<link>`s.

If the file should compile into the main bundle, `@import` it from `stylesheet.css` (see existing ui/catalog/product/live-search imports). Checkout stays out of that bundle on purpose.

## After CSS edits

`npm run dev` must be running, or the store still serves stale `tailwind.css`.

## Do not

- Put primitive styles into `stylesheet.css` or a random page CSS file.
- Put docs chrome into the store bundle.
- Copy JS into a component Twig demo when `ui.js` (or the page script) already owns it.
- Invent a new global bundle without a page-specific reason.

New UI kit pieces also need the Components gallery: [add-component](../add-component/SKILL.md).
