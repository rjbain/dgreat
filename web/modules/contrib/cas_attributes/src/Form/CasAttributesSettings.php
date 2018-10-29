<?php
/**
 * @file
 * Contains \Drupal\cas_attributes\Form\CasAttributesSettings.
 */

namespace Drupal\cas_attributes\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;

/**
 * @codeCoverageIgnore
 */
class CasAttributesSettings extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   *
   * The Entity Manager to provide field definitions.
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\cas\Form\CasSettings object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    parent::__construct($config_factory);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'cas_attributes_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas_attributes.settings');
    $mappings = unserialize($config->get('field.field_mapping'));
    
    $form['fetch'] = array(
      '#type' => 'details',
      '#title' => 'General Settings',
      '#open' => TRUE,
      '#tree' => TRUE,
    );

    $form['fetch']['frequency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Fetch CAS Attributes'),
      '#options' => array(
        0 => $this->t('only when a CAS account is created (i.e., the first login of a CAS user).'),
        1 => $this->t('every time a CAS user logs in.'),
      ),
      '#default_value' => $config->get('fetch.frequency'),
    );

    $form['fetch']['overwrite'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Overwrite existing values'),
      '#options' => array(
        0 => $this->t('only store data from attributes for fields that are empty (don\'t overwrite user fields that already have data).'),
        1 => $this->t('always store data from attributes (overwrite user fields that already have data).'),
      ),
      '#default_value' => $config->get('fetch.overwrite'),
    );

    $form['field'] = array(
      '#type' => 'details',
      '#title' => $this->t('CAS Attribute Mappings'),
      '#description' => $this->t('Token replacement strings used to populate each user field. Only text fields are eligible to be populated. Entries left blank will be ignored.'),
      '#tree' => TRUE,
      '#open' => TRUE,
    );

    $form['field']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The account username.'),
      '#size' => 60,
      '#default_value' => isset($mappings['name']) ? $mappings['name'] : '',
    );
    
    $form['field']['mail'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('The account e-mail address.'),
      '#size' => 60,
      '#default_value' => isset($mappings['mail']) ? $mappings['mail'] : '',
    );

    foreach ($this->entityManager->getFieldDefinitions('user', 'user') as $name => $definition) {
      if (!empty($definition->getTargetBundle())) {
        if ($definition->getType() == 'string' || $definition->getType() == 'list_string') {
          $form['field'][$name] = array(
            '#type' => 'textfield',
            '#title' => $definition->getLabel(),
            '#default_value' => isset($mappings[$name]) ? $mappings[$name] : '',
            '#size' => 60,
            '#description' => $this->t('The account field with name %field_name.', array('%field_name' => $definition->getName())),
          );
        }
      }
    }

    $form['role'] = array(
      '#type' => 'details',
      '#title' => $this->t('CAS Role Mapping Settings'),
      '#tree' => TRUE,
      '#open' => TRUE,
    );

    $form['role']['deny_no_match'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Deny login if no roles mapped?'),
      '#description' => $this->t('If this is set, users who do not receive roles from the role mappings 
      configured below will be denied login access.'),
      '#options' => array(
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ),
      '#default_value' => $config->get('role.deny_no_match'),
    );

    $form['role']['deny_registration_no_match'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Deny registration if no roles mapped?'),
      '#description' => $this->t('If this is set new users who would be automatically registered with CAS 
      will not be registered if they would not receive any roles from the role mappings configured below.'),
      '#options' => array(
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ),
      '#default_value' => $config->get('role.deny_registration_no_match'),
    );

    $form['role']['mappings'] = array(
      '#type' => 'details',
      '#title' => $this->t('CAS Role Mappings'),
      '#description' => $this->t('Each role mapping is a relationship between a role that is to be granted,
      an attribute name (not in token syntax!), an attribute value to match, and a method to use for matching.
      The \'match\' method will only validate a value if it is the only value for that particular value, and
      it (case-insensitively) matches exactly. The \'contains\' method will validate a value if any of the 
      supplied attribute values match exactly. The \'includes\' method will validate a value if any of the 
      supplied attribute value have the specified substring anywhere.'),
      '#tree' => TRUE,
      '#open' => TRUE,
    );

    $role_mappings = unserialize($config->get('role.role_mapping'));
    $roles_available = user_roles();
    unset($roles_available[RoleInterface::AUTHENTICATED_ID]);
    unset($roles_available[RoleInterface::ANONYMOUS_ID]);
    $roles_options = [];
    $count = empty($role_mappings) ? 0 : count($role_mappings);
    if (empty($role_mappings)) {
      $role_mappings = [];
    }
    foreach ($roles_available as $role_id => $role) {
      $roles_options[$role_id] = $role->label();
    }

    foreach ($role_mappings as $index => $condition) {
      $form['role']['mappings'][$index] = array(
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['container-inline']],
      );
      $form['role']['mappings'][$index]['rid'] = array(
        '#type' => 'select',
        '#title' => $this->t('Role'),
        '#title_display' => 'invisible',
        '#options' => $roles_options,
        '#default_value' => $condition['rid'],
      );
      $form['role']['mappings'][$index]['attribute'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Attribute Name'),
        '#default_value' => $condition['attribute'],
        '#size' => 30,
      );
      $form['role']['mappings'][$index]['value'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Attribute Value'),
        '#default_value' => $condition['value'],
        '#size' => 30,
      );
      $form['role']['mappings'][$index]['method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Method'),
        '#options' => array(
          'match' => $this->t('Match'),
          'contains' => $this->t('Contains'),
          'includes' => $this->t('Includes'),
        ),
        '#title_display' => 'invisible',
        '#default_value' => $condition['method'],
      );

      $form['role']['mappings'][$index]['delete'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Delete this mapping?'),
        '#default_value' => 0,
        '#title_display' => 'before',
      );
    }

    $form['role']['mappings'][$count] = array(
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['container-inline']],
    );
    $form['role']['mappings'][$count]['rid'] = array(
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#options' => $roles_options,
      '#title_display' => 'invisible',
    );
    $form['role']['mappings'][$count]['attribute'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Attribute Name'),
      '#size' => 30,
    );
    $form['role']['mappings'][$count]['value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Attribute Value'),
      '#size' => 30,
    );
    $form['role']['mappings'][$count]['method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => array(
        'match' => $this->t('Match'),
        'contains' => $this->t('Contains'),
        'includes' => $this->t('Includes'),
      ),
      '#title_display' => 'invisible',
    );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cas_attributes.settings');

    $fetch_data = $form_state->getValue('fetch');
    $config
      ->set('fetch.frequency', $fetch_data['frequency'])
      ->set('fetch.overwrite', $fetch_data['overwrite']);

    $field_data = $form_state->getValue('field');
    $config->set('field.field_mapping', serialize($field_data));

    $role_data = $form_state->getValue('role');
    $role_map = [];
    foreach ($role_data['mappings'] as $mapping) {
      if (isset($mapping['delete']) && $mapping['delete']) {
        continue;
      }
      if (empty($mapping['attribute']) || empty($mapping['value'])) {
        continue;
      }
      $role_map[] = $mapping;
    }

    $config
      ->set('role.deny_no_match', $role_data['deny_no_match'])
      ->set('role.deny_registration_no_match', $role_data['deny_registration_no_match'])
      ->set('role.role_mapping', serialize($role_map));

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array('cas_attributes.settings');
  }

}
