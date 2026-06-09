<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\sitewide_alert\Entity\SitewideAlertInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Sitewide Alert revision.
 *
 * @ingroup sitewide_alert
 */
class SitewideAlertRevisionRevertForm extends ConfirmFormBase {

  /**
   * The Sitewide Alert revision.
   *
   * @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface
   */
  protected $revision;

  /**
   * The Sitewide Alert storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $sitewideAlertStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new SitewideAlertRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The Sitewide Alert storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityStorageInterface $entity_storage, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->sitewideAlertStorage = $entity_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('sitewide_alert'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sitewide_alert_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to revert to the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.sitewide_alert.version_history', ['sitewide_alert' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sitewide_alert_revision = NULL): array {
    $this->revision = $this->sitewideAlertStorage->loadRevision($sitewide_alert_revision);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log_message = $this->t('Copy of the revision from %date.', [
      '%date' => $this->dateFormatter->format($original_revision_timestamp),
    ]);
    $this->revision->save();

    $this->logger('content')
      ->notice('Sitewide Alert: reverted %title revision %revision.',
        [
          '%title' => $this->revision->label(),
          '%revision' => $this->revision->getRevisionId(),
        ]
      );
    $this->messenger()
      ->addMessage($this->t(
        'Sitewide Alert %title has been reverted to the revision from %revision-date.',
        [
          '%title' => $this->revision->label(),
          '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
        ])
      );
    $form_state->setRedirect(
      'entity.sitewide_alert.version_history',
      ['sitewide_alert' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\sitewide_alert\Entity\SitewideAlertInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(SitewideAlertInterface $revision, FormStateInterface $form_state): SitewideAlertInterface {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime($this->time->getRequestTime());

    return $revision;
  }

}
