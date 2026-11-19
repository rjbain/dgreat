# MyUSF Solr semantic-search experiment

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

The authoritative AI search configuration is in `config/` and uses Pantheon's
connector. The historical snapshot in `dev_config/solr-ai` uses the local
connector and is no longer the deployment source. Both exclude the OpenAI
secret. Each environment supplies its own Key named `openai`, selected at
`/admin/config/ai/providers/openai`.

## Local site chatbot

The custom `myusf_site_chat` module connects the Drupal AI Chatbot interface directly to the existing `solr_ai` Search API index. It does not require the deprecated bundled AI Search module or a separate vector database.

After enabling it with `lando drush en myusf_site_chat -y`, run `lando drush php:script scripts/setup-local-site-chat.php`. This setup script is restricted to the `dgreataisolr` Lando app and grants signed-in users the two chat permissions. Visit `/ask-myusf` while signed in. Anonymous access is disabled by default. No Multidev configuration is changed by this setup.

The connector retrieves up to five published pages, checks the current user's node access, and sends the question and relevant excerpts to OpenAI using the existing `openai` key and `gpt-4o-mini`. It accepts only structured answers that claim support and cite retrieved source IDs; citation links are built by Drupal. It declines when evidence is insufficient. These safeguards reduce unsupported answers but do not guarantee factual accuracy. The interface includes a disclosure about sending questions and excerpts to OpenAI.

Requests are limited to 20 per hour per IP. Questions are capped at 2,000 characters, each source excerpt at 10,000 characters, and generated output at 700 tokens. The existing embedding index covers the first 8,000 tokens of each rendered page. Follow-up user questions are supplied as context; retrieval uses the current question, so explicit follow-ups work best. This is a local prototype; browser reloads do not preserve server-side conversation history.

Validation: `lando php vendor/bin/phpunit --bootstrap web/core/tests/bootstrap.php --no-configuration web/modules/custom/myusf_site_chat/tests/src/Unit/SiteChatTest.php` checks rejected unsupported/invented citations. `lando drush php:script scripts/test-local-site-chat.php` makes real OpenAI calls against local Solr, testing a financial-aid answer and an off-topic refusal. Run the latter only when intending to consume API tokens.

## PR Multidev restoration

CircleCI imports the main configuration during each PR build. Previously the AI experiment was absent from that directory, allowing imports to uninstall the modules. The shared export now includes it. For `dgreat.pr-748` only, the CI initialization script now preserves an existing `openai` Key configuration in temporary Drupal state before import, enables the experimental modules afterward, and runs `scripts/setup-pantheon-solr-ai.php`. The restoration script checks both the Pantheon site UUID and environment before making changes, restores required AI entities and processors, selects the Pantheon Solr connector, posts the vector schema, and grants chat access to signed-in users. It removes the temporary key state after restoration. These files take effect wherever this branch’s configuration is imported.

The restoration script creates an empty `openai` key entry when no saved key exists. Enter the API key at `/admin/config/system/keys/manage/openai`; do not export or commit that key configuration. The script does not copy local credentials. Replacing the entire Multidev database still requires re-entering any credentials not present in the replacement database. Indexing can be started at `/admin/config/search/search-api/index/solr_ai` after the key is configured.

The CI Multidev guard accepts either Pantheon's site name or its UUID. Restoration also replaces embedding fields/processors stripped from a surviving index during module uninstall. Before reinstalling a disabled dense-vector module, it removes that module's orphaned vector-field default, then recreates it at 1536 dimensions. This avoids an existing-configuration installation error and ensures long pages pass through the 8,000-token limiter and validated embedding processor.

AI restoration runs as scoped EXIT cleanup after PR database initialization, including when a configuration import fails partway through. It preserves the original nonzero CI status rather than reporting a failed import as successful. Avoid running manual index configuration changes while an older deployment is still importing configuration into the same Multidev.


## Shared AI configuration

The authoritative AI search configuration is now in `config/`, which CircleCI imports. This includes the enabled AI modules in `core.extension`, AI/OpenAI settings, the Solr server and 1536-dimension field type, index fields/processors, AI search view, and signed-in chatbot permissions. `scripts/setup-pantheon-solr-ai.php` reads these same files; the old `dev_config/solr-ai` snapshot is retained only as a historical local snapshot.

The shared server uses Pantheon's connector. `settings.php` selects the standard local connector only for `dgreataisolr` and sets the Solr environment per Pantheon environment. The OpenAI key entity is excluded from exports and preserved during import with `config_ignore` (`key.key.openai`); each environment supplies its own key. Do not commit `key.key.openai.yml` or `.local/` files. The PR restoration script remains a fallback and uploads the Solr schema.

Future local exports should update the AI files in `config/` while retaining the Pantheon connector in the exported server file. Review the diff to avoid exporting unrelated local settings or credentials. Once this branch is deployed, configuration imports keep the AI modules and search configuration enabled instead of uninstalling the experiment. These settings also enable AI on any environment that imports this branch's main configuration; there is no production deployment in this change.
