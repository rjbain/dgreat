<?php

namespace Drupal\rate_vote_summary\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rate_vote_summary\VoteTallyService;

/**
 * @ViewsField("rate_vote_summary_field")
 */
class VoteSummary extends FieldPluginBase implements ContainerFactoryPluginInterface {

  protected VoteTallyService $tally;
  protected RendererInterface $renderer;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->tally = $container->get('rate_vote_summary.tally');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  public function query() {
    // No query alteration; we render from the row entity.
  }

  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if (!$entity instanceof EntityInterface || $entity->getEntityTypeId() !== 'node' || $entity->bundle() !== 'submission') {
      return [];
    }

    [$up, $down, $mine] = $this->tally->getTallies((int) $entity->id());
    $wrapper_id = 'rate-vote-summary-' . (int) $entity->id();

    $build = [
      '#theme' => 'rate_vote_summary',              // reuses your Twig template
      '#up_total' => $up,
      '#down_total' => $down,
      '#my_vote' => $mine,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => ['rate_vote_summary/refresh'], // the same AJAX-refresh JS
      ],
      '#cache' => [
        'contexts' => ['user', 'session'],
        'tags' => $entity->getCacheTags(),
        'max-age' => 0,
      ],
    ];

    // Render to markup for the field cell.
    return $this->renderer->renderRoot($build);
  }

}