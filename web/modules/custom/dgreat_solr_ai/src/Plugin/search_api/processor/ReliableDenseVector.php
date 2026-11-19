<?php

namespace Drupal\dgreat_solr_ai\Plugin\search_api\processor;

use Drupal\ai\OperationType\Embeddings\EmbeddingsInput;
use Drupal\ai\Plugin\ProviderProxy;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_solr_dense_vector\Plugin\search_api\processor\DenseVector;

/**
 * Keeps failed embedding requests out of Solr's numeric vector fields.
 */
class ReliableDenseVector extends DenseVector {

  public function preprocessIndexItems(array $items) {
    // Preserve contributed behavior for other indexes.
    if ($this->index->id() !== 'solr_ai') {
      parent::preprocessIndexItems($items);
      return;
    }

    $config = $this->configuration;
    if (empty($config['ai_provider']) || empty($config['ai_model_id'])) {
      throw new SearchApiException('Configure an embedding provider and model before indexing.');
    }
    $provider = $this->getEmbeddingProvider();
    // This opt-in applies only to already-published indexed content, not queries.
    $tags = $this->index->getThirdPartySetting('dgreat_solr_ai', 'skip_index_moderation', FALSE)
      ? ['skip_moderation'] : [];

    foreach ($items as $item) {
      foreach ($item->getFields() as $field) {
        if ($field->getType() !== 'solr_densevector') {
          continue;
        }
        $vectors = [];
        foreach ($field->getValues() as $value) {
          $text = trim((string) $value);
          if ($text === '') {
            continue;
          }
          try {
            $vector = $provider->embeddings(new EmbeddingsInput($text), $config['ai_model_id'], $tags)->getNormalized();
            if (count($vector) !== (int) $config['vector_dimension']) {
              throw new \UnexpectedValueException('Embedding dimensions do not match the configured Solr field.');
            }
            foreach ($vector as $number) {
              if ((!is_float($number) && !is_int($number)) || !is_finite((float) $number)) {
                throw new \UnexpectedValueException('Embedding contains a non-numeric vector value.');
              }
            }
            $vectors[] = $vector;
          }
          catch (\Exception $exception) {
            // Search API's batch helper reports this cleanly and keeps the
            // entire failed batch queued. Never submit the original text.
            throw new SearchApiException('Embedding generation failed; items remain queued for retry. ' . $exception->getMessage(), 0, $exception);
          }
        }
        $field->setValues($vectors);
      }
    }
  }

  protected function getEmbeddingProvider(): ProviderProxy {
    return $this->aiProviderManager->createInstance($this->configuration['ai_provider']);
  }

}
