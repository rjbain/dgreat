<?php

/**
 * @file
 * Contains \Drupal\Tests\cas_attributes\Unit\Subscriber\CasAttributeSubscriberTest.
 */

namespace Drupal\Tests\cas_attributes\Unit\Subscriber;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasHelper;

 /**
  * CasAttributeSubscriber unit tests.
  *
  * @ingroup cas_attributes
  * @group cas_attributes
  *
  * @coversDefaultClass \Drupal\cas_attributes\Subscriber\CasAttributeSubscriber
  */
class CasAttributeSubscriberTest extends UnitTestCase {

  /**
   * The mocked UserInterface account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /** 
   * The mocked token service.
   *
   * @var \Drupal\Core\Utility\Token|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $tokenService;

  /**
   * The mocked CasPropertyBag.
   *
   * @var \Drupal\cas\CasPropertyBag|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $propertyBag;

  /**
   * The mocked RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The mocked Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $request;

  /**
   * The mocked SessionInterface.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->getMock('\Drupal\user\UserInterface');
    $this->tokenService = $this->getMockBuilder('\Drupal\Core\Utility\Token')
                               ->disableOriginalConstructor()
                               ->getMock();
    $this->propertyBag = $this->getMockBuilder('\Drupal\cas\CasPropertyBag')
                              ->disableOriginalConstructor()
                              ->getMock();
    $this->requestStack = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
                               ->disableOriginalConstructor()
                               ->getMock();
    $this->request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                          ->disableOriginalConstructor()
                          ->getMock();
    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\SessionInterface')
                          ->disableOriginalConstructor()
                          ->getMock();
  }

  /**
   * Test our event subscription declaration.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $this->assertThat(
      TestCasAttributeSubscriber::getSubscribedEvents()[CasHelper::EVENT_PRE_REGISTER][0],
      $this->contains('onPreRegister')
    );

    $this->assertThat(
      TestCasAttributeSubscriber::getSubscribedEvents()[CasHelper::EVENT_PRE_LOGIN][0],
      $this->contains('onPreLogin')
    );
  }

  /**
   * Verify that role map check only gets done in EVENT_PRE_LOGIN if the
   * configuration is set to deny registration when no role match occurs.
   *
   * @covers ::onPreRegister
   * @covers ::__construct
   */
  public function testonPreRegisterConfigNoCheck() {
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.deny_registration_no_match' => FALSE,
      ],
    ]);

    $subscriber_stub = $this->getMockBuilder('\Drupal\Tests\cas_attributes\Unit\Subscriber\TestCasAttributeSubscriber')
                            ->setConstructorArgs([$config_factory, $this->tokenService, $this->requestStack])
                            ->setMethods(NULL)
                            ->getMock();

    $subscriber_stub->expects($this->never())
                    ->method('doRoleMapCheck');

    $event = new CasPreRegisterEvent($this->propertyBag, 'test');
    $subscriber_stub->onPreRegister($event);
  }

  /**
   * Verify that the role map check in EVENT_PRE_LOGIN sets the register
   * status when the roles are empty.
   *
   * @covers ::onPreRegister
   * @covers ::__construct
   * @covers ::doRoleMapCheck
   */
  public function testonPreRegisterRolesEmpty() {
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.deny_registration_no_match' => TRUE,
        'role.role_mapping' => '',
      ],
    ]);

    $subscriber = new TestCasAttributeSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $this->propertyBag->expects($this->any())
      ->method('getAttributes')
      ->willReturn([]);

    $event = new CasPreRegisterEvent($this->propertyBag, 'test');
    $subscriber->onPreRegister($event);
    $this->assertEquals(FALSE, $event->getAllowAutomaticRegistration());
  }

  /**
   * Verify that the role map check in EVENT_PRE_LOGIN does not set register
   * status when roles are returned.
   *
   * @covers ::onPreRegister
   * @covers ::__construct
   * @covers ::doRoleMapCheck
   *
   * @dataProvider beforeUserAlterRolesNotEmptyDataProvider
   */
  public function testBeforeUserAlterRolesNotEmpty($method) {
    $rid = $this->randomMachineName(8);
    $attribute = $this->randomMachineName(8);
    $value = $this->randomMachineName(8);
    
    $role_map = serialize([0 => [
      'rid' => $rid,
      'method' => $method,
      'attribute' => $attribute,
      'value' => $value,
    ]]);

    $attribute_array = [$attribute => [$value]];

    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.deny_registration_no_match' => TRUE,
        'role.role_mapping' => $role_map,
      ],
    ]);

    $this->propertyBag->expects($this->once())
                      ->method('getAttributes')
                      ->willReturn($attribute_array);

    $event = new CasPreRegisterEvent($this->propertyBag, 'test');

    $subscriber = new TestCasAttributeSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreRegister($event);
    $this->assertEquals(TRUE, $event->getAllowAutomaticRegistration());

  }

  /**
   * Provides parameters for testBeforeUserAlterRolesNotEmpty.
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas_attributes\Unit\Subscriber\CasAttributeSubscriberTest::testBeforeUserAlterRolesNotEmpty
   */
  public function beforeUserAlterRolesNotEmptyDataProvider() {
    return [['match'], ['contains'], ['includes']];
  }

  /**
   * Test non-matching scenarios in doRoleMapCheck.
   *
   * @covers ::doRoleMapCheck
   * @covers ::onPreRegister
   * @covers ::__construct
   *
   * @dataProvider doRoleMapCheckNoMatchDataProvider
   */
  public function testDoRoleMapCheckNoMatch($attributes, $method) {
    $role_map = serialize([
      0 => [
        'rid' => 'foo',
        'method' => $method,
        'attribute' => array_keys($attributes)[0],
        'value' => 'not_the_correct_value',
      ],
      1 => [
        'rid' => 'bar',
        'method' => 'not_a_supported_method',
        'attribute' => $this->randomMachineName(8),
        'value' => $this->randomMachineName(8),
      ],
    ]);
    
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.deny_registration_no_match' => TRUE,
        'role.role_mapping' => $role_map,
      ],
    ]);

    $this->propertyBag->expects($this->once())
                      ->method('getAttributes')
                      ->willReturn($attributes);

    $event = new CasPreRegisterEvent($this->propertyBag, 'test');

    $subscriber = new TestCasAttributeSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreRegister($event);
    $this->assertEquals(FALSE, $event->getAllowAutomaticRegistration());
  }

  /**
   * Provides parameters for testDoRoleMapCheckNoMatch
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas_attributes\Unit\Subscriber\CasAttributeSubscriberTest::testDoRoleMapCheckNoMatch
   */
  public function doRoleMapCheckNoMatchDataProvider() {
    // This should cause the match method to fail for too many elements.
    $params[] = [['foo' => [0, 1]], 'match'];

    // This should cause the match method to fail for wrong value.
    $params[] = [['foo' => [0]], 'match'];

    // This should cause the contains method to fail for wrong value.
    $params[] = [['foo' => [0, 1]], 'contains'];

    // This should cause the 'includes' method to fail for wrong value.
    $params[] = [['foo' => [0, 1, 2]], 'includes'];

    // This should trigger the default switch statement.
    $params[] = [['foo' => [0, 1, 2]], 'not_a_valid_method'];
    
    return $params;
  }

  /**
   * Verify that the role map check in EVENT_PRE_REGISTER sets the login
   * status when the roles are empty.
   *
   * @covers ::onPreLogin
   * @covers ::mapRoles
   * @covers ::getFieldMappings
   * @covers ::__construct
   * @covers ::doRoleMapCheck
   */
  public function testonPreLoginRolesEmpty() {
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.role_mapping' => '',
        'role.deny_no_match' => TRUE,
        'field.field_mapping' => '',
        'fetch.overwrite' => FALSE,
        'fetch.frequency' => TRUE,
      ],
    ]);

    $subscriber = new TestCasAttributeSubscriber($config_factory, $this->tokenService, $this->requestStack);

    $this->requestStack->expects($this->once())
                       ->method('getCurrentRequest')
                       ->willReturn($this->request);
    $this->request->expects($this->once())
                  ->method('getSession')
                  ->willReturn($this->session);

    $this->propertyBag->expects($this->once())
      ->method('getAttributes')
      ->willReturn([]);

    $event = new CasPreLoginEvent($this->account, $this->propertyBag);
    $subscriber->onPreLogin($event);
    $this->assertEquals(FALSE, $event->getAllowLogin());
  }

  /**
   * Verify that a mapped role gets added to the account.
   *
   * @covers ::onPreLogin
   * @covers ::mapRoles
   * @covers ::getFieldMappings
   * @covers ::__construct
   * @covers ::doRoleMapCheck
   */
  public function testAfterUserNameRoleMap() {
    $rid = $this->randomMachineName(8);
    $attribute = $this->randomMachineName(8);
    $value = $this->randomMachineName(8);
    $method = 'match';

    $role_map = serialize([0 => [
      'rid' => $rid,
      'method' => $method,
      'attribute' => $attribute,
      'value' => $value,
    ]]);


    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.role_mapping' => $role_map,
        'role.deny_no_match' => FALSE,
        'field.field_mapping' => '',
        'fetch.overwrite' => FALSE,
        'fetch.frequency' => TRUE,
      ],
    ]);

    $attribute_array = [$attribute => [$value]];

    $subscriber = new TestCasAttributeSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $this->propertyBag->expects($this->once())
                      ->method('getAttributes')
                      ->willReturn($attribute_array);
    $this->requestStack->expects($this->once())
                       ->method('getCurrentRequest')
                       ->willReturn($this->request);
    $this->request->expects($this->once())
                  ->method('getSession')
                  ->willReturn($this->session);
    $this->account->expects($this->once())
                  ->method('addRole')
                  ->with($rid);

    $event = new CasPreLoginEvent($this->account, $this->propertyBag);
    $subscriber->onPreLogin($event);
  }

  /**
   * Test the field mapping functionality for username.
   *
   * @covers ::getFieldMappings
   * @covers ::onPreLogin
   * @covers ::__construct
   * @covers ::mapRoles
   *
   * @dataProvider mapFieldsOnLogin
   */
  public function testMapFieldsOnLogin($mappings, $attributes, $overwrite, $empty) {
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.role_mapping' => '',
        'field.field_mapping' => serialize($mappings),
        'fetch.overwrite' => $overwrite,
        'fetch.frequency' => TRUE,
        'role.deny_no_match' => FALSE,
      ],
    ]);

    $this->requestStack->expects($this->once())
                       ->method('getCurrentRequest')
                       ->willReturn($this->request);
    $this->request->expects($this->once())
                  ->method('getSession')
                  ->willReturn($this->session);

    $this->session->expects($this->once())
                  ->method('get')
                  ->with('cas_attributes_properties')
                  ->willReturn($this->propertyBag);

    $this->propertyBag->expects($this->any())
                      ->method('getAttributes')
                      ->willReturn($attributes);

    $this->tokenService->expects($this->any())
                       ->method('replace')
                       ->will($this->returnCallback([$this, 'tokenReplace']));

    $this->account->expects($this->any())
                  ->method('getUsername')
                  ->willReturn(!$empty);

    if ($empty || $overwrite) {
      $this->account->expects($this->once())
                    ->method('set')
                    ->with('name', $attributes[$mappings['name']][0]);
    }
    else {
      $this->account->expects($this->never())
                    ->method('setUsername');
    }

    $event = new CasPreLoginEvent($this->account, $this->propertyBag);
    $subscriber = new TestCasAttributeSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreLogin($event);
  }

  /**
   * Provide parameters for testMapFieldsUserName.
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas_attributes\Subscriber\CasAttributeSubscriberTest::testMapFieldsUserName
   */
  public function mapFieldsOnLogin() {
    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [$this->randomMachineName(8)]],
      TRUE,
      FALSE,
    ];

    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [$this->randomMachineName(8)]],
      FALSE,
      FALSE,
    ];

    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [$this->randomMachineName(8)]],
      FALSE,
      TRUE,
    ];

    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [$this->randomMachineName(8)]],
      TRUE,
      TRUE,
    ];

    return $params;
  }

  /**
   * Callback function for the mocked token replacement service.
   *
   * @param string $input
   *   The string containing a token.
   * @param array $data
   *   This is irrelevant to our use-case.
   * @param array $options
   *   This is irrelevant to our use-case.
   *
   * @return string
   *   The token replacement, from the fake session service.
   */
  public function tokenReplace($input, $data, $options) {
    // We don't particularly care about token replacement logic in this test,
    // only that it happens we want it to. So for the purposes of this test,
    // we use very simple fake token syntax.
    $supplied_attribute = preg_replace('/\[|\]/', '', $input);
    $session_info = $this->session->get('cas_attributes_properties');
    if (isset($session_info)) {
      $properties = $session_info->getAttributes();
      if (isset($properties[$supplied_attribute])) {
        return $properties[$supplied_attribute][0];
      }
    }
    
    // No match, just return the input.
    return $input;
  }
}
