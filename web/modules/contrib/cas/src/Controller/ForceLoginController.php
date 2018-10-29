<?php

namespace Drupal\cas\Controller;

use Drupal\cas\CasRedirectData;
use Drupal\cas\Service\CasRedirector;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ForceLoginController.
 *
 * Used to force CAS authentication for anonymous users.
 */
class ForceLoginController implements ContainerInjectionInterface {

  /**
   * The cas helper to get config settings from.
   *
   * @var \Drupal\cas\Service\CasRedirector
   */
  protected $casRedirector;

  /**
   * Used to get query string parameters from the request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\Service\CasRedirector $cas_redirector
   *   The CAS Redirector service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Symfony request stack.
   */
  public function __construct(CasRedirector $cas_redirector, RequestStack $request_stack) {
    $this->casRedirector = $cas_redirector;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.redirector'), $container->get('request_stack'));
  }

  /**
   * Handles a page request for our forced login route.
   */
  public function forceLogin() {
    // TODO: What if CAS is not configured? need to handle that case.
    $service_url_query_params = $this->requestStack->getCurrentRequest()->query->all();
    $cas_redirect_data = new CasRedirectData($service_url_query_params);
    $cas_redirect_data->setIsCacheable(TRUE);
    return $this->casRedirector->buildRedirectResponse($cas_redirect_data, TRUE);
  }

}
