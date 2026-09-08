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

Put new guides in repo `docs/{slug}.md`. They are picked up automatically. No PHP registration.

| File | Sidebar |
|---|---|
| `README.md` (repo root) | Home |
| `docs/DOCUMENTATION.md` | Overview |
| `docs/*.md` | Other Guides, sorted by filename |

Skip `public_html/docs/` for new guides. That tree is leftover.

First `# heading` becomes the sidebar title (keep it short, under ~48 characters, or the id label is used instead).

## Writing

- English, same tone as existing guides.
- Link other guides as `faq.md`, `backup.md` — the docs controller rewrites `*.md` links to `?doc=slug`.
- `DOCUMENTATION.md` links as Overview (`doc=overview`).
- Do not duplicate the Components gallery here. Point to `/index.php?route=docs/components` if needed.

## Chrome

Docs layout: `catalog/view/theme/default/template/docs/layout.twig`.
Styles/scripts: `stylesheet/docs.css`, `javascript/docs.js` — **only** there, never in the store Tailwind bundle.

After adding markdown, if Docker does not show the file, recreate the PHP container so `docs/` is mounted.

## Not this skill

New button/card/modal → [add-component](../add-component/SKILL.md).
