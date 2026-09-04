# dgreat site

[![CircleCI](https://circleci.com/gh/rjbain/dgreat.svg?style=shield)](https://circleci.com/gh/rjbain/dgreat)
[![Dashboard dgreat](https://img.shields.io/badge/dashboard-dgreat-yellow.svg)](https://dashboard.pantheon.io/sites/41cad7fe-472d-43a9-a470-d391b9622376#dev/code)
[![Dev Site dgreat](https://img.shields.io/badge/site-dgreat-blue.svg)](http://dev-dgreat.pantheonsite.io/)

## Solr configset note

If Drupal shows missing schema/configuration elements on the `Sitewide Search`
Search API server page, the usual cause is an outdated Solr configset deployed
to the remote Solr core rather than a bad Drupal config export.

This project is already configured for Search API Solr 4.4.0 with Solr 8:

- `composer.lock` includes `drupal/search_api_solr` 4.4.0
- `config/search_api.server.sitewide_search.yml` sets `solr_version: '8'`

To clear the warning:

1. Open the Search API server at
   `/admin/config/search/search-api/server/sitewide_search`.
2. Download the newly generated `config.zip` from the server page.
3. Deploy that `config.zip` to the backing Solr core/configset for the target
   Pantheon environment.
4. Reload the server page and reindex if prompted.

The recommended matching configset for this project is
`drupal-4.4.0-solr-8.x`.

Reference:
- Search API Solr docs say to download `config.zip` from the server details page
  and redeploy it whenever Solr config requirements change:
  https://www.drupal.org/project/search_api_solr
