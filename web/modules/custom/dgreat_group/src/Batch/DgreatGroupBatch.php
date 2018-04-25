<?php

namespace Drupal\dgreat_group\Batch;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DgreatGroupBatch.
 *
 * @package Drupal\dgreat_group
 */
class DgreatGroupBatch {

  /**
   * Common batch processing callback for all operations.
   *
   * @param object &$context
   *   The batch context object.
   */
  public static function batchProcess(&$context) {
    // Show message.
    $message = t('Now assimilating %url', ['%url' => $url]);
    $context['message'] = '<h2>' . $message . '</h2>';


    // Set the result.
    $context['results'][] = $url;

  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One user updated.', '@count users updated.');
      drupal_set_message($message, 'status', TRUE);
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))), 'status', TRUE);
    }

    // Redirect to New Leadz page.
    $response = new RedirectResponse('/kalalead/new');
    $response->send();
  }
}
