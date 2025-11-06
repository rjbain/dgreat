<?php

namespace Drupal\rate_vote_summary\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rate_vote_summary\VoteTallyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Vote Summary" block.
 *
 * @Block(
 *   id = "rate_vote_summary_block",
 *   admin_label = @Translation("Rate vote summary"),
 *   category = @Translation("Custom")
 * )
 */
class VoteSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected VoteTallyService $tally;
  protected RouteMatchInterface $routeMatch;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->tally = $container->get('rate_vote_summary.tally');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

public function build(): array {
  $entity = $this->getRoutedEntity();
  if (!$entity || $entity->getEntityTypeId() !== 'node') {
    return ['#markup' => ''];
  }
  if ($entity->bundle() !== 'submission') {
    return ['#markup' => ''];
  }

  [$up, $down, $mine] = $this->tally->getTallies((int) $entity->id());

  $wrapper_id = 'rate-vote-summary-' . (int) $entity->id();

  $build = [
    '#theme' => 'rate_vote_summary',
    '#up_total' => $up,
    '#down_total' => $down,
    '#my_vote' => $mine,
    '#prefix' => '<div id="' . $wrapper_id . '">',
    '#suffix' => '</div>',
    '#attached' => [
      'library' => ['rate_vote_summary/refresh'],
      'drupalSettings' => [
        'rateVoteSummary' => [
          (int) $entity->id() => [
            'wrapperId' => $wrapper_id,
            // Route below (Step 2) returns fresh HTML for this node’s block:
            'refreshUrl' => \Drupal\Core\Url::fromRoute(
              'rate_vote_summary.refresh',
              ['entity_type' => 'node', 'entity_id' => (int) $entity->id()]
            )->toString(),
            // Heuristic: substring to detect the Rate widget’s AJAX call URL.
            'watchUrlPart' => '/rate/', // adjust if your rate AJAX URL differs
          ],
        ],
      ],
    ],
    '#cache' => [
      'contexts' => ['user', 'session', 'route', 'url'],
      'tags' => $entity->getCacheTags(),
      'max-age' => 0,
    ],
  ];

  return $build;
}




  /**
   * Try to get the primary routed entity (e.g. node on node/%).
   */
  protected function getRoutedEntity(): ?EntityInterface {
    foreach (['node', 'entity'] as $param) {
      $candidate = $this->routeMatch->getParameter($param);
      if ($candidate instanceof EntityInterface) {
        return $candidate;
      }
    }
    // Fallback: if the route param is a scalar nid and we’re on node/%nid.
    $nid = $this->routeMatch->getParameter('node');
    if (is_scalar($nid)) {
      return \Drupal::entityTypeManager()->getStorage('node')->load((int) $nid);
    }
    return NULL;
  }

}
