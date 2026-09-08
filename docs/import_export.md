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

## For AI fills

**Use numeric IDs. Do not invent them.**

1. Export the live catalog (or the entity you will attach to).
2. Copy `product_id`, `category_id`, `parent_id`, `manufacturer_id`, `attribute_id`, `attribute_group_id`, `category_ids`.
3. Names, paths and `group` strings in the export are labels for you. On write, IDs win.
4. Own id `0` or omitted = **create**. A non-zero id that does not exist = **error** (OpenCart assigns ids; you cannot pick them).
5. Attach a product with `category_ids: [12, 15]` and `attributes: [{ "attribute_id": 7, "text": { "uk": "Чорний" } }]`. Do not rely on `Clothes > T-shirts` or attribute names.

Typical loop: export categories + attributes → generate products with those ids → import products.

## Workflow

1. **Templates** — empty CSV/JSON from the live schema (includes id columns).
2. Fill rows (or generate them from an **export**, so ids are real).
3. **Import** — preview (create / update / skip / error), then confirm.
4. **Export** — same shape, with ids and label fields.

JSON can be one entity or a **bundle** (`entities`: manufacturers, attribute groups, attributes, categories, products). Import order is always that list. Categories are sorted by `parent_id` so parents land first.

## Identity

| Entity | Update key | Create | Links |
|---|---|---|---|
| Product | `product_id`, else `sku` (or `model`) | omit / `0` + `sku` | `manufacturer_id`, `category_ids`, `attributes[].attribute_id` |
| Category | `category_id` | omit / `0` + `name` | `parent_id` (`0` = root) |
| Manufacturer | `manufacturer_id` | omit / `0` + `name` | — |
| Attribute group | `attribute_group_id` | omit / `0` + `name` | — |
| Attribute | `attribute_id` | omit / `0` + `name` | `attribute_group_id` |

Name / path / `group` remain as fallbacks when an id is absent. Missing references follow the setting: **create** (names/paths only), **error**, or **skip**. Unknown ids are always an error.

## Formats

**JSON:**

```json
{
  "version": 1,
  "entities": {
    "products": [{
      "product_id": 0,
      "sku": "TEE-001",
      "price": 499,
      "manufacturer_id": 1,
      "category_ids": [12],
      "name": { "uk": "Футболка", "en": "T-shirt" },
      "attributes": [{ "attribute_id": 7, "text": { "uk": "Чорний", "en": "Black" } }]
    }]
  }
}
```

Export also includes label fields (`manufacturer`, `categories` paths, attribute `name`) so a model can see what `12` and `7` are.

**CSV** — one entity per file. Id columns first. Localized columns are `name.uk`. `category_ids` uses `|` (`12|15`). `attributes` is a JSON cell with `attribute_id`.

Images are paths under `image/catalog/...`. HTTP URLs are skipped in v1.

Writes go through admin `add*` / `edit*` models (SEO URL, cache, FAQ / Redirect Manager events).

## CLI

```
php cli/import_export.php template product json
php cli/import_export.php export bundle json
php cli/import_export.php import catalog.json --dry-run
php cli/import_export.php import catalog.json
```

Use CLI for large files. The admin import still previews, then runs in one request.

## Database (`DB_PREFIX`)

- `import_export_job` — history only. Pending uploads sit in `system/storage/import_export/`.

Not in v1: options, Excel, deleting catalog rows that are absent from the file.
