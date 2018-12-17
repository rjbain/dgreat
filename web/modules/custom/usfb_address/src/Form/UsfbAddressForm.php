<?php

/**
 * @file
 * Contains \Drupal\usfb_address\Form\UsfbAddressAddressForm.
 */

namespace Drupal\usfb_address\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\usfb_address\UsfbBannerApi;
use Drupal\usfb_address\Service\UsfbUtility;
use Drupal\usfb_address\Service\UsfbFormFunctions;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

class UsfbAddressForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The USF Banner API.
   *
   * @var \Drupal\usfb_address\UsfbBannerApi
   */
  protected $api;

  /**
   * The USFB Utility Class.
   *
   * @var \Drupal\usfb_address\Service\UsfbUtility
   */
  protected $util;

  /**
   * The USFB Form Functions Class.
   *
   * @var \Drupal\usfb_address\Service\UsfbFormFunctions
   */
  protected $formFunctions;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Initializes an instance of the content translation controller.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\usfb_address\UsfbBannerApi $banner_api
   *   The USF Banner API.
   * @param \Drupal\usfb_address\Service\UsfbUtility $util
   *   The USFB Utility Class.
   * @param \Drupal\usfb_address\Service\UsfbFormFunctions $form_functions
   *   The USFB Utility Class.
   */
  public function __construct(AccountInterface $current_user, UsfbBannerApi $banner_api, UsfbUtility $util, UsfbFormFunctions $form_functions, LoggerInterface $logger) {
    $this->currentUser = $current_user;
    $this->api = $banner_api;
    $this->util = $util;
    $this->formFunctions = $form_functions;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('usf_banner_api'),
      $container->get('usf_utility'),
      $container->get('usf_form_functions'),
      $container->get('logger.factory')->get('usfb_address')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usfb_address_address_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = 'Address';
    $form['help'] = [
      '#markup' => t('Please complete the form below and click <em>Save</em> to update your current contact information.')
    ];

    $form['#attached']['library'][] = 'usfb_address/usfb-address';

    // Get address data from the Banner API.
    if (($address = $this->api->callApi($this->currentUser->getAccountName())) === NULL) {
      \Drupal::logger('USFB Address')->notice('usfb_address_address_form no address from banner.');
      $this->util->abort();
    }

    // Add a hidden form field to provide the addressType after submission.
    $form['address_type'] = [
      '#type' => 'hidden',
      '#value' => $address ? $address->addressType : 'LR',
    ];

    // Add hidden form fields to provide the user info after submission.
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $this->currentUser->id(),
    ];
    $form['name'] = [
      '#type' => 'hidden',
      '#value' => $this->currentUser->getAccountName(),
    ];

    // Provide the Address Field.
    // @see http://drupal.org/project/address
    // @see \Drupal\address\Element\Address
    $form['address'] = [
      '#type' => 'address',
      '#title' => t('Address'),
      '#required' => TRUE,
      '#default_value' => $address ?
        [
          'country_code' => $address->countryCode,
          'address_line1' => $address->addressLine1,
          'address_line2' => $address->addressLine2,
          'locality' => $address->city,
          'administrative_area' => $address->stateOrProvince,
          'postal_code' => $address->zipOrPostalCode,
        ]
        : ['country_code' => 'US'],
    ];

    // Provide the Phone Number.
    $form['phone_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Phone Number'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['phone_fieldset']['phone_code'] = [
      '#type' => 'select',
      '#default_value' => 'US',
      '#required' => TRUE,
    ];
    foreach ($this->util->getPhoneCodes() as $code => $info) {
      $form['phone_fieldset']['phone_code']['#options'][$code] = $info['text'];
    }

    // Provide the Phone Number.
    $form['phone_fieldset']['phone'] = [
      '#type' => 'telfield',
      '#placeholder' => '4154225555',
      '#required' => TRUE,
      '#description' => 'Do not include the country code. Select the appropriate country from the dropdown to the left.',
    ];

    // Set default values for the phone number and phone country code.
    if (!empty($address->cellPhone)) {
      $phone_raw = $address->cellPhone;
      if (strpos($phone_raw, '+1') === 0) {
        $country_abbrv = 'US';
        $phone = substr($phone_raw, 2);
      }
      else {
        $country_code = strtok($phone_raw, '-');
        $phone = strtok('-');
        foreach ($this->util->getPhoneCodes() as $country_abbrv => $country_info) {
          if ($country_code == $country_info['value']) {
            break;
          }
        }
      }
      $form['phone_fieldset']['phone']['#default_value'] = $phone;
      $form['phone_fieldset']['phone_code']['#default_value'] = $country_abbrv;
    }

    // Save button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#attributes' => [
        'style' => 'margin-right: 0.5em;'
        ],
    ];

    // Back button.
    $form['back'] = [
      '#type' => 'submit',
      '#value' => t('Back'),
      '#submit' => [[$this, 'backSubmit']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $address = $this->formFunctions->getAddressFromForm($form_state);
    // USFB-76 See if they're changing their address, and skip matching if it's the same.
    if (!$this->formFunctions->addressFormSame($form, $form_state)) {
      $match = $this->formFunctions->residenceCampusAddressesMatch($address);
      if ($match) {
        $form_state->setErrorByName('address',
          t('Your new address cannot match a Residence Hall or Campus address. Please enter a different address or click Cancel to go back to the previous screen.')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the current user's address information.
    // @TODO Check whether anything was changed, and if not, don't push to Banner.
    $address = $this->formFunctions->getAddressFromForm($form_state);

    // Remove the USFB Address Check session variable if it's set.
    unset($_SESSION['usfb_address_check']);

    // Send the updated address to Banner.
    $name = $form_state->getValue(['name']);
    try {
      $result = $this->api->callApi($name, $address, 'PUT');
    }
    catch (\Exception $e) {
      $result = FALSE;
      $this->logger->error($e->getMessage());
    }

    // Find the UID.
    $uid = $form_state->getValue(['uid']);

    // Construct the message.
    if ($result) {
      $output = t('<p><strong>Thank you!</strong> You have updated your current local address to the following. <em>If this is not correct, please click "Back"</em>.</p>');
      $output .= $this->util->formatAddress($address);
      $output .= $this->util->formatButtons(t('Back'), $uid);
      drupal_set_message($output, 'status', FALSE);
    }

    // Set the user's "Date of last address update," even if the push failed.
    // @TODO Once Banner figures out the Bad Request situation, change this
    // logic back to only update the user date field when the operation completed
    // successfully. And consider restoring the error output message.
    usfb_address_update_address_date($uid);

    // Forward them to the homepage.
    $form_state->set(['redirect'], _usfb_address_postlogin_path());
  }

  /**
   * Form callback; When the user clicks on Back.
   */
  public function backSubmit(array $form, FormStateInterface $form_state) {
    $uid = $form_state['values']['uid'];
    $form_state['redirect'] = "user/$uid/edit/address/check";
  }

}
