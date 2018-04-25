<?php

namespace Drupal\dgreat_group\Batch;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;

/**
 * Class DgreatGroupBatch.
 *
 * @package Drupal\dgreat_group
 */
class DgreatGroupBatch {

  /**
   * Common batch processing callback for all operations.
   *
   * @param array $new_nids
   *   The array of new default nids to add.
   * @param array 4removed_nids
   *   The array of nids to remove.
   * @param object $group
   *   The current group object.
   * @param object $member
   *   The current member of the current group.
   * @param object &$context
   *   The batch context object.
   */
  public static function batchProcess($new_nids, $removed_nids, $group, $member, &$context) {
    $user = $member->getUser();

    // Exit if we picked up user 0.
    if ($user->id() == 0) {
      return;
    }

    // Show message.
    $message = t('Now processing %name', ['%name' => $user->label()]);
    $context['message'] = '<h2>' . $message . '</h2>';

    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById('favorite');

    // Add new defaults.
    if (!empty($new_nids)) {
      foreach ($new_nids as $nid) {
        $node = Node::load($nid);
        $check = $flag_service->getFlagging($flag, $node, $user, []);
        $is_flagged = $flag->isFlagged($node, $user, []);

        // Check to remove flags when resaving users.
        if ($is_flagged && $check !== NULL) {
          $flag_service->unflag($flag, $node, $user, []);
        }
        if (!$is_flagged) {
          $flag_service->flag($flag, $node, $user, []);
        }
      }
    }

    // Remove defaults.
    if (!empty($removed_nids)) {
      foreach ($removed_nids as $nid) {
        $node = Node::load($nid);
        $check = $flag_service->getFlagging($flag, $node, $user, []);
        $is_flagged = $flag->isFlagged($node, $user, []);

        // UnFlag the Default.
        if ($is_flagged && $check !== NULL) {
          $flag_service->unflag($flag, $node, $user, []);
        }
      }
    }

    // Set the result.
    $context['results']['total'][] = $group;
    $context['results']['path'] = '/group/' . $group->id();

  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results['total']), 'One user updated.', '@count users updated.');
      drupal_set_message($message, 'status', TRUE);
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))), 'status', TRUE);
    }

    // Redirect back to group page.
    //$url = '/group/' . $group->id();
    $response = new RedirectResponse($results['path']);
    $response->send();
  }
}
