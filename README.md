# OpenCart + Tailwind CSS + Docker Setup

E-commerce project based on OpenCart (ocStore), styled with Tailwind CSS and containerized with Docker.

## Legacy

**OpenCart. Legacy. We know.**  
**Maybe a trash can. Trash cannot.**

A kit for OpenCart. The engine is legacy. We know.

### Why

OpenCart shops are still here. What usually grows around them is noise: extra modules, edits on a live site, styles that stop making sense over time.

**Legacy** puts in one place the things a shop needs before production — and lets you change the platform piece by piece.

### How

Not a pile of add-ons, but a shared core: markup, sitemap, languages, bundling, images, components, accessibility.

No work on production. Same environment, architecture, and code style while you write.

Styles do not pile up as a dead layer over the years. Components live next to their look and leave with it.

### Stance

OpenCart is legacy. Not an excuse. Not a flaw. The point where the work starts.

Maybe a trash can. Trash cannot.

## 📦 Stack

- OpenCart (ocStore)
- Tailwind CSS
- Docker + Docker Compose
- PHP 7.4 (Apache)
- MySQL 5.7
- Prettier (including Twig plugins)

## 🚀 Run (development)

```bash
docker-compose up -d --build

clear config.php file

localhost:8080/install

On step 3 pick **Empty store** (no catalog/blog/demo modules) or **Demo catalog**.

SEO URLs and SeoPro are on after install.
```

## Components Preview

```bash
http://localhost:8080/index.php?route=docs/components
http://localhost:8080/index.php?route=docs
```

Technical pages (noindex). Components is the UI kit. Guides are markdown in `docs/en` and `docs/ru` (sidebar: Guides / Technical). Screenshots live in `docs/images/`. Store setup: `http://localhost:8080/index.php?route=docs/index&doc=getting-started`. Recreate the PHP container after pulling so Docker can mount `docs/`.