<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Sitewide Alert edit forms.
 *
 * @ingroup sitewide_alert
 */
class SitewideAlertForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Constructs a new SitewideAlertForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, AccountProxyInterface $account) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * Order form elements.
   *
   * @param array $formKeys
   *   Form keys to set the order of.
   * @param array $form
   *   The form array.
   * @param int $offsetWeight
   *   The amount to offset each weight.
   *
   * @return array
   *   The modified form.
   */
  private static function orderFormElements(array $formKeys, array $form, int $offsetWeight = 0): array {
    foreach ($formKeys as $i => $formKey) {
      if (isset($form[$formKey])) {
        $form[$formKey]['#weight'] = $i + $offsetWeight;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity */
    $entity = $this->entity;
    $form = parent::buildForm($form, $form_state);

    // Make the scheduled alert dates conditional on the checkbox.
    $form['scheduled_date']['#states'] = [
      'visible' => [
        ':input[name="scheduled_alert[value]"]' => ['checked' => TRUE],
      ],
    ];

    // Authoring information for administrators.
    if (isset($form['user_id'])) {
      $form['author'] = [
        '#type' => 'details',
        '#title' => $this->t('Authoring information'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['sitewide_alert-form-author'],
        ],
        '#weight' => -3,
        '#optional' => TRUE,
      ];

      $form['user_id']['#group'] = 'author';
    }

    // Allow the editor to disable previous dismissals.
    if (!$entity->isNew()) {
      $form['dismissible_ignore_previous'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Ignore Previous Dismissals'),
        '#description' => $this->t(
          'Select this when making a major change and you want to ensure all visitors see this alert even if they have previously dismissed it. <em>Note: this checkbox will remain unchecked upon reload. The checked value is used during form submission to reset the site alert dismissible time.</em>'
        ),
        '#default_value' => FALSE,
        '#return_value' => TRUE,
        '#weight' => -9,
        '#states' => [
          'visible' => [
            ':input[name="dismissible[value]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Group the dismissible options.
    $form['dismissible_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Dismissible'),
      '#open' => $entity->isDismissible(),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['sitewide-alert--form--dismissible-options'],
      ],
    ];
    $form['dismissible']['#group'] = 'dismissible_options';
    $form['dismissible_ignore_previous']['#group'] = 'dismissible_options';

    // Group scheduling fields.
    $form['scheduling_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Scheduling'),
      '#open' => $entity->isScheduled(),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['sitewide-alert--form--scheduling-options'],
      ],
    ];
    $form['scheduled_alert']['#group'] = 'scheduling_options';
    $form['scheduled_date']['#group'] = 'scheduling_options';

    // Group the page visibility options.
    $form['page_visibility_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Visibility'),
      '#description' => $this->t('Limit the alert to only show on some pages.'),
      '#open' => !empty($entity->getPagesToShowOn()),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['sitewide-alert--form--page-visibility-options'],
      ],
    ];

    $form['limit_alert_by_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit by Page'),
      '#default_value' => !empty($entity->getPagesToShowOn()),
      '#return_value' => TRUE,
      '#weight' => -10,
      '#group' => 'page_visibility_options',
    ];

    $form['limit_to_pages']['#group'] = 'page_visibility_options';
    $form['limit_to_pages_negate']['#group'] = 'page_visibility_options';
    $form['limit_to_pages']['#states'] = [
      'visible' => [
        ':input[name="limit_alert_by_pages"]' => ['checked' => TRUE],
      ],
    ];
    $form['limit_to_pages_negate']['#states'] = [
      'visible' => [
        ':input[name="limit_alert_by_pages"]' => ['checked' => TRUE],
      ],
    ];

    // Order the advanced form elements.
    $form = self::orderFormElements([
      'dismissible_options',
      'scheduling_options',
      'page_visibility_options',
      'page_visibility_options',
      'revision_information',
      'author',
    ], $form);

    // Set the active element to the end.
    $form['status']['#group'] = 'footer';

    $form['#attached']['library'][] = 'sitewide_alert/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity */
    $entity = $this->entity;

    // Set the dismissal timestamp.
    if (!$form_state->isValueEmpty('dismissible_ignore_previous') && $form_state->getValue('dismissible_ignore_previous')) {
      $entity->setDismissibleIgnoreBeforeTime($this->time->getRequestTime());
    }

    // Clear previously set limit by pages if checkbox to limit them is not set.
    if ($form_state->isValueEmpty('limit_alert_by_pages') && !$form_state->getValue('limit_alert_by_pages')) {
      $entity->set('limit_to_pages', '');
    }

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Sitewide Alert.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Sitewide Alert.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.sitewide_alert.collection');
  }

}
