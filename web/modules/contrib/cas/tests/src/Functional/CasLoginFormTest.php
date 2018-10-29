<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the login link on the user login form.
 *
 * @group cas
 */
class CasLoginFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas', 'page_cache', 'dynamic_page_cache'];

  /**
   * Tests the login link on the user login form.
   */
  public function testLoginLinkOnLoginForm() {
    // Should be disabled by default.
    $config = $this->config('cas.settings');
    $this->assertFalse($config->get('login_link_enabled'));
    $this->assertEquals('CAS Login', $config->get('login_link_label'));
    $this->drupalGet('/user/login');
    $this->assertSession()->linkNotExists('CAS Login');

    // Enable it.
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $edit = [
      'general[login_link_enabled]' => TRUE,
      'general[login_link_label]' => 'Click here to login!',
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $config = $this->config('cas.settings');
    $this->assertTrue($config->get('login_link_enabled'));
    $this->assertEquals('Click here to login!', $config->get('login_link_label'));

    // Test that it appears properly.
    $this->drupalLogout();
    $this->drupalGet('/user/login');
    $this->assertSession()->linkExists('Click here to login!');
  }

  /**
   * Tests the "prevent normal login" feature.
   */
  public function testPreventNormalLogin() {
    // Should be enabled by default.
    $config = $this->config('cas.settings');
    $this->assertTrue($config->get('user_accounts.prevent_normal_login'));

    $normal_user = $this->drupalCreateUser([], 'normal_user');
    $normal_user->setPassword('password');
    $normal_user->save();
    $cas_user = $this->drupalCreateUser([], 'cas_user');
    $cas_user->setPassword('password');
    $cas_user->save();
    $this->container->get('cas.user_manager')->setCasUsernameForAccount($cas_user, 'cas_user');

    // Log in in as normal user should work.
    $this->drupalPostForm('/user/login', [
      'name' => 'normal_user',
      'pass' => 'password',
    ], 'Log in');
    $this->assertSession()->addressEquals('/user/' . $normal_user->id());
    $this->drupalLogout();

    // Log in as CAS user should not work.
    $this->drupalPostForm('/user/login', [
      'name' => 'cas_user',
      'pass' => 'password',
    ], 'Log in');
    $this->assertSession()->addressEquals('/user/login');
    $this->assertSession()->pageTextContains('This account must log in using');

    // Now turn off the setting and try again.
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $edit = [
      'user_accounts[prevent_normal_login]' => FALSE,
    ];
    $this->drupalPostForm('/admin/config/people/cas', $edit, 'Save configuration');
    $this->drupalLogout();

    // Log in as CAS user should work now.
    $this->drupalPostForm('/user/login', [
      'name' => 'cas_user',
      'pass' => 'password',
    ], 'Log in');
    $this->assertSession()->addressEquals('/user/' . $cas_user->id());
  }

}
