<?php

/**
 * @file
 * Contains \Drupal\usfb_address\EventSubscriber\BootSubscriber.
 */

namespace Drupal\usfb_address\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\State\StateInterface;
use Drupal\usfb_address\Service\UsfbUtility;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Event Subscriber for usfb_address.
 *
 * @ingroup usfb_address
 */
class BootSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The USFB Utility Class.
   *
   * @var \Drupal\usfb_address\Service\UsfbUtility
   */
  protected $util;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Initializes an instance of the BootSubscriber Event Subscriber
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The session from the request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\usfb_address\Service\UsfbUtility $util
   *   The USFB Utility Class.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger instance.
   */
  public function __construct(AccountInterface $current_user, RequestStack $request_stack, StateInterface $state, UsfbUtility $util, LoggerChannelFactoryInterface $logger) {
    $this->currentUser = $current_user;
    $this->session = $request_stack->getCurrentRequest() !== NULL ?
      $request_stack->getCurrentRequest()->getSession() : NULL;
    $this->state = $state;
    $this->util = $util;
    $this->logger = $logger->get('USFB Address');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onEvent', 210];
    return $events;
  }

  /**
   * Replicates hook_boot() functionality from D7 version.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Managed event.
   */
  public function onEvent(GetResponseEvent $event) {

    // Make sure this is not run when using cli (drush).
    // Make sure this does not run when installing Drupal either.
    if (PHP_SAPI === 'cli' || drupal_installation_attempted()) {
      return;
    }

    // Don't run when site is in maintenance mode.
    if ($this->state->get('system.maintenance_mode')) {
      return;
    }

    // Ignore non index.php requests (like cron).
    if (!empty($_SERVER['SCRIPT_FILENAME']) && realpath(DRUPAL_ROOT . '/index.php') != realpath($_SERVER['SCRIPT_FILENAME'])) {
      return;
    }

    $account = User::load($this->currentUser->id());
    if ($account === NULL || $account->id() == 0) {
      return;
    }

    $uid = $account->id();
    $name = $account->getAccountName();

    // Prevents continual redirects from this code.
    // @todo is there a better way to do this?
    if ($this->session->get('boot')) {
      $this->session->set('boot', FALSE);
      return;
    }

    // Set that the USFB Address Check still needs to happen.
    if ($this->session === NULL) {
      $this->session->set('boot', TRUE);
      $this->logger->notice('Session not set for some reason.');
      return new RedirectResponse($this->util->postLoginPath($uid)->toString());
    }

    // Is the Banner address update system enabled?
    if (!$this->state->get('usfb_address_enabled')) {
      $this->session->set('boot', TRUE);
      $event->setResponse(new RedirectResponse($this->util->postLoginPath($uid)->toString(), 307));
    }

    // Is today within the admin-defined start and end dates for address updates?
    $start  = $this->state->get('usfb_address_date_start');
    $end    = $this->state->get('usfb_address_date_end');
    $today  = \Drupal::time()->getRequestTime();
    if ($today < $start || $today > $end) {
      $this->session->set('boot', TRUE);
      $this->logger->notice('Address Check outside of date range');
      $event->setResponse(new RedirectResponse($this->util->postLoginPath($uid)->toString(), 307));
    }

    // Is this user a "Student" (rid 6) or "Online Student" (rid 7)?
    if (!$account->hasRole('student') && !$account->hasRole('online_student')) {
      $this->session->set('boot', TRUE);
      $msg = "User '{$name}' ({$uid}) does not have student roles.";
      $this->logger->notice($msg);
      $event->setResponse(new RedirectResponse($this->util->postLoginPath($uid)->toString(), 307));
    }

    // Has  the student confirmed or updated their address since the start date?
    $date = $account->get('field_usfb_address_date')->getValue();
    if (isset($date[0]['value'])) {
      if ($date[0]['value'] >= $start) {
        $this->session->set('boot', TRUE);
        $msg = "User '{$name}' ({$uid}) already updated their address.";
        $this->logger->notice($msg);
        $event->setResponse(new RedirectResponse($this->util->postLoginPath($uid)->toString(), 307));
      }
    }

    // All validations have passed so far; redirect to the address check form.
    $url = Url::fromUri("internal:/user/{$uid}/edit/address/check")->toString();
    $current = $event->getRequest()->getPathInfo();
    if ($current !== $url && !$this->currentUser->isAnonymous()) {
      $this->session->set('usfb_address_check', TRUE);
      $this->session->set('boot', TRUE);
      $event->setResponse(new RedirectResponse($url, 307));
    }
  }

}
