---
name: add-component
description: >-
  Add or update a storefront UI component in the default theme: Twig in
  components/, CSS, JS, and the /docs/components gallery. Use when the user
  asks to add a component, UI kit item, button, dropdown, modal, card, or
  similar, mentions shadcn/radix counterparts, or wants something on the
  technical Components page.
---

# Add a UI component

Theme: `public_html/catalog/view/theme/default`. Preview: `/index.php?route=docs/components`.

Copy this checklist and complete every item that applies:

```
- [ ] Twig file in components/
- [ ] CSS in the right stylesheet (not stylesheet.css, not docs.css)
- [ ] JS in the right script (not inside the Twig demo)
- [ ] Icon in sprite.svg if needed
- [ ] sections() entry in docs/components.php
- [ ] Preview sandwich in docs/components.twig
- [ ] npm run dev running so Tailwind rebuilds
```

## 1. Twig

Create `catalog/view/theme/default/components/{name}.twig` (kebab-case).

Two kinds of files live here:

| Kind | What the file is | Store pages use |
|---|---|---|
| Showcase (button, badge, dropdown, input) | All variants for the gallery | Raw HTML from the snippet, **not** `{% include %}` of the whole demo |
| Reusable (accordion, pagination, product-card) | Real markup driven by params | `{% include "default/components/{name}.twig" with { ... } %}` |

Follow an existing neighbor in the same group. Prefer classes + `data-*` over new BEM trees. Dark mode: `dark:` utilities.

Icons: add `<symbol id="icon-{name}">` to `catalog/view/theme/default/image/icons/sprite.svg`. In templates:

```html
<svg aria-hidden="true" viewBox="0 0 24 24">
	<use href="/assets/icons/sprite.svg#icon-{name}"></use>
</svg>
```

`/assets/` rewrites to `catalog/view/theme/default/image/`.

## 2. CSS

Edit the matching file. Do **not** dump rules into `stylesheet.css` (entry only) or `docs.css` (docs chrome only).

| File | When |
|---|---|
| `stylesheet/ui.css` | Primitive used on every page (button, badge, input, dropdown, modal, sheet, toast) |
| `stylesheet/catalog.css` | Header, footer, listing, cards, shared catalog chrome |
| `stylesheet/product.css` | Product page only |
| `stylesheet/checkout.css` | Checkout page only (loaded via controller, not imported) |
| `stylesheet/live-search.css` | Live search |
| `stylesheet/phone-login.css` | Phone login |

`stylesheet.css` imports ui/catalog/product/live-search into `tailwind.css`. Checkout is extra via `addStyle`. Docs CSS is linked only in `template/docs/layout.twig`.

If Tailwind watch is not running: `npm run dev`.

## 3. JS

Same split as CSS. Wrap in `(function ($) { ... })(jQuery);`. Bind with `$(document).on(...)`. Do not paste a second copy of the script into the Twig demo.

| File | When |
|---|---|
| `javascript/ui.js` | Primitive behavior (dropdown, modal, sheet, toast, theme) |
| `javascript/catalog.js` | Cart, wishlist, compare |
| `javascript/product.js` | Product page (already `addScript` in product controller) |
| `javascript/checkout.js` | Checkout |
| `javascript/live-search.js` | Live search (`addScript` in search controller) |
| `javascript/docs.js` | Components/docs pages only |

Need a **new page-only** file? Register it from that page's controller with `addScript(..., 'footer')`, same as `product.js`. Global files are already in `common/footer.php` (jquery → ui.js → catalog.js).

## 4. Register on the Components page

`catalog/controller/docs/components.php` → `sections()`. Key = id (kebab-case). Groups: `Primitives`, `Forms`, `Overlays`, `Content`, `Catalog`.

**Showcase** — include + raw HTML (controller builds both when `markup` is set):

```php
'tooltip' => [
	'id' => 'tooltip',
	'group' => 'Overlays',
	'title' => 'Tooltip',
	'file' => 'components/tooltip.twig',
	'description' => 'Short hint on hover/focus. data-side: top, right, bottom, left.',
	'markup' => '<button type="button" class="button-secondary" data-tooltip="Hint">Hover</button>',
],
```

**Reusable** — include with params, plus `params` table:

```php
'tooltip' => [
	'id' => 'tooltip',
	'group' => 'Overlays',
	'title' => 'Tooltip',
	'file' => 'components/tooltip.twig',
	'description' => '...',
	'include' => $this->includeOf('components/tooltip.twig', '{ items: items }'),
	'params' => [
		$this->param('items', 'array', true, '...'),
	],
],
```

`$this->param($name, $type, $required, $text, $default = '')`.

## 5. Preview markup

`catalog/view/theme/default/template/docs/components.twig`. Keep the sandwich: `section.twig` → live preview → `copy.twig`. Hyphenated ids use `sections['product-card']`.

```twig
{% include "default/template/docs/section.twig" with { section: sections.tooltip } %}
	<div class="flex flex-wrap gap-4">
		{% include "default/components/tooltip.twig" %}
	</div>
{% include "default/template/docs/copy.twig" with { section: sections.tooltip } %}
```

Overflow (dropdown, popover): preview must not clip. Do not empty `docs-card-preview` padding.

Sidebar TOC on Components is built from `sections()` groups. Right-hand page TOC stays empty on this route — do not add a fake one.

## 6. Use it on a store page

Reuse the new classes. Do not fork a one-off copy in `template/`. For showcases, paste the HTML snippet. For reusable files, include with the documented params.

## Extra

CSS/JS map and how files are attached: [theme-assets](../theme-assets/SKILL.md).
Markdown guides (not the UI kit): [add-docs-page](../add-docs-page/SKILL.md).
