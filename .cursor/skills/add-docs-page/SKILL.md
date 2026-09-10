---
name: add-docs-page
description: >-
  Add or edit a markdown guide on the technical Docs site (not the UI kit).
  Use when the user wants a docs page, guide, FAQ writeup, or markdown under
  docs/, or mentions route=docs, DOCUMENTATION.md, README on the docs home.
---

# Add a Docs guide

Storefront UI kit is a different page (`docs/components`). This skill is markdown guides at `/index.php?route=docs`.

## Files

Guides live in `docs/{en,ru}/{slug}.md`. Screenshots live in `docs/images/{slug}/` (never `public_html/image/`). Register the page in `docs/catalog.php` — files are not auto-listed from the folder.

| `catalog.php` group | Typical pages | Sidebar |
|---|---|---|
| `general` | `getting-started.md`, `DOCUMENTATION.md`, `roadmap.md` | Guides / Общее |
| `technical` | `faq.md`, `redirect_manager.md`, `sms.md`, … | Technical / Техническое |

Order in the `docs` array is sidebar order. Same `id` and `file` for both languages. `DOCUMENTATION.md` is `id: overview`.

First `# heading` becomes the sidebar title (keep it short, under ~48 characters, or the id label is used instead).

Skip `public_html/docs/` for new guides. That tree is leftover. Do not put markdown at `docs/*.md` root.

## Writing

- Write **both** `docs/en/` and `docs/ru/`. Same structure, same screenshot filenames.
- Link other guides as `faq.md`, `backup.md` — the docs controller rewrites `*.md` links to `?doc=slug&lang=…`.
- `DOCUMENTATION.md` links as Overview (`doc=overview`).
- Screenshots: `![Caption](images/{slug}/01-name.png)`. Served as `/docs-media/images/{slug}/01-name.png`. Grey placeholder until the PNG exists.
- Do not duplicate the Components gallery here. Point to `/index.php?route=docs/components` if needed.

## Chrome

Docs layout: `catalog/view/theme/default/template/docs/layout.twig`.
Styles/scripts: `stylesheet/docs.css`, `javascript/docs.js` — **only** there, never in the store Tailwind bundle.

After adding markdown, if Docker does not show the file, recreate the PHP container so `docs/` is mounted. After changing `docker/php/000-default.conf`, reload Apache so `/docs-media/` works.

## Not this skill

New button/card/modal → [add-component](../add-component/SKILL.md).
