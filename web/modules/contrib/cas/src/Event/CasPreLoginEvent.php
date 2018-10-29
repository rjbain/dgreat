<?php

namespace Drupal\cas\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\cas\CasPropertyBag;

/**
 * Class CasPreLoginEvent.
 *
 * CAS dispatches this event during the authentication process after a local
 * Drupal user account has been loaded for the user attempting login, but
 * before the user is actually authenticated to Drupal.
 *
 * Subscribe to this event to:
 *  - Prevent the user from logging in by setting $allowLogin to FALSE.
 *  - Change properties on the Drupal user account (like adding or removing
 *    roles). The CAS module saves the user entity after dispatching the
 *    event, so subscribers do not need to save it themselves.
 *
 * Any CAS attributes will be available via the $casPropertyBag data object.
 */
class CasPreLoginEvent extends Event {

  /**
   * Store the CAS property bag.
   *
   * @var \Drupal\cas\CasPropertyBag
   */
  protected $casPropertyBag;

  /**
   * The drupal user entity about to be logged in.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Controls whether or not the user will be allowed to login.
   *
   * @var bool
   */
  protected $allowLogin = TRUE;

  /**
   * Constructor.
   *
   * @param \Drupal\user\UserInterface $account
   *   The drupal user entity about to be logged in.
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag of the current login cycle.
   */
  public function __construct(UserInterface $account, CasPropertyBag $cas_property_bag) {
    $this->account = $account;
    $this->casPropertyBag = $cas_property_bag;
  }

  /**
   * CasPropertyBag getter.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The casPropertyBag property.
   */
  public function getCasPropertyBag() {
    return $this->casPropertyBag;
  }

  /**
   * Return the user account entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user account entity.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Set the $allowLogin property.
   *
   * @param bool $allow_login
   *   TRUE to allow login, FALSE otherwise.
   */
  public function setAllowLogin($allow_login) {
    if ($allow_login) {
      $this->allowLogin = TRUE;
    }
    else {
      $this->allowLogin = FALSE;
    }
  }

  /**
   * Return if this user is allowed to login.
   *
   * @return bool
   *   TRUE if the user is allowed to login, FALSE otherwise.
   */
  public function getAllowLogin() {
    return $this->allowLogin;
  }

}
