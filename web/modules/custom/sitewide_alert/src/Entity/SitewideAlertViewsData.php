<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Sitewide Alert entities.
 */
class SitewideAlertViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    $data['sitewide_alert_field_data']['scheduled_date__value']['filter'] = [
      'title' => $this->t('Scheduled Date'),
      'field' => 'scheduled_date__value',
      'table' => 'sitewide_alert_field_data',
      'id' => 'datetime',
      'field_name' => 'scheduled_date',
      'entity_type' => 'sitewide_alert',
      'allow empty' => TRUE,
    ];

    $data['sitewide_alert_field_data']['scheduled_date__end_value']['filter'] = [
      'title' => $this->t('Scheduled Date (end_value)'),
      'field' => 'scheduled_date__end_value',
      'table' => 'sitewide_alert_field_data',
      'id' => 'datetime',
      'field_name' => 'scheduled_date',
      'entity_type' => 'sitewide_alert',
      'allow empty' => TRUE,
    ];

    return $data;
  }

}
