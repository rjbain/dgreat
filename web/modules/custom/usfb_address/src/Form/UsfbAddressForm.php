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
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\RequestStack;

class UsfbAddressForm extends FormBase {

  /**
   * The current user id.
   *
   * @var string
   */
  protected $uid;

  /**
   * The current user name.
   *
   * @var string
   */
  protected $name;

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
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Initializes an instance of the content translation controller.
   *
   * @param \Drupal\usfb_address\UsfbBannerApi $banner_api
   *   The USF Banner API.
   * @param \Drupal\usfb_address\Service\UsfbUtility $util
   *   The USFB Utility Class.
   * @param \Drupal\usfb_address\Service\UsfbFormFunctions $form_functions
   *   The USFB Form Functions Class.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The session from the request stack.
   */
  public function __construct(UsfbBannerApi $banner_api, UsfbUtility $util, UsfbFormFunctions $form_functions, LoggerInterface $logger, MessengerInterface $messenger, RequestStack $request_stack) {
    $this->api = $banner_api;
    $this->util = $util;
    $this->formFunctions = $form_functions;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->session = $request_stack->getCurrentRequest() !== NULL ?
      $request_stack->getCurrentRequest()->getSession() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('usf_banner_api'),
      $container->get('usf_utility'),
      $container->get('usf_form_functions'),
      $container->get('logger.factory')->get('USFB Address'),
      $container->get('messenger'),
      $container->get('request_stack')
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
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $form['#title'] = 'Address';
    $form['help'] = [
      '#markup' => t('Please complete the form below and click <em>Save</em> to update your current contact information.')
    ];

    // Attach our custom libraries to the form.
    $form['#attached']['library'][] = 'usfb_address/intl-tel-input';
    $form['#attached']['library'][] = 'usfb_address/usfb-address';

    // Set vars.
    $this->name = $user->getAccountName();
    $this->uid = $user->id();

    // Get address data from the Banner API.
    if (($address = $this->api->callApi($this->name)) === NULL) {
      $this->logger->notice('No Address from Banner API.');
      $this->util->abort($this->uid);
    }

    // Add a hidden form field to provide the addressType after submission.
    $form['address_type'] = [
      '#type' => 'hidden',
      '#value' => $address ? $address->addressType : 'LR',
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
      '#field_overrides' => [
        AddressField::ORGANIZATION => FieldOverride::HIDDEN,
        AddressField::GIVEN_NAME => FieldOverride::HIDDEN,
        AddressField::FAMILY_NAME => FieldOverride::HIDDEN,
      ],
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
      '#type' => 'tel',
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
    if ($this->session !== NULL) {
      $this->session->remove('usfb_address_check');
    }

    // Send the updated address to Banner.
    try {
      $result = $this->api->callApi($this->name, $address, 'PUT');
    }
    catch (\Exception $e) {
      $result = FALSE;
      $this->logger->error($e->getMessage());
    }

    // Construct the message.
    if ($result) {
      $msg1 = '<p><strong>Thank you!</strong> You have updated your current local address to the following. <em>If this is not correct, please click Back"</em>.</p>';
      $msg2 = $this->util->formatAddress($address);
      $msg3 = $this->util->formatButtons('Back', $this->uid);
      $msg = Markup::create($msg1 . $msg2 . $msg3);
      $output = new TranslatableMarkup ('@message', array('@message' => $msg));
      $this->messenger->addStatus($output);
    }

    // Set the user's "Date of last address update," even if the push failed.
    // @TODO Once Banner figures out the Bad Request situation, change this
    // logic back to only update the user date field when the operation completed
    // successfully. And consider restoring the error output message.
    $this->util->updateAddressDate($this->uid);

    // Forward them to the homepage.
    $form_state->setRedirectUrl($this->util->postLoginPath($this->uid));
  }

  /**
   * Form callback; When the user clicks on Back.
   */
  public function backSubmit(array $form, FormStateInterface $form_state) {
    $url = Url::fromUri("internal:user/{$this->uid}/edit/address/check");
    $form_state->setRedirectUrl($url);
  }

}
