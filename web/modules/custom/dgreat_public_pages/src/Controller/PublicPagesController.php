<?php

namespace Drupal\dgreat_public_pages\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides publicly accessible utility pages.
 */
class PublicPagesController extends ControllerBase {

  /**
   * Builds the custom error page; Drupal preserves the original 404 status.
   */
  public function notFound(): array {
    return [
      '#markup' => $this->t('The requested page could not be found.'),
    ];
  }

}
