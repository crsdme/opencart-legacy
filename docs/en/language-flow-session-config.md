# Language flow

How `session['language']` and `config_language` are set on a catalog request.

Order of `action_pre_action` (catalog):

1. **startup/session**
2. **startup/startup**
3. startup/error
4. startup/event
5. startup/maintenance
6. **startup/multilang**
7. startup/seo_url
8. **startup/multilang_rewrite**
9. then router, the main controller, view

---

## 1. startup/session

- Starts the session (if needed) and reads storage (DB).
- **session** and **config** are not changed here.
- `session->data['language']` is the value from the previous request, or empty.

---

## 2. startup/startup (Language block)

**Input:** `session->data['language']` (storage), cookie `language`, Accept-Language, `config_language` from the DB.

`$code` is chosen in this order:

1. Valid `session->data['language']` → `$code = session`
2. Else valid cookie `language` → `$code = cookie`
3. Else Accept-Language
4. Else `$code = config_language` from the DB

Then:

- `config_language_main = config_language` (DB) — language with no URL prefix.
- If session is empty or session ≠ code → **session = $code**
- If cookie is empty or cookie ≠ code → cookie `language = $code`
- `Language($code)` is created and put on the registry.
- **config_language = $code**
- **config_language_id** = id from the language table.

**Output:** session and config share one chosen language (session / cookie / browser / DB).

---

## 3. startup/multilang

**Input:** `REQUEST_URI`, `_route_` (from .htaccess), session and config already set by startup.

Logic:

- Path from the URL: `path = parse_url(REQUEST_URI, path)` without leading/trailing `/`.
- Static suffix (css, js, images, …) → **return**, no change.
- Header `X-Requested-With: XMLHttpRequest` → **return**, no change.
- `route = _route_` or, if empty, `route = path`.
- First path segment: `prefix = parts[0]` (lowercase).

### A: the path has a language prefix (`ru`, `ua`, `en`, …)

- Prefix is stripped from `_route_` (`ru/category` → `category`); `$_GET['_route_']` and `$this->request->get['_route_']` are updated.
- **session->data['language'] = prefix**
- **config_language = prefix**
- **config_language_id** = that language id.
- Cookie `language = prefix`.
- New `Language(prefix)` replaces the registry object.

**Output:** page language = URL prefix; session and config match.

### B: no prefix, or it is not a language code

- If **path === 'index.php'** (direct or AJAX `index.php?route=...`) → **return unchanged**. Session and config stay as startup set them (so AJAX does not reset the language).
- Else (`/` or SEO URL without prefix, e.g. `/category`):
  - `main = config_language_main` (default from DB).
  - **session = main**
  - **config_language = main**
  - **config_language_id** = main id.
  - Cookie and Language object are reset to main.

**Output:** language = store main language; session and config match again.

---

## 4. startup/seo_url

- Turns `_route_` into a normal `route` (and query). **Does not touch session or config.**

---

## 5. startup/multilang_rewrite

- In **index()**: only registers the URL rewrite callback `$this->url->addRewrite($this)`.
- **session and config are not changed.**

On each `$this->url->link(...)`:

- **rewrite($link)** runs.
- Reads **config_language** only.
- If language = main → home becomes `/`, other links stay.
- If language ≠ main → prefix `/$code/...` is added when missing.
- **session is not read or written** — links use **config_language** only.

---

## 6. Rest of the request (router, controllers, view)

Controllers and templates use:

- `$this->config->get('config_language')` — current page language (already aligned with the URL in multilang).
- `$this->session->data['language']` — after this chain it should match config_language.

The language switcher in `catalog/controller/common/language` uses `config_language ?: session` so the active tab matches the visible version (ru/ua/en).

---

## Who writes what

| Stage | session['language'] | config_language |
|---|---|---|
| startup/session | load from storage | untouched |
| startup/startup | = code (session/cookie/browser/DB) | = code |
| startup/multilang | = prefix or main or unchanged (`index.php`) | = prefix or main or unchanged |
| startup/multilang_rewrite | untouched | read only, for links |
| After that | read by controllers/view | read by controllers/view |

After startup + multilang the page language comes from the URL (or main on `/` and SEO URLs without a prefix). Requests to `index.php` without a prefix are not rewritten, so session and config_language do not drift.
