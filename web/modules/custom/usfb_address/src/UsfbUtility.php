<?php

namespace Drupal\usfb_address;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Utility Service with common functions.
 *
 * @ingroup usfb_address
 */
class UsfbUtility {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a UsfbBannerApi object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Clears the session flag and rediects the user to the post-login destination.
   */
  public function abort() {
    unset($_SESSION['usfb_address_check']);
    $url = Url::fromUri("user/{$this->currentUser->id()}/view")->toString();
    $response = new RedirectResponse($url);
    $response->send();
  }

}