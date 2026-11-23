<?php

namespace Drupal\dgreat_solr_ai\Usage;

/**
 * Temporary per-search estimate for text-embedding-3-small.
 */
final class SearchUsage {

  // Standard USD per million tokens, checked November 23, 2026.
  // https://developers.openai.com/api/docs/models/text-embedding-3-small
  public const RATE = 0.02;

  public static function buildForView(\Drupal\views\ViewExecutable $view): array {
    if ($view->id() !== 'ai_search' || !\Drupal\Core\Site\Settings::get('myusf_ai_search_show_usage', getenv('LANDO_APP_NAME') === 'dgreataisolr')) {
      return [];
    }
    $query = $view->query instanceof \Drupal\search_api\Plugin\views\query\SearchApiQuery
      ? $view->query->getSearchApiQuery() : NULL;
    if (!$query || $query->getIndex()->id() !== 'solr_ai') {
      return [];
    }
    $configuration = $query->getIndex()->getProcessor('solr_densevector')->getConfiguration();
    // Do not quote this model's price if the index switches embedding models.
    if (($configuration['ai_provider'] ?? '') !== 'openai' || ($configuration['ai_model_id'] ?? '') !== 'text-embedding-3-small') {
      return [];
    }
    $keys = $query->getOriginalKeys();
    $text = is_array($keys) ? implode(' ', $keys) : (string) $keys;
    $tokens = 0;
    if ($text !== '') {
      $tokenizer = \Drupal::service('ai.tokenizer');
      $tokenizer->setModel('text-embedding-3-small');
      $tokens = count($tokenizer->getTokens($text));
    }
    return [
      '#markup' => \Drupal\dgreat_solr_ai\Usage\SearchUsage::markup($tokens),
      '#cache' => ['max-age' => 0],
    ];
  }

  public static function cost(int $tokens): float {
    return max(0, $tokens) * self::RATE / 1000000;
  }

  public static function markup(int $tokens): string {
    $tokens = max(0, $tokens);
    $cost = number_format(self::cost($tokens), 8, '.', '');
    $thousand = number_format(self::cost($tokens) * 1000, 6, '.', '');
    $note = $tokens ? 'Estimate assumes one embedding request for this search. Cached results may avoid a new API call.' : 'No search text: no search embedding is needed.';
    return '<div class="myusf-search-usage"><hr><strong>Temporary search usage</strong>'
      . '<p>Search embedding: <strong>' . number_format($tokens) . ' tokens</strong> (estimated). '
      . 'Generated answer tokens: 0.</p><p>Estimated cost: <strong>US$' . $cost
      . '</strong> per search; about US$' . $thousand . ' per 1,000 similar searches.</p>'
      . '<details><summary>Estimate details</summary><p>' . $note
      . ' Only the search text is embedded; the returned pages are not sent to a chat model. '
      . 'Model: text-embedding-3-small, $0.02 per million tokens. Rates checked November 23, 2026. '
      . 'Excludes content indexing, hosting, taxes and unobserved retries. This is an estimate, not billing data. '
      . '<a href="https://developers.openai.com/api/docs/models/text-embedding-3-small">OpenAI pricing</a>.</p></details></div>';
  }

}
