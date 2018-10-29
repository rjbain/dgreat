<?php

namespace Drupal\cas\Form;

use Drupal\cas\Service\CasUserManager;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cas\Service\CasHelper;

/**
 * Class CasSettings.
 *
 * @codeCoverageIgnore
 */
class CasSettings extends ConfigFormBase {

  /**
   * RequestPath condition that contains the paths to use for gateway.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $gatewayPaths;

  /**
   * RequestPath condition that contains the paths to used for forcedLogin.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $forcedLoginPaths;

  /**
   * Constructs a \Drupal\cas\Form\CasSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);
    $this->gatewayPaths = $plugin_factory->createInstance('request_path');
    $this->forcedLoginPaths = $plugin_factory->createInstance('request_path');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas.settings');

    $form['server'] = array(
      '#type' => 'details',
      '#title' => $this->t('CAS server'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Enter the details of the CAS server to authentication against.'),
    );
    $form['server']['version'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Protocol version'),
      '#options' => array(
        '1.0' => $this->t('1.0'),
        '2.0' => $this->t('2.0'),
        '3.0' => $this->t('3.0 or higher'),
      ),
      '#default_value' => $config->get('server.version'),
      '#description' => $this->t('The CAS protocol version your CAS server supports.'),
    );
    $form['server']['hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the CAS server.'),
      '#size' => 30,
      '#default_value' => $config->get('server.hostname'),
    );
    $form['server']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#size' => 5,
      '#description' => $this->t('443 is the standard SSL port. 8443 is the standard non-root port for Tomcat.'),
      '#default_value' => $config->get('server.port'),
    );
    $form['server']['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('If the CAS endpoints (like /login) are not at the root of the host, specify the path to the endpoints (e.g., /cas).'),
      '#size' => 30,
      '#default_value' => $config->get('server.path'),
    );
    $form['server']['verify'] = array(
      '#type' => 'radios',
      '#title' => 'SSL Verification',
      '#description' => $this->t("Choose an appropriate option for verifying the SSL/TLS certificate of your CAS server."),
      '#options' => array(
        CasHelper::CA_DEFAULT => $this->t("Verify using your web server's default certificate authority (CA) chain."),
        CasHelper::CA_NONE => $this->t('Do not verify. (Note: this should NEVER be used in production.)'),
        CasHelper::CA_CUSTOM => $this->t('Verify using a specific CA certificate. Use the field below to provide path.'),
      ),
      '#default_value' => $config->get('server.verify'),
    );
    $form['server']['cert'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom Certificate Authority PEM Certificate'),
      '#description' => $this->t('The PEM certificate of the Certificate Authority that issued the certificate on the CAS server, used only with the custom certificate option above.'),
      '#default_value' => $config->get('server.cert'),
      '#states' => array(
        'visible' => array(
          ':input[name="server[verify]"]' => array('value' => CasHelper::CA_CUSTOM),
        ),
      ),
    );

    $form['general'] = array(
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['general']['login_link_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Login link lnabled'),
      '#description' => $this->t('Display a link to login via CAS above the user login form.'),
      '#default_value' => $config->get('login_link_enabled'),
    );
    $form['general']['login_link_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login link label'),
      '#description' => $this->t('The text that makes up the login link to this CAS server.'),
      '#default_value' => $config->get('login_link_label'),
      '#states' => array(
        'visible' => array(
          ':input[name="general[login_link_enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['user_accounts'] = array(
      '#type' => 'details',
      '#title' => $this->t('User Account Handling'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['user_accounts']['auto_register'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Auto register users'),
      '#description' => $this->t(
        'Enable to automatically create local Drupal accounts for first-time CAS logins. ' .
        'If disabled, users must be pre-registered before being allowed to log in.'
      ),
      '#default_value' => $config->get('user_accounts.auto_register'),
    );

    $form['user_accounts']['email_assignment_strategy'] = array(
      '#type' => 'radios',
      '#title' => t('Email address assignment'),
      '#description' => t("Drupal requires every user have an email address. Select how you'd like to assign an email to automatically registered users."),
      '#default_value' => $config->get('user_accounts.email_assignment_strategy'),
      '#options' => array(
        CasUserManager::EMAIL_ASSIGNMENT_STANDARD => $this->t('Use the CAS username combined with a custom domain name you specify.'),
        CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE => $this->t("Use a CAS attribute that contains the user's complete email address."),
      ),
      '#states' => array(
        'visible' => array(
          'input[name="user_accounts[auto_register]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['user_accounts']['email_hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email hostname'),
      '#description' => $this->t("The email domain name used to combine with the username to form the user's email address."),
      '#field_prefix' => $this->t('username') . '@',
      '#default_value' => $config->get('user_accounts.email_hostname'),
      '#states' => array(
        'visible' => array(
          'input[name="user_accounts[auto_register]"]' => array('checked' => TRUE),
          'input[name="user_accounts[email_assignment_strategy]"]' => array('value' => CasUserManager::EMAIL_ASSIGNMENT_STANDARD),
        ),
      ),
    );
    $form['user_accounts']['email_attribute'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email attribute'),
      '#description' => $this->t("The CAS attribute name (case sensitive) that contains the user's email address."),
      '#default_value' => $config->get('user_accounts.email_attribute'),
      '#states' => array(
        'visible' => array(
          'input[name="user_accounts[auto_register]"]' => array('checked' => TRUE),
          'input[name="user_accounts[email_assignment_strategy]"]' => array('value' => CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE),
        ),
      ),
    );

    $auto_assigned_roles = $config->get('user_accounts.auto_assigned_roles');
    $form['user_accounts']['auto_assigned_roles_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically assign roles on user registration'),
      '#default_value' => count($auto_assigned_roles) > 0,
      '#states' => array(
        'invisible' => array(
          'input[name="user_accounts[auto_register]"]' => array('checked' => FALSE),
        ),
      ),
    );
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $form['user_accounts']['auto_assigned_roles'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Roles'),
      '#description' => t('The selected roles will be automatically assigned to each CAS user on login. Use this to automatically give CAS users additional privileges or to identify CAS users to other modules.'),
      '#default_value' => $auto_assigned_roles,
      '#options' => $roles,
      '#states' => array(
        'invisible' => array(
          'input[name="user_accounts[auto_assigned_roles_enable]"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['user_accounts']['prevent_normal_login'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent normal login for CAS users'),
      '#description' => $this->t(
        'If enabled, this will prevent any user associated with CAS from authenticating using the normal login form. ' .
        'If attempted, users will be presented with an error message and a link to login via CAS instead.'
      ),
      '#default_value' => $config->get('user_accounts.prevent_normal_login'),
    );
    $form['user_accounts']['restrict_password_management'] = array(
      '#type' => 'checkbox',
      '#title' => t('Restrict password management'),
      '#description' => $this->t('Prevents CAS users from changing their Drupal password by removing the password fields on the user profile form and disabling the "forgot password" functionality. Admins will still be able to change Drupal passwords for CAS users.'),
      '#default_value' => $config->get('user_accounts.restrict_password_management'),
    );
    $form['user_accounts']['restrict_email_management'] = array(
      '#type' => 'checkbox',
      '#title' => t('Restrict email management'),
      '#description' => $this->t("Prevents CAS users from changing their email by disabling the email field on the user profile form. Admins will still be able to change email addresses for CAS users. Note that Drupal requires a user enter their current password before changing their email, which your users may not know. Enable the restricted password management feature above to remove this password requirement."),
      '#default_value' => $config->get('user_accounts.restrict_email_management'),
    );

    $form['gateway'] = array(
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login)'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'This implements the <a href="@cas-gateway">Gateway feature</a> of the CAS Protocol. ' .
        'When enabled, Drupal will check if a visitor is already logged into your CAS server before ' .
        'serving a page request. If they have an active CAS session, they will be automatically ' .
        'logged into the Drupal site. This is done by quickly redirecting them to the CAS server to perform the ' .
        'active session check, and then redirecting them back to page they initially requested.<br/><br/>' .
        'If enabled, all pages on your site will trigger this feature by default. It is strongly recommended that ' .
        'you specify specific pages to trigger this feature below.<br/><br/>' .
        '<strong>WARNING:</strong> This feature will disable page caching on pages it is active on.',
        array('@cas-gateway' => 'https://wiki.jasig.org/display/CAS/gateway')
      ),
    );
    $form['gateway']['check_frequency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Check frequency'),
      '#default_value' => $config->get('gateway.check_frequency'),
      '#options' => array(
        CasHelper::CHECK_NEVER => 'Disable gateway feature',
        CasHelper::CHECK_ONCE => 'Once per browser session',
        CasHelper::CHECK_ALWAYS => 'Every page load (not recommended)',
      ),
    );
    $this->gatewayPaths->setConfiguration($config->get('gateway.paths'));
    $form['gateway']['paths'] = $this->gatewayPaths->buildConfigurationForm(array(), $form_state);

    $form['forced_login'] = array(
      '#type' => 'details',
      '#title' => $this->t('Forced login'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'Anonymous users will be forced to login through CAS when enabled. ' .
        'This differs from the "gateway feature" in that it will force a user to log in if they are not.'
      ),
    );
    $form['forced_login']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#description' => $this->t('If enabled, all pages on your site will trigger this feature. It is strongly recommended that you specify specific pages to trigger this feature below.'),
      '#default_value' => $config->get('forced_login.enabled'),
    );
    $this->forcedLoginPaths->setConfiguration($config->get('forced_login.paths'));
    $form['forced_login']['paths'] = $this->forcedLoginPaths->buildConfigurationForm(array(), $form_state);

    $form['logout'] = array(
      '#type' => 'details',
      '#title' => $this->t('Log out behavior'),
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['logout']['cas_logout'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Drupal logout triggers CAS logout'),
      '#description' => $this->t('When enabled, users that log out of your Drupal site will then be logged out of your CAS server as well. This is done by redirecting the user to the CAS logout page.'),
      '#default_value' => $config->get('logout.cas_logout'),
    );
    $form['logout']['logout_destination'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Log out destination'),
      '#description' => $this->t(
        'Drupal path or URL. Enter a destination if you want the CAS Server to ' .
        'redirect the user after logging out of CAS.'
      ),
      '#default_value' => $config->get('logout.logout_destination'),
    );
    $form['logout']['enable_single_logout'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable single log out?'),
      '#default_value' => $config->get('logout.enable_single_logout'),
      '#description' => $this->t('If enabled (and your CAS server supports it), ' .
        'users will be logged out of your Drupal site when they log out of your ' .
        'CAS server. <strong>WARNING:</strong> THIS WILL BYPASS A SECURITY HARDENING FEATURE ADDED ' .
        'IN DRUPAL 8, causing session IDs to be stored unhashed in the database.'),
    );
    $form['logout']['single_logout_session_lifetime'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Max lifetime of session mapping data'),
      '#description' => $this->t('This module stores a mapping of Drupal session IDs ' .
        'to CAS server session IDs to support single logout. Normally this data is ' .
        'cleared automatically when a user is logged out, but not always. ' .
        "To make sure this storage doesn't grow out of control, session mapping " .
        'data older than the specified amout of days is cleared during cron. ' .
        'This should be a length of time slightly longer than the session ' .
        'lifetime of your Drupal site or CAS server.'),
      '#default_value' => $config->get('logout.single_logout_session_lifetime'),
      '#field_suffix' => $this->t('days'),
      '#size' => 4,
      '#states' => array(
        'visible' => array(
          'input[name="logout[enable_single_logout]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['proxy'] = array(
      '#type' => 'details',
      '#title' => $this->t('Proxy'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t(
        'These options relate to the proxy feature of the CAS protocol, ' .
        'including configuring this client as a proxy and configuring ' .
        'this client to accept proxied connections from other clients.'),
    );
    $form['proxy']['initialize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Initialize this client as a proxy?'),
      '#description' => $this->t(
        'Initializing this client as a proxy allows it to access ' .
        'CAS-protected resources from other clients that have been ' .
        'configured to accept it as a proxy.'),
      '#default_value' => $config->get('proxy.initialize'),
    );
    $form['proxy']['can_be_proxied'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow this client to be proxied?'),
      '#description' => $this->t(
        'Allow other CAS clients to access this site\'s resources via the ' .
        'CAS proxy protocol. You will need to configure a list of allowed ' .
        'proxies below.'),
      '#default_value' => $config->get('proxy.can_be_proxied'),
    );
    $form['proxy']['proxy_chains'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Allowed proxy chains'),
      '#description' => $this->t(
        'A list of proxy chains to allow proxy connections from. Each line ' .
        'is a chain, and each chain is a whitespace delimited list of ' .
        'URLs for an allowed proxy in the chain, listed from most recent ' .
        '(left) to first (right). Each URL in the chain can be either a ' .
        'plain URL or a URL-matching regular expression (delimited only by ' .
        'slashes). Only if the proxy list returned by the CAS Server exactly ' .
        'matches a chain in this list will a proxy connection be allowed.'),
      '#default_value' => $config->get('proxy.proxy_chains'),
    );

    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['advanced']['debug_log'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug information?'),
      '#description' => $this->t(
        'This is not meant for production sites! Enable this to log debug ' .
        'information about the interactions with the CAS Server to the ' .
        'Drupal log.'),
      '#default_value' => $config->get('advanced.debug_log'),
    );
    $form['advanced']['connection_timeout'] = array(
      '#type' => 'textfield',
      '#size' => 3,
      '#title' => $this->t('Connection timeout'),
      '#field_suffix' => $this->t('seconds'),
      '#description' => $this->t('This module makes HTTP requests to your CAS server and, if configured as a proxy, to a proxied service. This value determines the maximum amount of time to wait on those requests before canceling them.'),
      '#default_value' => $config->get('advanced.connection_timeout'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->validateConfigurationForm($form, $condition_values);

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $this->forcedLoginPaths->validateConfigurationForm($form, $condition_values);

    $ssl_verification_method = $form_state->getValue(['server', 'verify']);
    $cert_path = $form_state->getValue(['server', 'cert']);
    if ($ssl_verification_method == CasHelper::CA_CUSTOM && !file_exists($cert_path)) {
      $form_state->setErrorByName('server][cert', $this->t('The path you provided to the custom PEM certificate for your CAS server does not exist or is not readable. Verify this path and try again.'));
    }

    if ($form_state->getValue(['user_accounts', 'auto_register'])) {
      $email_assignment_strategy = $form_state->getValue(['user_accounts', 'email_assignment_strategy']);
      if ($email_assignment_strategy == CasUserManager::EMAIL_ASSIGNMENT_STANDARD && empty($form_state->getValue(['user_accounts', 'email_hostname']))) {
        $form_state->setErrorByName('user_accounts][email_hostname', $this->t('You must provide a hostname for the auto assigned email address.'));
      }
      elseif ($email_assignment_strategy == CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE && empty($form_state->getValue(['user_accounts', 'email_attribute']))) {
        $form_state->setErrorByName('user_accounts][email_attribute', $this->t('You must provide an attribute name for the auto assigned email address.'));
      }

      if ($form_state->getValue(['server', 'version']) == '1.0' && $email_assignment_strategy == CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE) {
        $form_state->setErrorByName('user_accounts][email_assignment_strategy', $this->t("The CAS protocol version you've specified does not support attributes, so you cannot assign user emails from a CAS attribute value."));
      }
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cas.settings');

    $server_data = $form_state->getValue('server');
    $config
      ->set('server.version', $server_data['version'])
      ->set('server.hostname', $server_data['hostname'])
      ->set('server.port', $server_data['port'])
      ->set('server.path', $server_data['path'])
      ->set('server.verify', $server_data['verify'])
      ->set('server.cert', $server_data['cert']);

    $general_data = $form_state->getValue('general');
    $config
      ->set('login_link_enabled', $general_data['login_link_enabled'])
      ->set('login_link_label', $general_data['login_link_label']);

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('gateway.check_frequency', $form_state->getValue(['gateway', 'check_frequency']))
      ->set('gateway.paths', $this->gatewayPaths->getConfiguration());

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $this->forcedLoginPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('forced_login.enabled', $form_state->getValue(['forced_login', 'enabled']))
      ->set('forced_login.paths', $this->forcedLoginPaths->getConfiguration());

    $config
      ->set('logout.logout_destination', $form_state->getValue(['logout', 'logout_destination']))
      ->set('logout.enable_single_logout', $form_state->getValue(['logout', 'enable_single_logout']))
      ->set('logout.cas_logout', $form_state->getValue(['logout', 'cas_logout']))
      ->set('logout.single_logout_session_lifetime', $form_state->getValue(['logout', 'single_logout_session_lifetime']));
    $config
      ->set('proxy.initialize', $form_state->getValue(['proxy', 'initialize']))
      ->set('proxy.can_be_proxied', $form_state->getValue(['proxy', 'can_be_proxied']))
      ->set('proxy.proxy_chains', $form_state->getValue(['proxy', 'proxy_chains']));
    $config
      ->set('user_accounts.prevent_normal_login', $form_state->getValue(['user_accounts', 'prevent_normal_login']))
      ->set('user_accounts.auto_register', $form_state->getValue(['user_accounts', 'auto_register']))
      ->set('user_accounts.email_assignment_strategy', $form_state->getValue(['user_accounts', 'email_assignment_strategy']))
      ->set('user_accounts.email_hostname', $form_state->getValue(['user_accounts', 'email_hostname']))
      ->set('user_accounts.email_attribute', $form_state->getValue(['user_accounts', 'email_attribute']))
      ->set('user_accounts.restrict_password_management', $form_state->getValue(['user_accounts', 'restrict_password_management']))
      ->set('user_accounts.restrict_email_management', $form_state->getValue(['user_accounts', 'restrict_email_management']));

    $auto_assigned_roles = [];
    if ($form_state->getValue(['user_accounts', 'auto_assigned_roles_enable'])) {
      $auto_assigned_roles = array_keys($form_state->getValue(['user_accounts', 'auto_assigned_roles']));
    }
    $config
      ->set('user_accounts.auto_assigned_roles', $auto_assigned_roles);

    $config
      ->set('advanced.debug_log', $form_state->getValue(['advanced', 'debug_log']))
      ->set('advanced.connection_timeout', $form_state->getValue(['advanced', 'connection_timeout']));

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('cas.settings');
  }

}
