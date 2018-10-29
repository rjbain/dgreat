<?php

namespace Drupal\cas\Controller;

use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Exception\CasSloException;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Service\CasProxyHelper;
use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas\Service\CasValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\cas\Service\CasLogout;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ServiceController.
 */
class ServiceController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * CAS Helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * CAS proxy helper.
   *
   * @var \Drupal\cas\Service\CasProxyHelper
   */
  protected $casProxyHelper;

  /**
   * Used to validate CAS service tickets.
   *
   * @var \Drupal\cas\Service\CasValidator
   */
  protected $casValidator;

  /**
   * Used to log a user in after they've been validated.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * Used to log a user out due to a single log out request.
   *
   * @var \Drupal\cas\Service\CasLogout
   */
  protected $casLogout;

  /**
   * Used to retrieve request parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Used to generate redirect URLs.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS Helper service.
   * @param \Drupal\cas\Service\CasProxyHelper $cas_proxy_helper
   *   The CAS Proxy helper.
   * @param \Drupal\cas\Service\CasValidator $cas_validator
   *   The CAS Validator service.
   * @param \Drupal\cas\Service\CasUserManager $cas_user_manager
   *   The CAS User Manager service.
   * @param \Drupal\cas\Service\CasLogout $cas_logout
   *   The CAS Logout service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(CasHelper $cas_helper, CasProxyHelper $cas_proxy_helper, CasValidator $cas_validator, CasUserManager $cas_user_manager, CasLogout $cas_logout, RequestStack $request_stack, UrlGeneratorInterface $url_generator, ConfigFactoryInterface $config_factory) {
    $this->casHelper = $cas_helper;
    $this->casProxyHelper = $cas_proxy_helper;
    $this->casValidator = $cas_validator;
    $this->casUserManager = $cas_user_manager;
    $this->casLogout = $cas_logout;
    $this->requestStack = $request_stack;
    $this->urlGenerator = $url_generator;
    $this->settings = $config_factory->get('cas.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.helper'), $container->get('cas.proxy_helper'), $container->get('cas.validator'), $container->get('cas.user_manager'), $container->get('cas.logout'), $container->get('request_stack'), $container->get('url_generator'), $container->get('config.factory'));
  }

  /**
   * Main point of communication between CAS server and the Drupal site.
   *
   * The path that this controller/action handle are always set to the "service"
   * url when authenticating with the CAS server, so CAS server communicates
   * back to the Drupal site using this controller action. That's why there's
   * so much going on in here - it needs to process a few different types of
   * requests.
   */
  public function handle() {
    $request = $this->requestStack->getCurrentRequest();

    // First, check if this is a single-log-out (SLO) request from the server.
    if ($request->request->has('logoutRequest')) {
      try {
        $this->casLogout->handleSlo($request->request->get('logoutRequest'));
      }
      catch (CasSloException $e) {
        $this->casHelper->log(
          LogLevel::ERROR,
          'Error when handling single-log-out request: %error',
          ['%error' => $e->getMessage()]
        );
      }
      // Always return a 200 response. CAS Server doesn’t care either way what
      // happens here, since it is a fire-and-forget approach taken.
      return Response::create('', 200);
    }

    // We will be redirecting the user below. To prevent the CasSubscriber from
    // initiating an automatic authentiation on that request (like forced
    // auth or gateway auth) and potentially creating an authentication loop,
    // we set a session variable instructing the CasSubscriber skip auto auth
    // for that request.
    $request->getSession()->set('cas_temp_disable_auto_auth', TRUE);

    /* If there is no ticket parameter on the request, the browser either:
     * (a) is returning from a gateway request to the CAS server in which
     *     the user was not already authenticated to CAS, so there is no
     *     service ticket to validate and nothing to do.
     * (b) has hit this URL for some other reason (crawler, curiosity, etc)
     *     and there is nothing to do.
     * In either case, we just want to redirect them away from this controller.
     */
    if (!$request->query->has('ticket')) {
      $this->casHelper->log(LogLevel::DEBUG, "No CAS ticket found in request to service controller; backing out.");
      $this->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }

    // There is a ticket present, meaning CAS server has returned the browser
    // to the Drupal site so we can authenticate the user locally using the
    // ticket.
    $ticket = $request->query->get('ticket');

    // Our CAS service will need to reconstruct the original service URL
    // when validating the ticket. We always know what the base URL for
    // the service URL is (it's this page), but there may be some query params
    // attached as well (like a destination param) that we need to pass in
    // as well. So, detach the ticket param, and pass the rest off.
    $service_params = $request->query->all();
    unset($service_params['ticket']);
    try {
      $cas_validation_info = $this->casValidator->validateTicket($ticket, $service_params);
    }
    catch (CasValidateException $e) {
      // Validation failed, redirect to homepage and set message.
      $this->casHelper->log(
        LogLevel::ERROR,
        'Error when validating ticket: %error',
        ['%error' => $e->getMessage()]
      );
      $this->setMessage($this->t('There was a problem validating your login, please contact a site administrator.'), 'error');
      $this->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }

    // Now that the ticket has been validated, we can use the information from
    // validation request to authenticate the user locally on the Drupal site.
    try {
      $this->casUserManager->login($cas_validation_info, $ticket);
      if ($this->settings->get('proxy.initialize') && $cas_validation_info->getPgt()) {
        $this->casHelper->log(LogLevel::DEBUG, "Storing PGT information for this session.");
        $this->casProxyHelper->storePgtSession($cas_validation_info->getPgt());
      }
      $this->setMessage($this->t('You have been logged in.'));
    }
    catch (CasLoginException $e) {
      $this->casHelper->log(LogLevel::ERROR, $e->getMessage());
      $this->setMessage($this->t('There was a problem logging in, please contact a site administrator.'), 'error');
    }

    // And finally redirect the user to the homepage, or so a specific
    // destination found in the destination param (like the page they were on
    // prior to initiating authentication).
    $this->handleReturnToParameter($request);
    return RedirectResponse::create($this->urlGenerator->generate('<front>'));
  }

  /**
   * Converts a "returnto" query param to a "destination" query param.
   *
   * The original service URL for CAS server may contain a "returnto" query
   * parameter that was placed there to redirect a user to specific page after
   * logging in with CAS.
   *
   * Drupal has a built in mechanism for doing this, by instead using a
   * "destination" parameter in the URL. Anytime there's a RedirectResponse
   * returned, RedirectResponseSubscriber looks for the destination param and
   * will redirect a user there instead.
   *
   * We cannot use this built in method when constructing the service URL,
   * because when we redirect to the CAS server for login, Drupal would see
   * our destination parameter in the URL and redirect there instead of CAS.
   *
   * However, when we redirect the user after a login success / failure,
   * we can then convert it back to a "destination" parameter and let Drupal
   * do it's thing when redirecting.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   */
  private function handleReturnToParameter(Request $request) {
    if ($request->query->has('returnto')) {
      $this->casHelper->log(LogLevel::DEBUG, "Converting query parameter 'returnto' to 'destination'.");
      $request->query->set('destination', $request->query->get('returnto'));
    }
  }

  /**
   * Encapsulation of drupal_set_message.
   *
   * See https://www.drupal.org/node/2278383 for discussion about converting
   * drupal_set_message to a service. In the meantime, in order to unit test
   * the error handling here, we have to encapsulate the call in a method.
   *
   * @param string $message
   *   The message text to set.
   * @param string $type
   *   The message type.
   * @param bool $repeat
   *   Whether identical messages should all be shown.
   *
   * @codeCoverageIgnore
   */
  public function setMessage($message, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

}
