<?php

namespace Drupal\flag\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Controller responses to flag and unflag action links.
 *
 * If the action_link is a normal link then after an update the response to a
 *  valid request is a redirect to the entity with drupal update message.
 *
 * For an ajax_action_link the response is a set of AJAX commands to update the
 * link in the page. If the user agent has javascript disabled then the
 * behaviour reverts to that of a normal link.
 */

class ActionLinkController implements ContainerInjectionInterface {
  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   *
   * @param FlagServiceInterface $flag
   *   The flag service.
   */
  public function __construct(FlagServiceInterface $flag, RendererInterface $renderer) {
    $this->flagService = $flag;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('renderer')
    );
  }

  /**
   * Performs a flagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse|null
   *   The response object, only if successful.
   *
   * @see \Drupal\flag\Plugin\Reload
   */
  public function flag(FlagInterface $flag, $entity_id, Request $request) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    try {
      $this->flagService->flag($flag, $entity);
    }
    catch (\LogicException $e) {
      // Fail silently so we return to the entity, which will show an updated
      // link for the existing state of the flag.
    }

    return $this->generateResponse($flag, $entity, $request, $flag->getMessage('flag'));
  }

  /**
   * Performs a unflagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse|null
   *   The response object, only if successful.
   *
   * @see \Drupal\flag\Plugin\Reload
   */
  public function unflag(FlagInterface $flag, $entity_id, Request $request) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    try {
      $this->flagService->unflag($flag, $entity);
    }
    catch (\LogicException $e) {
      // Fail silently so we return to the entity, which will show an updated
      // link for the existing state of the flag.
    }

    return $this->generateResponse($flag, $entity, $request, $flag->getMessage('unflag'));
  }

  /**
   * Generates a response after the flag has been updated.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $message
   *   (optional) The message to flash.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response object.
   */
  protected function generateResponse(FlagInterface $flag, EntityInterface $entity, Request $request, $message = NULL) {
    if ($request->get(MainContentViewSubscriber::WRAPPER_FORMAT) == 'drupal_ajax') {
      // Create a new AJAX response.
      $response = new AjaxResponse();

      // Get the link type plugin.
      $link_type = $flag->getLinkTypePlugin();

      // Generate the link render array.
      $link = $link_type->getAsFlagLink($flag, $entity);

      // Generate a CSS selector to use in a JQuery Replace command.
      $selector = '.js-flag-' . Html::cleanCssIdentifier($flag->id()) . '-' . $entity->id();

      // Create a new JQuery Replace command to update the link display.
      $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($link));
      $response->addCommand($replace);

      if ($message) {
        // Push a message pulsing command onto the stack.
        $pulse = new ActionLinkFlashCommand($selector, $message);
        $response->addCommand($pulse);
     }
    }
    elseif ($entity->hasLinkTemplate('canonical')) {
      // Redirect back to the entity. A passed in destination query parameter
      // will automatically override this.
      $url_info = $entity->toUrl();

      $options['absolute'] = TRUE;
      $url = Url::fromRoute($url_info->getRouteName(), $url_info->getRouteParameters(), $options);
      $response = new RedirectResponse($url->toString());

    }
    else {
      // For entities that don't have a canonical URL (like paragraphs),
      // redirect to the front page.
      $front = Url::fromUri('internal:/');
      $response = new RedirectResponse($front);
    }

    return $response;
  }

}
