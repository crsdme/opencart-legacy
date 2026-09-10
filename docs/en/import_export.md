# Import / Export

Catalog CSV/JSON for products, categories, manufacturers, attribute groups and attributes. This is not the SQL dump in **System → Backup**.

## Installation

1. Files are already in the project.
2. Admin → Extensions → Extensions → Modules → Import / Export → Install.
3. Open **Import / Export** in the left menu (or the module form).
4. Grant `access` / `modify` for:
   - `extension/module/import_export`
   - `extension/import_export`

Uninstall does **not** drop job history unless **Delete job history on uninstall** is enabled. Catalog rows are never deleted.

## How to set IDs

OpenCart assigns real ids. You never invent a live `12` or `7`. Wrong positive ids update the wrong row or error.

| Value | Own id (`product_id`, `category_id`, …) | Links (`parent_id`, `category_ids`, `manufacturer_id`, `attribute_id`, …) |
|---|---|---|
| **omitted** | Create, or update if SKU / name / path already exists | Ignore that link (keep current on update) |
| **`0`** | Always **create** (do not match SKU / name) | Root / none (`parent_id: 0`, no manufacturer) |
| **`> 0`** | **Update** that shop row. Missing id = error | Must already exist in the shop |
| **`< 0`** (`-1`, `-2`) | **Create**, and remember this number **inside this file** | Point at another new row in the same import that uses the same number |

Preview shows Create vs Update before anything is written. If a row says Update and you meant Create, the id is a live shop id (or the SKU already exists and you omitted the id). Change it to `0` or a negative alias.

### Create a category and a product together

Use a **JSON bundle** so both entities are in one file. Pick unused negative numbers; they are not stored in the database.

```json
{
  "version": 1,
  "entities": {
    "categories": [
      {
        "category_id": -1,
        "parent_id": 0,
        "name": { "uk": "Футболки", "en": "T-shirts" }
      },
      {
        "category_id": -2,
        "parent_id": -1,
        "name": { "uk": "Чоловічі", "en": "Men" }
      }
    ],
    "products": [
      {
        "product_id": 0,
        "sku": "TEE-001",
        "price": 499,
        "category_ids": [-2],
        "name": { "uk": "Футболка", "en": "T-shirt" }
      }
    ]
  }
}
```

`-1` and `-2` only have to be unique **per entity** in this file (`category_id: -1` and `product_id: -1` are different). After import the shop has real ids; export again if you need them.

CSV is one entity per file, so local aliases work for a parent/child **category** CSV (`parent_id: -1` next to `category_id: -1`). A product CSV cannot see a category that is not in that same file — use real shop ids from an export, or a JSON bundle.

### Update existing rows

Export first. Keep the positive ids. Only fields present in the file change.

## For AI fills

1. Export the live catalog when you need to **attach to existing** rows. Copy those positive ids.
2. For **new** rows in one shot, use `0` or negative aliases as above. Do not copy live ids onto new rows.
3. Names, paths and `group` strings in the export are labels. On write, IDs win.
4. Attach a product to existing categories with `category_ids: [12, 15]` and `attributes: [{ "attribute_id": 7, "text": { "uk": "Чорний" } }]`.

Typical loops:

- New tree: JSON bundle with negative ids → import → export if you need the real ids later.
- New products in existing categories: export categories → copy `category_id` values → import products.

## Workflow

1. **Templates** — empty CSV/JSON from the live schema (includes id columns). Template `0` means create.
2. Fill rows (or generate them from an **export**, so ids are real).
3. **Import** — preview (create / update / skip / error), then confirm.
4. **Export** — same shape, with shop ids and label fields.

JSON can be one entity or a **bundle** (`entities`: manufacturers, attribute groups, attributes, categories, products). Import order is always that list. Categories with local `parent_id` are sorted so parents are created first.

Localized keys (`name.uk`, `name.ru`, …) follow **enabled** languages in **System → Localisation → Languages**. After you add a language, download the template again — old files still import; the new language copies the default until you fill it.

## Identity

| Entity | Update key | Create | Links |
|---|---|---|---|
| Product | `product_id`, else `sku` (or `model`) if id omitted | `0` / negative / omit + `sku` | `manufacturer_id`, `category_ids`, `attributes[].attribute_id` |
| Category | `category_id` | `0` / negative / omit + `name` | `parent_id` (`0` = root, negative = new parent in this file) |
| Manufacturer | `manufacturer_id` | `0` / negative / omit + `name` | — |
| Attribute group | `attribute_group_id` | `0` / negative / omit + `name` | — |
| Attribute | `attribute_id` | `0` / negative / omit + `name` | `attribute_group_id` |

Name / path / `group` remain as fallbacks when an id is **omitted**. Missing name-based references follow the setting: **create**, **error**, or **skip**. Unknown **positive** ids and unknown **negative** aliases (not declared on any row in the file) are always an error.

## Formats

**JSON:** downloaded templates put a `help` string next to `version` (ids, preview, images). Import ignores it.

```json
{
  "version": 1,
  "help": "Preview first. Live positive ids update that row. 0 = create. Negative ids (-1, -2) create and let other rows in this file link to them (JSON bundle for category + product). Images: local image/catalog path, or http(s) URL to download.",
  "entities": {
    "products": [{
      "product_id": 0,
      "sku": "TEE-001",
      "price": 499,
      "manufacturer_id": 1,
      "category_ids": [12],
      "image": "https://example.com/tee-001.jpg",
      "name": { "uk": "Футболка", "en": "T-shirt" },
      "attributes": [{ "attribute_id": 7, "text": { "uk": "Чорний", "en": "Black" } }]
    }]
  }
}
```

Export also includes label fields (`manufacturer`, `categories` paths, attribute `name`) so a model can see what `12` and `7` are.

**CSV** — one entity per file. Templates start with a `#` comment (same help text). Id columns first. Localized columns are `name.uk`. `category_ids` uses `|` (`12|15` or `-1|-2`). `attributes` is a JSON cell with `attribute_id`.

Images: local paths under `image/catalog/...`, or `http(s)` URLs (string, list, or `{ "url": "..." }`). Remote files are downloaded on import into `image/catalog/import/`. JPEG, PNG, GIF and WebP, max 16 MB. Cloudflare `/cdn-cgi/image/...` links are unwrapped to the original file when possible. Empty `image` / `images` are skipped — the importer cannot invent photos that are not in the file. Failed downloads skip that image; reason is written to `system/storage/logs/error.log`. Turn this off in **Settings** if you only want local paths.

Writes go through admin `add*` / `edit*` models (SEO URL, cache, FAQ / Redirect Manager events).

## CLI

```
php system/cli/import_export.php template product json
php system/cli/import_export.php export bundle json
php system/cli/import_export.php import catalog.json --dry-run
php system/cli/import_export.php import catalog.json
```

Use CLI for large files. The admin import previews, then confirm writes from the uploaded file on disk — the catalog is not stored in the PHP session (`oc_session.data` is only 64KB).

## Database (`DB_PREFIX`)

- `import_export_job` — history only. Pending uploads sit in `system/storage/import_export/`.

Not in v1: options, Excel, deleting catalog rows that are absent from the file.
