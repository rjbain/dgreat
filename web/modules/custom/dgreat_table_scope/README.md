# DGreat Table Header Scope

Adds accessible `scope="col"` / `scope="row"` attributes to table header cells
(`<th>`) produced by CKEditor 5.

## Why

CKEditor 5 (as bundled with Drupal core) marks header cells as `<th>` via its
**Header row** / **Header column** table toggles, but never emits a `scope`
attribute. `scope` is needed for screen readers to associate data cells with the
correct headers (WCAG technique H63). This module fills that gap.

## How it works

It is an **output text filter** — it runs when a field is rendered, not on save:

- `<th>` inside `<thead>` → `scope="col"`
- `<th>` elsewhere (e.g. a header column in `<tbody>`) → `scope="row"`

Because it operates on rendered output only:

- The stored markup in the database is **never modified**.
- **Existing content is fixed automatically** — no backfill/migration needed.
- **Uninstall is clean** — there is no persisted data to reverse.

An existing `scope` attribute is respected by default (assumed deliberate — e.g.
authored via Source editing or brought in by a migration). The optional
*Normalize existing scope values* setting will correct a plain `col`/`row`
value that conflicts with the cell's structural position, while always
preserving deliberate `colgroup`/`rowgroup` values.

## Individual header cells

CKEditor 5 can only mark whole header rows/columns, not a single cell. A `<th>`
added via Source editing reverts to `<td>` on load, but keeps its `scope`
attribute. This filter therefore **promotes any `<td scope="col|row|colgroup|
rowgroup">` to a real `<th>`** on render (preserving the authored scope), and
strips invalid `scope` values off `<td>`. Editor workflow for a one-off header
cell: in Source editing, add `scope="col"` (or `row`) to the `<td>`.

This only works if the `scope` attribute survives editing. On formats **without**
"Limit allowed HTML tags" (e.g. the `full_html` / WYSIWYG format), it already
does — no configuration needed. Only on a format that *does* restrict HTML would
you need to add `scope` to the allowed attributes of `<td>`/`<th>`. **Do not
enable "Limit allowed HTML tags" on a currently-unrestricted format** just for
this — it strips all non-allowlisted markup sitewide.

## Opting a table out

Add the class `no-table-filter` to a `<table>` and the filter leaves that entire
table alone — no scope added, promoted, or stripped:

```html
<table class="no-table-filter"> ... </table>
```

## Setup

1. Enable the module:

   ```bash
   drush en dgreat_table_scope
   ```

2. For each text format at
   `/admin/config/content/formats`, enable **"Add scope attributes to table
   headers"** and set its weight so it runs **after** "Limit allowed HTML tags"
   (the default weight of `20` already does this). This ensures the added
   `scope` attribute is not stripped by the allowed-HTML filter.

3. Export config as usual so the setting travels through your environments.

## Limitations

The col/row heuristic matches CKEditor 5's output structure (header rows in
`<thead>`, header columns as `<th>` in `<tbody>`). It intentionally does **not**
attempt `headers`/`id` association for genuinely complex tables — CKEditor 5
cannot author those relationships in the first place. For complex data tables,
author them as raw HTML via Source editing, or split them into simple tables.
