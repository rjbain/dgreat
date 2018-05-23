<?php

namespace Drupal\dgreat_group;


/**
 * Class DgreatGroupUtility
 *
 * Utility function pass the original group defaults around.
 * Needed this since D8 can seem to pass custom stuff between handlers now.
 *
 * @package Drupal\dgreat_group
 */
class DgreatGroupUtility {

  /**
   * The array of original nids.
   *
   * @var array
   */
  protected static $nids;

  /**
   * Set this array of original nids.
   *
   * @param array $nids
   */
  public static function setOriginal(array $nids) {
    self::$nids = $nids;
  }

  /**
   * Get this array of original nids.
   */
  public static function getOriginal() {
    return self::$nids;
  }
}