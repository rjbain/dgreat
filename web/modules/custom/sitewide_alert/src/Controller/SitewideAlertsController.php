<?php

namespace Drupal\sitewide_alert\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\sitewide_alert\SitewideAlertManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for callback that loads the alerts visible as JSON object.
 */
class SitewideAlertsController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The sitewide alert manager.
   *
   * @var \Drupal\sitewide_alert\SitewideAlertManager
   */
  private $sitewideAlertManager;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new SitewideAlertsController.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\sitewide_alert\SitewideAlertManager $sitewideAlertManager
   *   The sitewide alert manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(RendererInterface $renderer, SitewideAlertManager $sitewideAlertManager, ConfigFactoryInterface $configFactory) {
    $this->renderer = $renderer;
    $this->sitewideAlertManager = $sitewideAlertManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SitewideAlertsController {
    return new static(
      $container->get('renderer'),
      $container->get('sitewide_alert.sitewide_alert_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Load.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return Hello string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load() {
    $response = new CacheableJsonResponse([]);

    $sitewideAlertsJson = ['sitewideAlerts' => []];

    $sitewideAlerts = $this->sitewideAlertManager->activeVisibleSitewideAlerts();

    $sitewideAlertSettings = $this->configFactory->get('sitewide_alert.settings');

    if ($sitewideAlertSettings->get('display_order') === 'descending') {
      krsort($sitewideAlerts, SORT_NUMERIC);
    }

    $viewBuilder = $this->entityTypeManager()->getViewBuilder('sitewide_alert');

    foreach ($sitewideAlerts as $sitewideAlert) {
      $alertView = $viewBuilder->view($sitewideAlert);

      $sitewideAlertsJson['sitewideAlerts'][] = [
        'uuid' => $sitewideAlert->uuid(),
        'dismissible' => $sitewideAlert->isDismissible(),
        'dismissalIgnoreBefore' => $sitewideAlert->getDismissibleIgnoreBeforeTime(),
        'styleClass' => $sitewideAlert->getStyleClass(),
        'showOnPages' => $sitewideAlert->getPagesToShowOn(),
        'negateShowOnPages' => $sitewideAlert->shouldNegatePagesToShowOn(),
        'renderedAlert' => $this->renderer->renderPlain($alertView),
      ];
    }

    // Set response cache so it's invalidated whenever alerts get updated, or
    // settings are changed.
    $cacheableMetadata = (new CacheableMetadata())
      ->setCacheMaxAge(30)
      ->addCacheContexts(['languages'])
      ->setCacheTags(['sitewide_alert_list']);
    $cacheableMetadata->addCacheableDependency($sitewideAlertSettings);

    $response->addCacheableDependency($cacheableMetadata);
    $response->setData($sitewideAlertsJson);

    // Set the date this response expires so that Drupal's Page Cache will
    // expire this response when the next scheduled alert will be removed.
    // This is needed because Page Cache ignores max age as it does not respect
    // the cache max age. Note that the cache tags will still invalidate this
    // response in the case that new sitewide alerts are added or changed.
    // See Drupal\page_cache\StackMiddleware:storeResponse().
    if ($expireDate = $this->sitewideAlertManager->nextScheduledChange()) {
      $response->setExpires($expireDate->getPhpDateTime());
    }

    // Prevent the browser and downstream caches from caching for more than the
    // configured cache max age, in seconds.
    $cacheMaxAge = $sitewideAlertSettings->get('cache_max_age') ?: 15;
    $response->setMaxAge($cacheMaxAge);
    $response->setSharedMaxAge($cacheMaxAge);

    return $response;
  }

}
