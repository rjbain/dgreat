<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Sitewide Alert entity.
 *
 * @ingroup sitewide_alert
 *
 * @ContentEntityType(
 *   id = "sitewide_alert",
 *   label = @Translation("Sitewide Alert"),
 *   label_singular = @Translation("Sitewide Alert"),
 *   label_plural = @Translation("Sitewide Alerts"),
 *   label_collection = @Translation("Sitewide Alerts"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sitewide_alert\SitewideAlertListBuilder",
 *     "views_data" = "Drupal\sitewide_alert\Entity\SitewideAlertViewsData",
 *     "translation" = "Drupal\sitewide_alert\SitewideAlertTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\sitewide_alert\Form\SitewideAlertForm",
 *       "add" = "Drupal\sitewide_alert\Form\SitewideAlertForm",
 *       "edit" = "Drupal\sitewide_alert\Form\SitewideAlertForm",
 *       "delete" = "Drupal\sitewide_alert\Form\SitewideAlertDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\sitewide_alert\SitewideAlertHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\sitewide_alert\SitewideAlertAccessControlHandler",
 *   },
 *   base_table = "sitewide_alert",
 *   data_table = "sitewide_alert_field_data",
 *   revision_table = "sitewide_alert_revision",
 *   revision_data_table = "sitewide_alert_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer sitewide alert entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/sitewide_alert/{sitewide_alert}",
 *     "add-form" = "/admin/content/sitewide_alert/add",
 *     "edit-form" = "/admin/content/sitewide_alert/{sitewide_alert}",
 *     "delete-form" = "/admin/content/sitewide_alert/{sitewide_alert}/delete",
 *     "version-history" = "/admin/content/sitewide_alert/{sitewide_alert}/revisions",
 *     "revision" = "/admin/content/sitewide_alert/{sitewide_alert}/revisions/{sitewide_alert_revision}/view",
 *     "revision_revert" = "/admin/content/sitewide_alert/{sitewide_alert}/revisions/{sitewide_alert_revision}/revert",
 *     "revision_delete" = "/admin/content/sitewide_alert/{sitewide_alert}/revisions/{sitewide_alert_revision}/delete",
 *     "translation_revert" = "/admin/content/sitewide_alert/{sitewide_alert}/revisions/{sitewide_alert_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/sitewide_alert",
 *   },
 *   field_ui_base_route = "entity.sitewide_alert.config_form",
 *   constraints = {
 *     "ScheduledDateProvided" = {},
 *     "LimitToPages" = {},
 *   }
 * )
 */
class SitewideAlert extends EditorialContentEntityBase implements SitewideAlertInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values): void {
    parent::preCreate($storage, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel): array {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the sitewide_alert owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    // Unset the date field if this entity is not marked as scheduled.
    if (!$this->isScheduled()) {
      $this->set('scheduled_date', ['value' => NULL, 'end_value' => NULL]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name): SitewideAlertInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): SitewideAlertInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of this Sitewide Alert.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('A brief description of this Sitewide Alert. Used for administrative reference only.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -15,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRequired(TRUE);

    $fields['status']
      ->setLabel(new TranslatableMarkup('Active'))
      ->setDescription(t('If selected this Sitewide Alert will be active and will show if all other conditions are met.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ]);

    $fields['style'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Alert Style'))
      ->setDescription(new TranslatableMarkup('The style of this alert. This mainly can be used to change the color of the alert.'))
      ->setSettings([
        'allowed_values_function' => '\Drupal\sitewide_alert\AlertStyleProvider::alertStyles',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -14,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRequired(TRUE);

    $fields['dismissible'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Dismissible'))
      ->setDescription(new TranslatableMarkup('If selected, visitors will be able to dismiss the alert so it will not be seen again. This is per browser, and does not require the visitor to be logged in.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['dismissible_ignore_before_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Ignore Dismissals Before'))
      ->setDefaultValue(0)
      ->setDescription(new TranslatableMarkup('Ignore any dismissals made before this date. If you are editing an existing alert, and this is a major change to the content, you may want to ignore the fact that this alert was dismissed.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['limit_to_pages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Pages'))
      ->setDescription(new TranslatableMarkup("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is /user/* for every user page. / is the front page."))
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_value' => '',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRequired(FALSE);

    $fields['limit_to_pages_negate'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Negate for listed pages'))
      ->setDescription(new TranslatableMarkup('If selected, this Sitewide Alert will show on all paths except the above paths.'))
      ->setRequired(FALSE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Alert Message'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -13,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['scheduled_alert'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Schedule Alert'))
      ->setDescription(new TranslatableMarkup('Schedule this alert to start and stop showing at a particular date/time.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -8,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['scheduled_date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(new TranslatableMarkup('Scheduled Date'))
      ->setDescription(t('This defines when this Sitewide Alert be scheduled to show.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isScheduled(): bool {
    return (bool) $this->get('scheduled_alert')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isScheduledToShowAt(\DateTime $dateTime): bool {
    // If Sitewide Alert is not a scheduled alert, show regardless of time.
    if (!$this->isScheduled()) {
      return TRUE;
    }

    $startTime = $this->getScheduledStartDateTime();
    $endTime = $this->getScheduledEndDateTime();

    // Sitewide Alert is marked as scheduled but dates have not been provided.
    if ($startTime === NULL || $endTime === NULL) {
      return FALSE;
    }

    // Convert to a DrupalDatetime.
    $dateTimeToCompare = DrupalDateTime::createFromDateTime($dateTime);

    return $dateTimeToCompare >= $startTime && $dateTimeToCompare < $endTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduledStartDateTime(): ?DrupalDateTime {
    $value = $this->get('scheduled_date')->value;

    if ($value === NULL || (is_array($value) && (empty($value['date']) || empty($value['time'])))) {
      return NULL;
    }

    return DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $value, DateTimeItemInterface::STORAGE_TIMEZONE);
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduledEndDateTime(): ?DrupalDateTime {
    $end_value = $this->get('scheduled_date')->end_value;

    if ($end_value === NULL || (is_array($end_value) && (empty($end_value['date']) || empty($end_value['time'])))) {
      return NULL;
    }

    return DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_value, DateTimeItemInterface::STORAGE_TIMEZONE);
  }

  /**
   * {@inheritdoc}
   */
  public function isDismissible(): bool {
    return (bool) $this->get('dismissible')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDismissibleIgnoreBeforeTime(): int {
    return (int) $this->get('dismissible_ignore_before_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDismissibleIgnoreBeforeTime($timestamp): SitewideAlertInterface {
    $this->get('dismissible_ignore_before_time')->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle(): string {
    return $this->get('style')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleClass(): string {
    return Html::cleanCssIdentifier('alert-' . $this->get('style')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getPagesToShowOn(): array {
    $paths = [];
    $pagesString = $this->get('limit_to_pages')->value;

    // If it has not been set return no restrictions.
    if (!$pagesString) {
      return $paths;
    }

    foreach (explode("\n", strip_tags($pagesString)) as $path) {
      $path = trim($path);
      if (!empty($path) && str_starts_with($path, '/')) {
        $paths[] = $path;
      }
    }
    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldNegatePagesToShowOn(): bool {
    return (bool) $this->get('limit_to_pages_negate')->value;
  }

}
