<?php

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\flag\Tests\FlagCreateTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;

/**
 * Javascript test for AjaxLinks.
 *
 * When a user clicks on an AJAX link a salvo of AJAX commands is issued in
 * response which update the DOM with a new link and a short lived message.
 *
 * @group flag
 */
class AjaxLinkTest extends JavascriptTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['flag', 'node', 'user'];

  /**
   * Flag to test with.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Normal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // A article to test with.
    $this->createContentType(['type' => 'article']);

    $this->admin = $this->createUser();

    $this->node = $this->createNode([
      'type' => 'article',
      'uid' => $this->admin->id(),
    ]);

    // A test flag.
    $this->flag = $this->createFlag('node', ['article'], 'ajax_link');
    $this->flagService = $this->container->get('flag');

    $this->webUser = $this->createUser([
      'access content',
    ]);

    $this->grantFlagPermissions($this->flag);
    $this->drupalLogin($this->webUser);

  }

  /**
   * Tests DOM update.
   *
   * Click on flag and then unflag links verifying that the link cycles as
   * expected and flag flash message functions.
   */
  public function testDomUpdate() {
    // Get Page.
    $node_path = '/node/' . $this->node->id();
    $this->drupalGet($node_path);
    $session = $this->getSession();

    // Verify initially flag link is on the page.
    $page = $session->getPage();
    $flag_link = $page->findLink($this->flag->getShortText('flag'));
    $this->assertTrue($flag_link->isVisible(), 'flag link exists.');

    $flag_link->click();

    // Verify flags message appears.
    $flag_message = $this->flag->getMessage('flag');
    $p_flash = $this->assertSession()->waitForElementVisible('css', 'p.js-flag-message');
    $this->assertEquals($flag_message, $p_flash->getText(), 'DOM update: The flag message is flashed.');

    // Verify new link.
    $unflag_link = $session->getPage()->findLink($this->flag->getShortText('unflag'));
    $this->assertTrue($unflag_link->isVisible(), 'unflag link exists.');

    $unflag_link->click();

    // Verfy unflag message appears.
    $unflag_message = $this->flag->getMessage('unflag');
    $p_flash2 = $this->assertSession()->waitForElementVisible('css', 'p.js-flag-message');
    $this->assertEquals($unflag_message, $p_flash2->getText(), 'DOM update: The unflag message is flashed.');

    // Verify the cycle completes and flag returns.
    $flag_link2 = $session->getPage()->findLink($this->flag->getShortText('flag'));
    $this->assertTrue($flag_link2->isVisible(), 'flag cycle return to start.');

  }

}
