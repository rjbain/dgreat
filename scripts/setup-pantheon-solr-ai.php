<?php

/**
 * Restores the AI experiment after the PR Multidev's normal config import.
 * Does not run on dev, test, live, other Multidevs, or local environments.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\key\Entity\Key;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api_solr\Entity\SolrFieldType;
use Drupal\views\Entity\View;

if (getenv('PANTHEON_ENVIRONMENT') !== 'pr-748' || getenv('PANTHEON_SITE') !== '41cad7fe-472d-43a9-a470-d391b9622376') {
  throw new RuntimeException('This setup is restricted to dgreat.pr-748.');
}
$directory = dirname(DRUPAL_ROOT) . '/dev_config/solr-ai/';
$read = static function (string $name) use ($directory): array {
  $data = Yaml::decode(file_get_contents($directory . $name . '.yml'));
  unset($data['uuid'], $data['_core']);
  return $data;
};
$key = Key::load('openai');
if (!$key) {
  $saved = \Drupal::state()->get('myusf_solr_ai.preserved_openai_key');
  $key = Key::create($saved ?: [
    'id' => 'openai', 'label' => 'OpenAI', 'key_type' => 'authentication',
    'key_provider' => 'config', 'key_provider_settings' => ['key_value' => ''],
    'key_input' => 'text_field', 'key_input_settings' => [],
  ]);
  $key->save();
}
\Drupal::state()->delete('myusf_solr_ai.preserved_openai_key');
\Drupal::configFactory()->getEditable('ai_provider_openai.settings')->set('api_key', 'openai')->save();
\Drupal::configFactory()->getEditable('ai.settings')->set('default_providers.embeddings', [
  'provider_id' => 'openai', 'model_id' => 'text-embedding-3-small',
])->save();
$field_type = SolrFieldType::load('knn_vector_und_9_0_0');
// Module installation creates this entity with 1024 dimensions. Align it
// with text-embedding-3-small even when the default entity already exists.
$field_data = $read('search_api_solr.solr_field_type.knn_vector_und_9_0_0');
if (!$field_type) {
  $field_type = SolrFieldType::create($field_data);
}
$field_type->set('field_type', $field_data['field_type']);
$field_type->save();
$server = Server::load('solr_ai');
if (!$server) {
  $data = $read('search_api.server.solr_ai');
  $data['name'] = 'MyUSF Pantheon Solr AI';
  $data['backend_config']['connector'] = 'pantheon';
  $data['backend_config']['connector_config'] = ['timeout' => 10, 'index_timeout' => 60, 'solr_version' => '9'];
  $data['backend_config']['environment'] = 'pr-748';
  $server = Server::create($data);
  $server->save();
}
$index_data = $read('search_api.index.solr_ai');
$index = Index::load('solr_ai');
if (!$index) {
  Index::create($index_data)->save();
}
else {
  // Uninstalling processors during config import can strip them from an
  // otherwise surviving index. Restore the experiment's fields/processors.
  $changed = FALSE;
  foreach (['field_settings', 'processor_settings', 'third_party_settings'] as $setting) {
    $current = $index->get($setting) ?: [];
    $expected = array_replace($current, $index_data[$setting] ?? []);
    if ($current !== $expected) {
      $index->set($setting, $expected);
      $changed = TRUE;
    }
  }
  if ($changed) {
    $index->save();
  }
}
if (!View::load('ai_search')) {
  View::create($read('views.view.ai_search'))->save();
}
$role = \Drupal\user\Entity\Role::load('authenticated');
$role->grantPermission('access myusf site chat')->grantPermission('access deepchat api')->save();
\Drupal::service('search_api_pantheon.schema_poster')->postSchema('solr_ai');
echo "MyUSF AI search and chatbot restored. OpenAI key present: " . ($key->getKeyValue() ? 'yes' : 'no (enter it in the Keys form)') . ".\n";
