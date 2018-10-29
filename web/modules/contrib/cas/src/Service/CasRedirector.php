<?php

namespace Drupal\cas\Service;

use Drupal\cas\CasRedirectData;
use Drupal\cas\CasRedirectResponse;
use Drupal\cas\Event\CasPreRedirectEvent;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;

/**
 * Class CasRedirector.
 */
class CasRedirector {

  /**
   * The CasHelper.
   *
   * @var CasHelper
   */
  protected $casHelper;

  /**
   * The EventDispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Stores URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * CasRedirector constructor.
   *
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CasHelper service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The EventDispatcher service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(CasHelper $cas_helper, EventDispatcherInterface $event_dispatcher, UrlGeneratorInterface $url_generator) {
    $this->casHelper = $cas_helper;
    $this->eventDispatcher = $event_dispatcher;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Determine login URL response.
   *
   * @param \Drupal\cas\CasRedirectData $data
   *   Data used to generate redirector.
   * @param bool $force
   *   True implies that you always want to generate a redirector as occurs with
   *   the ForceRedirectController. False implies redirector is controlled by
   *   the allow_redirect property in the CasRedirectData object.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Drupal\cas\CasRedirectResponse|null
   *   The RedirectResponse or NULL if a redirect shouldn't be done.
   */
  public function buildRedirectResponse(CasRedirectData $data, $force = FALSE) {
    $response = NULL;

    // Generate login url.
    $login_url = $this->casHelper->getServerBaseUrl() . 'login';

    // Dispatch an event that allows modules to alter or prevent the redirect.
    $pre_redirect_event = new CasPreRedirectEvent($data);
    $this->eventDispatcher->dispatch(CasHelper::EVENT_PRE_REDIRECT, $pre_redirect_event);

    // Determine the service URL.
    $service_parameters = $data->getAllServiceParameters();
    $parameters = $data->getAllParameters();
    $parameters['service'] = $this->urlGenerator->generate('cas.service', $service_parameters, UrlGeneratorInterface::ABSOLUTE_URL);

    $login_url .= '?' . UrlHelper::buildQuery($parameters);

    // Get the redirection response.
    if ($force || $data->willRedirect()) {
      // $force implies we are on the /cas url or equivalent, so we
      // always want to redirect and data is always cacheable.
      if (!$force && !$data->getIsCacheable()) {
        return new CasRedirectResponse($login_url);
      }
      else {
        $cacheable_metadata = new CacheableMetadata();
        // Add caching metadata from CasRedirectData.
        if (!empty($data->getCacheTags())) {
          $cacheable_metadata->addCacheTags($data->getCacheTags());
        }
        if (!empty($data->getCacheContexts())) {
          $cacheable_metadata->addCacheContexts($data->getCacheContexts());
        }
        $response = new TrustedRedirectResponse($login_url);
        $response->addCacheableDependency($cacheable_metadata);
      }
      $this->casHelper->log(LogLevel::DEBUG, "Cas redirecting to %url", array('%url' => $login_url));
    }
    return $response;
  }

}
