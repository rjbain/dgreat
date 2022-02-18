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
   * @param object $members
   *   The current member of the current group.
   * @param object &$context
   *   The batch context object.
   */
  public static function batchProcess($new_nids, $removed_nids, $group, $members, &$context) {

    // Show message.
    $message = t('Processing users in chunks of 100');
    $context['message'] = '<h2>' . $message . '</h2>';

    foreach ($members as $member) {
      $user = $member->getUser();

      // Exit if we picked up user 0 or null.
      if ($user === NULL) {
        return;
      }

      if ($user->id() == 0) {
        return;
      }
      
      $flag_service = \Drupal::service('flag');
      $flag = $flag_service->getFlagById('favorite');
      $db = \Drupal::database();

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

          // Add link to user weights table.
          $link = $node->get('field_link_type')->getValue();
          if (isset($link[0]['value'])) {
            $name = $link[0]['value'] . '_links';
            $uid = $user->id();
            $nid = $node->id();

            // Grab the new weight.
            $sql = "SELECT MAX(weight) FROM {user_weights} WHERE uid = :uid";
            $weight = $db
              ->query($sql, [':uid' => $uid])
              ->fetchField();

            // No user weights setup, no need to inject this.
            if ($weight == NULL) {
              continue;
            }

            $check = $db
              ->select('user_weights', 'u')
              ->fields('u', ['entity_id'])
              ->condition('uid', $uid)
              ->condition('entity_id', $nid)
              ->condition('view_name', $name)
              ->execute()
              ->fetchField();

            if ($check === FALSE) {
              // Insert new item in weights table.
              $db->insert('user_weights')
                ->fields([
                  'entity_id' => $nid,
                  'uid' => $uid,
                  'view_name' => $name,
                  'weight' => $weight + 1,
                ])
                ->execute();

            }
            else {
              // Update the weights table.
              $db->update('user_weights')
                ->condition('uid', $uid)
                ->condition('entity_id', $nid)
                ->condition('view_name', $name)
                ->fields([
                  'entity_id' => $nid,
                  'uid' => $uid,
                  'view_name' => $name,
                  'weight' => $weight + 1,
                ])
                ->execute();
            }
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

          // Remove link from user weights table.
          $link = $node->get('field_link_type')->getValue();
          if (isset($link[0]['value'])) {
            $name = $link[0]['value'] . '_links';
            $uid = $user->id();
            $nid = $node->id();

            $check = $db
              ->delete('user_weights')
              ->condition('uid', $uid)
              ->condition('entity_id', $nid)
              ->condition('view_name', $name)
              ->execute();
          }
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
        \Drupal::messenger()->addMessage($message);
    }
    else {
      $error_operation = reset($operations);
        \Drupal::messenger()->addMessage('An error occurred while processing @operation with arguments : @args', ['@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE)], 'status', TRUE);
    }

    // Redirect back to group page.
    // $url = '/group/' . $group->id();
    $response = new RedirectResponse($results['path']);
    $response->send();
  }

}
