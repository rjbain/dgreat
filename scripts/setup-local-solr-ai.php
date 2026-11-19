<?php

/**
 * Configures the isolated Solr AI experiment after importing the live database.
 *
 * Run with: lando drush php:script scripts/setup-local-solr-ai.php
 * The OpenAI secret is read from the Git-ignored .local/openai.key file.
 */

use Drupal\key\Entity\Key;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\views\Entity\View;

if (getenv('LANDO_APP_NAME') !== 'dgreataisolr') {
  throw new RuntimeException('This script may only run in dgreataisolr.');
}
if (!is_readable('/app/.local/openai.key')) {
  throw new RuntimeException('Create the local OpenAI key file before setup.');
}

$key = Key::load('openai') ?: Key::create([
  'id' => 'openai',
  'label' => 'OpenAI (local file)',
  'key_type' => 'authentication',
  'key_input' => 'none',
]);
$key->set('key_provider', 'file');
$key->set('key_provider_settings', ['file_location' => '/app/.local/openai.key', 'strip_line_breaks' => TRUE]);
$key->save();
\Drupal::configFactory()->getEditable('ai_provider_openai.settings')->set('api_key', 'openai')->save();
\Drupal::configFactory()->getEditable('ai.settings')->set('default_providers.embeddings', [
  'provider_id' => 'openai', 'model_id' => 'text-embedding-3-small',
])->save();

$server = Server::load('solr_ai') ?: Server::create([
  'id' => 'solr_ai', 'name' => 'Local Solr AI', 'status' => TRUE, 'backend' => 'search_api_solr',
]);
$server->setBackendConfig([
  'connector' => 'standard',
  'connector_config' => [
    'scheme' => 'http', 'host' => 'index', 'port' => 8983, 'path' => '/', 'core' => 'dgreat',
    'solr_version' => '9.9.0', 'timeout' => 10, 'index_timeout' => 60,
  ],
  'retrieve_data' => TRUE, 'highlight_data' => FALSE, 'domain' => 'generic', 'environment' => 'local',
]);
$server->save();

$render_configuration = [
  'roles' => ['anonymous'],
  'view_mode' => ['entity:node' => [':default' => 'default']],
];
$index = Index::load('solr_ai') ?: Index::create([
  'id' => 'solr_ai', 'name' => 'Solr AI Pages', 'status' => TRUE, 'server' => 'solr_ai',
  'read_only' => FALSE,
  'datasource_settings' => ['entity:node' => [
    'bundles' => ['default' => FALSE, 'selected' => ['page', 'section_homepage']],
    'languages' => ['default' => TRUE, 'selected' => []],
  ]],
  'tracker_settings' => ['default' => ['indexing_order' => 'fifo']],
  'options' => ['cron_limit' => 0, 'index_directly' => FALSE, 'track_changes_in_references' => TRUE],
]);
$index->set('field_settings', [
  'title' => ['label' => 'Title', 'datasource_id' => 'entity:node', 'property_path' => 'title', 'type' => 'text'],
  'field_display_title' => ['label' => 'Display Title', 'datasource_id' => 'entity:node', 'property_path' => 'field_display_title', 'type' => 'string'],
  'body' => ['label' => 'Rendered page text', 'datasource_id' => NULL, 'property_path' => 'rendered_item', 'type' => 'text', 'configuration' => $render_configuration],
  'body_vector' => ['label' => 'Page embedding', 'datasource_id' => NULL, 'property_path' => 'rendered_item', 'type' => 'solr_densevector', 'configuration' => $render_configuration],
]);
$index->set('processor_settings', [
  'rendered_item' => [], 'entity_status' => [], 'content_access' => [],
  'dgreat_solr_embedding_text' => ['weights' => ['preprocess_index' => 90]],
  'html_filter' => ['all_fields' => FALSE, 'fields' => ['body'], 'title' => FALSE, 'alt' => FALSE, 'tags' => []],
  'solr_densevector' => [
    'ai_provider' => 'openai', 'ai_model_id' => 'text-embedding-3-small',
    'vector_dimension' => 1536, 'similarity_function' => 'cosine', 'content_field' => 'body',
    'weights' => ['preprocess_index' => 100],
  ],
]);
$index->setThirdPartySetting('search_api_solr', 'dense_vector', [
  'enable_vector_rerank' => TRUE, 'dense_vector_rank_plugin' => 'pure_vector',
  'plugin_configuration' => ['top_k' => 10, 'minimum_return' => 0.5, 'minimum_traverse' => 0],
]);
$index->setThirdPartySetting('dgreat_solr_ai', 'skip_index_moderation', TRUE);
$index->save();

$field_type = \Drupal::entityTypeManager()->getStorage('solr_field_type')->load('knn_vector_und_9_0_0');
$settings = $field_type->getFieldType();
$settings['vectorDimension'] = 1536;
$settings['similarityFunction'] = 'cosine';
$field_type->setFieldType($settings);
$field_type->save();

if (!View::load('ai_search')) {
  $table = 'search_api_index_solr_ai';
  $view = View::create([
    'id' => 'ai_search', 'label' => 'AI Search (Solr)', 'status' => TRUE,
    'base_table' => $table, 'base_field' => 'search_api_id',
    'display' => [
      'default' => [
        'id' => 'default', 'display_title' => 'Default', 'display_plugin' => 'default', 'position' => 0,
        'display_options' => [
          'title' => 'AI Search (Solr)', 'access' => ['type' => 'none'], 'cache' => ['type' => 'search_api_none'],
          'query' => ['type' => 'search_api_query', 'options' => ['bypass_access' => FALSE, 'skip_access' => FALSE]],
          'pager' => ['type' => 'full', 'options' => ['items_per_page' => 10]],
          'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Search']],
          'style' => ['type' => 'default'], 'row' => ['type' => 'fields'],
          'fields' => [
            'field_display_title' => [
              'id' => 'field_display_title', 'table' => $table, 'field' => 'field_display_title',
              'plugin_id' => 'search_api_field', 'label' => '', 'field_rendering' => TRUE,
              'type' => 'string', 'settings' => ['link_to_entity' => TRUE],
            ],
            'field_migrate_final_1' => [
              'id' => 'field_migrate_final_1', 'table' => $table, 'field' => 'body',
              'plugin_id' => 'search_api', 'label' => '',
            ],
          ],
          'filters' => ['search_api_fulltext' => [
            'id' => 'search_api_fulltext', 'table' => $table, 'field' => 'search_api_fulltext',
            'plugin_id' => 'search_api_fulltext', 'operator' => 'and', 'value' => '', 'exposed' => TRUE,
            'expose' => ['identifier' => 'search_api_fulltext', 'label' => 'Search', 'required' => FALSE],
            'parse_mode' => 'direct', 'fields' => ['title', 'body'],
          ]],
        ],
      ],
      'page_1' => [
        'id' => 'page_1', 'display_title' => 'Page', 'display_plugin' => 'page', 'position' => 1,
        'display_options' => ['path' => 'ai-search'],
      ],
    ],
  ]);
  $view->save();
}

echo "Configured local Solr server, semantic index, OpenAI provider, and /ai-search.\n";
