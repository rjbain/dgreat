<?php

namespace Drupal\dgreat_search_promotions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Edits phrase-to-page search promotion rules.
 */
class PromotionsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['dgreat_search_promotions.settings'];
  }

  public function getFormId() {
    return 'dgreat_search_promotions_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $rules = $this->config('dgreat_search_promotions.settings')->get('rules') ?: [];
    $lines = [];
    foreach ($rules as $rule) {
      $lines[] = $rule['phrase'] . ' | ' . $rule['nid'];
    }
    $form['rules'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Promotion rules'),
      '#description' => $this->t('One rule per line: search phrase | node ID. Matching ignores case and repeated spaces.'),
      '#default_value' => implode("\n", $lines),
      '#rows' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rules = [];
    foreach (preg_split('/\R/u', (string) $form_state->getValue('rules')) as $line) {
      if (trim($line) === '') {
        continue;
      }
      $parts = array_map('trim', explode('|', $line));
      if (count($parts) !== 2 || $parts[0] === '' || !ctype_digit($parts[1])) {
        $form_state->setErrorByName('rules', $this->t('Each rule must contain a search phrase and a numeric node ID separated by |.'));
        return;
      }
      $phrase = mb_strtolower(preg_replace('/\s+/u', ' ', $parts[0]));
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($parts[1]);
      if (!$node instanceof NodeInterface || !$node->isPublished()) {
        $form_state->setErrorByName('rules', $this->t('Node @id does not exist or is unpublished.', ['@id' => $parts[1]]));
        return;
      }
      if (isset($rules[$phrase])) {
        $form_state->setErrorByName('rules', $this->t('The phrase @phrase appears more than once.', ['@phrase' => $parts[0]]));
        return;
      }
      $rules[$phrase] = ['phrase' => $phrase, 'nid' => (int) $parts[1]];
    }
    $form_state->set('promotion_rules', array_values($rules));
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('dgreat_search_promotions.settings')
      ->set('rules', $form_state->get('promotion_rules'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
