<?php

namespace Drupal\dgreat_search_promotions\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api_solr\Event\PostConvertedQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Includes and boosts a promoted page in the sitewide Solr query.
 */
final class PromotionQuerySubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function getSubscribedEvents(): array {
    return [SearchApiSolrEvents::POST_CONVERT_QUERY => 'promote'];
  }

  public function promote(PostConvertedQueryEvent $event): void {
    $query = $event->getSearchApiQuery();
    $view = $query->getOption('search_api_view');
    if ($query->getIndex()->id() !== 'sitewide_search' || !$view || $view->id() !== 'sitewide_search' || $view->current_display !== 'page_1') {
      return;
    }

    $input = $view->getExposedInput();
    if (!empty($input['files_only'])) {
      return;
    }
    $raw_phrase = $input['search_api_fulltext'] ?? NULL;
    if (!is_string($raw_phrase) || trim($raw_phrase) === '') {
      return;
    }
    $phrase = mb_strtolower(preg_replace('/\s+/u', ' ', trim($raw_phrase)));
    foreach ($this->configFactory->get('dgreat_search_promotions.settings')->get('rules') ?: [] as $rule) {
      if ($rule['phrase'] !== $phrase) {
        continue;
      }
      $node = $this->entityTypeManager->getStorage('node')->load($rule['nid']);
      if (!$node instanceof NodeInterface || !$node->isPublished() || !$node->access('view')) {
        return;
      }

      $backend = $query->getIndex()->getServerInstance()->getBackend();
      $field = $backend->getSolrFieldNames($query->getIndex())['search_api_id'];
      $item_id = 'entity:node/' . $node->id() . ':' . $node->language()->getId();
      $solr_query = $event->getSolariumQuery();
      $promotion = $field . ':' . $solr_query->getHelper()->escapePhrase($item_id) . '^1000000';
      $solr_query->setQuery($solr_query->getQuery() . ' OR ' . $promotion);
      return;
    }
  }

}
