<?php

namespace Drupal\dgreat_student_surveys\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Entity\Query\Query;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
Use Drupal\user\Entity\User;
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
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $my_form = Webform::load($config['survey']);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform');
    $pre_render = $view_builder->view($my_form);

    return [
      '#label' => $my_form->label(),
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
    return in_array('student', $account->getRoles()) &&
      $this->currentCohort($account) && $this->hasNotAnswered($account);
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
  private function hasNotAnswered(AccountInterface $account) {
    $config = $this->getConfiguration();
    $webform = Webform::load($config['survey']);

    return \Drupal::entityQuery('webform_submission')
                    ->condition('webform_id', $webform->id())
                    ->condition('uid', $account->id())
                    ->count()
                    ->execute() < 1;

  }
}
