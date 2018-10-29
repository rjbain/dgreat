<?php

namespace Drupal\weight\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\weight\Plugin\Field\FieldWidget\WeightSelectorWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * Field handler to present a weight selector element.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("weight_selector")
 */
class WeightSelector extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['range'] = array('default' => 20);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['range'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Range'),
      '#description' => $this->t('The range of weights available to select. For
        example, a range of 20 will allow you to select a weight between -20
        and 20.'),
      '#default_value' => $this->options['range'],
      '#size' => 5,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ViewsRenderPipelineMarkup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  function viewsForm(array &$form, FormStateInterface $form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    $form[$this->options['id']] = array(
      '#tree' => TRUE,
    );

    $options = WeightSelectorWidget::rangeOptions($this->options['range']);

    // At this point, the query has already been run, so we can access the results
    foreach ($this->view->result as $row_index => $row) {
      $entity = $row->_entity;

      $form[$this->options['id']][$row_index]['weight'] = array(
        '#type' => 'select',
        '#options' =>  $options,
        '#default_value' => $this->getValue($row),
        '#attributes' => array('class' => array('weight-selector')),
      );

      $form[$this->options['id']][$row_index]['entity'] = array(
        '#type' => 'value',
        '#value' => $entity,
      );
    }

    $form['views_field'] = array(
      '#type' => 'value',
      '#value' => $this->field,
    );

    $form['#action'] = \Drupal::request()->getRequestUri();
  }

  function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->getValue('views_field');
    $rows = $form_state->getValue($field_name);

    foreach ($rows as $row) {
      $entity = $row['entity'];
      $entity->set($field_name, $row['weight']);
      $entity->save();
    }
  }

}
