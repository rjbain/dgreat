<?php

namespace Drupal\cas\Subscriber;

use Drupal\cas\CasRedirectData;
use Drupal\cas\Service\CasRedirector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\cas\Service\CasHelper;

/**
 * Provides a CasSubscriber.
 */
class CasSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Route matcher object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * CAS helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * CasRedirector.
   *
   * @var \Drupal\cas\Service\CasRedirector
   */
  protected $casRedirector;

  /**
   * Frequency to check for gateway login.
   *
   * @var array
   */
  protected $gatewayCheckFrequency;

  /**
   * Paths to check for gateway login.
   *
   * @var array
   */
  protected $gatewayPaths = [];

  /**
   * Is forced login configuration setting enabled.
   *
   * @var bool
   */
  protected $forcedLoginEnabled = FALSE;

  /**
   * Paths to check for forced login.
   *
   * @var array
   */
  protected $forcedLoginPaths = [];

  /**
   * Constructs a new CasSubscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_matcher
   *   The route matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS Helper service.
   * @param \Drupal\cas\Service\CasRedirector $cas_redirector
   *   The CAS Redirector Service.
   */
  public function __construct(RequestStack $request_stack, RouteMatchInterface $route_matcher, ConfigFactoryInterface $config_factory, AccountInterface $current_user, ConditionManager $condition_manager, CasHelper $cas_helper, CasRedirector $cas_redirector) {
    $this->requestStack = $request_stack;
    $this->routeMatcher = $route_matcher;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->conditionManager = $condition_manager;
    $this->casHelper = $cas_helper;
    $this->casRedirector = $cas_redirector;
    $settings = $this->configFactory->get('cas.settings');
    $this->gatewayCheckFrequency = $settings->get('gateway.check_frequency');
    $this->gatewayPaths = $settings->get('gateway.paths');
    $this->forcedLoginPaths = $settings->get('forced_login.paths');
    $this->forcedLoginEnabled = $settings->get('forced_login.enabled');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority is just before the Dynamic Page Cache subscriber, but after
    // important services like route matcher and maintenance mode subscribers.
    $events[KernelEvents::REQUEST][] = array('handle', 29);
    $events[KernelEvents::EXCEPTION][] = ['onException', 0];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The response event from the kernel.
   */
  public function handle(GetResponseEvent $event) {
    // Don't do anything if this is a sub request and not a master request.
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Some routes we don't want to run on.
    if ($this->isIgnoreableRoute()) {
      return;
    }

    // The service controller may have indicated that this current request
    // should not be automatically sent to CAS for authentication checking.
    // This is to prevent infinite redirect loops.
    $session = $this->requestStack->getCurrentRequest()->getSession();
    if ($session && $session->has('cas_temp_disable_auto_auth')) {
      $session->remove('cas_temp_disable_auto_auth');
      $this->casHelper->log(LogLevel::DEBUG, "Temp disable flag set, skipping CAS subscriber.");
      return;
    }

    $return_to = $this->requestStack->getCurrentRequest()->getUri();
    $redirect_data = new CasRedirectData(['returnto' => $return_to]);

    // Nothing to do if the user is already logged in.
    if ($this->currentUser->isAuthenticated()) {
      $redirect_data->preventRedirection();
    }
    else {
      // Default assumption is that we don't want to redirect unless page
      // critera matches.
      $redirect_data->preventRedirection();

      // Check to see if we should initiate a gateway auth check.
      if ($this->handleGateway()) {
        $redirect_data->setParameter('gateway', 'true');
        $this->casHelper->log(LogLevel::DEBUG, 'Initializing gateway auth from CasSubscriber.');
        $redirect_data->forceRedirection();
      };
      // Check to see if we should require a forced login.
      if ($this->handleForcedPath()) {
        $this->casHelper->log(LogLevel::DEBUG, 'Initializing forced login auth from CasSubscriber.');
        $redirect_data->setParameter('gateway', NULL);
        $redirect_data->setIsCacheable(TRUE);
        $redirect_data->forceRedirection();
      };
    }

    // If we're still going to redirect, lets do it.
    $response = $this->casRedirector->buildRedirectResponse($redirect_data);
    if ($response) {
      $event->setResponse($response);
    }
  }

  /**
   * Check if the current path is a forced login path.
   *
   * @return bool
   *   TRUE if current path is a forced login path, FALSE otherwise.
   */
  private function handleForcedPath() {
    if (!$this->forcedLoginEnabled) {
      return FALSE;
    }

    // Check if user provided specific paths to force/not force a login.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($this->forcedLoginPaths);

    if ($this->conditionManager->execute($condition)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the current path is a gateway path.
   *
   * @return bool
   *   TRUE if current path is a gateway path, FALSE otherwise.
   */
  private function handleGateway() {
    // Don't do anything if this is a request from cron, drush, crawler, etc.
    if ($this->isCrawlerRequest()) {
      return FALSE;
    }

    // Only implement gateway feature for GET requests, to prevent users from
    // being redirected to CAS server for things like form submissions.
    if (!$this->requestStack->getCurrentRequest()->isMethod('GET')) {
      return FALSE;
    }

    if ($this->gatewayCheckFrequency === CasHelper::CHECK_NEVER) {
      return FALSE;
    }

    // User can indicate specific paths to enable (or disable) gateway mode.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($this->gatewayPaths);
    if (!$this->conditionManager->execute($condition)) {
      return FALSE;
    }

    // If set to only implement gateway once per session, we use a session
    // variable to store the fact that we've already done the gateway check
    // so we don't keep doing it.
    if ($this->gatewayCheckFrequency === CasHelper::CHECK_ONCE) {
      // If the session var is already set, we know to back out.
      if ($this->requestStack->getCurrentRequest()->getSession()->has('cas_gateway_checked')) {
        $this->casHelper->log(LogLevel::DEBUG, 'CAS gateway auth has already been performed for this session.');
        return FALSE;
      }
      $this->requestStack->getCurrentRequest()->getSession()->set('cas_gateway_checked', TRUE);
    }
    return TRUE;
  }

  /**
   * Check is the current request is from a known list of web crawlers.
   *
   * We don't want to perform any CAS redirects in this case, because crawlers
   * need to be able to index the pages.
   *
   * @return bool
   *   True if the request is coming from a crawler, false otherwise.
   */
  private function isCrawlerRequest() {
    $current_request = $this->requestStack->getCurrentRequest();
    if ($current_request->server->get('HTTP_USER_AGENT')) {
      $crawlers = array(
        'Google',
        'msnbot',
        'Rambler',
        'Yahoo',
        'AbachoBOT',
        'accoona',
        'AcoiRobot',
        'ASPSeek',
        'CrocCrawler',
        'Dumbot',
        'FAST-WebCrawler',
        'GeonaBot',
        'Gigabot',
        'Lycos',
        'MSRBOT',
        'Scooter',
        'AltaVista',
        'IDBot',
        'eStyle',
        'Scrubby',
        'gsa-crawler',
      );
      // Return on the first find.
      foreach ($crawlers as $c) {
        if (stripos($current_request->server->get('HTTP_USER_AGENT'), $c) !== FALSE) {
          $this->casHelper->log(LogLevel::DEBUG, 'CasSubscriber ignoring request from suspected crawler "%crawler"', array('%crawler' => $c));
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks current request route against a list of routes we want to ignore.
   *
   * @return bool
   *   TRUE if we should ignore this request, FALSE otherwise.
   */
  private function isIgnoreableRoute() {
    $routes_to_ignore = array(
      'cas.service',
      'cas.proxyCallback',
      'cas.login',
      'cas.legacy_login',
      'cas.logout',
      'system.cron',
    );

    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, $routes_to_ignore)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handle 403 errors.
   *
   * Other request subscribers with a higher priority may intercept the request
   * and return a 403 before our request subscriber can handle it. In those
   * instances we handle the forced login redirect if applicable here instead,
   * using an exception subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if ($this->currentUser->isAnonymous()) {
      $return_to = $this->requestStack->getCurrentRequest()->getUri();
      $redirect_data = new CasRedirectData(['returnto' => $return_to]);
      if ($this->handleForcedPath()) {
        $this->casHelper->log(LogLevel::DEBUG, 'Initializing forced login auth from CasSubscriber.');
        $redirect_data->forceRedirection();
        $redirect_data->setIsCacheable(TRUE);
      }
      else {
        $redirect_data->preventRedirection();
      }

      // If we're still going to redirect, lets do it.
      $response = $this->casRedirector->buildRedirectResponse($redirect_data);
      if ($response) {
        $event->setResponse($response);
      }
    }
  }

}
