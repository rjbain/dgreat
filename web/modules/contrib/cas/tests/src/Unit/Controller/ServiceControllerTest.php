<?php

namespace Drupal\Tests\cas\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\CasPropertyBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * ServiceController unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Controller\ServiceController
 */
class ServiceControllerTest extends UnitTestCase {

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casHelper;

  /**
   * The mocked CasProxyHelper.
   *
   * @var \Drupal\cas\Service\CasProxyHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casProxyHelper;

  /**
   * The mocked Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The mocked CasValidator.
   *
   * @var \Drupal\cas\Service\CasValidator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casValidator;

  /**
   * The mocked CasUserManager.
   *
   * @var \Drupal\cas\Service\CasUserManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casUserManager;

  /**
   * The mocked CasLogout.
   *
   * @var \Drupal\cas\Service\CasLogout|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $casLogout;

  /**
   * The mocked Url Generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  protected $configFactory;

  protected $requestBag;

  protected $queryBag;

  protected $requestObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->casHelper = $this->getMockBuilder('\Drupal\cas\Service\CasHelper')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casProxyHelper = $this->getMockBuilder('\Drupal\cas\Service\CasProxyHelper')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casValidator = $this->getMockBuilder('\Drupal\cas\Service\CasValidator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casUserManager = $this->getMockBuilder('\Drupal\cas\Service\CasUserManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->casLogout = $this->getMockBuilder('\Drupal\cas\Service\CasLogout')
      ->disableOriginalConstructor()
      ->getMock();
    $this->configFactory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
      ),
    ));
    $this->requestStack = $this->getMock('\Symfony\Component\HttpFoundation\RequestStack');
    $this->urlGenerator = $this->getMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $this->requestObject = new Request();
    $request_bag = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $query_bag = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $this->requestObject->query = $query_bag;
    $this->requestObject->request = $request_bag;

    $storage = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage')
      ->setMethods(NULL)
      ->getMock();
    $session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
      ->setConstructorArgs(array($storage))
      ->setMethods(NULL)
      ->getMock();
    $session->start();

    $this->requestObject->setSession($session);

    $this->requestBag = $request_bag;
    $this->queryBag = $query_bag;
  }

  /**
   * Tests a single logout request.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSingleLogout($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      TRUE,
      // ticket.
      FALSE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    $this->casLogout->expects($this->once())
      ->method('handleSlo')
      ->with($this->equalTo('<foobar/>'));

    $serviceController = new TestServiceController(
      $this->casHelper,
      $this->casProxyHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory
    );
    $response = $serviceController->handle();
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('', $response->getContent());
  }

  /**
   * Tests that we redirect to the homepage when no service ticket is present.
   *
   * @dataProvider parameterDataProvider
   */
  public function testMissingTicketRedirectsHome($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      FALSE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $serviceController = new TestServiceController(
      $this->casHelper,
      $this->casProxyHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory
    );

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests that validation and logging in occurs when a ticket is present.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSuccessfulLogin($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $validation_data = new CasPropertyBag('testuser');

    $this->assertSuccessfulValidation($returnto);

    // Login should be called.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->with($this->equalTo($validation_data), $this->equalTo('ST-foobar'));

    $serviceController = new TestServiceController(
      $this->casHelper,
      $this->casProxyHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory
    );

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests that a user is validated and logged in with Drupal acting as proxy.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSuccessfulLoginProxyEnabled($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $this->assertSuccessfulValidation($returnto, TRUE);

    $validation_data = new CasPropertyBag('testuser');
    $validation_data->setPgt('testpgt');

    // Login should be called.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->with($this->equalTo($validation_data), $this->equalTo('ST-foobar'));

    // PGT should be saved.
    $this->casProxyHelper->expects($this->once())
      ->method('storePGTSession')
      ->with($this->equalTo('testpgt'));

    $configFactory = $this->getConfigFactoryStub(array(
      'cas.settings' => array(
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.initialize' => TRUE,
      ),
    ));

    $serviceController = new TestServiceController(
      $this->casHelper,
      $this->casProxyHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $configFactory
    );

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests for a potential validation error.
   *
   * @dataProvider parameterDataProvider
   */
  public function testTicketValidationError($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    // Validation should throw an exception.
    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->will($this->throwException(new CasValidateException()));

    // Login should not be called.
    $this->casUserManager->expects($this->never())
      ->method('login');

    $serviceController = new TestServiceController(
      $this->casHelper,
      $this->casProxyHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory
    );

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests for a potential login error.
   *
   * @dataProvider parameterDataProvider
   */
  public function testLoginError($returnto) {
    $this->setupRequestParameters(
      // returnto.
      $returnto,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($this->requestObject));

    if ($returnto) {
      $this->assertDestinationSetFromReturnTo();
    }

    $this->assertSuccessfulValidation($returnto);

    // Login should throw an exception.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->will($this->throwException(new CasLoginException()));

    $serviceController = new TestServiceController(
      $this->casHelper,
      $this->casProxyHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      $this->requestStack,
      $this->urlGenerator,
      $this->configFactory
    );

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Provides different query string params for tests.
   *
   * We want most test cases to behave accordingly for the matrix of
   * query string parameters that may be present on the request. This provider
   * will turn those params on or off.
   */
  public function parameterDataProvider() {
    return array(
      // "returnto" not set.
      array(FALSE),
      // "returnto" set.
      array(TRUE),
    );
  }

  /**
   * Assert user redirected to homepage when controller invoked.
   */
  private function assertRedirectedToFrontPageOnHandle($serviceController) {
    // URL Generator will generate a path to the homepage.
    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('<front>')
      ->will($this->returnValue('http://example.com/front'));

    $response = $serviceController->handle();
    $this->assertTrue($response->isRedirect('http://example.com/front'));
  }

  /**
   * Assert that the destination query param is set when returnto is present.
   */
  private function assertDestinationSetFromReturnTo() {
    $this->queryBag->expects($this->once())
      ->method('set')
      ->with('destination')
      ->will($this->returnValue('node/1'));
  }

  /**
   * Asserts that validation is executed.
   */
  private function assertSuccessfulValidation($returnto, $for_proxy = FALSE) {
    $service_params = array();
    if ($returnto) {
      $service_params['returnto'] = 'node/1';
    }

    $validation_data = new CasPropertyBag('testuser');
    if ($for_proxy) {
      $validation_data->setPgt('testpgt');
    }

    // Validation service should be called for that ticket.
    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->with($this->equalTo('ST-foobar'), $this->equalTo($service_params))
      ->will($this->returnValue($validation_data));
  }

  /**
   * Mock our request and query bags for the provided parameters.
   *
   * This method accepts each possible parameter that the Sevice Controller
   * may need to deal with. Each parameter passed in should just be TRUE or
   * FALSE. If it's TRUE, we also mock the "get" method for the appropriate
   * parameter bag to return some predefined value.
   *
   * @param bool $returnto
   *   If returnto param should be set.
   * @param bool $logout_request
   *   If logoutRequest param should be set.
   * @param bool $ticket
   *   If ticket param should be set.
   */
  private function setupRequestParameters($returnto, $logout_request, $ticket) {
    // Request params.
    $map = array(
      array('logoutRequest', $logout_request),
    );
    $this->requestBag->expects($this->any())
      ->method('has')
      ->will($this->returnValueMap($map));

    $map = array();
    if ($logout_request === TRUE) {
      $map[] = array('logoutRequest', NULL, '<foobar/>');
    }
    if (!empty($map)) {
      $this->requestBag->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
    }

    // Query string params.
    $map = array(
      array('returnto', $returnto),
      array('ticket', $ticket),
    );
    $this->queryBag->expects($this->any())
      ->method('has')
      ->will($this->returnValueMap($map));

    $map = array();
    if ($returnto === TRUE) {
      $map[] = array('returnto', NULL, 'node/1');
    }
    if ($ticket === TRUE) {
      $map[] = array('ticket', NULL, 'ST-foobar');
    }
    if (!empty($map)) {
      $this->queryBag->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
    }

    // Query string "all" method should include all params.
    $all = array();
    if ($returnto) {
      $all['returnto'] = 'node/1';
    }
    if ($ticket) {
      $all['ticket'] = 'ST-foobar';
    }
    $this->queryBag->method('all')
      ->will($this->returnValue($all));
  }

}
