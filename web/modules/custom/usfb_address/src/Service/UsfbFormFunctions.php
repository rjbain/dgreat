<?php

namespace Drupal\usfb_address\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\usfb_address\Service\UsfbUtility;

/**
 * Utility Service with common functions.
 *
 * @ingroup usfb_address
 */
class UsfbFormFunctions {

  /**
   * The USFB Utility Class.
   *
   * @var \Drupal\usfb_address\Service\UsfbUtility
   */
  protected $util;

  /**
   * Initializes an instance of the content translation controller.
   *
   * @param \Drupal\usfb_address\Service\UsfbUtility $util
   *   The USFB Utility Class.
   */
  public function __construct(UsfbUtility $util) {
    $this->util = $util;
  }

  /**
   * Retrieves an address from the given form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return object
   */
  public function getAddressFromForm(FormStateInterface $form_state) {
    // Get a shortcut to the form values.
    $values = $form_state->getValue('address');

    // Get the international country code, international format, and phone. For
    // some reason the API expects US numbers in a different format than
    // international ones. Also, remove all non-numerical characters from
    // the phone number.
    $country_abbrv = $form_state->getValue('phone_code');
    $country_code = $this->util->getPhoneCodes()[$country_abbrv]['value'];
    $dash = '1' === $country_code ? '' : '-';
    $phone = preg_replace('/[^0-9]/', '', $form_state->getValue('phone'));

    // Compose the address object as an array.
    $address_values = array(
      'addressLine1'    => $values['thoroughfare'],
      'addressLine2'    => $values['premise'],
      'city'            => $values['locality'],
      'stateOrProvince' => $values['administrative_area'],
      'zipOrPostalCode' => $values['postal_code'],
      'countryCode'     => $values['country'],
      'cellPhone'       => "+$country_code{$dash}$phone",
    );

    // Clean up the values.
    foreach ($address_values as &$value) {
      $value = preg_replace('/\s+/', ' ', trim($value));
    }

    // Cast the address array to an object and return.
    return (object) $address_values;
  }
}