<?php

/**
 * @file
 * Contains \Drupal\cas_attributes\Subscriber\CasAttributeSubscriber.
 */

namespace Drupal\cas_attributes\Subscriber;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a CasAttributeSubscriber.
 */
class CasAttributeSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory to get module settings.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service for token replacement.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack to get the current request.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Token $token_service,
    RequestStack $request_stack
  ) {
    $this->settings = $config_factory->get('cas_attributes.settings');
    $this->tokenService = $token_service;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CasHelper::EVENT_PRE_REGISTER][] = ['onPreRegister', -10];
    $events[CasHelper::EVENT_PRE_LOGIN][] = ['onPreLogin', 20];
    return $events;
  }

  /**
   * Subscribe to the CasPreRegisterEvent.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The CasPreAuthEvent containing property information.
   */
  public function onPreRegister(CasPreRegisterEvent $event) {
    // Deny user registration if no roles can be mapped to the user and the
    // config is set to do so.
    if ($this->settings->get('role.deny_registration_no_match')) {
      $roles = $this->doRoleMapCheck(User::create(),
        $event->getCasPropertyBag()->getAttributes());
      if (empty($roles['roles_mapped'])) {
        $event->setAllowAutomaticRegistration(FALSE);
      }
    }

    // Set field mapping data for the user if configured to do so on
    // registration.
    if ($this->settings->get('fetch.frequency') == 0) {
      $field_mappings = $this->getFieldMappings();
      if (!empty($field_mappings)) {
        $event->setPropertyValues($field_mappings);
      }
    }
  }

  /**
   * Subscribe to the CasPreLoginEvent.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   The CasPreAuthEvent containing account and property information.
   */
  public function onPreLogin(CasPreLoginEvent $event) {
    // Store attributes in the session for later use as token definitions.
    $property_bag = $event->getCasPropertyBag();
    $this->requestStack->getCurrentRequest()
                       ->getSession()
                       ->set('cas_attributes_properties',
                         serialize($property_bag));

    // Map field data and roles to the user before they are logged in.
    if ($this->settings->get('fetch.frequency') == 1) {
      $account = $event->getAccount();

      $field_mappings = $this->getFieldMappings();
      if (!empty($field_mappings)) {
        // If field already has data, only set new value if configured to
        // overwrite existing data.
        $overwrite = $this->settings->get('fetch.overwrite');
        foreach ($field_mappings as $field_name => $field_value) {
          if ($overwrite || empty($account->get($field_name))) {
            $account->set($field_name, $field_value);
          }
        }
      }

      $this->mapRoles($account, $event);
    }
  }

  /**
   * Map fields to the pre-defined CAS token values.
   */
  protected function getFieldMappings() {
    $mappings = unserialize($this->settings->get('field.field_mapping'));
    if (empty($mappings)) {
      return [];
    }

    $field_data = [];

    foreach ($mappings as $field_name => $attribute_token) {
      $result = trim($this->tokenService->replace($attribute_token, [],
        ['clear' => TRUE]));
      $result = html_entity_decode($result);

      // Only update the fields if there is data to set.
      if (!empty($result)) {
        $field_data[$field_name] = $result;
      }
    }

    return $field_data;
  }

  /**
   * Map roles based on the pre-defined attribute relations.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to modify.
   * @param \Drupal\cas\Event\CasPreAuthEvent
   *   The event containing attributes.
   */
  protected function mapRoles(UserInterface $account, CasPreLoginEvent $event) {
    $roles = $this->doRoleMapCheck(
      $account,
      $event->getCasPropertyBag()->getAttributes()
    );

    if ($this->settings->get('role.deny_no_match') && empty($roles['roles_added'])) {
      $event->setAllowLogin(FALSE);
    }
    else {
      foreach ($roles['roles_added'] as $rid) {
        $account->addRole($rid);
      }
      if (!empty($roles['roles_removed'])) {
        foreach ($roles['roles_removed'] as $rid) {
          $account->removeRole($rid);
        }
      }
    }
  }

  /**
   * Determine which roles will be mapped.
   *
   * @param \Drupal\user\UserInterface $account
   *    The current user object.
   * @param array $attributes
   *   The properties to determine which roles to map.
   *
   * @return array
   *   An array of rids that should be added.
   */
  protected function doRoleMapCheck(
    UserInterface $account,
    array $attributes = NULL
  ) {
    if (empty($attributes)) {
      return [];
    }

    $role_map = unserialize($this->settings->get('role.role_mapping'));
    if (empty($role_map)) {
      return [];
    }

    $roles_added = [];

    foreach ($role_map as $condition) {
      // Attribute not found; don't map role.
      if (!isset($attributes[$condition['attribute']])) {
        continue;
      }
      $attr = $attributes[$condition['attribute']];

      switch ($condition['method']) {

        // The use case for 'match' is one value that matches. All attributes
        // are arrays, so for this we make sure we only consider one value.
        case 'match':
          if (count($attr) > 1) {
            continue;
          }
          $value = array_shift($attr);
          if ($this->strtolower($value) == $this->strtolower($condition['value'])) {
            $roles_added[] = $condition['rid'];
          }
          break;

        // The use case for 'contains' is like 'match', except that any value
        // in a potentially multi-valued array is allowed.
        case 'contains':
          foreach ($attr as $value) {
            if ($this->strtolower($value) == $this->strtolower($condition['value'])) {
              $roles_added[] = $condition['rid'];
              continue 2;
            }
          }
          break;

        // The use case for 'includes' is any value of the array that has the
        // substring of the expected value present.
        case 'includes':
          foreach ($attr as $value) {
            if (strpos($this->strtolower($value),
                $this->strtolower($condition['value'])) !== FALSE) {
              $roles_added[] = $condition['rid'];
              continue 2;
            }
          }
          break;

        default:
      }
    }
    // Now we need to purge any cas-managed roles that the user no longer has.
    $roles_removed = [];
    foreach ($role_map as $condition) {
      if ($account->hasRole($condition['rid']) && !in_array($condition['rid'],
          $roles_added)) {
        $roles_removed[] = $condition['rid'];
      }
    }


    return [
      'roles_added' => $roles_added,
      'roles_removed' => $roles_removed,
    ];
  }

  /**
   * Encapsulate calls to Unicode::strtolower for unit testing purposes.
   *
   * @param string $string
   *   The string to pass through to Unicode::strtolower().
   *
   * @return string
   *   The process string from Unicode::strtolower().
   *
   * @codeCoverageIgnore
   */
  protected function strtolower($string) {
    return Unicode::strtolower($string);
  }
}
