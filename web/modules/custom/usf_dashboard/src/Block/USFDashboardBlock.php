<?php

namespace Drupal\usf_dashboard\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a USF Dashboard block.
 *
 * @Block(
 *   id = "usf_dashboard_block",
 *   admin_label = @Translation("USF Dashboard block"),
 * )
 */
class USFDashboardBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Return the form @ Form/USFDashboardBlockForm.php.
    return \Drupal::formBuilder()
      ->getForm('Drupal\usf_dashboard\Form\USFDashboardBlockForm');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'USF Dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('usf_dashboard_block_settings', $form_state->getValue('usf_dashboard_block_settings'));
  }


}