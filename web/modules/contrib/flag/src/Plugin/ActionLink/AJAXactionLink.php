<?php

namespace Drupal\flag\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagInterface;

/**
 * Provides the AJAX link type.
 *
 * This class is an extension of the Reload link type, but modified to
 * provide AJAX links.
 *
 * @ActionLinkType(
 *   id = "ajax_link",
 *   label = @Translation("AJAX link"),
 *   description = "An AJAX JavaScript request will be made without reloading the page."
 * )
 */
class AJAXactionLink extends Reload {

  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity) {
    $build = parent::getAsFlagLink($flag, $entity);
    $build['#attached']['library'][] = 'flag/flag.link_ajax';
    $build['#attributes']['class'][] = 'use-ajax';
    return $build;

  }

}
