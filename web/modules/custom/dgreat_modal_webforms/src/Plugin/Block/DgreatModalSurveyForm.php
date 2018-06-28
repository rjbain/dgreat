<?php

namespace Drupal\dgreat_modal_webforms\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
Use Drupal\user\Entity\User;

/**
 * Provides a 'DgreatModalContactForm' block.
 *
 * @Block(
 *  id = "dgreat_modal_student_survey",
 *  admin_label = @Translation("Student Survey"),
 * )
 */
class DgreatModalSurveyForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $my_form = \Drupal\webform\Entity\Webform::load('contact');
    $build = \Drupal::entityManager()
      ->getViewBuilder('webform')
      ->view($my_form);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $roles = $account->getRoles();
    if (in_array('student', $roles)) {
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
    $config = $this->getConfiguration();

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['my_block_settings'] = $form_state->getValue('my_block_settings');
  }
}
