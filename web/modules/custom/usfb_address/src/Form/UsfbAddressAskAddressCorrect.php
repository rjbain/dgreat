<?php

/**
 * @file
 * Contains \Drupal\usfb_address\Form\UsfbAddressAskAddressCorrect.
 */

namespace Drupal\usfb_address\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class UsfbAddressAskAddressCorrect extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usfb_address_ask_address_correct';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $account = NULL) {

    // Get the address data from USF's Banner API.
    if (!($address = usfb_address_get_banner_address($account->name))) {
      watchdog('usfb_address', "Error retrieving user '{$account->name}' ({$account->uid}) address from Banner API");
      // Clear the session flag and redirect to the post-login destination.
      _usfb_address_abort();
    }

    // Check whether the student has recently updated their address via SSB.
    if ($start = variable_get('usfb_address_date_start', FALSE)) {
      if (!empty($address->dateLastUpdated) && $address->dateLastUpdated >= $start) {
        // Update the user's "address last updated" in their Drupal profile.
        usfb_address_update_address_date($account->uid);
        // Clear the session flag and redirect to the post-login destination.
        _usfb_address_abort();
      }
    }

    // Add hidden form fields to provide the user info after submission.
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $account->uid,
    ];
    $form['name'] = [
      '#type' => 'hidden',
      '#value' => $account->name,
    ];

    $form['help'] = [
      '#markup' => t("Is this your local address? If so, please click <strong>Confirm</strong>. If not, or if no address is displayed below, click <strong>Update</strong>. Or click <strong>Skip</strong> and we'll prompt you again the next time you log in to myUSF.")
      ];
    $form['address'] = ['#markup' => _usfb_address_get_formatted($address)];
    $form['actions']['confim'] = [
      '#type' => 'submit',
      '#value' => t('Confirm'),
      '#attributes' => [
        'class' => [
          'btn-primary'
          ],
        'style' => 'margin-right: 0.5em;',
      ],
      '#submit' => ['usfb_address_ask_address_confirm'],
    ];
    $form['actions']['update'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      '#attributes' => [
        'class' => [
          'btn-success'
          ],
        'style' => 'margin-right: 0.5em;',
      ],
      '#submit' => ['usfb_address_ask_address_update'],
    ];
    $form['actions']['skip'] = [
      '#type' => 'submit',
      '#value' => t('Skip'),
      '#submit' => [
        'usfb_address_ask_address_skip'
        ],
    ];
    return $form;
  }

}
?>
