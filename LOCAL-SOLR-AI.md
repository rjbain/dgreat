# DGreat Solr semantic-search experiment

This checkout is separate from `../myusf-ai`. Its Lando application is
`dgreataisolr`, with its own MariaDB 10.6 database, Redis, and Solr 9.9 core.
The starting database is the latest downloaded backup of `dgreat.live`, dated
2026-11-17 16:00 UTC. Existing local public files were copied from `../myusf-ai`;
this is not a complete new download of Pantheon's files mount.

## Open the site

- Site: https://dgreataisolr.lndo.site
- Semantic search: https://dgreataisolr.lndo.site/ai-search
- Index: `/admin/config/search/search-api/index/solr_ai`
- Server: `/admin/config/search/search-api/server/solr_ai`

```sh
lando start
lando drush uli
```

## Search configuration

- AI 1.5.2 and OpenAI Provider 1.2.5
- Search API Solr Dense Vector 1.0.0-alpha9
- Embedding model: `text-embedding-3-small`, 1536 dimensions, cosine similarity
- Ranker: Pure Vector, top 10, minimum return 0.5
- Data sources: published pages and section homepages, rendered as anonymous
- Access processor enabled; the View does not bypass entity access
- Cron indexing and immediate indexing are disabled for this test index
- The existing keyword-search indexes are retained

The `dgreat_solr_ai` module strips layout markup before embedding rendered page
content. Because this Solr approach stores one vector per item, it embeds the
first 8000 tokens of a long page. It does not yet provide the chunk coverage of
the MariaDB experiment. Evaluate long-page relevance before production use.

The search View uses linked headings and plain-text excerpts of up to 300
characters. Search queries make real OpenAI embedding requests.

Browser indexing uses one item per request: the default 50-item batches can
exceed the local proxy's 60-second timeout. After a failed browser batch, return
to the index status page and start a new run, rather than retrying the old batch
URL (which retains its original batch size).

For `solr_ai`, the embedding processor skips the separate moderation request
when indexing this published site content. Query moderation remains enabled.
An embedding failure stops the batch with a clear error and leaves its items
queued; original text is never passed to a numeric vector field. The opt-in is
stored at `third_party_settings.dgreat_solr_ai.skip_index_moderation` on the index.

## Credentials

The existing OpenAI key is reused through the Key module's File provider at
`/app/.local/openai.key`. The `.local` directory is ignored by Git. Never export
the key value into configuration or commit that file. A deployment to Pantheon
needs an appropriate runtime secret and key-provider configuration of its own.

## Index more content

The initial run processed 25 of 6032 tracked items. Solr contains 11 searchable
documents with 1536-dimensional vectors; unpublished items are excluded by the
Entity status processor. The query "How can I practice speaking Spanish?"
returned relevant results through `/ai-search` with HTTP 200, linked titles,
and excerpts no longer than 300 characters. Solr logs confirmed a KNN query.

To continue in bounded batches:

```sh
lando drush search-api:index solr_ai --limit=100 --batch-size=5
lando drush search-api:status solr_ai
```

To index all remaining eligible items deliberately:

```sh
lando drush search-api:index solr_ai --batch-size=5
```

## Recreate configuration after another local database import

```sh
lando drush en ai ai_provider_openai search_api_solr search_api_pantheon search_api_solr_dense_vector dgreat_solr_ai -y
lando drush php:script scripts/setup-local-solr-ai.php
lando drush cr
```

Generated Solr configuration lives in `conf/solr9` and is mounted by Lando.
After changing embedding dimensions, model, or Solr field configuration,
regenerate and replace this configuration, rebuild the index service, and
re-index the content. For example:

```sh
lando drush search-api-solr:get-server-config solr_ai /app/.local/solr-config.zip
```

Extract the generated archive into `conf/solr9`, then run
`lando rebuild -s index -y`.

## Pantheon follow-up

The secret-free configuration snapshot is in `dev_config/solr-ai`. It contains
the current search server, index, View, embedding defaults, provider key
reference, and vector field type. It deliberately excludes `key.key.openai` and
the actual key value. These files are separate from the normal configuration
sync directory and are not imported automatically.

The exported server uses the local `index:8983` connector. Configure the
Pantheon connector before importing that server on Pantheon. Enable the
required modules first, create a Key named `openai` with the environment's
secure provider, and then import the adapted configuration. OpenAI Provider
can select that key at `/admin/config/ai/providers/openai`.

The Key module's File provider accepts a path rather than an API key value.
Use a Pantheon runtime secret with the Pantheon Secrets Key provider on the
hosted site. Choosing the Configuration provider instead would store the
secret in Drupal's database/configuration, where it can enter future exports.

`pantheon.yml` requests Solr 9. Before
Multidev testing, enable Pantheon Search, use the Pantheon connector instead of
the local `index:8983` connector, install the runtime API secret, and post the
Solr schema. The local setup script refuses to run outside `dgreataisolr`.

Pantheon's dense-vector integration guide describes experimental contributed
modules; the integration itself is not a supported Pantheon feature:
https://docs.pantheon.io/guides/pantheon-search/solr-drupal/dense-vector-search
