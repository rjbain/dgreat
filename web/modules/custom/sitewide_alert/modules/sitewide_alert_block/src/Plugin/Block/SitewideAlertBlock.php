<?php

namespace Drupal\sitewide_alert_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sitewide_alert\SitewideAlertRendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements SiteAlertBlock class.
 *
 * @Block(
 *   id = "sitewide_alert_block",
 *   admin_label = @Translation("Sitewide Alert"),
 * )
 */
class SitewideAlertBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The alert placeholder rendering service.
   *
   * @var \Drupal\sitewide_alert\SitewideAlertRendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Block configuration.
   * @param string $plugin_id
   *   Block plugin id.
   * @param mixed $plugin_definition
   *   Block plugin configuration.
   * @param \Drupal\sitewide_alert\SitewideAlertRendererInterface $renderer
   *   Alert placeholder rendering service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SitewideAlertRendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sitewide_alert.sitewide_alert_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['visibility_message'] = [
      '#type' => 'item',
      '#title' => $this->t('Note: Sitewide Alert block visibility'),
      '#description' => $this->t(
        'In most cases, this block should be set to always be visible and any visibility conditions configured when creating or editing each Sitewide Alert.'
      ),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // In block context, ignore admin vs. non-admin distinction.
    return $this->renderer->build(FALSE);
  }

}
