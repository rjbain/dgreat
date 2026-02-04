<?php

namespace Drupal\rate_vote_summary\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rate_vote_summary\VoteTallyService;

class RefreshController extends ControllerBase {

  protected VoteTallyService $tally;
  protected RendererInterface $renderer;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->tally = $container->get('rate_vote_summary.tally');
    $instance->renderer = $container->get('renderer'); // inject renderer service
    return $instance;
  }

  public function refresh(string $entity_type, int $entity_id): Response {
    $storage = $this->entityTypeManager()->getStorage($entity_type);
    /** @var \Drupal\Core\Entity\EntityInterface|null $entity */
    $entity = $storage ? $storage->load($entity_id) : NULL;
    if (!$entity instanceof EntityInterface) {
      return new Response('', 404);
    }
    // Only allow for node/submission as we scoped the block that way.
    if ($entity_type !== 'node' || $entity->bundle() !== 'submission') {
      return new Response('', 403);
    }

    [$up, $down, $mine] = $this->tally->getTallies($entity_id);
    $wrapper_id = 'rate-vote-summary-' . $entity_id;

    $build = [
      '#theme' => 'rate_vote_summary',
      '#up_total' => $up,
      '#down_total' => $down,
      '#my_vote' => $mine,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#cache' => [
        'contexts' => ['user', 'session', 'route', 'url'],
        'tags' => $entity->getCacheTags(),
        'max-age' => 0,
      ],
    ];

    $html = (string) $this->renderer->renderRoot($build);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

}
