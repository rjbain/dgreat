<?php
/**
 * @file
 * Contains Drupal\myusf_subnav_block\Plugin\Derivative\MyUSFSubnavBlock.
 */
namespace Drupal\demo\Plugin\Derivative;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides block plugin definitions for menu blocks.
 *
 * @see \Drupal\myusf_subnav_block\Plugin\Block\MyUSFSubnavBlock
 */
class MyUSFSubnavBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('node')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $nodes = $this->nodeStorage->loadByProperties(['type' => 'article']);
    foreach ($nodes as $node) {
      $this->derivatives[$node->id()] = $base_plugin_definition;
      $this->derivatives[$node->id()]['admin_label'] = t('Node block: ') . $node->label();
    }
    return $this->derivatives;
  }
}