<?php

/**
 * @file
 * Contains \Drupal\usfb_address\Form\UsfbAddressAskAddressCorrect.
 */

namespace Drupal\usfb_address\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\usfb_address\UsfbBannerApi;
use Drupal\usfb_address\Service\UsfbUtility;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class UsfbAddressAskAddressCorrect extends FormBase {

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
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Initializes an instance of the content translation controller.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\usfb_address\UsfbBannerApi $banner_api
   *   The USF Banner API.
   * @param \Drupal\usfb_address\Service\UsfbUtility $util
   *   The USFB Utility Class.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(UsfbBannerApi $banner_api, UsfbUtility $util, LoggerInterface $logger, MessengerInterface $messenger, StateInterface $state) {
    $this->api = $banner_api;
    $this->util = $util;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('usf_banner_api'),
      $container->get('usf_utility'),
      $container->get('logger.factory')->get('USFB Address'),
      $container->get('messenger'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usfb_address_ask_address_correct';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {

    // Set vars.
    $this->name = $user->getAccountName();
    $this->uid = $user->id();

    // Get the address data from USF's Banner API.
    if (($address = $this->api->callApi($this->name)) === NULL) {
      $msg = "Error retrieving user '{$this->name}' ({$this->uid}) address from Banner API";
      $this->logger->notice($msg);
      $this->util->abort();
    }

    // Check whether the student has recently updated their address via SSB.
    if ($start = $this->state->get('usfb_address_date_start', FALSE)) {
      if (!empty($address->dateLastUpdated) && $address->dateLastUpdated >= $start) {
        // Update the user's "address last updated" in their Drupal profile.
        $this->util->updateAddressDate($this->uid);
        // Clear the session flag and redirect to the post-login destination.
        $this->util->abort();
      }
    }

    $form['help'] = [
      '#markup' => t("Is this your local address? If so, please click <strong>Confirm</strong>. If not, or if no address is displayed below, click <strong>Update</strong>. Or click <strong>Skip</strong> and we'll prompt you again the next time you log in to myUSF.")
      ];
    $form['address'] = ['#markup' => $this->util->formatAddress($address)];

    $form['actions']['confim'] = [
      '#type' => 'submit',
      '#value' => t('Confirm'),
      '#attributes' => [
        'class' => [
          'btn-primary'
          ],
        'style' => 'margin-right: 0.5em;',
      ],
      '#submit' => [[$this, 'askAddressConfirm']],
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
      '#submit' => [[$this, 'askAddressUpdate']],
    ];
    $form['actions']['skip'] = [
      '#type' => 'submit',
      '#value' => t('Skip'),
      '#submit' => [[$this, 'askAddressSkip']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * User wants to CONFIRM their address.
   *
   * @param array $form
   *   The entity form to be altered to provide the translation workflow.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function askAddressConfirm($form, FormStateInterface $form_state) {

    // Remove the USFB Address Check session variable if it's set.
    unset($_SESSION['usfb_address_check']);

    // Update the user's Last Update Address Date.
    $this->util->updateAddressDate($this->uid);

    // Retrieve the current user's address information.
    // @TODO Don't call the API again, pull it out of form data.
    if (($address = $this->api->callApi($this->name)) === NULL) {
      return new RedirectResponse($this->util->postLoginPath($this->uid)->toString());
    }

    // Construct the message.
    $output = t('<p><strong>Thank you!</strong> You have confirmed that the information below is accurate. <em>If this is not correct, please click the Update button.</em></p>');
    $output .= $this->util->formatAddress($address);
    $output .= $this->util->formatButtons(t('Update'), $this->uid);

    // Display the message and forward them to the homepage.
    $this->messenger->addStatus($output);
    $form_state->setRedirectUrl($this->util->postLoginPath($this->uid));
  }

  /**
   * User wants to UPDATE their address.
   *
   * @param array $form
   *   The entity form to be altered to provide the translation workflow.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function askAddressUpdate($form, FormStateInterface $form_state) {
    $url = Url::fromUri("internal:/user/{$this->uid}/edit/address");
    $form_state->setRedirectUrl($url);
  }

  /**
   * User wants to Skip the address form check.
   *
   * @param array $form
   *   The entity form to be altered to provide the translation workflow.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  function askAddressSkip($form, FormStateInterface $form_state)  {
    // Remove the USFB Address Check session variable if it's set.
    unset($_SESSION['usfb_address_check']);
    // Redirect to the post-login destination.
    $form_state->setRedirectUrl($this->util->postLoginPath($this->uid));
  }

}
