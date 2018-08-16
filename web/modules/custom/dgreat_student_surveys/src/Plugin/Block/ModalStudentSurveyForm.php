<?php

namespace Drupal\dgreat_student_surveys\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'DgreatModalContactForm' block.
 *
 * @Block(
 *  id = "dgreat_modal_student_survey",
 *  admin_label = @Translation("Student Survey"),
 * )
 */
class ModalStudentSurveyForm extends BlockBase {

  /**
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $this->session = \Drupal::service('session');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $form = Webform::load($config['survey']);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform');
    $pre_render = $view_builder->view($form);
    $elements = collect($pre_render['elements'])->filter(function($element, $key) {
      return mb_strpos($key, '#') !== 0;
    })->all();
    $keep = array_rand($elements);
    $filtered_elements = collect($pre_render['elements'])->filter(function($element, $key) use ($keep) {
      return $key === $keep || mb_strpos($key, '#') === 0;
    })->all();
    $pre_render['elements'] = $filtered_elements;

    $this->session->set('student_surveys_has_seen_recently', TRUE);

    return [
      '#label' => $form->label(),
      '#content' => $pre_render,
      '#theme' => 'dgreat_modal_webforms',
      '#attached' => [
        'library' => 'dgreat_student_surveys/modal',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->canAccess($account)) {
      return AccessResult::allowedIfHasPermission($account, 'access content');
    }
    else {
      return AccessResult::forbidden();
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    /** @var Drupal\Core\Entity\Query\QueryFactory $query */
    $query = Drupal::service('entity.query');
    $webforms = $query->get('webform')
                      ->condition('category', 'student_survey')
                      ->execute();
    $form['survey'] = [
      '#title' => $this->t('Survey'),
      '#description' => $this->t('The survey that should be displayed'),
      '#type' => 'select',
      '#options' => $webforms,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['survey'] = $form_state->getValue('survey');
    return parent::blockSubmit($form, $form_state);
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  protected function canAccess(AccountInterface $account) {
    return $this->isStudent($account) && $this->currentCohort($account)
      && !$this->hasAnswered($account) && !$this->sawRecently($account);
  }

  /**
   * @todo implement this based on CAS data
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  private function currentCohort(AccountInterface $account) {
    return TRUE;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  private function hasAnswered(AccountInterface $account) {
    $config = $this->getConfiguration();
    $webform = Webform::load($config['survey']);

    if (!$webform) {
      return FALSE;
    }

    return \Drupal::entityQuery('webform_submission')
                  ->condition('webform_id', $webform->id())
                  ->condition('uid', $account->id())
                  ->count()
                  ->execute() >= 1;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  protected function isStudent(AccountInterface $account) {
    return TRUE;
    return in_array('student', $account->getRoles());
  }

  /**
   * Check the current session to see if we've already displayed the form.
   *
   * @return bool
   */
  private function sawRecently() {
    return FALSE;
    return $this->session->get('student_surveys_has_seen_recently');
  }
}
