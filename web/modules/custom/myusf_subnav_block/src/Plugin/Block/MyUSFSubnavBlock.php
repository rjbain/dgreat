<?php

namespace Drupal\myusf_subnav_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'MyUSFSubnavBlock'
 *
 * @Block(
 *   id = "myusf_subnav_block",
 *   admin_label = @Translation("Subnav Block"),
 *   category = @Translation("Custom Blocks"),
 * )
 */

class MyUSFSubnavBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->blockAccess($account);
    return $return_as_object ? $access : $access->isForbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['hello_block_name'])) {
      $name = $config['hello_block_name'];
    } else {
      $name = $this->t('to no one');
    }

    return array(
      '#type' => 'markup',
      '#markup' => $this->t('Hello @name!', array(
        '@name' => $name,
      )),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
//    $this->configuration['hello_block_name'] = $values['hello_block_name'];
    $this->configuration['hello_block_name'] = $form_state->getValue('hello_block_name');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['hello_block_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Who'),
      '#description' => $this->t('Who do you want to say hello to?'),
      '#default_value' => isset($config['hello_block_name']) ? $config['hello_block_name'] : '',
    ];

    return $form;
  }

  /**
   * Custom submit actions
   */
  public function custom_submit_hook($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    //Perform the required actions
  }
}
