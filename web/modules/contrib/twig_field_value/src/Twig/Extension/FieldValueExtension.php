<?php

namespace Drupal\twig_field_value\Twig\Extension;

/**
 * Provides field value filters for Twig templates.
 */
class FieldValueExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('field_label', [$this, 'getFieldLabel']),
      new \Twig_SimpleFilter('field_value', [$this, 'getFieldValue']),
      new \Twig_SimpleFilter('field_raw', [$this, 'getRawValues']),
      new \Twig_SimpleFilter('field_target_entity', [$this, 'getTargetEntity']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_field_value';
  }

  /**
   * Twig filter callback: Only return a field's label.
   *
   * @param $build
   *   Render array of a field.
   *
   * @return string
   *   The label of a field. If $build is not a render array of a field, NULL is
   *   returned.
   */
  public function getFieldLabel($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    return isset($build['#title']) ? $build['#title'] : NULL;
  }

  /**
   * Twig filter callback: Only return a field's value(s).
   *
   * @param $build
   *   Render array of a field.
   *
   * @return array
   *   Array of render array(s) of field value(s). If $build is not the render
   *   array of a field, NULL is returned.
   */
  public function getFieldValue($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    if (isset($build['#items'])) {
      $element = $build['#items'];
      $items = [];
      $delta = 0;

      while (!empty($element[$delta])) {
        $items[$delta] = $build[$delta];
        $delta++;
      }
      return $items;
    }

    return NULL;
  }

  /**
   * Twig filter callback: Return specific field item(s) value.
   *
   * @param $build
   *   Render array of a field.
   * @param string $key
   *   The name of the field value to retrieve.
   *
   * @return array|null
   *   Single field value or array of field values. If the field value is not
   *   found, null is returned.
   */
  public function getRawValues($build, $key = '') {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }

    if (isset($build['#items']) && $build['#items'] instanceof \Drupal\Core\TypedData\TypedDataInterface) {
      $item_values = $build['#items']->getValue();
      $raw_values = [];

      if (empty($item_values)) {
        return NULL;
      }

      foreach ($item_values as $delta => $values) {
        if ($key) {
          $raw_values[$delta] = isset($values[$key]) ? $values[$key] : NULL;
        }
        else {
          $raw_values[$delta] = $values;
        }
      }

      return count($raw_values) > 1 ? $raw_values : reset($raw_values);
    }

    return NULL;
  }

  /**
   * Twig filter callback: Return the entity that is referenced by an entity_reference field.
   *
   * Suitable for field types: Image, File, Taxonomy, etc.
   *
   * @param $build
   *   Render array of a field.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\ContentEntityInterface[]|null
   *   A single target entity or an array of target entities. If no target
   *   entity is found, null is returned.
   */
  public function getTargetEntity($build) {

    if (!$this->isFieldRenderArray($build)) {
      return NULL;
    }
    if (isset($build['#field_name'])) {
      $field_name = $build['#field_name'];
      $entities = [];

      // Determine the key of the parent object. Different field types use
      // different key names.
      $options = ['#object', '#field_collection_item'];
      $parent_key = '';
      foreach ($options as $option) {
        if (isset($build[$option])) {
          $parent_key = $option;
          break;
        }
      }

      // Use the parent object to load the target entity of the field.
      if ($parent_key) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
        $parent = $build[$parent_key];

        /** @var \Drupal\Core\Field\FieldItemInterface $field */
        foreach ($parent->get($field_name) as $item) {
          if (isset($item->entity)) {
            $entities[] = $item->entity;
          }
        }

        return count($entities) > 1 ? $entities : reset($entities);
      }
    }

    return NULL;
  }

  /**
   * Checks whether the render array is a field's render array.
   *
   * @param $build
   *   The renderable array.
   *
   * @return bool
   *   True if $build is a field render array.
   */
  protected function isFieldRenderArray($build) {

    return isset($build['#theme']) && $build['#theme'] == 'field';
  }
}
