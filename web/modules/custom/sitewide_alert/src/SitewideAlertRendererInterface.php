<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert;

/**
 * Sitewide Alert placeholder render array builder service.
 *
 * Used in default page_top display and by block submodule.
 */
interface SitewideAlertRendererInterface {

  /**
   * Construct the sitewide-alert placeholder render array.
   *
   * @param bool $adminAware
   *   Used to indicate a non-block context, where showing the alert on
   *   admin pages is configurable. When using blocks, block placement is
   *   per theme so this setting is ignored.
   */
  public function build(bool $adminAware = TRUE): array;

}
