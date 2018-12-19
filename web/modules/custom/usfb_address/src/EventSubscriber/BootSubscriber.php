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
   * Initializes an instance of the BootSubscriber Event Subscriber
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The session from the request stack.
   */
  public function __construct(AccountInterface $current_user, RequestStack $request_stack) {
    $this->currentUser = $current_user;
    $this->session = $request_stack->getCurrentRequest() !== NULL ?
      $request_stack->getCurrentRequest()->getSession() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  /**
   * Replicates hook_boot() functionality from D7 version.
   */
  public function onEvent(GetResponseEvent $event) {
    // Check the session to see if the user should be setting their address.
    if ($this->session !== NULL &&
        !empty($this->session->get('usfb_address_check'))) {
      // Check the URL to see if they're on the Address Form.
      $url = "user/{$this->currentUser->id()}/edit/address";
      $q = trim($_GET['q'], '/');
      if (strpos($q, $url) !== 0) {
        // If they are not, then force them back on it.
        header("Location: /$url/check", TRUE, 307);
        exit();
      }
    }
  }

}
