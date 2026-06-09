<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the module's config/settings admin page.
 */
class SitewideAlertConfigForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * SitewideAlertConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler for determining which modules are installed.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'sitewide_alert.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sitewide_alert_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('sitewide_alert.settings');

    // Config options change if block submodule is enabled.
    $block_submodule_enabled = $this->moduleHandler->moduleExists('sitewide_alert_block');

    if ($block_submodule_enabled) {
      $this->messenger()->addMessage(
        $this->t(
          'The Sitewide Alert Block submodule is enabled. Make sure to <a href="@block_link">configure Sitewide Alert block placement within the theme(s)</a>. Note that conditional visibility of Sitewide Alert blocks depends on both block visibility and the visibility configured below. In most cases, the block should be set to always be visible and any visibility conditions configured when creating or editing each Sitewide Alert.',
          ['@block_link' => Url::fromRoute('block.admin_display')->toString()]
        )
      );
    }

    $form['show_on_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on Administration Pages'),
      '#description' => $this->t('This will allow the alerts to show on backend admin pages as well as the frontend.'),
      '#default_value' => $config->get('show_on_admin'),
      '#access' => !$block_submodule_enabled,
    ];

    $form['alert_styles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Available alert styles'),
      '#default_value' => $config->get('alert_styles'),
      '#description' => '<p>' . $this->t(
          'Enter the list of key|value pairs of alert styles separated by new line, where key is the alert style class name without prefix, and the value is displayed to the alert editor. <br/><strong>For example:</strong><ul><li>To add the class <em>alert-info</em>, use <code>info|Info</code></li><li>To add the class <em>alert-danger</em>, use <code>danger|Very Important</code></li></ul><strong>Warning!</strong> Pre-existing values will be reset.'
      ) . '<br><br></p>',
    ];

    $form['display_order'] = [
      '#type' => 'select',
      '#options' => [
        'ascending' => $this->t('Display newer alerts last'),
        'descending' => $this->t('Display newer alerts first'),
      ],
      '#title' => $this->t('Display Order'),
      '#default_value' => $config->get('display_order'),
      '#description' => $this->t('The order that the alerts display on the page when there are multiple active alerts.'),
    ];

    $form['automatic_refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically Update (Refresh) Alerts'),
      '#default_value' => $config->get('automatic_refresh'),
      '#description' => $this->t('If enabled, the browser will periodically check and display any added, removed, or updated alerts without requiring the visitor to refresh the page. This is recommend for time sensitive alerts. When disabled, alerts are only updated once per page view.'),
    ];

    $form['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Browser Refresh Interval (in seconds)'),
      '#default_value' => $config->get('refresh_interval'),
      '#description' => $this->t('How often should an open page request information on any new or changed alerts. If you have a good full page / reverse proxy caching strategy in effect, this can be set this to a low number (5-15 seconds) to have a more of an "immediate" update. If you do not have a good caching strategy in place, or most of your traffic is authenticated and can\'t be cached, a larger time (60 or 120 seconds) may be warranted to reduce a potential performance impact on the web server.'),
      '#states' => [
        'visible' => [
          ':input[name="automatic_refresh"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['cache_max_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Browser and Shared Cache Maximum Age (in seconds)'),
      '#description' => $this->t('The maximum time that alerts should be cached by the browser and shared caches (reverse proxies, like Varnish). Increasing the maximum cache age may reduce the volume of requests to the Drupal site, but will increase the amount of time before new and changed alerts show.'),
      '#default_value' => $config->get('cache_max_age'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('cache_max_age') < 0) {
      $form_state->setErrorByName('cache_max_age', $this->t('Browser and Shared Cache Maximum Age (in seconds) can not be negative'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('sitewide_alert.settings')
      ->set('show_on_admin', $form_state->getValue('show_on_admin'))
      ->set('alert_styles', $form_state->getValue('alert_styles'))
      ->set('display_order', $form_state->getValue('display_order'))
      ->set('refresh_interval', $form_state->getValue('refresh_interval'))
      ->set('automatic_refresh', $form_state->getValue('automatic_refresh'))
      ->set('cache_max_age', $form_state->getValue('cache_max_age'))
      ->save();
  }

}
