<?php

namespace Drupal\dgreat_solr_ai\Plugin\search_api\processor;

use Drupal\ai\Utility\Tokenizer;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts rendered pages to bounded plain text before generating embeddings.
 */
#[SearchApiProcessor(
  id: 'dgreat_solr_embedding_text',
  label: new TranslatableMarkup('DGreat embedding text'),
  description: new TranslatableMarkup('Removes layout markup and limits embedding input to 8000 tokens.'),
  stages: ['preprocess_index' => 90],
)]
class EmbeddingText extends ProcessorPluginBase {

  protected Tokenizer $tokenizer;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->tokenizer = $container->get('ai.tokenizer');
    return $instance;
  }

  public function preprocessIndexItems(array $items) {
    $model = $this->index->getProcessor('solr_densevector')->getConfiguration()['ai_model_id'];
    $this->tokenizer->setModel($model);
    foreach ($items as $item) {
      foreach ($item->getFields() as $field) {
        if ($field->getType() !== 'solr_densevector') {
          continue;
        }
        $values = [];
        foreach ($field->getValues() as $value) {
          $text = preg_replace('/<!--.*?-->|<(script|style)\b[^>]*>.*?<\/\1>/is', '', (string) $value);
          $text = preg_replace('/<\/?(?:p|div|section|article|li|h[1-6]|br|hr)\b[^>]*>/i', ' ', $text);
          $text = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', Html::decodeEntities(strip_tags($text))));
          if ($text === '') {
            continue;
          }
          $tokens = $this->tokenizer->getTokens($text);
          if (count($tokens) > 8000) {
            $text = $this->tokenizer->decodeChunk(array_slice($tokens, 0, 8000));
            // A token boundary can split a multi-byte character.
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
          }
          $values[] = $text;
        }
        $field->setValues($values);
      }
    }
  }

}
