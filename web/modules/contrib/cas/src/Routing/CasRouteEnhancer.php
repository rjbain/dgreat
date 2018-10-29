<?php

namespace Drupal\cas\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Class CasRouteEnhancer.
 *
 * Override the default logout controller action with our own.
 *
 * Our controller action will log the user out of Drupal and then redirect
 * to the CAS server logout page as well.
 */
class CasRouteEnhancer implements RouteEnhancerInterface {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->settings = $config_factory->get('cas.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    // Replace the logout controller with our own if the logged in user logged
    // in using CAS and if we're configured to perform a CAS server logout
    // during normal Drupal logouts. Overriding the controller allows us to
    // redirect the user to the CAS server logout after logging out locally.
    if ($this->settings->get('logout.cas_logout') && $request->getSession() && $request->getSession()->get('is_cas_user')) {
      $defaults['_controller'] = '\Drupal\cas\Controller\LogoutController::logout';
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->getPath() == '/user/logout';
  }

}
