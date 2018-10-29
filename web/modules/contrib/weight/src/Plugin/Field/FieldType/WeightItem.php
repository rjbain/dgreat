<?php

namespace Drupal\weight\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'weight' field type.
 *
 * @FieldType(
 *   id = "weight",
 *   label = @Translation("Weight"),
 *   description = @Translation("This field stores a weight in the database as an integer."),
 *   default_widget = "weight_selector",
 *   default_formatter = "number_integer"
 * )
 */
class WeightItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'unsigned' => FALSE,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = array(
      'range' => 20,
    ) + parent::defaultFieldSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Weight value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $range = $this->getSetting('range');

    $element['range'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Range'),
      '#description' => $this->t('The range of weights available to select. For
        example, a range of 20 will allow you to select a weight between -20
        and 20.'),
      '#default_value' => $range,
      '#size' => 5,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (empty($this->value) && (string) $this->value !== '0') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'unsigned' => $field_definition->getSetting('unsigned'),
          'size' => $field_definition->getSetting('size'),
        ),
      ),
    );
  }

}
