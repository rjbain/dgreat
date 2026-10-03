# CKEditor 5 Media embed, for Drupal

This repository packages the **build output** of the
[`@ckeditor/ckeditor5-media-embed`](https://www.npmjs.com/package/@ckeditor/ckeditor5-media-embed)
plugin as a Composer `drupal-library`, so `drupal/ckeditor_media_embed` can require it the way
`drupal/anchor_link` requires
[`vardot/ckeditor5-anchor-drupal`](https://github.com/Vardot/ckeditor5-anchor-drupal) — with
Composer, rather than a Drush download or a copy out of `node_modules`.

Only what Drupal serves is shipped: `build/`, the `lang/` contexts and `theme/`.

## Installation

```bash
composer require vardot/ckeditor5-media-embed-drupal
```

This package declares **no `require` of its own**. It is a built JavaScript asset,
not PHP: requiring `drupal/core` here would pull Drupal core and about 130 other
packages in just to place one file, and it would say nothing useful, because the
constraint that actually matters is the CKEditor 5 version core bundles — which a
Composer constraint on `drupal/core` cannot express. That coupling is carried by
the tag you require, and by the table below.

It works out of the box — **the consuming project adds nothing**.

`drupal/ckeditor_media_embed` loads the plugin from
`libraries/ckeditor5/plugins/media-embed/build/media-embed.js`, which is a nested directory that
the usual `web/libraries/{$name}` installer path cannot produce on its own. This package places
itself there instead, by declaring `composer/installers`' own
[`extra.installer-name`](https://github.com/composer/installers#custom-install-names):

```json
"extra": {
  "installer-name": "ckeditor5/plugins/media-embed"
}
```

That overrides `{$name}` for this package only, so the project keeps the single generic
`"web/libraries/{$name}": ["type:drupal-library"]` rule it already has, with no extra plugin and no
per-package path.

## Versioning — match Drupal core's CKEditor 5

**A CKEditor 5 plugin must be built against the same CKEditor 5 version Drupal core bundles.**
Mixing minors makes the editor fail with `ckeditor-duplicated-modules`. Read core's version from
`web/core/core.libraries.yml` (the `ckeditor5:` entry) and require the tag that matches it:

| Drupal core | CKEditor 5 in core | Require |
|---|---|---|
| 11.4.x | 47.6.2 | `~47.6.2` |

## Licence, and why not the LTS line

Tags here are built **only from GPL dual-licensed CKEditor 5 releases**.

CKEditor 5 releases from 47.7.0 on that line are the **Long Term Support edition**, which CKSource
publishes under a **commercial licence only** — there is no GPL option, so they cannot be
redistributed here or shipped in a GPL-2.0-or-later Drupal distribution. `47.6.2` is the last
GPL dual-licensed release of the 47.6 line, and it is the one Drupal 11.4 core bundles.

See [LICENSE.md](LICENSE.md) — GNU General Public License Version 2 or later, or commercial terms
from CKSource. © 2003–2026 CKSource Holding sp. z o.o.

## Upstream

- Source: https://github.com/ckeditor/ckeditor5 (`packages/ckeditor5-media-embed`)
- Documentation: https://ckeditor.com/docs/ckeditor5/latest/features/media-embed.html

## Maintainers

- [Vardot](https://github.com/vardot)
